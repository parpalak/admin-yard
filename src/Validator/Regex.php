<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Validator;

use Symfony\Contracts\Translation\TranslatorInterface;

class Regex implements ValidatorInterface
{
    public string $message = 'This value is not a valid {{ pattern }}.';

    public function __construct(private readonly string $pattern)
    {
        if (!@\preg_match($this->pattern, '')) {
            throw new \InvalidArgumentException(sprintf('Invalid regex pattern: "%s".', $this->pattern));
        }
    }

    public function getValidationErrors(mixed $value, TranslatorInterface $translator): array
    {
        if (!\is_string($value)) {
            throw new \InvalidArgumentException('Validator Regex can be used only with a string or an array.');
        }

        return !\preg_match($this->pattern, $value) ? [
            $translator->trans($this->message, ['%pattern%' => $this->pattern])
        ] : [];
    }
}
