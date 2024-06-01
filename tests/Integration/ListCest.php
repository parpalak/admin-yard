<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

namespace Tests\Integration;

use Codeception\Example;
use Tests\Support\IntegrationTester;

/**
 * @group list
 */
class ListCest
{
    public const ENTITY_ROW_SELECTOR  = 'section.list-content tbody tr';
    public const FILTER_FORM_SELECTOR = '.filter-content form';

    public function findDefaultEntityTest(IntegrationTester $I): void
    {
        $I->amOnPage('?');
        $I->seeResponseCodeIs(200);
        $I->see('Post', 'h1');
        $I->assertCount(50, $I->grabMultiple(self::ENTITY_ROW_SELECTOR));
    }

    public function filterRadioDateInputTest(IntegrationTester $I): void
    {
        $I->amOnPage('?entity=Post&action=list');
        $I->seeResponseCodeIs(200);
        $I->see('Post', 'h1');

        $I->seeElement(self::FILTER_FORM_SELECTOR . ' input[type="search"][name="search"]');
        $I->seeElement(self::FILTER_FORM_SELECTOR . ' input[type="radio"][name="is_active"][value="1"]');
        $I->seeElement(self::FILTER_FORM_SELECTOR . ' input[type="radio"][name="is_active"][value="0"]');
        $I->seeElement(self::FILTER_FORM_SELECTOR . ' input[type="radio"][name="is_active"][value=""]');
        $I->seeElement(self::FILTER_FORM_SELECTOR . ' input[type="date"][name="modified_from"]');
        $I->seeElement(self::FILTER_FORM_SELECTOR . ' input[type="date"][name="modified_to"]');
        $I->seeElement(self::FILTER_FORM_SELECTOR . ' input[type="hidden"][name="entity"][value="Post"]');
        $I->seeElement(self::FILTER_FORM_SELECTOR . ' input[type="hidden"][name="action"][value="list"]');

        $I->seeElement(self::FILTER_FORM_SELECTOR . ' button[type="submit"]');

        $I->submitForm(self::FILTER_FORM_SELECTOR, [
            'search'        => 'post 10',
            'is_active'     => '',
            'modified_from' => '',
            'modified_to'   => '',
        ]);

        $I->seeResponseCodeIs(200);
        $I->see('Post', 'h1');
        $I->assertCount(1, $I->grabMultiple(self::ENTITY_ROW_SELECTOR));

        $I->submitForm(self::FILTER_FORM_SELECTOR, [
            'search'        => 'post 1',
            'is_active'     => '',
            'modified_from' => '',
            'modified_to'   => '',
        ]);

        $I->seeResponseCodeIs(200);
        $I->see('Post', 'h1');
        $I->assertCount(11, $I->grabMultiple(self::ENTITY_ROW_SELECTOR));

        $I->submitForm(self::FILTER_FORM_SELECTOR, [
            'search'        => 'post 1',
            'is_active'     => '1',
            'modified_from' => '',
            'modified_to'   => (new \DateTime('+1 year'))->format('Y-m-d'),
        ]);

        $I->seeResponseCodeIs(200);
        $I->see('Post', 'h1');
        $I->assertCount(5, $I->grabMultiple(self::ENTITY_ROW_SELECTOR));
        $I->see('Post 10');
        $I->see('Post 12');
        $I->see('Post 14');
        $I->see('Post 16');
        $I->see('Post 18');

        $I->submitForm(self::FILTER_FORM_SELECTOR, [
            'search'        => 'post 1',
            'is_active'     => '1',
            'modified_from' => (new \DateTime('+1 year'))->format('Y-m-d'),
            'modified_to'   => '',
        ]);

        $I->seeResponseCodeIs(200);
        $I->see('Post', 'h1');
        $I->assertCount(0, $I->grabMultiple(self::ENTITY_ROW_SELECTOR));
    }

    /**
     * @dataProvider filterCheckboxArrayDataProvider
     */
    public function filterSelectEntityTest(IntegrationTester $I): void
    {
        $I->amOnPage('?entity=Comment&action=list');
        $I->seeResponseCodeIs(200);
        $I->see('Comment', 'h1');
        $I->seeElement(self::FILTER_FORM_SELECTOR . ' input[type="hidden"][name="entity"][value="Comment"]');
        $I->seeElement(self::FILTER_FORM_SELECTOR . ' input[type="hidden"][name="action"][value="list"]');
        $I->seeElement(self::FILTER_FORM_SELECTOR . ' input[type="checkbox"][name="statuses[]"][value="new"]');
        $I->seeElement(self::FILTER_FORM_SELECTOR . ' input[type="checkbox"][name="statuses[]"][value="approved"]');
        $I->seeElement(self::FILTER_FORM_SELECTOR . ' input[type="checkbox"][name="statuses[]"][value="rejected"]');
        $I->seeElement(self::FILTER_FORM_SELECTOR . ' input[type="search"][name="search"]');
        $I->seeElement(self::FILTER_FORM_SELECTOR . ' select[name="post_id"]');
        $I->seeElement(self::FILTER_FORM_SELECTOR . ' input[type="date"][name="created_from"]');
        $I->seeElement(self::FILTER_FORM_SELECTOR . ' input[type="date"][name="created_to"]');

        $I->seeElement(self::FILTER_FORM_SELECTOR . ' button[type="submit"]');

        $I->submitForm(self::FILTER_FORM_SELECTOR, [
            'post_id' => '1',
            'search'  => 'post 1',
        ]);
        $I->seeResponseCodeIs(200);
        $I->assertCount(3, $I->grabMultiple(self::ENTITY_ROW_SELECTOR));

        $I->submitForm(self::FILTER_FORM_SELECTOR, [
            'post_id' => '1',
            'search'  => 'post 2',
        ]);
        $I->seeResponseCodeIs(200);
        $I->assertCount(0, $I->grabMultiple(self::ENTITY_ROW_SELECTOR));

        $I->submitForm(self::FILTER_FORM_SELECTOR, [
            'post_id' => '10',
            'search'  => 'post 10',
        ]);
        $I->seeResponseCodeIs(200);
        $I->assertCount(11, $I->grabMultiple(self::ENTITY_ROW_SELECTOR));

        // Check that filters were stored in session
        $I->amOnPage('?entity=Comment&action=list');
        $I->assertCount(11, $I->grabMultiple(self::ENTITY_ROW_SELECTOR));
        $filterFormData = $I->grabFormValues(self::FILTER_FORM_SELECTOR);
        $I->assertEquals('10', $filterFormData['post_id']);
        $I->assertEquals('post 10', $filterFormData['search']);

        $I->submitForm(self::FILTER_FORM_SELECTOR, [
            'post_id' => '1111111',
        ]);
        $I->seeResponseCodeIs(200);
        $I->assertCount(0, $I->grabMultiple(self::ENTITY_ROW_SELECTOR));
    }

    /**
     * @dataProvider filterCheckboxArrayDataProvider
     */
    public function filterCheckboxArrayTest(IntegrationTester $I, Example $example): void
    {
        $I->amOnPage('?entity=Comment&action=list');
        $I->seeResponseCodeIs(200);
        $I->see('Comment', 'h1');

        $I->submitForm(self::FILTER_FORM_SELECTOR, [
            'search'       => 'post 1',
            'statuses'     => $example['statuses'],
            'created_from' => (new \DateTime('-1 year'))->format('Y-m-d'),
            'created_to'   => (new \DateTime('+1 year'))->format('Y-m-d'),
        ]);
        $I->seeResponseCodeIs(200);
        $I->assertCount($example['expectedCount'], $I->grabMultiple(self::ENTITY_ROW_SELECTOR));
    }

    public function filterCheckboxArrayDataProvider(): array
    {
        return [
            ['statuses' => ['new', 'approved'], 'expectedCount' => 6],
            ['statuses' => ['new'], 'expectedCount' => 4],
            ['statuses' => ['approved'], 'expectedCount' => 2],
            ['statuses' => ['rejected'], 'expectedCount' => 8],
            ['statuses' => ['new', 'approved', 'rejected'], 'expectedCount' => 14],
            ['statuses' => ['approved', 'rejected'], 'expectedCount' => 10],
            ['statuses' => [], 'expectedCount' => 14],
        ];
    }

    public function invalidFilterList(IntegrationTester $I): void
    {
        $I->amOnPage('?entity=Post&action=list&search=[]&is_active=invalid_field&modified_from=invalid_date&modified_to=invalid_date');
        $I->seeResponseCodeIs(200);
        $I->see('Post', 'h1');
        $I->see('The value you selected is not a valid choice.', self::FILTER_FORM_SELECTOR . ' .filter-Post-is_active');
        $I->see('This value is not a valid date.', self::FILTER_FORM_SELECTOR . ' .filter-Post-modified_from');
        $I->see('This value is not a valid date.', self::FILTER_FORM_SELECTOR . ' .filter-Post-modified_to');
    }

    public function sortTest(IntegrationTester $I): void
    {
        $I->amOnPage('?entity=Post&action=list');
        $I->seeResponseCodeIs(200);
        $I->see('Post', 'h1');

        $I->assertEquals(range(1, 50), $I->grabMultiple(self::ENTITY_ROW_SELECTOR . ' .field-Post-id'));
        $I->click('section.list-content th.field-Post-id a');
        $I->assertEquals(range(1, 50), $I->grabMultiple(self::ENTITY_ROW_SELECTOR . ' .field-Post-id'));
        $I->click('section.list-content th.field-Post-id a');
        $I->assertEquals(range(50, 1), $I->grabMultiple(self::ENTITY_ROW_SELECTOR . ' .field-Post-id'));

        $I->submitForm(self::FILTER_FORM_SELECTOR, [
            'search'        => 'post 1',
            'is_active'     => '',
            'modified_from' => '',
            'modified_to'   => '',
        ]);

        $I->assertEquals([19, 18, 17, 16, 15, 14, 13, 12, 11, 10, 1], $I->grabMultiple(self::ENTITY_ROW_SELECTOR . ' .field-Post-id'));
        $I->click('section.list-content th.field-Post-id a');
        $I->assertEquals([1, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19], $I->grabMultiple(self::ENTITY_ROW_SELECTOR . ' .field-Post-id'));

        // Sort one-to-many
        $I->click('section.list-content th.field-Post-comments a');
        $I->assertEquals(['null', 'null', 'null', 'null', 'null', 'null', 'null', 'null', 'null', '3', '11'], $I->grabMultiple(self::ENTITY_ROW_SELECTOR . ' .field-Post-comments'));

        $I->click('section.list-content th.field-Post-comments a');
        $I->assertEquals(['11', '3', 'null', 'null', 'null', 'null', 'null', 'null', 'null', 'null', 'null'], $I->grabMultiple(self::ENTITY_ROW_SELECTOR . ' .field-Post-comments'));

        // Sort many-to-one
        $I->amOnPage('?entity=Comment&action=list');
        $I->seeResponseCodeIs(200);
        $I->click('section.list-content th.field-Comment-post_id a');
        $I->assertEquals([
            '#1 Post 1',
            '#1 Post 1',
            '#1 Post 1',
            '#10 Post 10',
            '#10 Post 10',
            '#10 Post 10',
            '#10 Post 10',
            '#10 Post 10',
            '#10 Post 10',
            '#10 Post 10',
            '#10 Post 10',
            '#10 Post 10',
            '#10 Post 10',
            '#10 Post 10',
            '#2 Post 2',
            '#2 Post 2',
            '#2 Post 2',
            '#3 Post 3',
            '#3 Post 3',
            '#3 Post 3',
            '#4 Post 4',
            '#4 Post 4',
            '#4 Post 4',
            '#5 Post 5',
            '#5 Post 5',
            '#5 Post 5',
            '#6 Post 6',
            '#6 Post 6',
            '#6 Post 6',
            '#7 Post 7',
            '#7 Post 7',
            '#7 Post 7',
            '#8 Post 8',
            '#8 Post 8',
            '#8 Post 8',
            '#9 Post 9',
            '#9 Post 9',
            '#9 Post 9',

        ], $I->grabMultiple(self::ENTITY_ROW_SELECTOR . ' .field-Comment-post_id'));
    }
}
