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
     * @param PdoDataProvider $dataProvider
     * @param Key             $primaryKey Of entity to be deleted.
     */
    public function __construct(
        public PdoDataProvider $dataProvider,
        public Key             $primaryKey,
    ) {
    }
}
