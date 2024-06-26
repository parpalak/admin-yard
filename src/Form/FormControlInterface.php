<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Form;

/**
 * Responsible for generating HTML code for form control
 * and to convert form control data into normalized internal format.
 */
interface FormControlInterface extends ValidatableInterface
{
    /**
     * Converts POST data into normalized format and sets it into control.
     *
     * @param string|string[] $value
     */
    public function setPostValue($value): static;

    /**
     * Sets value into control.
     *
     * @param mixed $value
     */
    public function setValue($value): static;

    /**
     * @return mixed Normalized value that is contained in control.
     */
    public function getValue(): mixed;

    /**
     * @return string HTML markup of form control with current value.
     */
    public function getHtml(?string $id = null): string;
}
