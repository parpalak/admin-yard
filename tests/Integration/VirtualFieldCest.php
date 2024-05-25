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
 * @group virtual
 */
class VirtualFieldCest
{
    public const ENTITY_ROW_SELECTOR = 'section.list-content tbody tr';

    public function invalidNewCest(IntegrationTester $I): void
    {
        $I->amOnPage('?entity=Post&action=new');

        $formData = [
            'title'      => 'Test title',
            'text'       => 'Test text',
            'tags'       => 'invalid tag(),valid tag',
            'is_active'  => 'on',
            'created_at' => '2020-01-01T00:00',
            'updated_at' => '2020-01-01T00:00',
        ];
        $I->submitForm('form', $formData);
        $I->see('Tags must contain only letters, numbers and spaces', $this->getValidationErrorSelector('Post', 'tags'));

        $I->amOnPage('?entity=Post&action=list');
        $I->seeResponseCodeIs(200);
        $I->see('Post', 'h1');

        // No new posts
        $I->assertEquals(range(1, 50), $I->grabMultiple(self::ENTITY_ROW_SELECTOR . ' .field-Post-id'));

        // No new tags
        $I->amOnPage('?entity=Tag&action=list');
        $I->seeResponseCodeIs(200);
        $I->see('Tag', 'h1');
        $I->dontSee('valid tag');
        $I->assertCount(12, $I->grabMultiple(self::ENTITY_ROW_SELECTOR));
    }

    public function newCest(IntegrationTester $I): void
    {
        $I->amOnPage('?entity=Post&action=new');

        $formData = [
            'title'      => 'Test title',
            'text'       => 'Test text',
            'tags'       => 'html  , valid tag , ,,,',
            'is_active'  => 'on',
            'created_at' => '2020-01-01T00:00',
            'updated_at' => '2020-01-01T00:00',
        ];
        $I->submitForm('form', $formData);
        $I->seeResponseCodeIs(302);
        $I->followRedirect();

        $formValues = $I->grabFormValues('form');
        $I->assertEquals(array_merge($formData, ['tags' => 'HTML, valid tag']), array_intersect_key($formValues, $formData));

        // New post
        $I->amOnPage('?entity=Post&action=list');
        $I->seeResponseCodeIs(200);
        $postIds = $I->grabMultiple(self::ENTITY_ROW_SELECTOR . ' .field-Post-id');
        $I->assertCount(51, $postIds);
        $I->see('HTML, valid tag');

        // New tag
        $I->amOnPage('?entity=Tag&action=list');
        $I->seeResponseCodeIs(200);
        $I->assertCount(13, $I->grabMultiple(self::ENTITY_ROW_SELECTOR));
        $I->see('valid tag');

        $I->amOnPage('?entity=Post&action=show&id=' . max($postIds));
        $I->seeResponseCodeIs(200);
        $I->see('Post', 'h1');
        $I->see('HTML, valid tag');

        $I->click('edit');
        $formData = [
            'title'      => 'Test title',
            'text'       => 'Test text',
            'tags'       => 'valid tag, js, html',
            'is_active'  => 'on',
            'created_at' => '2020-01-01T00:00',
            'updated_at' => '2020-01-01T00:00',
        ];
        $I->submitForm('form', $formData);
        $I->seeResponseCodeIs(302);
        $I->followRedirect();

        $formValues = $I->grabFormValues('form');
        $I->assertEquals(array_merge($formData, [
            'tags' => 'HTML, JS, valid tag', // order is NOT preserved! TODO: fix it
        ]), array_intersect_key($formValues, $formData));

        $tokenMatch = $I->grabAndMatch('a.edit-action-link-delete', '#csrf_token=([0-9a-z]+)#');
        $urlMatch   = $I->grabAndMatch('a.edit-action-link-delete', '#href="([^"]+)"#');
        $I->sendPost($urlMatch[1], [
            'csrf_token' => $tokenMatch[1],
        ]);

        $I->seeResponseCodeIs(Response::HTTP_OK);
        $I->see('Entity was deleted');
    }

    public function linkToEntityParamsCest(IntegrationTester $I): void
    {
        $I->amOnPage('?entity=Tag&action=show&id=1');
        $I->click('5');
        $I->seeResponseCodeIs(200);
        $I->see('Post', 'h1');
        $postIds = $I->grabMultiple(self::ENTITY_ROW_SELECTOR . ' .field-Post-id');
        sort($postIds);
        $I->assertEquals([7, 11, 18, 25, 29], $postIds);
    }


    private function getValidationErrorSelector(string $entity, string $field): string
    {
        return '.field-' . $entity . '-' . $field . ' .validation-error';
    }
}
