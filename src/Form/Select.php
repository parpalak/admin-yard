<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Form;

class Select implements FormControlInterface, OptionsInterface
{
    use ValidatableTrait;

    protected ?string $value = null;
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
        if ($value === null) {
            $value = '';
        }
        if (!\is_string($value)) {
            throw new \InvalidArgumentException(sprintf('Value must be a string, "%s" given.', \gettype($value)));
        }
        $this->value = $value;
        return $this;
    }

    public function setPostValue($value): static
    {
        return $this->setValue($value);
    }

    public function getHtml(?string $id = null): string
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
            '<select name="%s"%s>%s</select>',
            htmlspecialchars($this->fieldName, ENT_QUOTES, 'UTF-8'),
            $id !== null ? ' id="' . $id . '"' : '',
            $options
        );
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    private function getOptionHtml(int|string $key, mixed $value): string
    {
        /** @noinspection HtmlUnknownAttribute */
        return sprintf(
            '<option value="%s" %s>%s</option>',
            htmlspecialchars((string)$key, ENT_QUOTES, 'UTF-8'),
            $this->isCurrentOption($key) ? 'selected' : '',
            htmlspecialchars($value, ENT_QUOTES, 'UTF-8')
        );
    }

    protected function isCurrentOption(int|string $key): bool
    {
        return (string)$key === $this->value;
    }
}
