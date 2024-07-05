<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Form;

class CheckboxArray extends MultiSelect
{
    public function getHtml(?string $id = null): string
    {
        $options = '';
        foreach ($this->options as $key => $value) {
            /** @noinspection HtmlUnknownAttribute */
            $options .= sprintf(
                '<label><input type="checkbox" name="%s" value="%s" %s>%s</label>',
                htmlspecialchars($this->fieldName . '[]', ENT_QUOTES, 'UTF-8'),
                htmlspecialchars((string)$key, ENT_QUOTES, 'UTF-8'),
                $this->isCurrentOption($key) ? 'checked' : '',
                htmlspecialchars($value, ENT_QUOTES, 'UTF-8')
            );
        }
        return $options;
    }
}
