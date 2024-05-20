<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Config;

use InvalidArgumentException;
use S2\AdminYard\Validator\ValidatorInterface;

class FieldConfig
{
    public const  DATA_TYPE_STRING    = 'string';
    public const  DATA_TYPE_INT       = 'int';
    public const  DATA_TYPE_FLOAT     = 'float';
    public const  DATA_TYPE_BOOL      = 'bool';
    public const  DATA_TYPE_DATE      = 'date';
    public const  DATA_TYPE_TIMESTAMP = 'timestamp';
    public const  DATA_TYPE_UNIXTIME  = 'unixtime';
    public const  DATA_TYPE_VIRTUAL   = 'virtual'; // Not stored in database. NOTE: maybe this solution doesn't smell good
    private const ALLOWED_DATA_TYPES  = [
        self::DATA_TYPE_STRING,
        self::DATA_TYPE_INT,
        self::DATA_TYPE_FLOAT,
        self::DATA_TYPE_BOOL,
        self::DATA_TYPE_DATE,
        self::DATA_TYPE_TIMESTAMP,
        self::DATA_TYPE_UNIXTIME,
    ];

    public const ACTION_LIST   = 'list';
    public const ACTION_SHOW   = 'show';
    public const ACTION_NEW    = 'new';
    public const ACTION_EDIT   = 'edit';
    public const ACTION_DELETE = 'delete';

    private string $name;
    private ?string $label = null;
    private string $dataType = self::DATA_TYPE_STRING;
    private ?string $control = null;
    private bool $sortable = false;
    private bool $filterable = false;
    private ?array $useOnActions = null;
    /**
     * @var ValidatorInterface[]
     */
    private array $validators = [];
    private bool $primaryKey = false;

    private ?EntityConfig $foreignEntity = null;
    private ?string $titleSqlExpression = null;
    private ?string $inverseFieldName = null;
    private string $viewTemplate = __DIR__ . '/../../templates/view_cell.php';
    private ?array $options = null;
    private string|int|null $defaultValue = null;
    private ?string $linkToAction = null;

    /**
     * @param string $name Column name in the database.
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @param string $label Field label in the interface. If not set, column name will be used as a label.
     *
     * @return $this
     */
    public function setLabel(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    /**
     * Describes data type of the column in database.
     *
     * @param string $dataType Data type of the field in database. One of the DATA_TYPE_* constants.
     */
    public function setDataType(string $dataType): self
    {
        if (!\in_array($dataType, self::ALLOWED_DATA_TYPES)) {
            throw new InvalidArgumentException(sprintf(
                'Unknown data type "%s". Data type must be one of %s.',
                $dataType,
                implode(', ', self::ALLOWED_DATA_TYPES)
            ));
        }
        $this->dataType = $dataType;
        return $this;
    }

    /**
     * Specifies control type of this field for the new and edit forms.
     *
     * @param string $control What control should be used for this field in the new and edit forms.
     */
    public function setControl(string $control): self
    {
        // TODO add control validation
        $this->control = $control;
        return $this;
    }

    public function markAsSortable(): self
    {
        $this->sortable = true;
        return $this;
    }

    public function markAsFilterable(bool $filterable): self
    {
        $this->filterable = $filterable;
        return $this;
    }

    /**
     * Describes on which action screens this field will be used.
     */
    public function setUseOnActions(array $actions): self
    {
        $this->useOnActions = $actions;
        return $this;
    }

    public function markAsPrimaryKey(): self
    {
        $this->primaryKey = true;

        return $this;
    }

    public function addValidator(ValidatorInterface $validator): self
    {
        $this->validators[] = $validator;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): ?string
    {
        return $this->label ?? mb_convert_case(str_replace('_', ' ', preg_replace('#_id$#', '', $this->name)), MB_CASE_TITLE, 'UTF-8');
    }

    public function getDataType(): string
    {
        return $this->dataType;
    }

    public function getControl(): ?string
    {
        return $this->control;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function isFilterable(): bool
    {
        return $this->filterable;
    }

    public function getUseOnActions(): ?array
    {
        return $this->useOnActions;
    }

    /**
     * @return ValidatorInterface[]
     */
    public function getValidators(): array
    {
        return $this->validators;
    }

    public function isPrimaryKey(): bool
    {
        return $this->primaryKey;
    }

    /**
     * Defines this field as foreign key.
     *
     * @param EntityConfig $foreignEntity      The config of entity this field pointing to.
     * @param string       $titleSqlExpression SQL expression that returns title of the foreign entity.
     *                                         Example: 'CONCAT(first_name, " ", last_name)'
     *
     * @return $this
     */
    public function manyToOne(EntityConfig $foreignEntity, string $titleSqlExpression): static
    {
        $this->foreignEntity      = $foreignEntity;
        $this->titleSqlExpression = $titleSqlExpression;

        return $this;
    }

    /**
     * Defines this field as a virtual field.
     *
     * @param EntityConfig $foreignEntity      The config of entity which is pointing to this field.
     * @param string       $titleSqlExpression Aggregate function to be applied for all foreign entities
     *                                         on the list and show screens. Example: 'COUNT(*)'
     *                                         Expression should return NULL if there is no associated entities.
     *                                         Otherwise, a link to filtered foreign entity list will be displayed.
     * @param string       $inverseFieldName   The column name of the foreign entity that is pointing to this field.
     *
     * @return $this
     */
    public function oneToMany(EntityConfig $foreignEntity, string $titleSqlExpression, string $inverseFieldName): static
    {
        $this->dataType           = self::DATA_TYPE_VIRTUAL;
        $this->foreignEntity      = $foreignEntity;
        $this->titleSqlExpression = $titleSqlExpression;
        $this->inverseFieldName   = $inverseFieldName;

        return $this;
    }

    public function getForeignEntity(): ?EntityConfig
    {
        return $this->foreignEntity;
    }

    public function getTitleSqlExpression(): ?string
    {
        return $this->titleSqlExpression;
    }

    public function getInverseFieldName(): ?string
    {
        return $this->inverseFieldName;
    }

    public function getViewTemplate(): string
    {
        return $this->viewTemplate;
    }

    /**
     * Specifies the selection options for select and radio controls
     * and converts the values from the normalized internal representation for display.
     *
     * @param array $options Mapping of internal to displayed values. For example: ['active' => 'Enabled', 'inactive'
     *                       => 'Disabled']
     */
    public function setOptions(array $options): static
    {
        $this->options = $options;
        return $this;
    }

    public function getOptions(): ?array
    {
        return $this->options;
    }

    /**
     * If this field is not present on the new form, and the column in the database has no default value,
     * this value will be used.
     *
     * @param int|string $defaultValue The default value to be inserted in the database when the entity is created.
     *
     * @return $this
     */
    public function setDefaultValue(int|string $defaultValue): static
    {
        $this->defaultValue = $defaultValue;
        return $this;
    }

    public function getDefaultValue(): int|string|null
    {
        return $this->defaultValue;
    }

    public function setLinkToAction(string $action): static
    {
        $this->linkToAction = $action;

        return $this;
    }

    public function getLinkToAction(): ?string
    {
        return $this->linkToAction;
    }
}
