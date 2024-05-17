<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license http://opensource.org/licenses/MIT MIT
 * @package AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Form;

use InvalidArgumentException;

class FormControlFactory implements FormControlFactoryInterface
{
    public function create(string $control, string $fieldName): FormControlInterface
    {
        return match ($control) {
            'input' => new Input($fieldName),
            'int_input' => new IntInput($fieldName),
            'textarea' => new Textarea($fieldName),
            'select' => new Select($fieldName),
            'checkbox' => new Checkbox($fieldName),
            'radio' => new Radio($fieldName),
            'datetime' => new Datetime($fieldName),
            'date' => new Date($fieldName),
            default => throw new InvalidArgumentException(sprintf('Unknown control type "%s".', $control)),
        };
    }
}
