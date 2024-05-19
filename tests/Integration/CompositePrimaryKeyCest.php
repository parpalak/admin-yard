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
 * @group composite
 */
class CompositePrimaryKeyCest
{
    public function crudTest(IntegrationTester $I): void
    {
        $I->amOnPage('?entity=CompositeKey&action=new');
        $I->seeElement('form input[type="number"][name="column1"]');
        $I->seeElement('form input[type="text"][name="column2"]');
        $I->seeElement('form input[type="date"][name="column3"]');

        $I->assertEquals('?entity=CompositeKey&action=new', $I->grabAttributeFrom('form', 'action'));

        $formData = [
            'column1' => 1,
            'column2' => 'Test title',
            'column3' => '2020-01-01',
        ];
        $I->submitForm('form', $formData);

        $I->seeResponseCodeIs(Response::HTTP_FOUND);

        $I->followRedirect();

        $I->assertCount(1, $I->grabMultiple('form'));
        $editFormAction = $I->grabAttributeFrom('form', 'action');
        $I->assertStringContainsString('?entity=CompositeKey&action=edit&', $editFormAction);

        $I->seeElement('form input[type="number"][name="column1"]');
        $I->seeElement('form input[type="text"][name="column2"]');
        $I->seeElement('form input[type="date"][name="column3"]');

        $I->assertEquals($formData, $I->grabFormValues('form'));

        $I->submitForm('form', [
            'column1' => 234,
            'column2' => 'Test title after edit',
            'column3' => '2020-01-01',
        ]);

        $I->seeResponseCodeIs(Response::HTTP_FOUND);
        $I->seeLocationIs('?entity=CompositeKey&action=edit&column1=234&column2=Test+title+after+edit&column3=2020-01-01');
        $I->followRedirect();

        $I->click('show');
        $I->see('CompositeKey', 'h1');
        $I->see('2020-01-01');
        $I->see('Test title after edit');
        $I->see('234');

        $I->click('delete');

        $I->seeResponseCodeIs(Response::HTTP_FOUND);
    }

    public function uniqueKeyViolationTest(IntegrationTester $I): void
    {
        $I->amOnPage('?entity=CompositeKey&action=new');
        $I->submitForm('form', [
            'column1' => 1,
            'column2' => 'Test title',
            'column3' => '2020-01-01',
        ]);
        $I->seeResponseCodeIs(Response::HTTP_FOUND);

        $I->amOnPage('?entity=CompositeKey&action=new');
        $I->submitForm('form', [
            'column1' => 1,
            'column2' => 'Test title',
            'column3' => '2020-01-01',
        ]);
        // $I->seeResponseCodeIs(Response::HTTP_CONFLICT); // TODO implementation required
        $I->see('This entity already exists.');

        $I->amOnPage('?entity=CompositeKey&action=show&column1=1&column2=Test+title&column3=2020-01-01');
        $I->click('delete');
        $I->seeResponseCodeIs(Response::HTTP_FOUND);
    }
}
