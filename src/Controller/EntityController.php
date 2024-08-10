<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Controller;

use Psr\Log\LoggerAwareTrait;
use S2\AdminYard\Config\DbColumnFieldType;
use S2\AdminYard\Config\EntityConfig;
use S2\AdminYard\Config\FieldConfig;
use S2\AdminYard\Config\Filter;
use S2\AdminYard\Config\VirtualFieldType;
use S2\AdminYard\Database\DatabaseHelper;
use S2\AdminYard\Database\DataProviderException;
use S2\AdminYard\Database\Key;
use S2\AdminYard\Database\LogicalExpression;
use S2\AdminYard\Database\PdoDataProvider;
use S2\AdminYard\Database\SafeDataProviderException;
use S2\AdminYard\Event\AfterLoadEvent;
use S2\AdminYard\Event\AfterSaveEvent;
use S2\AdminYard\Event\BeforeDeleteEvent;
use S2\AdminYard\Event\BeforeRenderEvent;
use S2\AdminYard\Event\BeforeSaveEvent;
use S2\AdminYard\Form\Form;
use S2\AdminYard\Form\FormFactory;
use S2\AdminYard\Form\FormParams;
use S2\AdminYard\SettingStorage\SettingStorageInterface;
use S2\AdminYard\TemplateRenderer;
use S2\AdminYard\Transformer\ViewTransformer;
use S2\AdminYard\Translator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class EntityController
{
    use LoggerAwareTrait;

    final public function __construct(
        readonly protected EntityConfig            $entityConfig,
        readonly protected EventDispatcher         $eventDispatcher,
        readonly protected PdoDataProvider         $dataProvider,
        readonly protected ViewTransformer         $viewTransformer,
        readonly protected Translator              $translator,
        readonly protected TemplateRenderer        $templateRenderer,
        readonly protected FormFactory             $formFactory,
        readonly protected SettingStorageInterface $settingStorage,
    ) {
    }

    /**
     * @throws DataProviderException
     */
    public function listAction(Request $request): string|Response
    {
        $filterForm = $this->getListFilterForm($request);
        $filterData = $filterForm->getData();

        [$sortField, $sortDirection] = $this->getListSorting($request);

        $filters = $this->entityConfig->getFilters();

        $conditions = [];
        foreach ($filterData as $filterName => $filterValue) {
            $filter       = $filters[$filterName] ?? null;
            $conditions[] = $filter?->getCondition($filterValue) ?? new LogicalExpression($filterName, $filterValue);
        }
        $data = $this->getEntityList($conditions, $sortField, $sortDirection);

        $renderedRows = array_map(
            fn(array $row) => $this->renderCellsForNormalizedRow($request, $row, FieldConfig::ACTION_LIST),
            $data
        );

        $beforeRenderEvent = new BeforeRenderEvent([
            'title'      => $this->entityConfig->getPluralName(),
            'entityName' => $this->entityConfig->getName(),

            'filterControls' => $filterForm->getVisibleControls(), // Hidden controls on the list page are only for processing extra query parameters, they should not be in the form data since it's impossible to clear them.
            'filterLabels'   => array_map(static fn(Filter $filter) => $filter->label, $filters),
            'filterData'     => array_map(static fn($value) => $value ?? '', $filterData),

            'sortableFields' => $this->entityConfig->getSortableFieldNames(),
            'sortField'      => $sortField,
            'sortDirection'  => $sortDirection,

            'header'        => $this->entityConfig->getLabels(FieldConfig::ACTION_LIST),
            'hint'          => $this->entityConfig->getHints(FieldConfig::ACTION_LIST),
            'rows'          => $renderedRows,
            'entityActions' => array_map(static fn(string $action) => [
                'name' => $action,
            ], array_intersect($this->entityConfig->getEnabledActions(), [FieldConfig::ACTION_NEW])),
        ]);

        $this->eventDispatcher->dispatch(
            $beforeRenderEvent,
            'adminyard.' . $this->entityConfig->getName() . '.' . EntityConfig::EVENT_BEFORE_LIST_RENDER
        );

        return $this->templateRenderer->render($this->entityConfig->getListTemplate(), $beforeRenderEvent->data);
    }

    /**
     * @throws BadRequestException
     * @throws DataProviderException
     * @throws InvalidRequestException
     * @throws NotFoundException
     */
    public function showAction(Request $request): string|Response
    {
        $primaryKey = $this->getEntityPrimaryKeyFromRequest($request);

        $labels = DatabaseHelper::getSqlExpressionsForAssociations($this->entityConfig, FieldConfig::ACTION_SHOW);

        $labels['write_access_control'] = $this->entityConfig->getWriteAccessControl() ?? LogicalExpression::true();

        $data = $this->dataProvider->getEntity(
            $this->entityConfig->getTableName(),
            $this->entityConfig->getFieldDataTypes(FieldConfig::ACTION_SHOW, true),
            $labels,
            DatabaseHelper::getReadAccessControlConditions($this->entityConfig),
            $primaryKey,
        );

        if ($data === null) {
            throw new NotFoundException(sprintf($this->translator->trans('%s with %s not found.'), $this->entityConfig->getName(), $primaryKey->toString()));
        }

        $renderedRow = $this->renderCellsForNormalizedRow($request, $data, FieldConfig::ACTION_SHOW);

        return $this->templateRenderer->render(
            $this->entityConfig->getShowTemplate(),
            [
                'title'      => $this->entityConfig->getSingularName(),
                'entityName' => $this->entityConfig->getName(),
                'header'     => $this->entityConfig->getLabels(FieldConfig::ACTION_SHOW),
                'row'        => $renderedRow,
                'primaryKey' => $primaryKey->toArray(),
                'csrfToken'  => $this->getDeleteCsrfToken($primaryKey->toArray()),
                'actions'    => array_map(static fn(string $action) => [
                    'name' => $action,
                ], array_diff($this->entityConfig->getEnabledActions(), [FieldConfig::ACTION_SHOW, FieldConfig::ACTION_NEW])),
            ]
        );
    }

    /**
     * @throws BadRequestException
     * @throws DataProviderException
     * @throws InvalidRequestException
     * @throws NotFoundException
     */
    public function editAction(Request $request): string|Response
    {
        $primaryKey = $this->getEntityPrimaryKeyFromRequest($request);

        $errorMessages = [];

        $form = $this->formFactory->createEntityForm($formParams = new FormParams(
            $this->entityConfig->getName(),
            $this->entityConfig->getFields(FieldConfig::ACTION_EDIT),
            $this->settingStorage,
            FieldConfig::ACTION_EDIT,
            $primaryKey->toArray()
        ));
        if ($request->getRealMethod() === Request::METHOD_POST) {
            $form->submit($request);
            if ($form->isValid()) {
                $data = $form->getData();

                $context = [];
                $this->eventDispatcher->dispatch(
                    $event = new BeforeSaveEvent($this->dataProvider, $data, $context, $primaryKey),
                    'adminyard.' . $this->entityConfig->getName() . '.' . EntityConfig::EVENT_BEFORE_UPDATE
                );
                $errorMessages = $event->errorMessages;
                $data          = $event->data;

                if ($errorMessages === []) {
                    try {
                        $this->dataProvider->updateEntity(
                            $this->entityConfig->getTableName(),
                            $this->entityConfig->getFieldDataTypes(FieldConfig::ACTION_EDIT, includePrimaryKey: true),
                            DatabaseHelper::getReadAndWriteAccessControlConditions($this->entityConfig),
                            $primaryKey,
                            $data
                        );
                    } catch (SafeDataProviderException $e) {
                        $this->logger?->critical($e->getMessage(), ['exception' => $e]);
                        $errorMessages[] = $this->translator->trans($e->getMessage());
                        if ($request->isXmlHttpRequest()) {
                            return new JsonResponse(['success' => false, 'errors' => $errorMessages], $e->getCode());
                        }
                    }
                }

                if ($errorMessages === []) {
                    $this->eventDispatcher->dispatch(
                        $afterSaveEvent = new AfterSaveEvent($this->dataProvider, $primaryKey, $context),
                        'adminyard.' . $this->entityConfig->getName() . '.' . EntityConfig::EVENT_AFTER_UPDATE
                    );

                    if ($request->isXmlHttpRequest()) {
                        return new JsonResponse(['success' => true, ...$afterSaveEvent->ajaxExtraResponse], 200);
                    }

                    // Update primary key for correct URL in form
                    $primaryKey = $primaryKey->withColumnValues($data);

                    return new RedirectResponse('?' . http_build_query([
                            'entity' => $this->entityConfig->getName(),
                            'action' => 'edit',
                            ...$primaryKey->toArray()
                        ]));
                } else {
                    if ($request->isXmlHttpRequest()) {
                        return new JsonResponse(['success' => false, 'errors' => $errorMessages], Response::HTTP_UNPROCESSABLE_ENTITY);
                    }
                }
            } else {
                if ($request->isXmlHttpRequest()) {
                    $response = new JsonResponse([
                        'success'      => false,
                        'errors'       => $form->getGlobalFormErrors(),
                        'field_errors' => $form->getFieldErrors(),
                        ... $form->isCsrfCheckPassed() ? [] : ['invalid_csrf_token' => true]
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
                    if (!$form->isCsrfCheckPassed()) {
                        $response->headers->setCookie(Cookie::create(
                            name: 'adminyard_temp_csrf_token',
                            value: Form::generateTempCsrfToken($formParams->getCsrfToken()),
                            httpOnly: false,
                            sameSite: Cookie::SAMESITE_STRICT
                        ));
                    }
                    return $response;
                }
            }
        } else {
            $data = $this->dataProvider->getEntity(
                $this->entityConfig->getTableName(),
                $this->entityConfig->getFieldDataTypes(FieldConfig::ACTION_EDIT, includePrimaryKey: true),
                DatabaseHelper::getSqlExpressionsForAssociations($this->entityConfig, FieldConfig::ACTION_EDIT),
                DatabaseHelper::getReadAndWriteAccessControlConditions($this->entityConfig),
                $primaryKey,
            );
            $this->eventDispatcher->dispatch(
                $event = new AfterLoadEvent($data),
                'adminyard.' . $this->entityConfig->getName() . '.' . EntityConfig::EVENT_AFTER_EDIT_FETCH
            );
            $data = $event->data;
            if ($data === null) {
                throw new NotFoundException(sprintf(
                    $this->translator->trans('%s with %s not found.'),
                    $this->entityConfig->getName(),
                    $primaryKey->toString()
                ));
            }
            $form->fillFromArray($data, ['column_', 'virtual_']);
        }

        $beforeRenderEvent = new BeforeRenderEvent([
            'title'         => $this->entityConfig->getEditTitle(),
            'entityName'    => $this->entityConfig->getName(),
            'errorMessages' => $errorMessages,
            'primaryKey'    => $primaryKey->toArray(),
            'csrfToken'     => $this->getDeleteCsrfToken($primaryKey->toArray()),
            'header'        => $this->entityConfig->getLabels(FieldConfig::ACTION_EDIT),
            'hint'          => $this->entityConfig->getHints(FieldConfig::ACTION_EDIT),
            'form'          => $form,
            'actions'       => array_map(static fn(string $action) => [
                'name' => $action,
            ], array_diff($this->entityConfig->getEnabledActions(), [FieldConfig::ACTION_EDIT, FieldConfig::ACTION_NEW])),
        ]);

        $this->eventDispatcher->dispatch(
            $beforeRenderEvent,
            'adminyard.' . $this->entityConfig->getName() . '.' . EntityConfig::EVENT_BEFORE_EDIT_RENDER
        );

        return $this->templateRenderer->render($this->entityConfig->getEditTemplate(), $beforeRenderEvent->data);
    }

    public function newAction(Request $request): string|Response
    {
        $form = $this->formFactory->createEntityForm(new FormParams(
            $this->entityConfig->getName(),
            $this->entityConfig->getFields(FieldConfig::ACTION_NEW),
            $this->settingStorage,
            FieldConfig::ACTION_NEW
        ));

        $errorMessages = [];

        if ($request->getRealMethod() === Request::METHOD_POST) {
            $form->submit($request);

            if ($form->isValid()) {
                $data = $form->getData();

                $context = [];
                $this->eventDispatcher->dispatch(
                    $event = new BeforeSaveEvent($this->dataProvider, $data, $context),
                    'adminyard.' . $this->entityConfig->getName() . '.' . EntityConfig::EVENT_BEFORE_CREATE
                );
                $data = $event->data;

                $errorMessages = $event->errorMessages;
                if ($errorMessages === []) {
                    try {
                        $this->dataProvider->createEntity(
                            $this->entityConfig->getTableName(),
                            $this->entityConfig->getFieldDataTypes(FieldConfig::ACTION_NEW, includeDefault: true),
                            array_merge($this->entityConfig->getFieldDefaultValues(), $data)
                        );
                    } catch (SafeDataProviderException $e) {
                        $this->logger?->critical($e->getMessage(), ['exception' => $e]);
                        $errorMessages[] = $this->translator->trans($e->getMessage());
                    }
                }

                if ($errorMessages === []) {
                    $newPrimaryKey = $this->detectNewPrimaryKey($data);

                    $this->eventDispatcher->dispatch(
                        new AfterSaveEvent($this->dataProvider, $newPrimaryKey, $context),
                        'adminyard.' . $this->entityConfig->getName() . '.' . EntityConfig::EVENT_AFTER_CREATE
                    );

                    if ($newPrimaryKey === null) {
                        $this->addFlashMessage($request, 'success', sprintf(
                            $this->translator->trans('%s created successfully.'),
                            $this->entityConfig->getName()
                        ));

                        return new RedirectResponse('?' . http_build_query([
                                'entity' => $this->entityConfig->getName(),
                                'action' => 'list',
                            ]));
                    }

                    $allowedAction = $this->entityConfig->isAllowedAction('edit')
                        ? 'edit'
                        : ($this->entityConfig->isAllowedAction('show') ? 'show' : 'list');

                    return new RedirectResponse('?' . http_build_query([
                            'entity' => $this->entityConfig->getName(),
                            'action' => $allowedAction,
                            ...($allowedAction !== 'list' ? $newPrimaryKey->toArray() : []),
                        ]));
                }
            }
        }

        return $this->templateRenderer->render(
            $this->entityConfig->getNewTemplate(),
            [
                'title'         => $this->entityConfig->getNewTitle(),
                'entityName'    => $this->entityConfig->getName(),
                'errorMessages' => $errorMessages,
                'header'        => $this->entityConfig->getLabels(FieldConfig::ACTION_NEW),
                'hint'          => $this->entityConfig->getHints(FieldConfig::ACTION_NEW),
                'form'          => $form,
                'actions'       => array_map(static fn(string $action) => [
                    'name' => $action,
                ], array_intersect($this->entityConfig->getEnabledActions(), [FieldConfig::ACTION_LIST])),
            ]
        );
    }

    /**
     * @throws BadRequestException
     * @throws DataProviderException
     * @throws InvalidRequestException
     * @throws SuspiciousOperationException
     */
    public function deleteAction(Request $request): Response
    {
        if ($request->getMethod() !== Request::METHOD_POST) {
            throw new InvalidRequestException('Delete action must be called via POST request.', Response::HTTP_METHOD_NOT_ALLOWED);
        }
        $primaryKey = $this->getEntityPrimaryKeyFromRequest($request);
        $csrfToken  = $request->request->get('csrf_token');
        if ($this->getDeleteCsrfToken($primaryKey->toArray()) !== $csrfToken) {
            $errorMessage = $this->translator->trans('Unable to confirm security token. A likely cause for this is that some time passed between when you first entered the page and when you submitted the form. If that is the case and you would like to continue, submit the form again.');
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'errors'  => [$errorMessage]
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $this->addFlashMessage($request, 'error', $errorMessage);
            return new Response('CSRF token mismatch', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->eventDispatcher->dispatch(
            $event = new BeforeDeleteEvent($this->dataProvider, $primaryKey),
            'adminyard.' . $this->entityConfig->getName() . '.' . EntityConfig::EVENT_BEFORE_DELETE
        );

        if ($event->errorMessages !== []) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'errors'  => $event->errorMessages
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            array_map(fn(string $message) => $this->addFlashMessage($request, 'error', $message), $event->errorMessages);
            return new Response('CSRF token mismatch', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $deletedRows = $this->dataProvider->deleteEntity(
                $this->entityConfig->getTableName(),
                $this->entityConfig->getFieldDataTypes(FieldConfig::ACTION_DELETE, includePrimaryKey: true),
                $primaryKey,
                DatabaseHelper::getReadAndWriteAccessControlConditions($this->entityConfig),
            );
        } catch (SafeDataProviderException $e) {
            $this->logger?->critical($e->getMessage(), ['exception' => $e]);
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'errors'  => ['Unable to delete entity'],
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $this->addFlashMessage($request, 'error', $this->translator->trans($e->getMessage()));
            return new Response('Unable to delete entity', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if ($deletedRows === 0) {
            $message = sprintf(
                $this->translator->trans('%s was not deleted.'),
                $this->entityConfig->getName()
            );
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse([
                    'success' => false,
                    'errors'  => [$message],
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            $this->addFlashMessage($request, 'warning', $message);

            return new Response('No entity was deleted', Response::HTTP_NOT_FOUND);
        }

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['success' => true]);
        }

        $this->addFlashMessage($request, 'success', sprintf(
            $this->translator->trans('%s deleted successfully.'),
            $this->entityConfig->getName()
        ));
        return new Response('Entity was deleted', Response::HTTP_OK);
    }

    /**
     * @throws BadRequestException
     * @throws InvalidRequestException
     */
    public function patchAction(Request $request): Response
    {
        if ($request->getRealMethod() !== Request::METHOD_POST) {
            throw new InvalidRequestException('Patch action must be called via POST request.', Response::HTTP_METHOD_NOT_ALLOWED);
        }
        $fieldName = $request->query->get('field');
        if ($fieldName === null) {
            throw new InvalidRequestException('Field name must be provided.', Response::HTTP_BAD_REQUEST);
        }
        $field = $this->entityConfig->findFieldByName($fieldName);
        if ($field === null) {
            throw new InvalidRequestException(sprintf('Field "%s" not found in entity "%s".', $fieldName, $this->entityConfig->getName()));
        }
        if (!$field->inlineEdit) {
            throw new InvalidRequestException(sprintf('Field "%s" is not declared as inline editable.', $fieldName));
        }
        $primaryKey = $this->getEntityPrimaryKeyFromRequest($request);

        $form = $this->formFactory->createEntityForm(new FormParams(
            $this->entityConfig->getName(),
            [$fieldName => $field],
            $this->settingStorage,
            'patch',
            $primaryKey->toArray(),
        ));

        $form->submit($request);
        if (!$form->isValid()) {
            return new JsonResponse(['errors' => $form->getMergedErrors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $data = $form->getData();

        $context = [];
        $this->eventDispatcher->dispatch(
            $event = new BeforeSaveEvent($this->dataProvider, $data, $context, $primaryKey),
            'adminyard.' . $this->entityConfig->getName() . '.' . EntityConfig::EVENT_BEFORE_PATCH
        );

        if ($event->errorMessages !== []) {
            return new JsonResponse(['success' => false, 'errors' => $event->errorMessages], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
        $data = $event->data;

        try {
            $this->dataProvider->updateEntity(
                $this->entityConfig->getTableName(),
                $this->entityConfig->getFieldDataTypes('patch', includePrimaryKey: true, includeInlineEditable: true),
                DatabaseHelper::getReadAndWriteAccessControlConditions($this->entityConfig),
                $primaryKey,
                $data
            );
        } catch (SafeDataProviderException $e) {
            $this->logger?->critical($e->getMessage(), ['exception' => $e]);
            return new JsonResponse(['errors' => [$this->translator->trans($e->getMessage())]], $e->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Exception $e) {
            $this->logger?->critical($e->getMessage(), ['exception' => $e]);
            return new JsonResponse(['errors' => ['Unable to update entity']], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->eventDispatcher->dispatch(
            new AfterSaveEvent($this->dataProvider, $primaryKey, $context),
            'adminyard.' . $this->entityConfig->getName() . '.' . EntityConfig::EVENT_AFTER_PATCH
        );

        return new JsonResponse(['success' => true]);
    }

    /**
     * @throws BadRequestException
     * @throws DataProviderException
     * @throws InvalidRequestException
     */
    public function autocompleteAction(Request $request): Response
    {
        $hash = $request->query->get('hash');
        if ($hash === null) {
            throw new InvalidRequestException('Autocomplete action must be called via GET request with a hash parameter.');
        }

        $autocompleteSqlExpression = $this->entityConfig->getAutocompleteSqlExpression($hash);
        if ($autocompleteSqlExpression === null) {
            throw new InvalidRequestException(sprintf(
                'Entity "%s" must have an autocompleteSqlExpression configured compatible with the hash provided.',
                $this->entityConfig->getName()
            ));
        }

        if (!$this->entityConfig->primaryKeyIsInt()) {
            throw new \DomainException(sprintf(
                'Entity "%s" must have an int primary key for autocomplete.',
                $this->entityConfig->getName()
            ));
        }

        $query      = $request->query->getString('query');
        $conditions = DatabaseHelper::getReadAccessControlConditions($this->entityConfig);
        if (($autocompleteFilter = $this->entityConfig->getAutocompleteFilter($hash)) !== null) {
            $conditions[] = $autocompleteFilter;
        }
        $results = $this->dataProvider->getAutocompleteResults(
            $this->entityConfig->getTableName(),
            $this->entityConfig->getFieldNamesOfPrimaryKey()[0],
            $autocompleteSqlExpression,
            $conditions,
            $query,
            20,
            (int)$request->query->get('additional'),
        );

        return new JsonResponse($results);
    }

    /**
     * @param LogicalExpression[] $filterConditions
     *
     * @throws DataProviderException
     */
    protected function getEntityList(array $filterConditions, ?string $sortField, ?string $sortDirection): array
    {
        $sortField = $this->entityConfig->modifySortableField($sortField);

        if ($sortDirection !== 'desc') {
            $sortDirection = 'asc';
        }

        $labels = DatabaseHelper::getSqlExpressionsForAssociations($this->entityConfig, FieldConfig::ACTION_LIST);

        $labels['write_access_control'] = $this->entityConfig->getWriteAccessControl() ?? LogicalExpression::true();
        $accessControlConditions        = DatabaseHelper::getReadAccessControlConditions($this->entityConfig);

        return $this->dataProvider->getEntityList(
            $this->entityConfig->getTableName(),
            $this->entityConfig->getFieldDataTypes(FieldConfig::ACTION_LIST, true),
            $labels,
            array_merge($accessControlConditions, $filterConditions),
            $sortField,
            $sortDirection,
            $this->entityConfig->getLimit(),
            0// $request->query->getInt('offset', 0)
        );
    }

    protected function renderCellsForNormalizedRow(Request $request, array $row, string $actionForFieldRestriction): array
    {
        $idFieldNames = $this->entityConfig->getFieldNamesOfPrimaryKey();
        $idValues     = array_map(static fn(string $columnName) => $row['column_' . $columnName], $idFieldNames);
        $primaryKey   = array_combine($idFieldNames, $idValues);
        $rowActions   = array_map(static fn(string $action) => [
            'name' => $action,
        ], array_diff($this->entityConfig->getEnabledActions(), [FieldConfig::ACTION_LIST, FieldConfig::ACTION_NEW]));

        $result = [
            'cells'            => [],
            'primary_key'      => $primaryKey,
            'rendered_actions' => $actionForFieldRestriction === FieldConfig::ACTION_LIST
                ? $this->templateRenderer->render($this->entityConfig->getListActionsTemplate(), [
                    'rowActions' => $rowActions,
                    'row'        => $row,
                    'entityName' => $this->entityConfig->getName(),
                    'primaryKey' => $primaryKey,
                    'csrfToken'  => $this->getDeleteCsrfToken($primaryKey),
                ])
                : null,
            ... $this->entityConfig->isAllowedAction(FieldConfig::ACTION_DELETE) ? [
                'csrf_token' => $this->getDeleteCsrfToken($primaryKey),
            ] : [],
        ];

        foreach ($this->entityConfig->getFields($actionForFieldRestriction) as $field) {
            $fieldName = $field->name;
            $dataType  = $field->type instanceof DbColumnFieldType ? $field->type->dataType : 'virtual';
            $cellValue = match (\get_class($field->type)) {
                DbColumnFieldType::class => $this->viewTransformer->viewFromNormalized($row['column_' . $fieldName], $dataType, $field->options),
                VirtualFieldType::class => $row['virtual_' . $fieldName],
                default => null,
            };

            if ($field->inlineEdit && $row['virtual_write_access_control']) {
                $form = $this->formFactory->createEntityForm(new FormParams(
                    $this->entityConfig->getName(),
                    [$fieldName => $field],
                    $this->settingStorage,
                    'patch',
                    $primaryKey,
                ));
                $form->fillFromArray($row, ['column_', 'virtual_']);

                $result['cells'][$fieldName] = [
                    'type'    => $dataType,
                    'content' => $this->templateRenderer->render($field->inlineFormTemplate, [
                        'value'      => $cellValue,
                        'form'       => $form,
                        'entityName' => $this->entityConfig->getName(),
                        'fieldName'  => $fieldName,
                        'primaryKey' => $primaryKey,
                    ]),
                ];

                continue;
            }

            // Additional attributes to build a link to an associated entity.
            $linkCellParams = $this->getLinkCellParams($field, new Key($primaryKey), $row);
            $cellParams     = [
                'value'      => $cellValue,
                'label'      => (string)($row['virtual_' . $fieldName] ?? $cellValue),
                'type'       => $dataType,
                'linkParams' => $linkCellParams,
                'row'        => $row,
            ];

            $result['cells'][$fieldName] = [
                'type'    => $linkCellParams === null && $field->linkToEntity === null ? $dataType : FieldConfig::DATA_TYPE_STRING, // string for int IDs converted to links
                'content' => $this->templateRenderer->render($field->viewTemplate, $cellParams),
            ];
        }

        return $result;
    }

    protected function getLinkCellParams(FieldConfig $currentField, Key $primaryKey, array $row): ?array
    {
        $columnName = $currentField->name;
        if ($currentField->actionOnClick !== null) {
            if (!$this->entityConfig->isAllowedAction($currentField->actionOnClick)) {
                return null;
            }
            if ($currentField->actionOnClick === FieldConfig::ACTION_EDIT && !$row['virtual_write_access_control']) {
                return null;
            }
            return [
                'action' => $currentField->actionOnClick,
                'entity' => $this->entityConfig->getName(),
                ... $primaryKey->toArray(),
            ];
        }

        if ($currentField->type instanceof VirtualFieldType) {
            if ($currentField->type->linkToEntityParams === null) {
                return null;
            }
            $externalEntityName        = $currentField->type->linkToEntityParams->entityName;
            $externalFilterColumnNames = $currentField->type->linkToEntityParams->filterColumnNames;
            $valueColumns              = $currentField->type->linkToEntityParams->valueColumnNamesOfFilters;

            if (!\array_key_exists('virtual_' . $columnName, $row)) {
                throw new \LogicException(sprintf('Row data array for entity "%s" must have a "virtual_%s" key.', $this->entityConfig->getName(), $columnName));
            }
            if ($row['virtual_' . $columnName] === null) {
                // Label is NULL so there will be no link to associated entities
                return null;
            }

            return [
                'entity'       => $externalEntityName,
                'action'       => 'list',
                'apply_filter' => '0', /** Skip filters saved in setting storage, @see self::getListFilterForm() */
                ... array_combine($externalFilterColumnNames, array_map(static fn(string $columnName) => $row['column_' . $columnName], $valueColumns)),
            ];
        }

        if ($currentField->linkToEntity !== null) {
            if (!\array_key_exists('virtual_' . $columnName, $row)) {
                throw new \LogicException(sprintf('Row data array for entity "%s" must have a "virtual_%s" key.', $this->entityConfig->getName(), $columnName));
            }
            if ($row['virtual_' . $columnName] === null) {
                // Label is NULL so there will be no link to associated entity
                return null;
            }

            // Many-To-One, link to "parent" entity
            $foreignEntity          = $currentField->linkToEntity->foreignEntity;
            $fieldNamesOfPrimaryKey = $foreignEntity->getFieldNamesOfPrimaryKey();
            if (\count($fieldNamesOfPrimaryKey) === 0) {
                throw new \LogicException(sprintf('Entity "%s" has no primary key configured and it cannot be used in a many-to-one relationship.', $foreignEntity->getName()));
            }

            $allowedAction = match (true) {
                $foreignEntity->isAllowedAction(FieldConfig::ACTION_SHOW) => FieldConfig::ACTION_SHOW,
                $foreignEntity->isAllowedAction(FieldConfig::ACTION_EDIT) => FieldConfig::ACTION_EDIT,
                $foreignEntity->isAllowedAction(FieldConfig::ACTION_LIST) => FieldConfig::ACTION_LIST,
                default => null,
            };

            if ($allowedAction === null) {
                return null;
            }

            return [
                'entity' => $foreignEntity->getName(),
                'action' => $allowedAction,
                $allowedAction !== FieldConfig::ACTION_LIST ? [
                    // NOTE: think about how to handle primary keys with more than one field.
                    //       For now, we just take the first field. It's ok for usual ID fields.
                    $fieldNamesOfPrimaryKey[0] => $row['column_' . $currentField->name],
                ] : [],
            ];
        }

        return null;
    }

    protected function addFlashMessage(Request $request, string $type, string $message): void
    {
        $request->getSession()->getFlashBag()->add($type, $message);
    }

    protected function getListFilterForm(Request $request): Form
    {
        $filterForm = $this->formFactory->createFilterForm($this->entityConfig);

        $applyFilter = $request->get('apply_filter');

        // First we fill the filter form with the previous filter values
        if ($applyFilter !== '0') {
            $storedFilterData = $this->settingStorage->get('filter_' . $this->entityConfig->getName());
            if (\is_array($storedFilterData)) {
                $filterForm->fillFromArray($storedFilterData);
            }
        }

        // Then we overwrite with the new values if there are any
        $filterFormWasSubmitted = $applyFilter === '1';
        $filterForm->submit($request, $filterFormWasSubmitted);

        if ($filterFormWasSubmitted) {
            // Skip data in hidden input fields since they cannot be updated via the filter form, only via the URL
            $visibleFilterData = $filterForm->getData(includeHidden: false);
            // Update filter state in the setting storage to current state for the next request
            if ($visibleFilterData !== []) {
                $this->settingStorage->set('filter_' . $this->entityConfig->getName(), array_filter($visibleFilterData, static fn($value) => $value !== null));
            } else {
                $this->settingStorage->remove('filter_' . $this->entityConfig->getName());
            }
        }

        return $filterForm;
    }

    protected function getListSorting(Request $request): array
    {
        $entityName = $this->entityConfig->getName();

        $sortField     = $request->get('sort_field');
        $sortDirection = $request->get('sort_direction');

        if ($sortField !== null && $sortDirection !== null) {
            $this->settingStorage->set('sort_field_' . $entityName, $sortField);
            $this->settingStorage->set('sort_direction_' . $entityName, $sortDirection);
        } else {
            $sortField     = $this->settingStorage->get('sort_field_' . $entityName);
            $sortDirection = $this->settingStorage->get('sort_direction_' . $entityName);
        }

        return [$sortField, $sortDirection];
    }

    protected function getDeleteCsrfToken(array $primaryKey): string
    {
        $formParams = new FormParams(
            $this->entityConfig->getName(),
            [],
            $this->settingStorage,
            FieldConfig::ACTION_DELETE,
            $primaryKey
        );
        return $formParams->getCsrfToken();
    }

    /**
     * @throws InvalidRequestException
     * @throws BadRequestException
     */
    protected function getEntityPrimaryKeyFromRequest(Request $request): Key
    {
        $fieldNamesOfPrimaryKey = $this->entityConfig->getFieldNamesOfPrimaryKey();
        if ($fieldNamesOfPrimaryKey === []) {
            throw new InvalidConfigException(sprintf('Entity "%s" without primary key columns cannot be accessed.', $this->entityConfig->getName()));
        }

        $values = [];
        foreach ($fieldNamesOfPrimaryKey as $fieldName) {
            if (!$request->query->has($fieldName)) {
                throw new InvalidRequestException(sprintf($this->translator->trans('Parameter "%s" must be provided.'), $fieldName));
            }
            $values[$fieldName] = $request->query->get($fieldName);
        }

        return new Key($values);
    }

    private function detectNewPrimaryKey(array $postData): ?Key
    {
        $pkFieldNames = $this->entityConfig->getFieldNamesOfPrimaryKey();

        /**
         * Trying not to call lastInsertId on every insert.
         *
         * In PostgreSQL, if there was no actual nextval() call, we have the following error
         * that corrupts a possible transaction:
         *
         * SQLSTATE[55000]: Object not in prerequisite state: 7 ERROR: lastval is not yet defined in this session
         *
         * So, if primary key does not seem to be auto-increment, we skip lastInsertId call.
         */
        if ($this->entityConfig->primaryKeyIsInt()) {
            $lastInsertId = $this->dataProvider->lastInsertId();
            if (is_numeric($lastInsertId) && (int)$lastInsertId > 0 && \count($pkFieldNames) === 1) {
                // We have detected an assigned value of usual auto-increment ID
                return new Key([$pkFieldNames[0] => (int)$lastInsertId]);
            }
        }

        // No PK detected from auto-increment. Check if we have all columns of PK submitted.
        $postPrimaryKey = [];
        foreach ($pkFieldNames as $pkFieldName) {
            if (!isset($postData[$pkFieldName])) {
                // We do not know some part of primary key.
                return null;
            }
            $postPrimaryKey[$pkFieldName] = $postData[$pkFieldName];
        }

        return $postPrimaryKey !== [] ? new Key($postPrimaryKey) : null;
    }
}
