<?php
/**
 * @copyright 2025 Roman Parpalak
 * @license   https://opensource.org/license/mit MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace Tests\Integration;

use Tests\Support\IntegrationTester;

/**
 * @group json
 */
class JsonCest
{
    public function jsonOnTheList(IntegrationTester $I): void
    {
        $I->amOnPage('?entity=User&action=list');
        $I->seeResponseCodeIs(200);
        $I->see('Work place', 'table.rows-table th');
        $I->see('Senior Software Engineer', 'table.rows-table td');
    }

    public function jsonOnTheShow(IntegrationTester $I): void
    {
        $I->amOnPage('?entity=User&action=show&id=2');
        $I->seeResponseCodeIs(200);
        $I->see('Work place', 'table.rows-table th');
        $I->see('Senior Software Engineer', 'table.rows-table td');

        $I->amOnPage('?entity=User&action=show&id=1');
        $I->seeResponseCodeIs(200);
        $I->see('null', 'table.rows-table td');
    }
}
