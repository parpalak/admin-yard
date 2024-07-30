<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Config;

use S2\AdminYard\Controller\EntityController;
use S2\AdminYard\Database\LogicalExpression;

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
    private ?string $singularName = null;
    private ?string $pluralName = null;
    private ?string $newTitle = null;
    private ?string $editTitle = null;

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
    private string $listActionsTemplate = __DIR__ . '/../../templates/list-actions.php.inc';
    private string $showTemplate = __DIR__ . '/../../templates/show.php.inc';
    private string $newTemplate = __DIR__ . '/../../templates/new.php.inc';
    private string $editTemplate = __DIR__ . '/../../templates/edit.php.inc';

    /**
     * @var array<string,callable[]>
     */
    private array $listeners = [];

    /**
     * @var array<string, array{sqExpression: string, filter: ?LogicalExpression}>
     */
    private array $autocompleteParams = [];

    private ?string $controllerClass = null;

    private array $extraActions = [];

    private ?LogicalExpression $readAccessControl = null;
    private ?LogicalExpression $writeAccessControl = null;

    /**
     * @param string  $name      Entity name, used in URLs
     * @param ?string $tableName Database table name
     */
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

    public function setSingularName(string $singularName): self
    {
        $this->singularName = $singularName;

        return $this;
    }

    public function getSingularName(): string
    {
        return $this->singularName ?? $this->name;
    }

    public function setPluralName(string $pluralName): self
    {
        $this->pluralName = $pluralName;

        return $this;
    }

    public function getPluralName(): string
    {
        return $this->pluralName ?? $this->name;
    }

    public function setNewTitle(string $newTitle): self
    {
        $this->newTitle = $newTitle;

        return $this;
    }

    public function getNewTitle(): string
    {
        return $this->newTitle ?? $this->name;
    }

    public function setEditTitle(string $editTitle): self
    {
        $this->editTitle = $editTitle;

        return $this;
    }

    public function getEditTitle(): string
    {
        return $this->editTitle ?? $this->name;
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
        foreach ($enabledActions as $action) {
            if (!\is_string($action)) {
                throw new \InvalidArgumentException(sprintf('Action must be a string, "%s" given.', var_export($action, true)));
            }
        }
        $allowedActions = array_merge(self::ALLOWED_ACTIONS, $this->extraActions);
        if (\count(array_diff($enabledActions, $allowedActions)) > 0) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown action encountered: "%s". Actions must be set of %s.',
                implode(', ', $enabledActions),
                implode(', ', $allowedActions)
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

    public function setReadAccessControl(?LogicalExpression $readAccessControlCondition): static
    {
        $this->readAccessControl = $readAccessControlCondition;

        return $this;
    }

    public function setWriteAccessControl(?LogicalExpression $writeAccessControlCondition): static
    {
        $this->writeAccessControl = $writeAccessControlCondition;

        return $this;
    }

    public function getReadAccessControl(): ?LogicalExpression
    {
        return $this->readAccessControl;
    }

    public function getWriteAccessControl(): ?LogicalExpression
    {
        return $this->writeAccessControl;
    }

    /**
     * @return array<string,string>
     */
    public function getFieldDataTypes(
        string $action,
        bool   $includePrimaryKey = false,
        bool   $includeDefault = false,
        bool   $includeInlineEditable = false
    ): array {
        $dataTypes = [];

        foreach ($this->fields as $fieldName => $field) {
            if (!$field->type instanceof DbColumnFieldType) {
                continue;
            }

            if (
                ($includePrimaryKey && $field->type->primaryKey)
                || ($includeDefault && $field->type->defaultValue !== null)
                || ($includeInlineEditable && $field->inlineEdit)
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

    public function setControllerClass(string $controller, array $extraActions = []): static
    {
        if (!is_a($controller, EntityController::class, true)) {
            throw new \InvalidArgumentException(sprintf('Controller class "%s" must extend "%s".', $controller, EntityController::class));
        }
        $this->controllerClass = $controller;
        $this->extraActions    = $extraActions;
        return $this;
    }

    public function getControllerClass(): ?string
    {
        return $this->controllerClass;
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

    public function setListActionsTemplate(string $listActionsTemplate): static
    {
        $this->listActionsTemplate = $listActionsTemplate;
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

    public function getListActionsTemplate(): string
    {
        return $this->listActionsTemplate;
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
     * @return array|string[]
     */
    public function getHints(string $action): array
    {
        return array_map(static fn(FieldConfig $field) => $field->hint, $this->getFields($action));
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

        if ($action === 'autocomplete' && $this->autocompleteParams !== []) {
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
            $this->listeners[$this->name . '.' . $eventName][] = $listener;
        }

        return $this;
    }

    /**
     * @return array<string,callable[]>
     */
    public function getListeners(): array
    {
        return $this->listeners;
    }

    public function addAutocompleteParams(string $hash, string $sqExpression, ?LogicalExpression $contentFilter): static
    {
        $this->autocompleteParams[$hash] = ['sqExpression' => $sqExpression, 'filter' => $contentFilter];

        return $this;
    }

    public function getAutocompleteSqlExpression(string $hash): ?string
    {
        return $this->autocompleteParams[$hash]['sqExpression'] ?? null;
    }

    public function getAutocompleteFilter(string $hash): ?LogicalExpression
    {
        return $this->autocompleteParams[$hash]['filter'] ?? null;
    }
}
