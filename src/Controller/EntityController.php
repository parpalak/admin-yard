<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license http://opensource.org/licenses/MIT MIT
 * @package AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Controller;

use S2\AdminYard\Config\EntityConfig;
use S2\AdminYard\Config\FieldConfig;
use S2\AdminYard\Database\DatabaseHelper;
use S2\AdminYard\Database\PdoDataProvider;
use S2\AdminYard\Database\PrimaryKey;
use S2\AdminYard\Form\FormFactory;
use S2\AdminYard\TemplateRenderer;
use S2\AdminYard\Transformer\ViewTransformer;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

readonly class EntityController
{
    public function __construct(
        protected EntityConfig     $entityConfig,
        protected PdoDataProvider  $dataProvider,
        protected ViewTransformer  $dataTransformer,
        protected TemplateRenderer $templateRenderer,
        protected FormFactory      $formFactory,
    ) {
    }

    final public function listAction(Request $request): string
    {
        $data = $this->getEntityListFromRequest($request);

        $renderedRows = array_map(function (array $row) {
            return $this->viewDataFromDbRow($row, FieldConfig::ACTION_LIST);
        }, $data);

        return $this->templateRenderer->render(
            $this->entityConfig->getListTemplate(),
            [
                'title'      => $this->entityConfig->getName(),
                'entityName' => $this->entityConfig->getName(),
                'header'     => $this->entityConfig->getLabels(FieldConfig::ACTION_LIST),
                'rows'       => $renderedRows,
                'actions'    => array_map(static fn(string $action) => [
                    'name' => $action,
                ], array_diff($this->entityConfig->getEnabledActions(), [FieldConfig::ACTION_LIST, FieldConfig::ACTION_NEW])),
            ]
        );
    }

    public function showAction(Request $request): string
    {
        $primaryKey = PrimaryKey::fromRequestQueryParams($request, $this->entityConfig->getFieldNamesOfPrimaryKey());

        $data = $this->dataProvider->getEntity(
            $this->entityConfig->getTableName(),
            $this->entityConfig->getFieldDataTypes(FieldConfig::ACTION_SHOW, true),
            DatabaseHelper::getSqlExpressionsForManyToOne($this->entityConfig),
            $primaryKey,
        );

        $renderedRow = $this->viewDataFromDbRow($data, FieldConfig::ACTION_SHOW);

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

    public function editAction(Request $request): string|Response
    {
        $primaryKey = PrimaryKey::fromRequestQueryParams($request, $this->entityConfig->getFieldNamesOfPrimaryKey());

        $form = $this->formFactory->create($this->entityConfig, FieldConfig::ACTION_EDIT);
        if ($request->getMethod() === Request::METHOD_POST) {
            $form->fillFromRequest($request);
            // TODO validate
            $data = $form->getData();

            $this->dataProvider->updateEntity(
                $this->entityConfig->getTableName(),
                $this->entityConfig->getFieldDataTypes(FieldConfig::ACTION_EDIT),
                $primaryKey,
                $data
            );

            // Update primary key for correct URL in form
            $primaryKey = $primaryKey->withColumnValues($data);

            return new RedirectResponse('?' . http_build_query([
                    'entity' => $this->entityConfig->getName(),
                    'action' => 'edit',
                    ...$primaryKey->toArray()
                ]));
        }

        $data = $this->dataProvider->getEntity(
            $this->entityConfig->getTableName(),
            $this->entityConfig->getFieldDataTypes(FieldConfig::ACTION_EDIT),
            [],
            $primaryKey,
        );
        $form->fillFromNormalizedData($data);

        return $this->templateRenderer->render(
            $this->entityConfig->getEditTemplate(),
            [
                'title'      => $this->entityConfig->getName(),
                'entityName' => $this->entityConfig->getName(),
                'primaryKey' => $primaryKey->toArray(),
                'header'     => array_map(static fn(FieldConfig $field) => $field->getLabel(), $this->entityConfig->getFields(FieldConfig::ACTION_SHOW)),
                'fields'     => $form->getControls(),
                'actions'    => array_map(static fn(string $action) => [
                    'name' => $action,
                ], array_diff($this->entityConfig->getEnabledActions(), [FieldConfig::ACTION_EDIT, FieldConfig::ACTION_NEW])),
            ]
        );
    }

    public function newAction(Request $request): string|Response
    {
        $form = $this->formFactory->create($this->entityConfig, FieldConfig::ACTION_NEW);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->fillFromRequest($request);
            // TODO validate
            $data = $form->getData();

            $lastInsertId         = $this->dataProvider->createEntity(
                $this->entityConfig->getTableName(),
                $this->entityConfig->getFieldDataTypes(FieldConfig::ACTION_NEW),
                $data
            );
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

        return $this->templateRenderer->render(
            $this->entityConfig->getNewTemplate(),
            [
                'title'      => $this->entityConfig->getName(),
                'entityName' => $this->entityConfig->getName(),
                'header'     => array_map(static fn(FieldConfig $field) => $field->getLabel(), $this->entityConfig->getFields(FieldConfig::ACTION_SHOW)),
                'fields'     => $form->getControls(),
                'actions'    => array_map(static fn(string $action) => [
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

    protected function getEntityListFromRequest(Request $request): array
    {
        return $this->dataProvider->getEntityList(
            $this->entityConfig->getTableName(),
            $this->entityConfig->getFieldDataTypes(FieldConfig::ACTION_LIST, true),
            DatabaseHelper::getSqlExpressionsForManyToOne($this->entityConfig),
            $this->entityConfig->getLimit(),
            $request->query->getInt('offset', 0)
        );
    }

    protected function viewDataFromDbRow(array $row, string $actionForFieldRestriction): array
    {
        $idFieldNames = $this->entityConfig->getFieldNamesOfPrimaryKey();
        $idValues     = array_map(static fn(string $columnName) => $row['field_' . $columnName], $idFieldNames);
        $result       = ['primary_key' => array_combine($idFieldNames, $idValues)];

        foreach ($this->entityConfig->getFields($actionForFieldRestriction) as $field) {
            $columnName                   = $field->getName();
            $result['cells'][$columnName] = [
                'value' => $this->dataTransformer->viewFromDb($row['field_' . $columnName], $field->getDataType()),
                'type'  => $field->getDataType(),
                // Additional attributes to build a link to an associated entity.
                ... (static function (?EntityConfig $foreignEntity) use ($columnName, $row): array {
                    if ($foreignEntity === null) {
                        return [];
                    }
                    $fieldNamesOfPrimaryKey = $foreignEntity->getFieldNamesOfPrimaryKey();
                    if (\count($fieldNamesOfPrimaryKey) === 0) {
                        throw new \LogicException(sprintf('Entity "%s" has no primary key configured and it cannot be used in a many-to-one relationship.', $foreignEntity->getName()));
                    }
                    return [
                        'foreign_entity' => $foreignEntity->getName(),
                        // NOTE: think about how to handle primary keys with more than one field.
                        //       For now, we just take the first field. It's ok for usual ID fields.
                        'foreign_column' => $fieldNamesOfPrimaryKey[0],
                        'label'          => $row['label_' . $columnName],
                    ];
                }) ($field->getForeignEntity())
            ];
        }

        return $result;
    }
}