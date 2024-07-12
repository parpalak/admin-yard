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
 * @group access-control
 * @group ac
 */
class AccessControlCest
{
    /**
     * @dataProvider notFoundDataProvider
     */
    public function notFoundTest(IntegrationTester $I, Example $example): void
    {
        $I->amOnPage('?entity=' . $example['entity'] . '&action=' . $example['action'] . '&id=' . $example['id']);
        $I->seeResponseCodeIs($example['code']);
        if ($example['code'] === 404) {
            $I->see(sprintf($example['entity'] . " with id='%s' not found.", $example['id']));
        }
    }

    protected function notFoundDataProvider(): array
    {
        return [
            ['entity' => 'Comment', 'action' => 'edit', 'code' => 404, 'id' => 40],
            ['entity' => 'Post', 'action' => 'show', 'code' => 404, 'id' => 40],
            ['entity' => 'Post', 'action' => 'edit', 'code' => 404, 'id' => 40],
            ['entity' => 'Post', 'action' => 'show', 'code' => 404, 'id' => 41],
            ['entity' => 'Post', 'action' => 'edit', 'code' => 404, 'id' => 41],
            ['entity' => 'Post', 'action' => 'show', 'code' => 200, 'id' => 31],
            ['entity' => 'Post', 'action' => 'edit', 'code' => 404, 'id' => 31],
            ['entity' => 'Comment', 'action' => 'show', 'code' => 404, 'id' => 40],
        ];
    }

    public function newEntityWithoutAssociationControlTest(IntegrationTester $I): void
    {
        $I->amOnPage('?entity=Comment&action=new');
        $I->seeResponseCodeIs(200);
        $I->see('Comment', 'h1');

        $formData = [
            'post_id'      => 40, // No read access to post 40
            'name'         => 'John Doe',
            'email'        => 'Test email',
            'comment_text' => 'Test comment text',
            'created_at'   => '2020-01-01T00:00',
        ];
        $I->submitForm('form', $formData);
        $I->seeResponseCodeIs(200);
        $I->dontSee('Unable to confirm security token.');
        $I->see('The value you selected is not a valid choice.', $this->getValidationErrorSelector('Comment', 'post_id'));
    }

    private function getValidationErrorSelector(string $entity, string $field): string
    {
        return '.field-' . $entity . '-' . $field . ' .validation-error';
    }
}
