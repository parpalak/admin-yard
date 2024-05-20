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
        if (!\is_string($value)) {
            throw new \InvalidArgumentException(sprintf('Value must be a string, "%s" given.', \gettype($value)));
        }
        return $this->setValue($value === '' ? null : $value);
    }

    public function getHtml(): string
    {
        return sprintf('<input type="date" name="%s" value="%s">', $this->fieldName, $this->value ?? '');
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
