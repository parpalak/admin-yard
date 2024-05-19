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
 * @group associations
 */
class AssociationsCest
{
    public function newEntityWithManyToOneFieldTest(IntegrationTester $I): void
    {
        $I->amOnPage('?entity=Comment&action=new');
        $I->seeResponseCodeIs(200);
        $I->see('Comment', 'h1');

        $I->seeElement('form select[name="post_id"]');
        $I->seeElement('form input[type="text"][name="name"]');
        $I->seeElement('form input[type="text"][name="email"]');
        $I->seeElement('form textarea[name="comment_text"]');
        $I->seeElement('form input[type="datetime-local"][name="created_at"]');

        $formData = [
            'post_id'      => 1,
            'name'         => 'John Doe',
            'email'        => 'Test email',
            'comment_text' => 'Test comment text',
            'created_at'   => '2020-01-01T00:00',
        ];
        $I->submitForm('form', $formData);
        $I->seeResponseCodeIs(302);

        $I->followRedirect();
        $I->see('Comment', 'h1');
        $I->see('John Doe');
        $I->see('Test email');
        $I->see('Test comment text');
        $I->see('2020-01-01');
    }
}
