<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace Tests\Unit\Validator;

use Codeception\Test\Unit;
use S2\AdminYard\Validator\Length;

class LengthTest extends Unit
{
    public function testInvalidRange1(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Min length cannot be less than zero.');
        new Length(-5);
    }

    public function testInvalidRange2(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Max length cannot be less than zero.');
        new Length(max: -5);
    }

    public function testInvalidRange3(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Min length cannot be greater than max length.');
        new Length(min: 10, max: 5);
    }
}
