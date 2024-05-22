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

class DeleteCest
{
    public function errorTest(IntegrationTester $I): void
    {
        $I->amOnPage('?entity=Post&action=delete&id=1');
        $I->seeResponseCodeIs(Response::HTTP_METHOD_NOT_ALLOWED);

        $I->sendPost('?entity=Post&action=delete', [
            'csrf_token' => 'fake',
        ]);
        $I->seeResponseCodeIs(Response::HTTP_BAD_REQUEST);
        $I->see('Parameter "id" must be provided.');

        $I->sendPost('?entity=Post&action=delete&id=1', [
            'csrf_token' => 'fake',
        ]);
        $I->seeResponseCodeIs(Response::HTTP_UNPROCESSABLE_ENTITY);
        $I->see('CSRF token mismatch');

        $I->seeFlashMessage('Unable to confirm security token.');
    }
}
