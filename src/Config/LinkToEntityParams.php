<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Config;

readonly class LinkToEntityParams
{
    public function __construct(
        public string $entityName,
        public array  $filterColumnNames,
        public array  $valueColumnNamesOfFilters
    ) {
    }
}
