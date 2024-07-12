<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace Tests\Integration;

use Symfony\Component\HttpFoundation\Response;
use Tests\Support\IntegrationTester;

/**
 * @group service
 */
class ServicePageCest
{
    public function errorTest(IntegrationTester $I): void
    {
        $I->amOnPage('?entity=About');
        $I->seeResponseCodeIs(Response::HTTP_OK);

        $I->see('Installation', 'h2');
        $I->see('Contributing', 'h2');
        $I->see('License', 'h2');
    }
}
