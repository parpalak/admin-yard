<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Config;

use S2\AdminYard\Validator\NotBlank;
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
    public const  DATA_TYPE_PASSWORD  = 'password';
    public const  DATA_TYPE_JSON_ROWS = 'json_rows';

    public const ALLOWED_DATA_TYPES = [
        self::DATA_TYPE_STRING,
        self::DATA_TYPE_INT,
        self::DATA_TYPE_FLOAT,
        self::DATA_TYPE_BOOL,
        self::DATA_TYPE_DATE,
        self::DATA_TYPE_TIMESTAMP,
        self::DATA_TYPE_UNIXTIME,
        self::DATA_TYPE_PASSWORD,
        self::DATA_TYPE_JSON_ROWS,
    ];

    public const ACTION_LIST   = 'list';
    public const ACTION_SHOW   = 'show';
    public const ACTION_NEW    = 'new';
    public const ACTION_EDIT   = 'edit';
    public const ACTION_DELETE = 'delete';

    public const ALLOWED_ACTIONS = [
        self::ACTION_LIST,
        self::ACTION_SHOW,
        self::ACTION_NEW,
        self::ACTION_EDIT,
        self::ACTION_DELETE,
    ];

    public const ACTIONS_ALLOWED_FOR_ENTITY_LINK = [self::ACTION_SHOW, self::ACTION_EDIT];

    /**
     * @param string               $name          Column name in the database.
     * @param string|null          $label         Field label in the interface. If not set, column name will be used as
     *                                            a label.
     * @param string|null          $hint          More information about this field in the title of the label.
     * @param AbstractFieldType    $type          Behavior of this field. Either DbColumnFieldType for usual columns or
     *                                            VirtualFieldType for fields that are not present in the database.
     * @param string|null          $control       What control should be used for this field in the new and edit forms.
     * @param array|null           $options       Selection options for select and radio controls. Also used to convert
     *                                            the values from the normalized internal representation for
     *                                            displaying. Example: ['active' => 'Enabled', 'inactive' => 'Disabled']
     * @param array                $columnLabels  Labels for attributes in JSON_ROWS data type to be displayed.
     * @param ValidatorInterface[] $validators    Validators for new and edit forms.
     * @param bool                 $sortable      True if the column in the list table must be sortable.
     * @param string|null          $actionOnClick Action for link target if the value on view screens must be clickable.
     * @param LinkTo|null          $linkToEntity  Specifies that this field is a foreign key to another entity
     *                                            (many-to-one).
     * @param bool                 $inlineEdit    True if the cell on the list screen may be editable inline.
     * @param array|null           $useOnActions  Describes on which action screens this field will be used.
     * @param string               $viewTemplate  View template for rendering cell content on the list and show screens.
     */
    public function __construct(
        public readonly string            $name,
        private readonly ?string          $label = null,
        public readonly ?string           $hint = null,
        public readonly AbstractFieldType $type = new DbColumnFieldType(),
        public readonly ?string           $control = null,
        public readonly ?array            $options = null,
        public readonly array             $columnLabels = [],
        public readonly array             $validators = [],
        public readonly bool              $sortable = false,
        public readonly ?string           $actionOnClick = null,
        public readonly ?LinkTo           $linkToEntity = null,
        public readonly bool              $inlineEdit = false,
        public readonly ?array            $useOnActions = null,
        public readonly string            $viewTemplate = __DIR__ . '/../../templates/view_cell.php.inc',
        public readonly string            $inlineFormTemplate = __DIR__ . '/../../templates/inline_form_cell.php.inc',
    ) {
        if ($this->actionOnClick !== null && !\in_array($this->actionOnClick, self::ACTIONS_ALLOWED_FOR_ENTITY_LINK, true)) {
            throw new \InvalidArgumentException(\sprintf(
                'Invalid linkToAction "%s". Must be one of [%s].',
                $this->actionOnClick,
                implode(', ', self::ACTIONS_ALLOWED_FOR_ENTITY_LINK)
            ));
        }
        foreach ($this->validators as $validator) {
            if (!$validator instanceof ValidatorInterface) {
                throw new \InvalidArgumentException(\sprintf('Validator must implement "%s".', ValidatorInterface::class));
            }
        }
        if (\count(array_filter([
                !$this->type instanceof DbColumnFieldType,
                $this->linkToEntity !== null,
                $this->actionOnClick !== null,
                $this->inlineEdit !== false,
            ])) > 1) {
            throw new \InvalidArgumentException('Only one of linkToEntity, actionOnClick, inlineEdit or type other than DbColumnFieldType can be set.');
        }
        if ($useOnActions !== null) {
            foreach ($useOnActions as $action) {
                if (!\is_string($action)) {
                    throw new \InvalidArgumentException(\sprintf('Action must be a string, "%s" given.', var_export($action, true)));
                }
            }
            if (\count(array_diff($useOnActions, self::ALLOWED_ACTIONS)) > 0) {
                throw new \InvalidArgumentException(\sprintf(
                    'Unknown actions encountered: [%s]. Actions must be set of [%s].',
                    implode(', ', array_diff($useOnActions, self::ALLOWED_ACTIONS)),
                    implode(', ', self::ALLOWED_ACTIONS)
                ));
            }
        }
        if ($this->control === null && $this->options !== null) {
            throw new \InvalidArgumentException('Options can be set only if control is set.');
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

    public function allowedOnAction(string $action): bool
    {
        $allowedInConfig = $this->useOnActions === null || \in_array($action, $this->useOnActions, true);

        // NOTE: It would be better to restrict actions in constructor, but it's impossible
        // to overwrite null in the readonly property and to distinguish provided value from the default one.
        $oneToManyOnForms = $this->type instanceof LinkedByFieldType && \in_array($action, [self::ACTION_NEW, self::ACTION_EDIT], true);

        return $allowedInConfig && !$oneToManyOnForms;
    }

    public function canBeEmpty(): bool
    {
        // NOTE: maybe it would be better to add a flag to DbColumnFieldType
        foreach ($this->validators as $validator) {
            if ($validator instanceof NotBlank) {
                return false;
            }
        }

        return true;
    }
}
