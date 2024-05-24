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

readonly class AfterSaveEvent
{
    /**
     * @param PdoDataProvider $dataProvider
     * @param Key|null        $primaryKey Of inserted or updated entity. Null if primary key cannot be detected.
     * @param array           $context    Data passed from BeforeSaveEvent.
     */
    public function __construct(
        public PdoDataProvider $dataProvider,
        public ?Key            $primaryKey,
        public array           $context
    ) {
    }
}
