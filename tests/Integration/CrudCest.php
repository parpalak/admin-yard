<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license http://opensource.org/licenses/MIT MIT
 * @package AdminYard
 */

declare(strict_types=1);

namespace Tests\Integration;

use Symfony\Component\HttpFoundation\Response;
use Tests\Support\IntegrationTester;

/**
 * @group crud
 */
class CrudCest
{
    public function crudTest(IntegrationTester $I): void
    {
        $I->amOnPage('?entity=Post&action=new');
        $I->seeElement('form input[type="text"][name="title"]');
        $I->seeElement('form textarea[name="text"]');
        $I->seeElement('form input[type="checkbox"][name="is_active"]');
        $I->seeElement('form input[type="datetime-local"][name="created_at"]');
        $I->seeElement('form input[type="datetime-local"][name="updated_at"]');

        $I->assertEquals('?entity=Post&action=new', $I->grabAttributeFrom('form', 'action'));

        $formData = [
            'title'      => 'Test title',
            'text'       => 'Test text',
            'is_active'  => 'on',
            'created_at' => '2020-01-01T00:00',
            'updated_at' => '2020-01-01T00:00',
        ];
        $I->submitForm('form', $formData);

        $I->seeResponseCodeIs(Response::HTTP_FOUND);
        $I->seeLocationMatches('#' . preg_quote('?entity=Post&action=edit&id=', '#') . '\d+#');

        $I->followRedirect();

        $I->assertCount(1, $I->grabMultiple('form'));
        $editFormAction = $I->grabAttributeFrom('form', 'action');
        $I->assertStringContainsString('?entity=Post&action=edit&id=', $editFormAction);
        $I->seeElement('form input[type="text"][name="title"]');
        $I->seeElement('form textarea[name="text"]');
        $I->seeElement('form input[type="checkbox"][name="is_active"]');
        $I->seeElement('form input[type="datetime-local"][name="created_at"]');
        $I->seeElement('form input[type="datetime-local"][name="updated_at"]');

        $formValues = $I->grabFormValues('form');
        $I->assertEquals($formData, array_intersect_key($formValues, $formData));

        $I->click('show');
        $I->see('Post', 'h1');
        $I->see('2020-01-01 00:00:00');
        $I->see('Test title');
        $I->see('Test text');

        $tokenMatch = $I->grabAndMatch('a.show-action-link-delete', '#csrf_token=([0-9a-z]+)#');
        $urlMatch   = $I->grabAndMatch('a.show-action-link-delete', '#href="([^"]+)"#');
        $I->sendPost($urlMatch[1], [
            'csrf_token' => $tokenMatch[1],
        ]);

        $I->seeResponseCodeIs(Response::HTTP_OK);
        $I->see('Entity was deleted');

        $I->sendPost($urlMatch[1], [
            'csrf_token' => $tokenMatch[1],
        ]);

        $I->seeResponseCodeIs(Response::HTTP_NOT_FOUND);
        $I->see('No entity was deleted');
    }
}
