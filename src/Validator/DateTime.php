<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Validator;

use Symfony\Contracts\Translation\TranslatorInterface;

class DateTime implements ValidatorInterface
{
    public string $message = 'This value is not a valid date.';

    public function __construct(private readonly string $format = 'Y-m-d')
    {
    }

    public function getValidationErrors(mixed $value, TranslatorInterface $translator): array
    {
        if ($value !== null && !\is_string($value)) {
            throw new \InvalidArgumentException('Validator Date can be used only with a string.');
        }
        if ($value !== null && !$this->dateIsValid($value)) {
            return [$translator->trans($this->message)];
        }

        return [];
    }

    private function dateIsValid(string $date): bool
    {
        $d = \DateTime::createFromFormat($this->format, $date);

        return $d && $d->format($this->format) === $date;
    }
}
