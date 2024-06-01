<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace Tests\Integration;

use Codeception\Example;
use Tests\Support\IntegrationTester;

/**
 * @group autocomplete
 */
class AutocompleteCest
{
    public function autocompleteNoHashTest(IntegrationTester $I): void
    {
        $I->amOnPage('?entity=Post&action=autocomplete&query=foo');
        $I->seeResponseCodeIs(400);
        $I->see('Autocomplete action must be called via GET request with a hash parameter.');
    }

    public function autocompleteInvalidHashTest(IntegrationTester $I): void
    {
        $I->amOnPage('?entity=Post&action=autocomplete&query=foo&hash=invalid_hash');
        $I->seeResponseCodeIs(400);
        $I->see('Entity "Post" must have an autocompleteSqlExpression configured compatible with the hash provided.');
    }

    /**
     * @dataProvider autocompleteTestProvider
     */
    public function autocompleteSuccessTest(IntegrationTester $I, Example $example): void
    {
        $I->amOnPage('?entity=Comment&action=list');
        $I->seeResponseCodeIs(200);
        $I->see('Comment', 'h1');
        $matches = $I->grabAndMatch('.filter-Comment-post_id', '#hash=([0-9a-f]+)#');
        codecept_debug($matches);

        $I->amOnPage('?entity=Post&action=autocomplete&query=' . $example['query'] . '&hash=' . $matches[1] . '&additional=' . ($example['additional'] ?? ''));
        $I->seeResponseCodeIs(200);
        $data = $I->grabJson();
        $I->assertEquals($example['expected'], $data);
    }

    public function autocompleteTestProvider(): array
    {
        return [
            ['query' => 'foo', 'expected' => []],
            ['query' => '19', 'additional' => '1', 'expected' => [
                [
                    'value' => 1,
                    'text'  => '#1 Post 1',
                ],
                [
                    'value' => 19,
                    'text'  => '#19 Post 19',
                ],
            ]],
            ['query' => 'post 1', 'expected' => [
                [
                    'value' => 1,
                    'text'  => '#1 Post 1',
                ],
                [
                    'value' => 10,
                    'text'  => '#10 Post 10',
                ],
                [
                    'value' => 11,
                    'text'  => '#11 Post 11',
                ],
                [
                    'value' => 12,
                    'text'  => '#12 Post 12',
                ],
                [
                    'value' => 13,
                    'text'  => '#13 Post 13',
                ],
                [
                    'value' => 14,
                    'text'  => '#14 Post 14',
                ],
                [
                    'value' => 15,
                    'text'  => '#15 Post 15',
                ],
                [
                    'value' => 16,
                    'text'  => '#16 Post 16',
                ],
                [
                    'value' => 17,
                    'text'  => '#17 Post 17',
                ],
                [
                    'value' => 18,
                    'text'  => '#18 Post 18',
                ],
                [
                    'value' => 19,
                    'text'  => '#19 Post 19',
                ],
            ]],
        ];
    }
}
