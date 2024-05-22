<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace Tests\Unit\Config;

use Codeception\Test\Unit;
use S2\AdminYard\Config\EntityConfig;
use S2\AdminYard\Config\FieldConfig;
use S2\AdminYard\Config\LinkedBy;
use S2\AdminYard\Config\LinkTo;

class FieldConfigTest extends Unit
{
    public function testInvalidDataType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown data type');
        $fieldConfig = new FieldConfig('test', dataType: 'invalid');
    }

    public function testGetLabel(): void
    {
        $fieldConfig = new FieldConfig('test_field');
        $this->assertEquals('Test Field', $fieldConfig->getLabel(), 'Field label can be transformed from the column name.');

        $fieldConfig = new FieldConfig('test_field', label: 'Некое поле');
        $this->assertEquals('Некое поле', $fieldConfig->getLabel());

        $fieldConfig = new FieldConfig('some_table_id');
        $this->assertEquals('Some Table', $fieldConfig->getLabel(), 'Field label can be transformed from the column name.');
    }

    public function testInvalidLinkToAction(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid linkToAction');
        $fieldConfig = new FieldConfig(
            name: 'test_field',
            linkToAction: 'new',
        );
    }

    public function testLinkConflict1(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only one of');
        $foreignEntity = new EntityConfig('test_entity');
        $fieldConfig   = new FieldConfig(
            name: 'test_field',
            linkToEntity: new LinkTo($foreignEntity, 'title'),
            linkedBy: new LinkedBy($foreignEntity, 'COUNT(*)', 'other_field')
        );
    }

    public function testLinkConflict2(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only one of');
        $foreignEntity = new EntityConfig('test_entity');
        $fieldConfig   = new FieldConfig(
            name: 'test_field',
            linkToAction: 'edit',
            linkedBy: new LinkedBy($foreignEntity, 'COUNT(*)', 'other_field')
        );
    }

    public function testLinkConflict3(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only one of');
        $foreignEntity = new EntityConfig('test_entity');
        $fieldConfig   = new FieldConfig(
            name: 'test_field',
            linkToAction: 'show',
            linkToEntity: new LinkTo($foreignEntity, 'title')
        );
    }

    public function testValidators(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Validator must implement');
        /** @noinspection PhpParamsInspection */
        $fieldConfig = new FieldConfig(
            name: 'test_field',
            validators: ['invalid' => 'invalid']
        );
    }
}
