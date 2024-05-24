<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Event;

class BeforeSaveEvent
{
    /**
     * @param array $data    Array of normalized rows to be inserted or updated. May be modified in event listeners.
     * @param array $context An empty array. Can hold some data. Later it will be passed to AfterSaveEvent.
     */
    public function __construct(
        public array $data,
        public array &$context
    ) {
    }
}
