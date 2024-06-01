<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace Tests\Unit\Config;

use Codeception\Test\Unit;
use S2\AdminYard\Config\DbColumnFieldType;
use S2\AdminYard\Config\EntityConfig;
use S2\AdminYard\Config\FieldConfig;
use S2\AdminYard\Config\LinkedByFieldType;
use S2\AdminYard\Config\LinkTo;

class FieldConfigTest extends Unit
{
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
        new FieldConfig(
            name: 'test_field',
            actionOnClick: 'new',
        );
    }

    public function testLinkedByFieldTypeWithoutPk(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must have exactly one primary key column to be used in the LinkedByFieldType');
        $foreignEntity = (new EntityConfig('test_entity'));
        new FieldConfig(
            name: 'test_field',
            type: new LinkedByFieldType($foreignEntity, 'COUNT(*)', 'other_field'),
            linkToEntity: new LinkTo($foreignEntity, 'title')
        );
    }

    public function testLinkConflict1(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only one of');
        $foreignEntity = (new EntityConfig('test_entity'))->addField(new FieldConfig('id', type: new DbColumnFieldType(FieldConfig::DATA_TYPE_INT, true)));
        new FieldConfig(
            name: 'test_field',
            type: new LinkedByFieldType($foreignEntity, 'COUNT(*)', 'other_field'),
            linkToEntity: new LinkTo($foreignEntity, 'title')
        );
    }

    public function testLinkConflict2(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only one of');
        $foreignEntity = (new EntityConfig('test_entity'))->addField(new FieldConfig('id', type: new DbColumnFieldType(FieldConfig::DATA_TYPE_INT, true)));
        new FieldConfig(
            name: 'test_field',
            type: new LinkedByFieldType($foreignEntity, 'COUNT(*)', 'other_field'),
            actionOnClick: 'edit'
        );
    }

    public function testLinkConflict3(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Only one of');
        $foreignEntity = new EntityConfig('test_entity');
        new FieldConfig(
            name: 'test_field',
            actionOnClick: 'show',
            linkToEntity: new LinkTo($foreignEntity, 'title')
        );
    }

    public function testValidators(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Validator must implement');
        /** @noinspection PhpParamsInspection */
        new FieldConfig(
            name: 'test_field',
            validators: ['invalid' => 'invalid']
        );
    }

    public function testActions(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown actions encountered: [invalid]. Actions must be set of [list, show, new, edit, delete].');
        new FieldConfig(
            name: 'test_field',
            useOnActions: ['invalid', 'show']
        );
    }

    public function testOptions(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Options can be set only if control is set.');
        new FieldConfig(
            name: 'test_field',
            options: ['' => ''],
        );
    }
}
