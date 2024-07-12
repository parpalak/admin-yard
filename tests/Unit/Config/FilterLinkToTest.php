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
use S2\AdminYard\Config\FilterLinkTo;
use S2\AdminYard\Config\LinkTo;

class FilterLinkToTest extends Unit
{
    public function testInvalidLinkToEntity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Filter "LinkTo" can only be used for a field with linkToEntity set. Field "test_field" has no linkToEntity configured.');
        new FilterLinkTo(
            new FieldConfig('test_field'),
            'test'
        );
    }

    public function testOk(): void
    {
        $filter = new FilterLinkTo(
            new FieldConfig(
                'test_field',
                control: 'autocomplete',
                linkToEntity: new LinkTo(new EntityConfig('TestEntity'), 'title'),
            ),
            'test label'
        );

        $this->assertEquals('autocomplete', $filter->control);
        $this->assertEquals('test_field = :test_field', $filter->getCondition('test')->getSqlExpression());
        $this->assertEquals('test label', $filter->label);
    }
}
