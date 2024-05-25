<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Form;

class Checkbox implements FormControlInterface
{
    use ValidatableTrait;

    protected bool $value = false;

    public function __construct(private readonly string $fieldName)
    {
    }

    public function setValue($value): static
    {
        if (!\is_bool($value)) {
            throw new \InvalidArgumentException(sprintf('Value must be a boolean, "%s" given.', \gettype($value)));
        }
        $this->value = $value;
        return $this;
    }

    public function setPostValue($value): static
    {
        if (!\is_string($value) && !\is_null($value)) {
            throw new \InvalidArgumentException(sprintf('Value must be a string or null, "%s" given.', \gettype($value)));
        }
        return $this->setValue($value !== null);
    }

    public function getHtml(?string $id = null): string
    {
        /** @noinspection HtmlUnknownAttribute */
        return sprintf(
            '<input type="checkbox" name="%s"%s%s>',
            htmlspecialchars($this->fieldName, ENT_QUOTES, 'UTF-8'),
            $this->value ? ' checked' : '',
            $id ? ' id="' . $id . '"' : ''
        );
    }

    public function getValue(): bool
    {
        return $this->value;
    }
}
