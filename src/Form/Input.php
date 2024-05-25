<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Form;

class Input implements FormControlInterface
{
    use ValidatableTrait;

    protected string $value = '';

    public function __construct(protected readonly string $fieldName)
    {
    }

    public function setValue($value): static
    {
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
        return sprintf(
            '<input type="text" name="%s" value="%s"%s>',
            htmlspecialchars($this->fieldName, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($this->value, ENT_QUOTES, 'UTF-8'),
            $id !== null ? ' id="' . $id . '"' : ''
        );
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
