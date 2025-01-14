<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Helper;

use Random\RandomException;

class RandomHelper
{
    public static function getRandomHexString32(): string
    {
        try {
            return bin2hex(random_bytes(16));
        } catch (RandomException $e) {
            return md5(uniqid((string)mt_rand(), true) . microtime(true));
        }
    }
}
