<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Controller;

use S2\AdminYard\Config\EntityConfig;
use S2\AdminYard\Config\FieldConfig;
use S2\AdminYard\Config\Filter;
use S2\AdminYard\Database\DatabaseHelper;
use S2\AdminYard\Database\DataProviderException;
use S2\AdminYard\Database\PdoDataProvider;
use S2\AdminYard\Database\PrimaryKey;
use S2\AdminYard\Form\FormFactory;
use S2\AdminYard\TemplateRenderer;
use S2\AdminYard\Transformer\ViewTransformer;
use S2\AdminYard\Translator;
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

    final public function listAction(Request $request): string
    {
        $filterForm = $this->formFactory->createFilterForm($this->entityConfig);
        $filterForm->fillFromInputBag($request->query);
        $filterData = $filterForm->getData();

        $filters = $this->entityConfig->getFilters();
        $data    = $this->getEntityList($filters, $filterData);

        $renderedRows = array_map(function (array $row) {
            return $this->renderCellsForNormalizedRow($row, FieldConfig::ACTION_LIST);
        }, $data);

        return $this->templateRenderer->render(
            $this->entityConfig->getListTemplate(),
            [
                'title'          => $this->entityConfig->getName(),
                'entityName'     => $this->entityConfig->getName(),
                'filterControls' => $filterForm->getControls(),
                'filterLabels'   => array_map(static fn(Filter $filter) => $filter->label, $filters),
                'header'         => $this->entityConfig->getLabels(FieldConfig::ACTION_LIST),
                'rows'           => $renderedRows,
                'rowActions'     => array_map(static fn(string $action) => [
                    'name' => $action,
                ], array_diff($this->entityConfig->getEnabledActions(), [FieldConfig::ACTION_LIST, FieldConfig::ACTION_NEW])),
                'entityActions'  => array_map(static fn(string $action) => [
                    'name' => $action,
                ], array_intersect($this->entityConfig->getEnabledActions(), [FieldConfig::ACTION_NEW])),
            ]
        );
    }

    /**
     * @throws BadRequestException
     * @throws NotFoundException
     */
    public function showAction(Request $request): string
    {
        $fieldNamesOfPrimaryKey = $this->entityConfig->getFieldNamesOfPrimaryKey();
        foreach ($fieldNamesOfPrimaryKey as $fieldName) {
            if (!$request->query->has($fieldName)) {
                throw new BadRequestException(sprintf($this->translator->trans('Parameter "%s" must be provided.'), $fieldName));
            }
        }
        $primaryKey = PrimaryKey::fromRequestQueryParams($request, $fieldNamesOfPrimaryKey);

        $data = $this->dataProvider->getEntity(
            $this->entityConfig->getTableName(),
            $this->entityConfig->getFieldDataTypes(FieldConfig::ACTION_SHOW, true),
            DatabaseHelper::getSqlExpressionsForAssociations($this->entityConfig),
            $primaryKey,
        );

        if ($data === null) {
            throw new NotFoundException(sprintf($this->translator->trans('%s with %s not found.'), $this->entityConfig->getName(), $primaryKey->toString()));
        }

        $renderedRow = $this->renderCellsForNormalizedRow($data, FieldConfig::ACTION_SHOW);

        return $this->templateRenderer->render(
            $this->entityConfig->getShowTemplate(),
            [
                'title'      => $this->entityConfig->getName(),
                'entityName' => $this->entityConfig->getName(),
                'header'     => $this->entityConfig->getLabels(FieldConfig::ACTION_SHOW),
                'row'        => $renderedRow,
                'primaryKey' => $primaryKey->toArray(),
                'actions'    => array_map(static fn(string $action) => [
                    'name' => $action,
                ], array_diff($this->entityConfig->getEnabledActions(), [FieldConfig::ACTION_SHOW, FieldConfig::ACTION_NEW])),
            ]
        );
    }

    /**
     * @throws NotFoundException
     * @throws SuspiciousOperationException
     */
    public function editAction(Request $request): string|Response
    {
        $primaryKey    = PrimaryKey::fromRequestQueryParams($request, $this->entityConfig->getFieldNamesOfPrimaryKey());
        $errorMessages = [];

        $form = $this->formFactory->createEntityForm($this->entityConfig, FieldConfig::ACTION_EDIT);
        if ($request->getMethod() === Request::METHOD_POST) {
            $form->fillFromInputBag($request->request);
            // TODO validate
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

        $data = $this->dataProvider->getEntity(
            $this->entityConfig->getTableName(),
            $this->entityConfig->getFieldDataTypes(FieldConfig::ACTION_EDIT),
            [],
            $primaryKey,
        );
        if ($data === null) {
            throw new NotFoundException(sprintf($this->translator->trans('%s with %s not found.'), $this->entityConfig->getName(), $primaryKey->toString()));
        }
        $form->fillFromNormalizedData($data);

        return $this->templateRenderer->render(
            $this->entityConfig->getEditTemplate(),
            [
                'title'         => $this->entityConfig->getName(),
                'entityName'    => $this->entityConfig->getName(),
                'errorMessages' => $errorMessages,
                'primaryKey'    => $primaryKey->toArray(),
                'header'        => array_map(static fn(FieldConfig $field) => $field->getLabel(), $this->entityConfig->getFields(FieldConfig::ACTION_SHOW)),
                'fields'        => $form->getControls(),
                'actions'       => array_map(static fn(string $action) => [
                    'name' => $action,
                ], array_diff($this->entityConfig->getEnabledActions(), [FieldConfig::ACTION_EDIT, FieldConfig::ACTION_NEW])),
            ]
        );
    }

    public function newAction(Request $request): string|Response
    {
        $form          = $this->formFactory->createEntityForm($this->entityConfig, FieldConfig::ACTION_NEW);
        $errorMessages = [];

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->fillFromInputBag($request->request);
            // TODO validate
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

        return $this->templateRenderer->render(
            $this->entityConfig->getNewTemplate(),
            [
                'title'         => $this->entityConfig->getName(),
                'entityName'    => $this->entityConfig->getName(),
                'errorMessages' => $errorMessages,
                'header'        => array_map(static fn(FieldConfig $field) => $field->getLabel(), $this->entityConfig->getFields(FieldConfig::ACTION_SHOW)),
                'fields'        => $form->getControls(),
                'actions'       => array_map(static fn(string $action) => [
                    'name' => $action,
                ], array_intersect($this->entityConfig->getEnabledActions(), [FieldConfig::ACTION_LIST])),
            ]
        );
    }

    public function deleteAction(Request $request): RedirectResponse
    {
        $primaryKey = PrimaryKey::fromRequestQueryParams($request, $this->entityConfig->getFieldNamesOfPrimaryKey());
        $this->dataProvider->deleteEntity($this->entityConfig->getTableName(), $primaryKey);

        return new RedirectResponse('?entity=' . $this->entityConfig->getName() . '&action=list');
    }

    /**
     * @param array<string, Filter> $filters
     */
    protected function getEntityList(array $filters, array $filterData): array
    {
        return $this->dataProvider->getEntityList(
            $this->entityConfig->getTableName(),
            $this->entityConfig->getFieldDataTypes(FieldConfig::ACTION_LIST, true),
            DatabaseHelper::getSqlExpressionsForAssociations($this->entityConfig),
            $filters,
            $filterData,
            $this->entityConfig->getLimit(),
            0// $request->query->getInt('offset', 0)
        );
    }

    protected function renderCellsForNormalizedRow(array $row, string $actionForFieldRestriction): array
    {
        $idFieldNames = $this->entityConfig->getFieldNamesOfPrimaryKey();
        $idValues     = array_map(static fn(string $columnName) => $row['field_' . $columnName], $idFieldNames);
        $primaryKey   = array_combine($idFieldNames, $idValues);
        $result       = ['primary_key' => $primaryKey];

        foreach ($this->entityConfig->getFields($actionForFieldRestriction) as $field) {
            $columnName = $field->getName();
            $dataType   = $field->getDataType();
            $cellValue  = $dataType === FieldConfig::DATA_TYPE_VIRTUAL
                ? null
                : $this->viewTransformer->viewFromNormalized($row['field_' . $columnName], $dataType, $field->getOptions());

            // Additional attributes to build a link to an associated entity.
            $additionalParams = $this->getLinkCellParamsForAssociations($field, $idValues, $row);
            $cellParams       = [
                'value'        => $cellValue,
                'type'         => $dataType,
                'linkToAction' => $field->getLinkToAction(),
                ...$field->getLinkToAction() !== null ? [
                    'entity'     => $this->entityConfig->getName(),
                    'primaryKey' => $primaryKey
                ] : [],
                ... $additionalParams,
            ];

            $result['cells'][$columnName] = [
                'type'    => $additionalParams === [] ? $dataType : FieldConfig::DATA_TYPE_STRING, // string for int IDs converted to links
                'content' => $this->templateRenderer->render($field->getViewTemplate(), $cellParams),
            ];
        }

        return $result;
    }

    protected function getLinkCellParamsForAssociations(FieldConfig $currentField, array $idValues, array $row): array
    {
        $foreignEntity = $currentField->getForeignEntity();
        $columnName    = $currentField->getName();
        if ($foreignEntity === null) {
            return [];
        }
        if (!\array_key_exists('label_' . $columnName, $row)) {
            throw new \LogicException(sprintf('Row data array for entity "%s" must have a "label_%s" key.', $this->entityConfig->getName(), $columnName));
        }
        if ($row['label_' . $columnName] === null) {
            // Label is NULL so there will be no link to associated entity
            return [];
        }
        $labelContent = (string)$row['label_' . $columnName];

        if ($currentField->getInverseFieldName() !== null) {
            // One-To-Many, link to "children" entities
            if (\count($idValues) === 0) {
                throw new \LogicException(sprintf(
                    'Entity "%s" has no primary key configured and it cannot be used in a one-to-many relationship.',
                    $this->entityConfig->getName()
                ));
            }
            return [
                'foreign_entity' => $foreignEntity->getName(),
                'inverse_column' => $currentField->getInverseFieldName(),
                'inverse_id'     => $idValues[0],
                'label'          => $labelContent,
            ];
        }

        // Many-To-One, link to "parent" entity
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
}
