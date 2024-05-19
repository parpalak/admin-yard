<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
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
                $this->isCurrentOption($key) ? 'checked' : '',
                htmlspecialchars($value, ENT_QUOTES, 'UTF-8')
            );
        }
        return $options;
    }

    /**
     * NOTE: In HTML, radio buttons can have two different states for '' and null (or missing corresponding key) values.
     * '' corresponds to <input type="radio" value=""> selected, null to no inputs selected.
     * The second one is not fully functional, because after selecting one of the radio buttons,
     * they cannot be deselected, only switched between each other.
     *
     * Therefore, by checking ($key === '' && $this->value === null), we remove the second state from the possible ones,
     * equating the behavior for values '' and null. Moreover, this behavior is similar to that of a select element,
     * which does not have a "deselected" state and automatically selects the first option.
     */
    protected function isCurrentOption(int|string $key): bool
    {
        return parent::isCurrentOption($key) || ($key === '' && $this->value === null);
    }
}
