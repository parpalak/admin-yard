<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license http://opensource.org/licenses/MIT MIT
 * @package AdminYard
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
        self::DATA_TYPE_VIRTUAL,
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

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;
        return $this;
    }

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
        return $this->label ?? mb_convert_case($this->name, MB_CASE_TITLE, 'UTF-8');
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

    public function getValidators(): array
    {
        return $this->validators;
    }

    public function isPrimaryKey(): bool
    {
        return $this->primaryKey;
    }

    public function manyToOne(EntityConfig $foreignEntity, string $titleSqlExpression): static
    {
        $this->foreignEntity      = $foreignEntity;
        $this->titleSqlExpression = $titleSqlExpression;

        return $this;
    }

    public function oneToMany(EntityConfig $foreignEntity, string $titleSqlExpression, string $inverseFieldName): static
    {
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
}
