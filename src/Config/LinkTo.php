<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Config;

use S2\AdminYard\Database\LogicalExpression;

/**
 * Many-to-one association configuration.
 */
readonly class LinkTo
{
    /**
     * Defines this field as foreign key.
     *
     * @param EntityConfig       $foreignEntity       The config of entity this field pointing to.
     * @param string             $titleSqlExpression  SQL expression that returns title of the foreign entity.
     *                                                Example: `CONCAT(first_name, ' ', last_name)`
     * @param ?LogicalExpression $contentFilter       Filter to limit the options that can be selected.
     */
    public function __construct(
        public EntityConfig       $foreignEntity,
        public string             $titleSqlExpression,
        public ?LogicalExpression $contentFilter = null,
    ) {
        /**
         * If a list of foreign entities can be used as the content of an autocomplete control,
         * it must be accessible via a request to that entity endpoint (entity=foreignEntity&action=autocomplete).
         *
         * Therefore, we add the corresponding SQL expression to the foreign entity configuration.
         */
        $this->foreignEntity->addAutocompleteParams($this->getHash(), $this->titleSqlExpression, $this->contentFilter);
    }

    public function getHash(): string
    {
        return md5(serialize([$this->titleSqlExpression]));
    }

    public function getConditions(): array
    {
        return $this->contentFilter !== null ? [$this->contentFilter] : [];
    }
}
