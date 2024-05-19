<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace Tests\Unit\Config;

use Codeception\Test\Unit;
use S2\AdminYard\Config\FieldConfig;

class FieldConfigTest extends Unit
{
    public function testGetDataType(): void
    {
        $fieldConfig = new FieldConfig('test');

        $this->expectException(\InvalidArgumentException::class);
        $fieldConfig->setDataType('invalid');
    }

    public function testGetLabel(): void
    {
        $fieldConfig = new FieldConfig('test_field');
        $this->assertEquals('Test Field', $fieldConfig->getLabel(), 'Field label can be transformed from the column name.');

        $fieldConfig->setLabel('Некое поле');
        $this->assertEquals('Некое поле', $fieldConfig->getLabel());

        $fieldConfig = new FieldConfig('some_table_id');
        $this->assertEquals('Some Table', $fieldConfig->getLabel(), 'Field label can be transformed from the column name.');
    }
}
