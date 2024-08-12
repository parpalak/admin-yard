<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Form;

use S2\AdminYard\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

trait ValidatableTrait
{
    /**
     * @var ValidatorInterface[]
     */
    private array $validators = [];
    private array $validationErrors = [];

    public function setValidators(ValidatorInterface ...$validators): self
    {
        $this->validators = $validators;

        return $this;
    }

    abstract public function getValue(): mixed;

    /**
     * Internal value is required for correct validation.
     *
     * Consider example of the datetime control. It returns a value of type \DateTimeImmutable.
     * If a user fakes its value and sends a garbage, then the control should return this garbage in this method
     * to output validation errors, but getValue() method may throw an exception
     * as it should not be called before validation.
     */
    public function getInternalValue(): mixed
    {
        return $this->getValue();
    }

    public function validate(TranslatorInterface $translator): void
    {
        $this->validationErrors = [];
        foreach ($this->getInternalValidators() as $validator) {
            $this->validationErrors[] = $validator->getValidationErrors($this->getInternalValue(), $translator);
        }
        foreach ($this->validators as $validator) {
            $this->validationErrors[] = $validator->getValidationErrors($this->getInternalValue(), $translator);
        }
        $this->validationErrors = array_merge(...$this->validationErrors);
    }

    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    protected function getInternalValidators(): array
    {
        return [];
    }
}
