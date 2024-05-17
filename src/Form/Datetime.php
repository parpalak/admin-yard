<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license http://opensource.org/licenses/MIT MIT
 * @package AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Form;

use DateTimeImmutable;

class Datetime implements FormControlInterface
{
    private ?DateTimeImmutable $value = null;

    public function __construct(
        private readonly string $fieldName,
    ) {
    }

    public function setValue($value): static
    {
        if (!$value instanceof DateTimeImmutable && $value !== null) {
            throw new \InvalidArgumentException(sprintf('Value must be a DateTimeImmutable or null, "%s" given.', \gettype($value)));
        }
        $this->value = $value;

        return $this;
    }

    /**
     * @throws \Exception
     */
    public function setPostValue(string $value): static
    {
        return $this->setValue($value === '' ? null : new DateTimeImmutable($value));
    }

    public function getHtml(): string
    {
        return sprintf('<input type="datetime-local" name="%s" value="%s">', $this->fieldName, $this->value?->format('Y-m-d\TH:i') ?? '');
    }

    public function getValue(): ?DateTimeImmutable
    {
        return $this->value;
    }
}
