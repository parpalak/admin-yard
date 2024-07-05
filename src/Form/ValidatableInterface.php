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

interface ValidatableInterface
{
    public function setValidators(ValidatorInterface ...$validators): self;

    public function validate(TranslatorInterface $translator): void;

    /**
     * @return string[]
     */
    public function getValidationErrors(): array;
}
