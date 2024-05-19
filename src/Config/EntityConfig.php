<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Config;

use InvalidArgumentException;
use LogicException;

class EntityConfig
{
    private const ALLOWED_ACTIONS = [
        FieldConfig::ACTION_LIST,
        FieldConfig::ACTION_SHOW,
        FieldConfig::ACTION_NEW,
        FieldConfig::ACTION_EDIT,
        FieldConfig::ACTION_DELETE,
    ];
    private readonly string $tableName;

    /**
     * @var array<string,FieldConfig>
     */
    private array $fields = [];

    /**
     * @var array<string,Filter>
     */
    private array $filters = [];

    /**
     * @var string[]
     */
    private array $enabledActions = self::ALLOWED_ACTIONS;

    private bool $default = false;
    private string $listTemplate = __DIR__ . '/../../templates/list.php';
    private string $showTemplate = __DIR__ . '/../../templates/show.php';
    private string $newTemplate = __DIR__ . '/../../templates/new.php';
    private string $editTemplate = __DIR__ . '/../../templates/edit.php';

    public function __construct(
        private readonly string $name,
        string                  $tableName = null
    ) {
        $this->tableName = $tableName ?? $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function addField(FieldConfig $fieldConfig): self
    {
        if (isset($this->fields[$fieldConfig->getName()])) {
            throw new LogicException(sprintf('Field "%s" has already been defined.', $fieldConfig->getName()));
        }
        $this->fields[$fieldConfig->getName()] = $fieldConfig;
        return $this;
    }

    /**
     * @return array<FieldConfig>
     */
    public function getFields(?string $action = null): array
    {
        if ($action !== null) {
            return array_filter($this->fields, static function (FieldConfig $field) use ($action) {
                return $field->getUseOnActions() === null || \in_array($action, $field->getUseOnActions(), true);
            });
        }

        return $this->fields;
    }

    public function setEnabledActions(array $enabledActions): static
    {
        if (\count(array_diff($enabledActions, self::ALLOWED_ACTIONS)) > 0) {
            throw new InvalidArgumentException(sprintf(
                'Unknown action encountered: "%s". Actions must be set of %s.',
                implode(', ', $enabledActions),
                implode(', ', self::ALLOWED_ACTIONS)
            ));
        }
        $this->enabledActions = $enabledActions;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getEnabledActions(): array
    {
        return $this->enabledActions;
    }

    /**
     * @return array<string,string>
     */
    public function getFieldDataTypes(string $action, bool $includePrimaryKey = false, bool $includeDefault = false): array
    {
        $dataTypes = [];

        foreach ($this->fields as $fieldName => $field) {
            if ($field->getDataType() === FieldConfig::DATA_TYPE_VIRTUAL) {
                continue;
            }

            if ($includePrimaryKey && $field->isPrimaryKey()) {
                $dataTypes[$fieldName] = $field->getDataType();
            } elseif ($includeDefault && $field->getDefaultValue() !== null) {
                $dataTypes[$fieldName] = $field->getDataType();
            } elseif ($field->getUseOnActions() === null || \in_array($action, $field->getUseOnActions(), true)) {
                $dataTypes[$fieldName] = $field->getDataType();
            }
        }

        return $dataTypes;
    }

    /**
     * @return string[]
     */
    public function getFieldNamesOfPrimaryKey(): array
    {
        $result = [];
        foreach ($this->getFields() as $field) {
            if ($field->isPrimaryKey()) {
                $result[] = $field->getName();
            }
        }

        return $result;
    }

    public function isDefault(): bool
    {
        return $this->default;
    }

    /**
     * Display this entity list on the first screen.
     */
    public function markAsDefault(): static
    {
        $this->default = true;

        return $this;
    }

    public function getControllerClass(): ?string
    {
        // TODO
        return null;
    }

    public function getLimit(): ?int
    {
        // TODO
        return 200;
    }

    public function getListTemplate(): string
    {
        return $this->listTemplate;
    }

    public function getShowTemplate(): string
    {
        return $this->showTemplate;
    }

    public function getEditTemplate(): string
    {
        return $this->editTemplate;
    }

    public function getNewTemplate(): string
    {
        return $this->newTemplate;
    }

    /**
     * @return array|string[]
     */
    public function getLabels(string $action): array
    {
        return array_map(static fn(FieldConfig $field) => $field->getLabel(), $this->getFields($action));
    }

    /**
     * @return array<string,FieldConfig>
     */
    public function getFieldsWithForeignEntities(): array
    {
        return array_filter($this->fields, static function (FieldConfig $field) {
            return $field->getForeignEntity() !== null;
        });
    }

    /**
     * @return array<string,FieldConfig>
     */
    public function getOneToManyFields(): array
    {
        return array_filter($this->fields, static function (FieldConfig $field) {
            return $field->getForeignEntity() !== null && $field->getInverseFieldName() !== null;
        });
    }

    /**
     * @return array<string,FieldConfig>
     */
    public function getManyToOneFields(): array
    {
        return array_filter($this->fields, static function (FieldConfig $field) {
            return $field->getForeignEntity() !== null && $field->getInverseFieldName() === null;
        });
    }

    public function getFieldDefaultValues(): array
    {
        $defaultValues = array_map(static fn(FieldConfig $field) => $field->getDefaultValue(), $this->fields);
        $defaultValues = array_filter($defaultValues, static fn($value) => $value !== null);

        return $defaultValues;
    }

    public function addFilter(Filter $filter): static
    {
        if (isset($this->filters[$filter->name])) {
            throw new InvalidArgumentException(sprintf('Filter "%s" already exists.', $filter->name));
        }
        $this->filters[$filter->name] = $filter;
        return $this;
    }

    /**
     * @return array<string,Filter>
     */
    public function getFilters(): array
    {
        return $this->filters;
    }
}
