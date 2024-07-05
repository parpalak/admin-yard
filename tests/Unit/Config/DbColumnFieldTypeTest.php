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

class DbColumnFieldTypeTest extends Unit
{
    public function testInvalidDataType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown data type');
        new DbColumnFieldType('invalid');
    }
}
