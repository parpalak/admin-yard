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
 * @group delete
 */
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

    public function foreignKeyDeleteTest(IntegrationTester $I): void
    {
        $I->amOnPage('?entity=Tag&action=show&id=1');
        $tokenMatch = $I->grabAndMatch('a.show-action-link-delete', '#csrf_token=([0-9a-z]+)#');
        $urlMatch   = $I->grabAndMatch('a.show-action-link-delete', '#href="([^"]+)"#');
        $I->sendPost($urlMatch[1], [
            'csrf_token' => $tokenMatch[1],
        ]);

        $I->seeResponseCodeIs(Response::HTTP_INTERNAL_SERVER_ERROR);
        $I->see('Unable to delete entity');
        $I->seeFlashMessage('Cannot delete entity because it is used in other entities.');
    }
}
