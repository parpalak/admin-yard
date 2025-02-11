<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Form;

class Date implements FormControlInterface
{
    use ValidatableTrait;

    private ?string $value = null;

    public function __construct(
        private readonly string $fieldName,
    ) {
    }

    public function setValue($value): static
    {
        if (!\is_string($value) && $value !== null) {
            throw new \InvalidArgumentException(sprintf('Value must be a string, "%s" given.', \gettype($value)));
        }
        $this->value = $value;

        return $this;
    }

    public function setPostValue($value): static
    {
        if (!\is_string($value) && $value !== null) {
            // Check for null for Laravel, it converts empty strings in _GET to null
            throw new \InvalidArgumentException(sprintf('Value must be a string, "%s" given.', \gettype($value)));
        }
        return $this->setValue($value === '' ? null : $value);
    }

    public function getHtml(?string $id = null): string
    {
        return sprintf(
            '<input type="date" name="%s" value="%s" autocomplete="off"%s>',
            htmlspecialchars($this->fieldName, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($this->value ?? '', ENT_QUOTES, 'UTF-8'),
            $id ? ' id="' . $id . '"' : ''
        );
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    protected function getInternalValidators(): array
    {
        return [
            new \S2\AdminYard\Validator\DateTime(),
        ];
    }
}
