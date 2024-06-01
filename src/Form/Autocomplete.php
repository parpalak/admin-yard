<?php
/**
 * @copyright 2024 Roman Parpalak
 * @license   http://opensource.org/licenses/MIT MIT
 * @package   AdminYard
 */

declare(strict_types=1);

namespace S2\AdminYard\Form;

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

        $selectId   = $id ?? uniqid('autocomplete_', true);
        $dropdownId = $selectId . '_dropdown';
        $filterId   = $selectId . '_filter';
        $buttonId   = $selectId . '_button';

        $escapedFieldName = htmlspecialchars($this->fieldName, ENT_QUOTES, 'UTF-8');

        $emptyLabel = FormFactory::EMPTY_SELECT_LABEL;
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

        return <<<HTML
            <div class="ay-select">
                <button type="button" id="$buttonId" class="ay-select-button">$currentOption</button>
                <div class="ay-select-dropdown" id="$dropdownId" style="display: none;">
                    <div id="$filterId" class="search"><span class="highlight"></span></div>
                    <select name="$escapedFieldName" id="$selectId" size="5" class="dropdown-select">
                        $options
                    </select>
                </div>
            </div>
            <script>
            (function () {
                const button = document.getElementById("$buttonId");
                const select = document.getElementById("$selectId");
                const filterDiv = document.getElementById("$filterId");
                const filterContent = filterDiv.querySelector('span');
                const dropdown = document.getElementById("$dropdownId");

                let filter = '', currentValue = select.value;

                function globalClick(event) {
                    if (!dropdown.contains(event.target)) {
                        collapse();
                    }
                }

                function expand() {
                    dropdown.style.display = 'block';
                    button.classList.toggle('opened', true);
                    setTimeout(function () {
                        document.addEventListener('click', globalClick);
                    }, 0);
                }

                function collapse() {
                    dropdown.style.display = 'none';
                    button.classList.toggle('opened', false);
                    document.removeEventListener('click', globalClick);
                }

                function toggleSelectVisibility() {
                    if (dropdown.style.display === 'none') {
                        expand();
                    } else {
                        collapse();
                    }
                }

                function buttonKeyDown(event) {
                    if (dropdown.style.display === 'none') {
                        expand();
                    }

                    let newFilter = filter;
                    if (event.key === 'Escape') {
                        collapse();
                        return;
                    } else if (event.key === 'Backspace') {
                        newFilter = filter.slice(0, -1);
                    } else if (event.key.length === 1) {
                        newFilter += event.key;
                    }
                    if (event.key === ' ') {
                        event.preventDefault();
                    }

                    if (newFilter !== filter) {
                        filter = newFilter;
                        filterContent.innerText = filter;
                        updateOptions(filter);
                    }
                }

                function selectKeyDown(event) {
                    let newFilter = filter;
                    if (event.key === 'Escape' || event.key === 'Enter') {
                        event.preventDefault();
                        collapse();
                        return;
                    } else if (event.key === 'Backspace') {
                        newFilter = filter.slice(0, -1);
                    } else if (event.key.length === 1) {
                        newFilter += event.key;
                    }
                    if (event.key === ' ') {
                        event.preventDefault();
                    }

                    allowCollapseOnChange = false;

                    if (newFilter !== filter) {
                        filter = newFilter;
                        filterContent.innerText = filter;
                        updateOptions(filter);
                    }
                }

                button.addEventListener("keydown", buttonKeyDown);
                select.addEventListener("keydown", selectKeyDown);

                button.onclick = toggleSelectVisibility;
                filterDiv.onclick = function () { button.focus(); };

                let controller = null;

                function updateOptions(query) {
                    const url = "?entity=" + encodeURIComponent("{$this->entityName}") + "&hash={$this->hash}&action=autocomplete&query=" + encodeURIComponent(query) + "&additional=" + encodeURIComponent(currentValue);
                    if (controller !== null) {
                        controller.abort('changed query');
                    }

                    controller = new AbortController();
                    filterDiv.classList.toggle('animate', true);
                    fetch(url, {signal: controller.signal})
                        .then(response => response.json())
                        .then(data => {
                            select.innerHTML = '';
                            let appendItem = function(item) {
                                const option = document.createElement("option");
                                const value = String(item.value);
                                option.value = value;
                                option.text = item.text;
                                if (value === currentValue) {
                                    option.selected = true;
                                }
                                select.appendChild(option);
                            };
                            if ($allowEmpty) {
                                appendItem({text: '$emptyLabel', value: ''});
                            }
                            data.forEach(appendItem);
                            controller = null;
                            filterDiv.classList.toggle('animate', false);
                        })
                        .catch(error => {
                            if (error === 'changed query') {
                                return;
                            }
                            filterDiv.classList.toggle('animate', false);
                            console.warn(error);
                        })
                    ;
                }

                select.onchange = selectOption;
                let allowCollapseOnChange = true;
                select.addEventListener('onmousedown', () => { allowCollapseOnChange = true; });

                function selectOption(event) {
                    button.textContent = event.target.selectedOptions[0] ? event.target.selectedOptions[0].textContent : '$emptyLabel';
                    currentValue = event.target.value;

                    if (allowCollapseOnChange) {
                        collapse();
                    }
                }
            })();
            </script>
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
}
