<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license http://opensource.org/licenses/MIT MIT
 * @package AdminYard
 */

namespace Tests\Integration;

use Tests\Support\IntegrationTester;

class ListCest
{
    public function _before(IntegrationTester $I)
    {

    }

    // tests
    public function findDefaultEntityTest(IntegrationTester $I): void
    {
        $I->amOnPage('?');
        $I->seeResponseCodeIs(200);
        $I->see('Post', 'h1');
        $I->assertCount(50, $I->grabMultiple('section.list-content tbody tr'));
    }
}
