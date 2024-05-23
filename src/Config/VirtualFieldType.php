<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Config;

readonly class VirtualFieldType extends AbstractFieldType
{
    /**
     * Defines this field as a virtual field.
     *
     * @param string $titleSqlExpression Subquery that fetches data for the field.
     *                                   Example: 'SELECT GROUP_CONCAT(title) FROM books WHERE author_id = entity.id'
     */
    public function __construct(
        public string $titleSqlExpression
    ) {
    }
}
