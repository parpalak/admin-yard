<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Validator;

use Symfony\Contracts\Translation\TranslatorInterface;

class Length implements ValidatorInterface
{
    public string $maxMessage = 'This value is too long. It should have {{ limit }} character or less.|This value is too long. It should have {{ limit }} characters or less.';
    public string $minMessage = 'This value is too short. It should have {{ limit }} character or more.|This value is too short. It should have {{ limit }} characters or more.';

    public function __construct(
        protected readonly ?int $min = null,
        protected readonly ?int $max = null
    ) {
        if ($this->min !== null && $this->min < 0) {
            throw new \InvalidArgumentException('Min length cannot be less than zero.');
        }
        if ($this->max !== null && $this->max < 0) {
            throw new \InvalidArgumentException('Max length cannot be less than zero.');
        }
        if ($this->min !== null && $this->max !== null && $this->min > $this->max) {
            throw new \InvalidArgumentException('Min length cannot be greater than max length.');
        }
    }

    public function getValidationErrors(mixed $value, TranslatorInterface $translator): array
    {
        if (!\is_string($value)) {
            throw new \InvalidArgumentException('Validator Length can be used only with a string or an array.');
        }

        return [
            ... $this->max !== null && \mb_strlen($value) > $this->max ? [
                $translator->trans($this->maxMessage, ['{{ limit }}' => $this->max, '%count%' => $this->max])
            ] : [],
            ... $this->min !== null && \mb_strlen($value) < $this->min ? [
                $translator->trans($this->minMessage, ['{{ limit }}' => $this->min, '%count%' => $this->min])
            ] : [],
        ];
    }
}
