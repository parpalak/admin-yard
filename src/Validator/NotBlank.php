<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Validator;

use Symfony\Contracts\Translation\TranslatorInterface;

class NotBlank implements ValidatorInterface
{
    public string $message = 'This value should not be blank.';

    public function getValidationErrors(mixed $value, TranslatorInterface $translator): array
    {
        if ($value === null || $value === '' || $value === [] || $value === 0) {
            return [$translator->trans($this->message)];
        }
        return [];
    }
}
