<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license http://opensource.org/licenses/MIT MIT
 * @package AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Form;

class Select implements FormControlInterface, OptionsInterface
{
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
        if (!\is_string($value)) {
            throw new \InvalidArgumentException(sprintf('Value must be a string, "%s" given.', \gettype($value)));
        }
        $this->value = $value;
        return $this;
    }

    public function setPostValue(string $value): static
    {
        $this->value = $value;
        return $this;
    }

    public function getHtml(): string
    {
        $options = '';
        foreach ($this->options as $key => $value) {
            /** @noinspection HtmlUnknownAttribute */
            $options .= sprintf(
                '<option value="%s" %s>%s</option>',
                $key,
                $key === $this->value ? 'selected' : '',
                htmlspecialchars($value, ENT_QUOTES, 'UTF-8')
            );
        }
        return sprintf('<select name="%s">%s</select>', $this->fieldName, $options);
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
