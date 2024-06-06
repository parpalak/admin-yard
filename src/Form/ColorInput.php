<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Form;

class ColorInput extends Input
{
    public function getHtml(?string $id = null): string
    {
        return sprintf(
            '<input type="color" name="%s" value="%s" autocomplete="off"%s>',
            htmlspecialchars($this->fieldName, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($this->value, ENT_QUOTES, 'UTF-8'),
            $id !== null ? ' id="' . $id . '"' : ''
        );
    }
}
