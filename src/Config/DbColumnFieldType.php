<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Config;

readonly class DbColumnFieldType extends AbstractFieldType
{
    /**
     * @param string          $dataType     Data type of the field in database. One of the DATA_TYPE_* constants.
     * @param bool            $primaryKey   True for column that is part of the primary key.
     * @param string|int|null $defaultValue The default value to be inserted to the database when the entity is
     *                                      created. Useful if the field is not present on the new form, and the column
     *                                      in the database has no default value.
     */
    public function __construct(
        public string $dataType = FieldConfig::DATA_TYPE_STRING,
        public bool   $primaryKey = false,
        public mixed  $defaultValue = null,
    ) {
        if (!\in_array($this->dataType, FieldConfig::ALLOWED_DATA_TYPES)) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown data type "%s". Data type must be one of %s.',
                $this->dataType,
                implode(', ', FieldConfig::ALLOWED_DATA_TYPES)
            ));
        }
    }
}
