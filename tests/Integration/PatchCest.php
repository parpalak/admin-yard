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
 * @group patch
 */
class PatchCest
{
    public const ENTITY_ROW_SELECTOR = 'section.list-content tbody tr';

    public function validationTest(IntegrationTester $I): void
    {
        $I->amOnPage('?' . http_build_query([
                'entity' => 'Comment',
                'action' => 'list',
                'search' => 'This is the first comment for post 1.',
            ]));
        $I->seeResponseCodeIs(Response::HTTP_OK);
        $I->submitForm('.field-Comment-name form', [
            'name' => '',
        ]);
        $I->seeResponseCodeIs(Response::HTTP_UNPROCESSABLE_ENTITY);
        $I->assertEquals(['errors' => ['This value should not be blank.']], $I->grabJson());
    }

    public function successTest(IntegrationTester $I): void
    {
        // No Tom Adams
        $I->amOnPage('?' . http_build_query([
                'entity' => 'Comment',
                'action' => 'list',
                'search' => 'Tom Adams',
            ]));
        $I->seeResponseCodeIs(Response::HTTP_OK);
        $I->assertCount(0, $I->grabMultiple(self::ENTITY_ROW_SELECTOR));

        // Inline update
        $I->amOnPage('?' . http_build_query([
                'entity' => 'Comment',
                'action' => 'list',
                'search' => 'This is the first comment for post 1.',
            ]));
        $I->seeResponseCodeIs(Response::HTTP_OK);
        $I->submitForm('.field-Comment-name form', [
            'name' => 'Tom Adams',
        ]);
        $I->seeResponseCodeIs(Response::HTTP_OK);

        // See Tom Adams
        $I->amOnPage('?' . http_build_query([
                'entity' => 'Comment',
                'action' => 'list',
                'search' => 'Tom Adams',
            ]));
        $I->seeResponseCodeIs(Response::HTTP_OK);
        $I->assertCount(1, $I->grabMultiple(self::ENTITY_ROW_SELECTOR));
        $I->see('Tom Adams', '.field-Comment-name');
    }

    public function errorTest(IntegrationTester $I): void
    {
        $I->amOnPage('?entity=Post&action=patch&id=1');
        $I->seeResponseCodeIs(Response::HTTP_METHOD_NOT_ALLOWED);

        $I->sendPost('?entity=Post&action=patch', [
            'csrf_token' => 'fake',
        ]);
        $I->seeResponseCodeIs(Response::HTTP_BAD_REQUEST);
        $I->see('Field name must be provided.');

        $I->sendPost('?entity=Post&action=patch&field=unknown_field', [
            'csrf_token' => 'fake',
        ]);
        $I->seeResponseCodeIs(Response::HTTP_BAD_REQUEST);
        $I->see('Field "unknown_field" not found in entity "Post".');

        $I->sendPost('?entity=Post&action=patch&field=is_active', [
            'csrf_token' => 'fake',
        ]);
        $I->seeResponseCodeIs(Response::HTTP_BAD_REQUEST);
        $I->see('Parameter "id" must be provided.');

        $I->sendPost('?entity=Post&action=patch&field=title&id=1', [
            'csrf_token' => 'fake',
        ]);
        $I->seeResponseCodeIs(Response::HTTP_BAD_REQUEST);
        $I->see('Field "title" is not declared as inline editable.');

        $I->sendPost('?entity=Post&action=patch&field=is_active&id=1', [
            'csrf_token' => 'fake',
        ]);
        $I->seeResponseCodeIs(Response::HTTP_UNPROCESSABLE_ENTITY);
        $I->assertEquals(['errors' => ['Unable to confirm security token. A likely cause for this is that some time passed between when you first entered the page and when you submitted the form. If that is the case and you would like to continue, submit the form again.']], $I->grabJson());
    }
}
