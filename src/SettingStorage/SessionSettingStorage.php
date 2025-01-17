<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\SettingStorage;

use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Dummy setting storage implementation that stores data in Symfony session.
 */
readonly class SessionSettingStorage implements SettingStorageInterface
{
    public function __construct(private Session $session)
    {
    }

    public function has(string $key): bool
    {
        return $this->session->has($key);
    }

    public function get(string $key): array|string|int|float|bool|null
    {
        return $this->session->get($key);
    }

    public function set(string $key, array|string|int|float|bool|null $data): void
    {
        $this->session->set($key, $data);
    }

    public function remove(string $key): void
    {
        $this->session->remove($key);
    }
}
