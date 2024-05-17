<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license http://opensource.org/licenses/MIT MIT
 * @package AdminYard
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
     * @var array<FieldConfig>
     */
    private array $fields = [];
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
    public function getFieldDataTypes(string $action, bool $includePrimaryKey = false): array
    {
        $relatedFields = array_filter($this->fields, static function (FieldConfig $field) use ($includePrimaryKey, $action) {
            return $field->getUseOnActions() === null || \in_array($action, $field->getUseOnActions(), true) || ($field->isPrimaryKey() && $includePrimaryKey);
        });
        return array_map(static fn(FieldConfig $field) => $field->getDataType(), $relatedFields);
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
     * @return array<FieldConfig>
     */
    public function getFieldsWithForeignEntities(): array
    {
        return array_filter($this->fields, static function (FieldConfig $field) {
            return $field->getForeignEntity() !== null;
        });
    }
}
