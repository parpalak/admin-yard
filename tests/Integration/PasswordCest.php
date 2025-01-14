<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace Tests\Integration;

use Tests\Support\IntegrationTester;

/**
 * @group pass
 */
class PasswordCest
{
    public function noPublicPasswords(IntegrationTester $I): void
    {
        $I->amOnPage('?entity=User&action=edit&id=1');
        $I->seeResponseCodeIs(200);
        $formValues = $I->grabFormValues('form');
        $I->assertEquals('***', $formValues['password']);
    }
}
