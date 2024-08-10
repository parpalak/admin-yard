<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\SettingStorage;

interface SettingStorageInterface
{
    public function has(string $string): bool;

    public function get(string $key): array|string|int|float|bool|null;

    public function set(string $key, array|string|int|float|bool|null $data): void;

    public function remove(string $key): void;
}
