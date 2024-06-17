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
    protected array $options = [];

    public function setOptions(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    public function getHtml(?string $id = null): string
    {
        if ($id === null) {
            $id = uniqid('color-', true);
        }
        $listId = $id . '-list';

        $escapedFieldName = htmlspecialchars($this->fieldName, ENT_QUOTES, 'UTF-8');
        $escapedValue     = htmlspecialchars($this->value, ENT_QUOTES, 'UTF-8');
        $idAttr           = $id !== null ? ' id="' . $id . '"' : '';

        $options  = '';
        $listAttr = '';
        if ($this->options !== []) {
            $listAttr = ' list="' . $listId . '"';
            foreach ($this->options as $color) {
                $options .= '<option value="' . $color . '" label="' . $color . '"></option>';
            }
            $options = '<datalist id="' . $listId . '">' . $options . '</datalist>';
        }

        return <<<HTML
<input type="color" name="{$escapedFieldName}" value="{$escapedValue}" autocomplete="off"{$idAttr}{$listAttr}>{$options}
HTML;
    }
}
