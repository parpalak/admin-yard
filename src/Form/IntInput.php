<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Form;

class IntInput extends Input
{
    public function __construct(string $fieldName)
    {
        parent::__construct($fieldName);
        $this->value = '0';
    }

    public function getHtml(?string $id = null): string
    {
        return sprintf(
            '<input type="number" name="%s" value="%s"%s>',
            htmlspecialchars($this->fieldName, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($this->value, ENT_QUOTES, 'UTF-8'),
            $id !== null ? ' id="' . $id . '"' : ''
        );
    }
}
