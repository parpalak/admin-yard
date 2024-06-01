<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace Tests\Unit\Validator;

use Codeception\Test\Unit;
use S2\AdminYard\Validator\Regex;

class RegexTest extends Unit
{
    public function testInvalidRegex(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid regex');
        new Regex('invalid');
    }
}
