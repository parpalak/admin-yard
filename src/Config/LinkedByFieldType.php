<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Config;

/**
 * One-to-many association configuration.
 */
readonly class LinkedByFieldType extends AbstractFieldType
{
    /**
     * Defines field as a virtual field.
     *
     * @param EntityConfig $foreignEntity      The config of entity which is pointing to this field.
     * @param string       $titleSqlExpression Specifies an aggregate function that will be applied for all foreign
     *                                         entities and its result will be displayed on the list and show screens.
     *                                         Example: 'COUNT(*)'
     *                                         Expression should return NULL if there are no associated entities.
     *                                         Otherwise, a link to filtered foreign entity list will be displayed.
     * @param string       $inverseFieldName   The column name of the foreign entity that is pointing to this field.
     */
    public function __construct(
        public EntityConfig $foreignEntity,
        public string       $titleSqlExpression,
        public string       $inverseFieldName
    ) {
    }
}
