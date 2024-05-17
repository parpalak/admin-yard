<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license http://opensource.org/licenses/MIT MIT
 * @package AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Form;

class Radio extends Select
{
    public function getHtml(): string
    {
        $options = '';
        foreach ($this->options as $key => $value) {
            /** @noinspection HtmlUnknownAttribute */
            $options .= sprintf(
                '<label><input type="radio" name="%s" value="%s" %s>%s</label>',
                $this->fieldName,
                $key,
                $key === $this->value ? 'checked' : '',
                htmlspecialchars($value, ENT_QUOTES, 'UTF-8')
            );
        }
        return $options;
    }
}
