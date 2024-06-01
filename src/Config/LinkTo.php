<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Config;

/**
 * Many-to-one association configuration.
 */
readonly class LinkTo
{
    /**
     * Defines this field as foreign key.
     *
     * @param EntityConfig $foreignEntity      The config of entity this field pointing to.
     * @param string       $titleSqlExpression SQL expression that returns title of the foreign entity.
     *                                         Example: 'CONCAT(first_name, " ", last_name)'
     */
    public function __construct(
        public EntityConfig $foreignEntity,
        public string       $titleSqlExpression
    ) {
        $this->foreignEntity->addAutocompleteSqExpression($this->titleSqlExpression);
    }
}
