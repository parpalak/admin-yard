<?php
/**
 * @copyright 2025 Roman Parpalak
 * @license   https://opensource.org/license/mit MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Database;

readonly class AndWrapper
{
    private array $value;

    public function __construct(string|int|float ...$value)
    {
        $this->value = $value;
    }

    public function getPartialValue(): array
    {
        return $this->value;
    }
}
