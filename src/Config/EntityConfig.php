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
    public const EVENT_BEFORE_UPDATE      = 'before_update';
    public const EVENT_AFTER_UPDATE       = 'after_update';
    public const EVENT_BEFORE_CREATE      = 'before_create';
    public const EVENT_AFTER_CREATE       = 'after_create';
    public const EVENT_BEFORE_PATCH       = 'before_patch';
    public const EVENT_AFTER_PATCH        = 'after_patch';
    public const EVENT_BEFORE_DELETE      = 'before_delete';
    public const EVENT_AFTER_EDIT_FETCH   = 'after_edit_fetch';
    public const EVENT_BEFORE_EDIT_RENDER = 'before_edit_render';
    public const EVENT_BEFORE_LIST_RENDER = 'before_list_render';

    private const ALLOWED_ACTIONS = FieldConfig::ALLOWED_ACTIONS;

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
    private string $listTemplate = __DIR__ . '/../../templates/list.php.inc';
    private string $showTemplate = __DIR__ . '/../../templates/show.php.inc';
    private string $newTemplate = __DIR__ . '/../../templates/new.php.inc';
    private string $editTemplate = __DIR__ . '/../../templates/edit.php.inc';

    /**
     * @var array<string,callable>
     */
    private array $listeners = [];

    /**
     * @var string[]
     */
    private array $autocompleteSqlExpression = [];

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

    public function addField(FieldConfig $fieldConfig, ?string $after = null): self
    {
        if (isset($this->fields[$fieldConfig->name])) {
            throw new \LogicException(sprintf('Field "%s" has already been defined.', $fieldConfig->name));
        }

        if ($after !== null && ($keyNumber = array_search($after, array_keys($this->fields), true)) !== false) {
            $this->fields = array_merge(
                \array_slice($this->fields, 0, $keyNumber + 1, true),
                [$fieldConfig->name => $fieldConfig],
                \array_slice($this->fields, $keyNumber + 1, null, true)
            );
        } else {
            $this->fields[$fieldConfig->name] = $fieldConfig;
        }
        return $this;
    }

    /**
     * @return array<string,FieldConfig>
     */
    public function getFields(string $action): array
    {
        return array_filter($this->fields, static fn(FieldConfig $field) => $field->allowedOnAction($action));
    }

    public function findFieldByName(string $name): ?FieldConfig
    {
        return $this->fields[$name] ?? null;
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
            if (!$field->type instanceof DbColumnFieldType) {
                continue;
            }

            if (
                ($includePrimaryKey && $field->type->primaryKey)
                || ($includeDefault && $field->type->defaultValue !== null)
                || $field->allowedOnAction($action)
            ) {
                $dataTypes[$fieldName] = $field->type->dataType;
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
            if ($field->type instanceof DbColumnFieldType && $field->type->primaryKey) {
                $result[] = $field->name;
            }
        }

        return $result;
    }

    public function primaryKeyIsInt(): bool
    {
        $intCount = 0;
        foreach ($this->fields as $field) {
            if ($field->type instanceof DbColumnFieldType && $field->type->primaryKey) {
                if ($field->type->dataType === FieldConfig::DATA_TYPE_INT) {
                    $intCount++;
                } else {
                    return false;
                }
            }
        }

        return $intCount === 1;
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

    public function setListTemplate(string $listTemplate): static
    {
        $this->listTemplate = $listTemplate;
        return $this;
    }

    public function setShowTemplate(string $showTemplate): static
    {
        $this->showTemplate = $showTemplate;
        return $this;
    }

    public function setNewTemplate(string $newTemplate): static
    {
        $this->newTemplate = $newTemplate;
        return $this;
    }

    public function setEditTemplate(string $editTemplate): static
    {
        $this->editTemplate = $editTemplate;
        return $this;
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
    public function getManyToOneFields(): array
    {
        return array_filter($this->fields, static function (FieldConfig $field) {
            return $field->linkToEntity !== null;
        });
    }

    public function getFieldDefaultValues(): array
    {
        $defaultValues = [];
        foreach ($this->fields as $field) {
            if ($field->type instanceof DbColumnFieldType && $field->type->defaultValue !== null) {
                $defaultValues[$field->name] = $field->type->defaultValue;
            }
        }

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
        if ($action === 'patch') {
            return true;
        }

        if ($action === 'autocomplete' && $this->autocompleteSqlExpression !== []) {
            return true;
        }

        return \in_array($action, $this->enabledActions, true);
    }

    public function modifySortableField(?string $sortField): ?string
    {
        if ($sortField === null) {
            return null;
        }

        foreach ($this->fields as $field) {
            if ($field->name === $sortField) {
                return $field->type instanceof DbColumnFieldType && $field->linkToEntity === null ? $sortField : 'virtual_' . $sortField;
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

    public function addListener(string|array $eventNames, callable $listener): static
    {
        foreach ((array)$eventNames as $eventName) {
            $this->listeners[$this->name . '.' . $eventName] = $listener;
        }

        return $this;
    }

    /**
     * @return array<string,callable>
     */
    public function getListeners(): array
    {
        return $this->listeners;
    }

    public function addAutocompleteSqExpression(string $sqExpression): static
    {
        $this->autocompleteSqlExpression[] = $sqExpression;
        return $this;
    }

    public function getAutocompleteSqlExpression(string $hash): ?string
    {
        foreach ($this->autocompleteSqlExpression as $sqlExpression) {
            if (md5($sqlExpression) === $hash) {
                return $sqlExpression;
            }
        }
        return null;
    }
}
