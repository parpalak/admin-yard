<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Event;

class AfterLoadEvent
{
    public function __construct(public ?array $data)
    {
    }
}
