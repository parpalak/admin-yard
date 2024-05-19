<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Form;

use Symfony\Component\HttpFoundation\InputBag;

class Form
{
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
        return array_map(static fn(FormControlInterface $control) => $control->getValue(), $this->controls);
    }

    public function fillFromInputBag(InputBag $inputBag): void
    {
        foreach ($this->controls as $columnName => $control) {
            if ($inputBag->has($columnName)) {
                // TODO: check interface
                if ($control instanceof MultiSelect) {
                    $control->setPostValue($inputBag->all($columnName));
                } else {
                    $control->setPostValue($inputBag->get($columnName));
                }
            }
        }
    }

    public function fillFromNormalizedData(array $data): void
    {
        foreach ($this->controls as $columnName => $control) {
            $control->setValue($data['field_' . $columnName]);
        }
    }
}
