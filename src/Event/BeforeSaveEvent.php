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

class BeforeSaveEvent
{
    /**
     * Messages for errors which do not allow to save the entity.
     * May be set in event listeners.
     * Displayed as errors for the whole form.
     *
     * @var string[]
     */
    public array $errorMessages = [];

    /**
     * @param array $data       Array of normalized rows to be inserted or updated. May be modified in event listeners.
     * @param array $context    An empty array. Can hold some data. Later it will be passed to AfterSaveEvent.
     * @param ?Key  $primaryKey Primary key of the entity, if it exists (in case of update).
     */
    public function __construct(
        public readonly PdoDataProvider $dataProvider,
        public array                    $data,
        public array                    &$context,
        public readonly ?Key            $primaryKey = null
    ) {
    }
}
