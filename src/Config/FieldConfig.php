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

    private const ALLOWED_DATA_TYPES = [
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

    /**
     * @param string               $name         Column name in the database.
     * @param string|null          $label        Field label in the interface. If not set, column name will be used as
     *                                           a label.
     * @param string               $dataType     Data type of the field in database. One of the DATA_TYPE_* constants.
     * @param bool                 $primaryKey   True for column that is part of the primary key.
     * @param string|null          $control      What control should be used for this field in the new and edit forms.
     * @param array|null           $options      Selection options for select and radio controls. Also used to convert
     *                                           the values from the normalized internal representation for displaying.
     *                                           Example: ['active' => 'Enabled', 'inactive' => 'Disabled']
     * @param string|int|null      $defaultValue The default value to be inserted in the database when the entity is
     *                                           created. Useful if the field is not present on the new form, and
     *                                           the column in the database has no default value.
     * @param ValidatorInterface[] $validators   Validators for new and edit forms.
     * @param bool                 $sortable     True if the column in the list table must be sortable.
     * @param string|null          $linkToAction Link target if the column in the list table must be clickable.
     * @param LinkTo|null          $linkToEntity Specifies that this field is a foreign key to another entity
     *                                           (many-to-one).
     * @param LinkedBy|null        $linkedBy     Specifies that this field is a virtual field (one-to-many).
     * @param array|null           $useOnActions Describes on which action screens this field will be used.
     * @param string               $viewTemplate View template for rendering cell content on the list and show screens.
     */
    public function __construct(
        public readonly string          $name,
        private readonly ?string        $label = null,
        public readonly string          $dataType = self::DATA_TYPE_STRING,
        public readonly bool            $primaryKey = false,
        public readonly ?string         $control = null,
        public readonly ?array          $options = null,
        public readonly string|int|null $defaultValue = null,
        public readonly array           $validators = [],
        public readonly bool            $sortable = false,
        public readonly ?string         $linkToAction = null,
        public readonly ?LinkTo         $linkToEntity = null,
        public readonly ?LinkedBy       $linkedBy = null,
        public readonly ?array          $useOnActions = null,
        public readonly string          $viewTemplate = __DIR__ . '/../../templates/view_cell.php',

    ) {
        if (!\in_array($this->dataType, self::ALLOWED_DATA_TYPES)) {
            throw new InvalidArgumentException(sprintf(
                'Unknown data type "%s". Data type must be one of %s.',
                $this->dataType,
                implode(', ', self::ALLOWED_DATA_TYPES)
            ));
        }
        if ($this->linkToAction !== null && !\in_array($this->linkToAction, [self::ACTION_SHOW, self::ACTION_EDIT], true)) {
            throw new InvalidArgumentException(sprintf('Invalid linkToAction "%s". Must be one of [%s].', $this->linkToAction, implode(', ', [self::ACTION_SHOW, self::ACTION_EDIT])));
        }
        foreach ($this->validators as $validator) {
            if (!$validator instanceof ValidatorInterface) {
                throw new InvalidArgumentException(sprintf('Validator must implement "%s".', ValidatorInterface::class));
            }
        }
        if (\count(array_filter([
                $this->linkToEntity !== null,
                $this->linkedBy !== null,
                $this->linkToAction !== null,
            ])) > 1) {
            throw new InvalidArgumentException('Only one of linkToEntity, linkedByEntities or linkToAction property can be set.');
        }
        /**
         * TODO think about controls and options validation.
         * If FormControlFactory can be extended to produce custom controls, how can we validate them here?
         */
    }

    public function getLabel(): string
    {
        return $this->label ?? mb_convert_case(str_replace('_', ' ', preg_replace('#_id$#', '', $this->name)), MB_CASE_TITLE, 'UTF-8');
    }

    public function isVirtual(): bool
    {
        // Virtual fields are not stored in the database, they are generated on the fly
        // For now virtual fields can be configured only as a one-to-many association.
        return $this->linkedBy !== null;
    }
}
