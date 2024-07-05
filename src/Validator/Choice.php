<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Validator;

use Symfony\Contracts\Translation\TranslatorInterface;

class Choice implements ValidatorInterface
{
    public string $message = 'The value you selected is not a valid choice.';

    public function __construct(private readonly array $choices, private readonly bool $strict = false)
    {
    }

    public function getValidationErrors(mixed $value, TranslatorInterface $translator): array
    {
        if (!\in_array($value, $this->choices, $this->strict)) {
            return [$translator->trans($this->message)];
        }
        return [];
    }
}
