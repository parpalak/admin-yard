<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license http://opensource.org/licenses/MIT MIT
 * @package AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Form;

use Symfony\Component\HttpFoundation\Request;

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

    public function fillFromRequest(Request $request): void
    {
        foreach ($this->controls as $columnName => $control) {
            $control->setPostValue($request->request->get($columnName));
        }
    }

    public function fillFromNormalizedData(array $data): void
    {
        foreach ($this->controls as $columnName => $control) {
            $control->setValue($data['field_' . $columnName]);
        }
    }
}
