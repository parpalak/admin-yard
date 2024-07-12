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
 * @group permissions
 */
class PermissionsCest
{
    public function defaultEntityTest(IntegrationTester $I): void
    {
        $I->amOnPage('?');
        $I->seeResponseCodeIs(200);
        $I->see('Post', 'section.list-header h1');
    }

    public function unknownEntityTest(IntegrationTester $I): void
    {
        $I->amOnPage('?entity=Unknown&action=list');
        $I->seeResponseCodeIs(404);
        $I->see('Unknown entity "Unknown" was requested.');
    }

    public function invalidPrimaryKeyTest(IntegrationTester $I): void
    {
        $I->amOnPage('?entity=Post&action=show&key=1');
        $I->seeResponseCodeIs(400);
        $I->see('Parameter "id" must be provided.');
    }

    public function renderedButtonsTest(IntegrationTester $I): void
    {
        $I->amOnPage('?entity=Post&action=list');
        $I->seeResponseCodeIs(200);
        $I->seeElement('.list-header-actions a.entity-action-new');

        $I->amOnPage('?entity=Config&action=list');
        $I->seeResponseCodeIs(200);
        $I->dontSeeElement('.list-header-actions a.entity-action-new');
    }

    public function forbiddenPostTest(IntegrationTester $I): void
    {
        $I->sendPost('?entity=Config&action=new', [
            'name'  => 'new config',
            'value' => 'new value',
        ]);
        $I->seeResponseCodeIs(403);

        $I->sendPost('?entity=Config&action=edit', [
            'name'  => 'some',
            'value' => 'some',
        ]);
        $I->seeResponseCodeIs(403);
    }

    /**
     * @dataProvider actionsDataProvider
     */
    public function actionsTest(IntegrationTester $I, Example $example): void
    {
        $I->amOnPage('?entity=Config&action=' . $example['action']);
        $I->seeResponseCodeIs($example['code']);
        if ($example['code'] === 403) {
            $I->see(sprintf('Action "%s" is not allowed for entity "Config".', $example['action']));
        }
    }

    protected function actionsDataProvider(): array
    {
        return [
            ['action' => '', 'code' => 400],
            ['action' => 'unknown', 'code' => 400],
            ['action' => 'list', 'code' => 200],
            ['action' => 'show', 'code' => 403],
            ['action' => 'new', 'code' => 403],
            ['action' => 'edit', 'code' => 403],
            ['action' => 'delete', 'code' => 403],
        ];
    }

    /**
     * @dataProvider notFoundDataProvider
     */
    public function notFoundTest(IntegrationTester $I, Example $example): void
    {
        $I->amOnPage('?entity=Post&action=' . $example['action'] . '&id=111111');
        $I->seeResponseCodeIs($example['code']);
        if ($example['code'] === 404) {
            $I->see('Post with id=\'111111\' not found.');
        }
    }

    protected function notFoundDataProvider(): array
    {
        return [
            ['action' => 'show', 'code' => 404],
            ['action' => 'edit', 'code' => 404],
        ];
    }
}
