<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Event;

use S2\AdminYard\Database\Key;
use S2\AdminYard\Database\PdoDataProvider;

class BeforeDeleteEvent
{
    /**
     * Messages for errors which do not allow to delete the entity.
     * May be set in event listeners.
     * Displayed as a flash or popup message.
     *
     * @var string[]
     */
    public array $errorMessages = [];

    /**
     * @param PdoDataProvider $dataProvider
     * @param Key             $primaryKey Of entity to be deleted.
     */
    public function __construct(
        public readonly PdoDataProvider $dataProvider,
        public readonly Key             $primaryKey,
    ) {
    }
}
