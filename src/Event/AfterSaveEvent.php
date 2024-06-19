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

class AfterSaveEvent
{
    public array $ajaxExtraResponse = [];

    /**
     * @param PdoDataProvider $dataProvider
     * @param ?Key            $primaryKey Of inserted or updated entity. Null if primary key cannot be detected.
     * @param array           $context    Data passed from BeforeSaveEvent.
     */
    public function __construct(
        public readonly PdoDataProvider $dataProvider,
        public readonly ?Key            $primaryKey,
        public readonly array           $context
    ) {
    }
}
