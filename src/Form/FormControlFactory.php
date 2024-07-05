<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
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
            'float_input' => new FloatInput($fieldName),
            'search_input' => new SearchInput($fieldName),
            'email_input' => new EmailInput($fieldName),
            'color_input' => new ColorInput($fieldName),
            'password' => new Password($fieldName),
            'hidden_input' => new HiddenInput($fieldName),
            'textarea' => new Textarea($fieldName),
            'select' => new Select($fieldName),
            'multiselect' => new MultiSelect($fieldName),
            'radio' => new Radio($fieldName),
            'checkbox' => new Checkbox($fieldName),
            'checkbox_array' => new CheckboxArray($fieldName),
            'datetime' => new Datetime($fieldName),
            'date' => new Date($fieldName),
            'autocomplete' => new Autocomplete($fieldName),
            default => throw new InvalidArgumentException(sprintf('Unknown control type "%s".', $control)),
        };
    }
}
