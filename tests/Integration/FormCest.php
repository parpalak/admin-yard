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
 * @group validation
 */
class FormCest
{
    public function validationNewTest(IntegrationTester $I): void
    {
        $I->sendPost('?entity=Comment&action=new', []);
        $I->seeResponseCodeIs(200);
        $I->see('Unable to confirm security token.');
        $I->see('This value should not be blank.', $this->getValidationErrorSelector('Comment', 'name'));
        $I->see('This value should not be blank.', $this->getValidationErrorSelector('Comment', 'post_id'));

        $I->submitForm('form', []);
        $I->seeResponseCodeIs(200);
        $I->dontSee('Unable to confirm security token.');
        $I->see('This value should not be blank.', $this->getValidationErrorSelector('Comment', 'name'));
        $I->dontSeeElement($this->getValidationErrorSelector('Comment', 'post_id'));

        $I->sendPost('?entity=Tag&action=new', []);
        $I->seeResponseCodeIs(200);
        $I->see('Unable to confirm security token.');
        $I->see('This value should not be blank.', $this->getValidationErrorSelector('Tag', 'name'));
        $I->see('This value is too short. It should have 1 character or more.', $this->getValidationErrorSelector('Tag', 'name'));

        $I->submitForm('form', []);
        $I->dontSee('Unable to confirm security token.');
        $I->seeResponseCodeIs(200);
        $I->see('This value should not be blank.', $this->getValidationErrorSelector('Tag', 'name'));
        $I->see('This value is too short. It should have 1 character or more.', $this->getValidationErrorSelector('Tag', 'name'));
    }

    public function validationEditTest(IntegrationTester $I): void
    {
        $I->sendPost('?entity=Comment&action=edit&id=1', []);
        $I->seeResponseCodeIs(200);
        $I->see('Unable to confirm security token.');
        $I->see('This value should not be blank.', $this->getValidationErrorSelector('Comment', 'name'));
        $I->dontSeeElement($this->getValidationErrorSelector('Comment', 'post_id')); // post_id is only on new form

        $I->submitForm('form', [
            'status_code' => 'unknown',
            'created_at'  => 'invalid',
        ]);
        $I->seeResponseCodeIs(200);
        $I->dontSee('Unable to confirm security token.');
        $I->see('The value you selected is not a valid choice.', $this->getValidationErrorSelector('Comment', 'status_code'));
        $I->see('This value should not be blank.', $this->getValidationErrorSelector('Comment', 'name'));
        $I->see('This value is not a valid date.', $this->getValidationErrorSelector('Comment', 'created_at'));
    }

    public function disabledFieldsAreIgnoredTest(IntegrationTester $I): void
    {
        $I->amOnPage('?entity=Comment&action=edit&id=1');
        $formValues = $I->grabFormValues('form');

        $I->sendPost('?entity=Comment&action=edit&id=1', [
            'post_id'      => '9',
            'name'         => 'Integration tester',
            'status_code'  => 'approved',
            '__csrf_token' => $formValues['__csrf_token'],
        ]);
        $I->seeResponseCodeIs(302);
        $I->followRedirect();
        $I->click('show');
        $I->see('Integration tester');
        $I->see('Approved');
        $I->see('Post 1');
        $I->dontSee('Post 9');
    }

    private function getValidationErrorSelector(string $entity, string $field): string
    {
        return '.field-' . $entity . '-' . $field . ' .validation-error';
    }
}
