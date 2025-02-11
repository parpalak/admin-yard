<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Form;

class Datetime implements FormControlInterface
{
    use ValidatableTrait;

    private \DateTimeImmutable|string|null $value = null;

    public function __construct(
        private readonly string $fieldName,
    ) {
    }

    public function setValue($value): static
    {
        if (!$value instanceof \DateTimeImmutable && $value !== null) {
            throw new \InvalidArgumentException(sprintf('Value must be a DateTimeImmutable or null, "%s" given.', \gettype($value)));
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
        $this->value = $value === '' ? null : $value;

        return $this;
    }

    public function getHtml(?string $id = null): string
    {
        if ($this->value === null) {
            $formattedValue = '';
        } elseif ($this->value instanceof \DateTimeImmutable) {
            $formattedValue = $this->value?->format('Y-m-d\TH:i');
        } else {
            $formattedValue = $this->value;
        }
        return sprintf(
            '<input type="datetime-local" name="%s" value="%s" autocomplete="off"%s>',
            htmlspecialchars($this->fieldName, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($formattedValue, ENT_QUOTES, 'UTF-8'),
            $id !== null ? ' id="' . $id . '"' : ''
        );
    }

    public function getInternalValue(): \DateTimeImmutable|string|null
    {
        return $this->value;
    }

    public function getValue(): ?\DateTimeImmutable
    {
        if (\is_string($this->value)) {
            $this->value = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $this->value);
        }
        return $this->value;
    }

    protected function getInternalValidators(): array
    {
        return [
            new \S2\AdminYard\Validator\DateTime('Y-m-d\TH:i'),
        ];
    }
}
