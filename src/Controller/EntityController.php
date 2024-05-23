<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Controller;

use S2\AdminYard\Config\DbColumnFieldType;
use S2\AdminYard\Config\EntityConfig;
use S2\AdminYard\Config\FieldConfig;
use S2\AdminYard\Config\Filter;
use S2\AdminYard\Config\LinkedByFieldType;
use S2\AdminYard\Config\VirtualFieldType;
use S2\AdminYard\Database\DatabaseHelper;
use S2\AdminYard\Database\DataProviderException;
use S2\AdminYard\Database\PdoDataProvider;
use S2\AdminYard\Database\PrimaryKey;
use S2\AdminYard\Form\Form;
use S2\AdminYard\Form\FormFactory;
use S2\AdminYard\TemplateRenderer;
use S2\AdminYard\Transformer\ViewTransformer;
use S2\AdminYard\Translator;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

readonly class EntityController
{
    public function __construct(
        protected EntityConfig     $entityConfig,
        protected PdoDataProvider  $dataProvider,
        protected ViewTransformer  $viewTransformer,
        protected Translator       $translator,
        protected TemplateRenderer $templateRenderer,
        protected FormFactory      $formFactory,
    ) {
    }

    /**
     * @throws \PDOException
     */
    final public function listAction(Request $request): string
    {
        $filterForm = $this->getListFilterForm($request);
        $filterData = $filterForm->getData();

        [$sortField, $sortDirection] = $this->getListSorting($request);

        $filters = $this->entityConfig->getFilters();
        $data    = $this->getEntityList($filters, $filterData, $sortField, $sortDirection);

        $renderedRows = array_map(
            fn(array $row) => $this->renderCellsForNormalizedRow($request, $row, FieldConfig::ACTION_LIST),
            $data
        );

        return $this->templateRenderer->render(
            $this->entityConfig->getListTemplate(),
            [
                'title'      => $this->entityConfig->getName(),
                'entityName' => $this->entityConfig->getName(),

                'filterControls' => $filterForm->getControls(),
                'filterLabels'   => array_map(static fn(Filter $filter) => $filter->label, $filters),
                'filterData'     => array_map(static fn($value) => $value ?? '', $filterData),

                'sortableFields' => $this->entityConfig->getSortableFieldNames(),
                'sortField'      => $sortField,
                'sortDirection'  => $sortDirection,

                'header'        => $this->entityConfig->getLabels(FieldConfig::ACTION_LIST),
                'rows'          => $renderedRows,
                'rowActions'    => array_map(static fn(string $action) => [
                    'name' => $action,
                ], array_diff($this->entityConfig->getEnabledActions(), [FieldConfig::ACTION_LIST, FieldConfig::ACTION_NEW])),
                'entityActions' => array_map(static fn(string $action) => [
                    'name' => $action,
                ], array_intersect($this->entityConfig->getEnabledActions(), [FieldConfig::ACTION_NEW])),
            ]
        );
    }

    /**
     * @throws BadRequestException
     * @throws InvalidRequestException
     * @throws NotFoundException
     */
    public function showAction(Request $request): string
    {
        $primaryKey = $this->getEntityPrimaryKeyFromRequest($request);

        $data = $this->dataProvider->getEntity(
            $this->entityConfig->getTableName(),
            $this->entityConfig->getFieldDataTypes(FieldConfig::ACTION_SHOW, true),
            DatabaseHelper::getSqlExpressionsForAssociations($this->entityConfig, FieldConfig::ACTION_SHOW),
            $primaryKey,
        );

        if ($data === null) {
            throw new NotFoundException(sprintf($this->translator->trans('%s with %s not found.'), $this->entityConfig->getName(), $primaryKey->toString()));
        }

        $renderedRow = $this->renderCellsForNormalizedRow($request, $data, FieldConfig::ACTION_SHOW);

        return $this->templateRenderer->render(
            $this->entityConfig->getShowTemplate(),
            [
                'title'      => $this->entityConfig->getName(),
                'entityName' => $this->entityConfig->getName(),
                'header'     => $this->entityConfig->getLabels(FieldConfig::ACTION_SHOW),
                'row'        => $renderedRow,
                'primaryKey' => $primaryKey->toArray(),
                'csrfToken'  => $this->getDeleteCsrfToken($primaryKey->toArray(), $request),
                'actions'    => array_map(static fn(string $action) => [
                    'name' => $action,
                ], array_diff($this->entityConfig->getEnabledActions(), [FieldConfig::ACTION_SHOW, FieldConfig::ACTION_NEW])),
            ]
        );
    }

    /**
     * @throws BadRequestException
     * @throws InvalidRequestException
     * @throws NotFoundException
     * @throws SuspiciousOperationException
     */
    public function editAction(Request $request): string|Response
    {
        $primaryKey = $this->getEntityPrimaryKeyFromRequest($request);

        $errorMessages = [];

        $form = $this->formFactory->createEntityForm($this->entityConfig, FieldConfig::ACTION_EDIT, $request);
        if ($request->getMethod() === Request::METHOD_POST) {
            $form->submit($request);
            if ($form->isValid()) {
                $data = $form->getData();

                try {
                    $this->dataProvider->updateEntity(
                        $this->entityConfig->getTableName(),
                        $this->entityConfig->getFieldDataTypes(FieldConfig::ACTION_EDIT),
                        $primaryKey,
                        $data
                    );
                } catch (DataProviderException $e) {
                    $errorMessages[] = $this->translator->trans($e->getMessage());
                }

                if ($errorMessages === []) {
                    // Update primary key for correct URL in form
                    $primaryKey = $primaryKey->withColumnValues($data);

                    return new RedirectResponse('?' . http_build_query([
                            'entity' => $this->entityConfig->getName(),
                            'action' => 'edit',
                            ...$primaryKey->toArray()
                        ]));
                }
            }
        } else {
            $data = $this->dataProvider->getEntity(
                $this->entityConfig->getTableName(),
                $this->entityConfig->getFieldDataTypes(FieldConfig::ACTION_EDIT),
                [],
                $primaryKey,
            );
            if ($data === null) {
                throw new NotFoundException(sprintf($this->translator->trans('%s with %s not found.'), $this->entityConfig->getName(), $primaryKey->toString()));
            }
            $form->fillFromArray($data, 'field_');
        }

        return $this->templateRenderer->render(
            $this->entityConfig->getEditTemplate(),
            [
                'title'         => $this->entityConfig->getName(),
                'entityName'    => $this->entityConfig->getName(),
                'errorMessages' => $errorMessages,
                'primaryKey'    => $primaryKey->toArray(),
                'csrfToken'     => $this->getDeleteCsrfToken($primaryKey->toArray(), $request),
                'header'        => $this->entityConfig->getLabels(FieldConfig::ACTION_SHOW),
                'form'          => $form,
                'actions'       => array_map(static fn(string $action) => [
                    'name' => $action,
                ], array_diff($this->entityConfig->getEnabledActions(), [FieldConfig::ACTION_EDIT, FieldConfig::ACTION_NEW])),
            ]
        );
    }

    public function newAction(Request $request): string|Response
    {
        $form          = $this->formFactory->createEntityForm($this->entityConfig, FieldConfig::ACTION_NEW, $request);
        $errorMessages = [];

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->submit($request);

            if ($form->isValid()) {
                $data = $form->getData();

                try {
                    $lastInsertId = $this->dataProvider->createEntity(
                        $this->entityConfig->getTableName(),
                        $this->entityConfig->getFieldDataTypes(FieldConfig::ACTION_NEW, includeDefault: true),
                        array_merge($this->entityConfig->getFieldDefaultValues(), $data)
                    );
                } catch (DataProviderException $e) {
                    $errorMessages[] = $this->translator->trans($e->getMessage());
                }

                if ($errorMessages === []) {
                    $primaryKeyFieldNames = $this->entityConfig->getFieldNamesOfPrimaryKey();
                    if (is_numeric($lastInsertId) && (int)$lastInsertId > 0 && \count($primaryKeyFieldNames) === 1) {
                        // We have detected an assigned value of usual auto-increment ID
                        return new RedirectResponse('?' . http_build_query([
                                'entity'                 => $this->entityConfig->getName(),
                                'action'                 => 'edit',
                                $primaryKeyFieldNames[0] => $lastInsertId
                            ]));
                    }

                    $postPrimaryKey = [];
                    foreach ($primaryKeyFieldNames as $primaryKeyFieldName) {
                        if (!isset($data[$primaryKeyFieldName])) {
                            // We do not know some part of primary key. Redirecting to the list page.

                            $this->addFlashMessage($request, 'success', sprintf($this->translator->trans('%s created successfully.'), $this->entityConfig->getName()));

                            return new RedirectResponse('?' . http_build_query([
                                    'entity' => $this->entityConfig->getName(),
                                    'action' => 'list',
                                ]));
                        }
                        $postPrimaryKey[$primaryKeyFieldName] = $data[$primaryKeyFieldName];
                    }
                    return new RedirectResponse('?' . http_build_query([
                            'entity' => $this->entityConfig->getName(),
                            'action' => 'edit',
                            ...$postPrimaryKey
                        ]));
                }
            }
        }

        return $this->templateRenderer->render(
            $this->entityConfig->getNewTemplate(),
            [
                'title'         => $this->entityConfig->getName(),
                'entityName'    => $this->entityConfig->getName(),
                'errorMessages' => $errorMessages,
                'header'        => $this->entityConfig->getLabels(FieldConfig::ACTION_SHOW),
                'form'          => $form,
                'actions'       => array_map(static fn(string $action) => [
                    'name' => $action,
                ], array_intersect($this->entityConfig->getEnabledActions(), [FieldConfig::ACTION_LIST])),
            ]
        );
    }

    /**
     * @throws InvalidRequestException
     * @throws SuspiciousOperationException
     * @throws \PDOException
     * @throws \Symfony\Component\HttpFoundation\Exception\BadRequestException
     */
    public function deleteAction(Request $request): Response
    {
        if ($request->getMethod() !== Request::METHOD_POST) {
            throw new InvalidRequestException('Delete action must be called via POST request.', Response::HTTP_METHOD_NOT_ALLOWED);
        }
        $primaryKey = $this->getEntityPrimaryKeyFromRequest($request);
        $csrfToken  = $request->request->get('csrf_token');
        if ($this->getDeleteCsrfToken($primaryKey->toArray(), $request) !== $csrfToken) {
            $this->addFlashMessage(
                $request,
                'error',
                $this->translator->trans('Unable to confirm security token. A likely cause for this is that some time passed between when you first entered the page and when you submitted the form. If that is the case and you would like to continue, submit the form again.')
            );
            return new Response('CSRF token mismatch', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $deletedRows = $this->dataProvider->deleteEntity($this->entityConfig->getTableName(), $primaryKey);
        if ($deletedRows === 0) {
            $this->addFlashMessage($request, 'warning', sprintf(
                $this->translator->trans('%s was not deleted.'),
                $this->entityConfig->getName()
            ));
            return new Response('No entity was deleted', Response::HTTP_NOT_FOUND);
        }

        $this->addFlashMessage($request, 'success', sprintf(
            $this->translator->trans('%s deleted successfully.'),
            $this->entityConfig->getName()
        ));
        return new Response('Entity was deleted', Response::HTTP_OK);
    }

    /**
     * @param array<string, Filter> $filters
     *
     * @throws \PDOException
     */
    protected function getEntityList(array $filters, array $filterData, ?string $sortField, ?string $sortDirection): array
    {
        $sortField = $this->entityConfig->modifySortableField($sortField);

        if ($sortDirection !== 'desc') {
            $sortDirection = 'asc';
        }

        return $this->dataProvider->getEntityList(
            $this->entityConfig->getTableName(),
            $this->entityConfig->getFieldDataTypes(FieldConfig::ACTION_LIST, true),
            DatabaseHelper::getSqlExpressionsForAssociations($this->entityConfig, FieldConfig::ACTION_LIST),
            $filters,
            $filterData,
            $sortField,
            $sortDirection,
            $this->entityConfig->getLimit(),
            0// $request->query->getInt('offset', 0)
        );
    }

    protected function renderCellsForNormalizedRow(Request $request, array $row, string $actionForFieldRestriction): array
    {
        $idFieldNames = $this->entityConfig->getFieldNamesOfPrimaryKey();
        $idValues     = array_map(static fn(string $columnName) => $row['field_' . $columnName], $idFieldNames);
        $primaryKey   = array_combine($idFieldNames, $idValues);
        $result       = [
            'cells'       => [],
            'primary_key' => $primaryKey,
            ... $this->entityConfig->isAllowedAction(FieldConfig::ACTION_DELETE) ? [
                'csrf_token' => $this->getDeleteCsrfToken($primaryKey, $request),
            ] : [],
        ];

        foreach ($this->entityConfig->getFields($actionForFieldRestriction) as $field) {
            $columnName = $field->name;
            $dataType   = $field->type instanceof DbColumnFieldType ? $field->type->dataType : 'virtual';
            $cellValue  = match (\get_class($field->type)) {
                DbColumnFieldType::class => $this->viewTransformer->viewFromNormalized($row['field_' . $columnName], $dataType, $field->options),
                VirtualFieldType::class => $row['label_' . $columnName],
                default => null,
            };

            // Additional attributes to build a link to an associated entity.
            $additionalParams = $this->getLinkCellParamsForAssociations($field, $idValues, $row);
            $cellParams       = [
                'value'        => $cellValue,
                'type'         => $dataType,
                'linkToAction' => $field->actionOnClick,
                ...$field->actionOnClick !== null ? [
                    'entity'     => $this->entityConfig->getName(),
                    'primaryKey' => $primaryKey
                ] : [],
                ... $additionalParams,
            ];

            $result['cells'][$columnName] = [
                'type'    => $additionalParams === [] ? $dataType : FieldConfig::DATA_TYPE_STRING, // string for int IDs converted to links
                'content' => $this->templateRenderer->render($field->viewTemplate, $cellParams),
            ];
        }

        return $result;
    }

    protected function getLinkCellParamsForAssociations(FieldConfig $currentField, array $idValues, array $row): array
    {
        if (!$currentField->type instanceof LinkedByFieldType && $currentField->linkToEntity === null) {
            return [];
        }
        $columnName = $currentField->name;
        if (!\array_key_exists('label_' . $columnName, $row)) {
            throw new \LogicException(sprintf('Row data array for entity "%s" must have a "label_%s" key.', $this->entityConfig->getName(), $columnName));
        }
        if ($row['label_' . $columnName] === null) {
            // Label is NULL so there will be no link to associated entity
            return [];
        }
        $labelContent = (string)$row['label_' . $columnName];

        if ($currentField->type instanceof LinkedByFieldType) {
            // One-To-Many, link to "children" entities
            if (\count($idValues) === 0) {
                throw new \LogicException(sprintf(
                    'Entity "%s" has no primary key configured and it cannot be used in a one-to-many relationship.',
                    $this->entityConfig->getName()
                ));
            }
            return [
                'foreign_entity' => $currentField->type->foreignEntity->getName(),
                'inverse_column' => $currentField->type->inverseFieldName,
                'inverse_id'     => $idValues[0],
                'label'          => $labelContent,
            ];
        }
        if ($currentField->linkToEntity !== null) {
            // Many-To-One, link to "parent" entity
            $foreignEntity          = $currentField->linkToEntity->foreignEntity;
            $fieldNamesOfPrimaryKey = $foreignEntity->getFieldNamesOfPrimaryKey();
            if (\count($fieldNamesOfPrimaryKey) === 0) {
                throw new \LogicException(sprintf('Entity "%s" has no primary key configured and it cannot be used in a many-to-one relationship.', $foreignEntity->getName()));
            }

            return [
                'foreign_entity'    => $foreignEntity->getName(),
                // NOTE: think about how to handle primary keys with more than one field.
                //       For now, we just take the first field. It's ok for usual ID fields.
                'foreign_id_column' => $fieldNamesOfPrimaryKey[0],
                'label'             => $labelContent,
            ];
        }
        throw new \LogicException('Unreachable code.');
    }

    protected function addFlashMessage(Request $request, string $type, string $message): void
    {
        $request->getSession()->getFlashBag()->add($type, $message);
    }

    protected function getListFilterForm(Request $request): Form
    {
        $filterForm = $this->formFactory->createFilterForm($this->entityConfig);
        $session    = $request->getSession();

        // First we fill the filter form with the previous filter values
        $storedFilterData = $session->get('filter_' . $this->entityConfig->getName());
        if (\is_array($storedFilterData)) {
            $filterForm->fillFromArray($storedFilterData);
        }

        // Then we overwrite with the new values if there are any
        $filterFormWasSubmitted = $request->get('apply_filter') !== null;
        $filterForm->submit($request, $filterFormWasSubmitted);
        $filterData = $filterForm->getData();

        if ($filterFormWasSubmitted) {
            // Update filter state in the session to current state for the next request
            if ($filterData !== []) {
                $session->set('filter_' . $this->entityConfig->getName(), array_filter($filterData, static fn($value) => $value !== null));
            } else {
                $session->remove('filter_' . $this->entityConfig->getName());
            }
        }

        return $filterForm;
    }

    protected function getListSorting(Request $request): array
    {
        $entityName = $this->entityConfig->getName();
        $session    = $request->getSession();

        $sortField     = $request->get('sort_field');
        $sortDirection = $request->get('sort_direction');

        if ($sortField !== null && $sortDirection !== null) {
            $session->set('sort_field_' . $entityName, $sortField);
            $session->set('sort_direction_' . $entityName, $sortDirection);
        } else {
            $sortField     = $session->get('sort_field_' . $entityName);
            $sortDirection = $session->get('sort_direction_' . $entityName);
        }

        return [$sortField, $sortDirection];
    }

    protected function getDeleteCsrfToken(array $primaryKey, Request $request): string
    {
        return $this->formFactory->generateCsrfToken($this->entityConfig->getName(), FieldConfig::ACTION_DELETE, $primaryKey, $request);
    }

    /**
     * @throws InvalidRequestException
     * @throws BadRequestException
     */
    protected function getEntityPrimaryKeyFromRequest(Request $request): PrimaryKey
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

        return new PrimaryKey($values);
    }
}
