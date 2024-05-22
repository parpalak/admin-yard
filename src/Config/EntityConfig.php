<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Config;

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
        if (isset($this->fields[$fieldConfig->name])) {
            throw new \LogicException(sprintf('Field "%s" has already been defined.', $fieldConfig->name));
        }
        $this->fields[$fieldConfig->name] = $fieldConfig;
        return $this;
    }

    /**
     * @return array<string,FieldConfig>
     */
    public function getFields(string $action): array
    {
        return array_filter($this->fields, static function (FieldConfig $field) use ($action) {
            $allowedInConfig = $field->useOnActions === null || \in_array($action, $field->useOnActions, true);
            $virtualOnForms  = $field->isVirtual() && \in_array($action, [FieldConfig::ACTION_NEW, FieldConfig::ACTION_EDIT], true);
            return $allowedInConfig && !$virtualOnForms;
        });
    }

    public function setEnabledActions(array $enabledActions): static
    {
        if (\count(array_diff($enabledActions, self::ALLOWED_ACTIONS)) > 0) {
            throw new \InvalidArgumentException(sprintf(
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
            if ($field->isVirtual()) {
                continue;
            }

            if ($includePrimaryKey && $field->primaryKey) {
                $dataTypes[$fieldName] = $field->dataType;
            } elseif ($includeDefault && $field->defaultValue !== null) {
                $dataTypes[$fieldName] = $field->dataType;
            } elseif ($field->useOnActions === null || \in_array($action, $field->useOnActions, true)) {
                $dataTypes[$fieldName] = $field->dataType;
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
        foreach ($this->fields as $field) {
            if ($field->primaryKey) {
                $result[] = $field->name;
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
    public function getOneToManyFields(): array
    {
        return array_filter($this->fields, static function (FieldConfig $field) {
            return $field->linkedBy !== null;
        });
    }

    /**
     * @return array<string,FieldConfig>
     */
    public function getManyToOneFields(): array
    {
        return array_filter($this->fields, static function (FieldConfig $field) {
            return $field->linkToEntity !== null;
        });
    }

    public function getFieldDefaultValues(): array
    {
        $defaultValues = array_map(static fn(FieldConfig $field) => $field->defaultValue, $this->fields);
        $defaultValues = array_filter($defaultValues, static fn($value) => $value !== null);

        return $defaultValues;
    }

    public function addFilter(Filter $filter): static
    {
        if (isset($this->filters[$filter->name])) {
            throw new \InvalidArgumentException(sprintf('Filter "%s" already exists.', $filter->name));
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

    public function isAllowedAction(string $action): bool
    {
        return \in_array($action, $this->enabledActions, true);
    }

    public function modifySortableField(?string $sortField): ?string
    {
        if ($sortField === null) {
            return null;
        }

        foreach ($this->fields as $field) {
            if ($field->name === $sortField) {
                return $field->linkedBy === null && $field->linkToEntity === null ? $sortField : 'label_' . $sortField;
            }
        }

        return null;
    }

    public function getSortableFieldNames(): array
    {
        return array_keys(array_filter(
            $this->fields,
            static fn(FieldConfig $fieldConfig) => $fieldConfig->sortable
        ));
    }
}
