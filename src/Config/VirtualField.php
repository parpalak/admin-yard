<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Config;

class VirtualField
{
    /**
     * Defines this field as a virtual field.
     *
     * @param string $titleSqlExpression Aggregate function to be applied for all foreign entities
     *                                   on the list and show screens. Example: 'COUNT(*)'
     *                                   Expression should return NULL if there is no associated entities.
     *                                   Otherwise, a link to filtered foreign entity list will be displayed.
     */
    public function __construct(
        public string $titleSqlExpression
    ) {
    }
}
