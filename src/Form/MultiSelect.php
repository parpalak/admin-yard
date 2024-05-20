<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Form;

class MultiSelect implements FormControlInterface, OptionsInterface
{
    use ValidatableTrait;

    protected array $values = [];
    protected array $options = [];

    public function __construct(protected readonly string $fieldName)
    {
    }

    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    public function setValue($value): static
    {
        if (!\is_array($value)) {
            throw new \InvalidArgumentException(sprintf('Value must be an array, "%s" given.', \gettype($value)));
        }
        $this->values = $value;

        return $this;
    }

    public function setPostValue($value): static
    {
        return $this->setValue($value);
    }

    public function getHtml(): string
    {
        $options = '';
        if (isset($this->options[''])) {
            // Move the empty option to the top
            $options .= $this->getOptionHtml('', $this->options['']) . '<hr>';
            unset($this->options['']);
        }

        foreach ($this->options as $key => $value) {
            $options .= $this->getOptionHtml($key, $value);
        }
        return sprintf(
            '<select name="%s" multiple>%s</select>',
            $this->fieldName . '[]',
            $options
        );
    }

    public function getValue(): array
    {
        return $this->values;
    }

    private function getOptionHtml(int|string $key, mixed $value): string
    {
        /** @noinspection HtmlUnknownAttribute */
        return sprintf(
            '<option value="%s" %s>%s</option>',
            $key,
            $this->isCurrentOption($key) ? 'selected' : '',
            htmlspecialchars($value, ENT_QUOTES, 'UTF-8')
        );
    }

    protected function isCurrentOption(int|string $key): bool
    {
        return \in_array((string)$key, $this->values, true);
    }
}
