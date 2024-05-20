<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Form;

use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Contracts\Translation\TranslatorInterface;

class Form
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    /**
     * @var array<string, FormControlInterface>
     */
    private array $controls = [];

    public function addControl(FormControlInterface $control, string $columnName): static
    {
        $this->controls[$columnName] = $control;

        return $this;
    }

    public function getControls(): array
    {
        return $this->controls;
    }

    public function getData(): array
    {
        $result = [];
        foreach ($this->controls as $columnName => $control) {
            if ($control->getValidationErrors() === []) {
                $result[$columnName] = $control->getValue();
            }
        }

        return $result;
    }

    public function submit(InputBag $inputBag): void
    {
        foreach ($this->controls as $columnName => $control) {
            if ($inputBag->has($columnName)) {
                // TODO: check interface
                try {
                    if ($control instanceof MultiSelect) {
                        $control->setPostValue($inputBag->all($columnName));
                    } else {
                        $control->setPostValue($inputBag->get($columnName));
                    }
                } catch (BadRequestException $e) {
                    // Ignore values that does not match the setter input type (string vs array)
                }
            }
            $control->validate($this->translator);
        }
    }

    public function fillFromNormalizedData(array $data): void
    {
        foreach ($this->controls as $columnName => $control) {
            $control->setValue($data['field_' . $columnName]);
        }
    }

    public function isValid(): bool
    {
        foreach ($this->controls as $control) {
            if ($control->getValidationErrors() !== []) {
                return false;
            }
        }

        return true;
    }
}
