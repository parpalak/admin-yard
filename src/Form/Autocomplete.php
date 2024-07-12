<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Form;

use S2\AdminYard\Validator\Choice;

class Autocomplete extends Input
{
    private ?string $entityName = null;
    private ?string $hash = null;
    private ?\Closure $optionsProvider = null;
    private ?array $options = null;
    private bool $allowEmpty = false;

    public function getHtml(?string $id = null): string
    {
        if ($this->entityName === null) {
            throw new \LogicException('Entity name must be set before using autocomplete.');
        }

        if ($this->options === null) {
            $this->fillOptions();
        }

        $selectId  = $id ?? uniqid('autocomplete-', true);
        $controlId = $selectId . '-control';

        $escapedFieldName = htmlspecialchars($this->fieldName, ENT_QUOTES, 'UTF-8');

        $emptyLabel    = FormFactory::EMPTY_SELECT_LABEL;
        $options       = '';
        $currentOption = $emptyLabel;
        foreach ($this->options as $option) {
            $key     = (string)$option['value'];
            $options .= '<option value="' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . '" ' . ($key === $this->value ? 'selected' : '') . '>'
                . htmlspecialchars($option['text'], ENT_QUOTES, 'UTF-8')
                . '</option>';
            if ($key === $this->value) {
                $currentOption = $option['text'];
            }
        }

        $allowEmpty = (int)$this->allowEmpty;

        $fetchUrl = '?' . http_build_query([
                'entity' => $this->entityName,
                'hash'   => $this->hash,
                'action' => 'autocomplete',
            ]);


        return <<<HTML
            <div class="ay-select" id="$controlId">
                <button type="button" class="ay-select-button">$currentOption</button>
                <div class="ay-select-dropdown" style="display: none;">
                    <div class="search"><span class="highlight"></span></div>
                    <select name="$escapedFieldName" id="$selectId" size="5" class="dropdown-select">
                        $options
                    </select>
                </div>
            </div>
            <script>makeAutocompleteControl('$controlId', $allowEmpty, '$emptyLabel', '$fetchUrl');</script>
        HTML;
    }

    public function setValue($value): static
    {
        parent::setValue($value);
        $this->fillOptions();
        return $this;
    }


    public function setAutocompleteParams(string $entityName, string $hash, \Closure $optionsProvider, bool $allowEmpty): static
    {
        $this->entityName      = $entityName;
        $this->hash            = $hash;
        $this->optionsProvider = $optionsProvider;
        $this->allowEmpty      = $allowEmpty;

        return $this;
    }

    private function fillOptions(): void
    {
        $this->options = [
            ... $this->allowEmpty ? [['value' => '', 'text' => FormFactory::EMPTY_SELECT_LABEL]] : [],
            ...($this->optionsProvider)($this->value)
        ];
    }

    protected function getInternalValidators(): array
    {
        $options = [
            ... $this->allowEmpty ? [['value' => '', 'text' => FormFactory::EMPTY_SELECT_LABEL]] : [],
            ...($this->optionsProvider)($this->value, 0)
        ];

        $allowedValues = array_map(static fn($option) => (string)$option['value'], $options);
        return [
            new Choice($allowedValues, true),
        ];
    }
}
