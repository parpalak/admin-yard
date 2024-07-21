function makeAutocompleteControl(controlId, allowEmpty, emptyLabel, fetchUrl) {
    const control = document.getElementById(controlId);
    const button = control.querySelector('button.ay-select-button');
    const select = control.querySelector('select.dropdown-select');
    const filterDiv = control.querySelector('div.search');
    const filterContent = filterDiv.querySelector('span');
    const dropdown = control.querySelector('div.ay-select-dropdown');

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
        if (event.key === 'Escape') {
            collapse();
            return;
        }
        if (event.key === 'Tab') {
            return;
        }

        if (dropdown.style.display === 'none') {
            expand();
        }

        let newFilter = filter;
        if (event.key === 'Backspace') {
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
        const url = fetchUrl + "&query=" + encodeURIComponent(query) + "&additional=" + encodeURIComponent(currentValue);

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
                if (allowEmpty) {
                    appendItem({text: emptyLabel, value: ''});
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
        button.textContent = event.target.selectedOptions[0] ? event.target.selectedOptions[0].textContent : emptyLabel;
        currentValue = event.target.value;

        if (allowCollapseOnChange) {
            collapse();
        }
    }
}

function makeInlineForm(formId, unknownErrorMessage) {
    const checkTypes = ['checkbox', 'radio'];
    const fieldTypes = ['text', 'number', 'password', 'email', 'url', 'tel', 'search', 'date', 'time', 'datetime-local', 'month', 'week', 'color'];

    const form = document.getElementById(formId);

    form.addEventListener('submit', event => {
        event.preventDefault();
    });

    function sendData() {
        fetch(form.action, {
            method: 'POST',
            body: new FormData(form)
        }).then(response => {
            if (!response.ok) {
                if (response.status === 422) {
                    response.json().then(data => {
                        handleFailure(data.errors);
                    });
                } else {
                    throw new Error('Network response was not ok');
                }
            } else {
                response.json().then(data => {
                    handleSuccess();
                });
            }
        }).catch(error => {
            console.warn('There was a problem with form submission:', error);
            handleFailure([unknownErrorMessage]);
        });
    }

    let formBackup = [];

    function backupFormChecks() {
        for (let i = 0; i < form.elements.length; i++) {
            const input = form.elements[i];
            if (checkTypes.includes(input.type)) {
                if (input.type === 'checkbox' && input.parentNode.tagName === 'LABEL') {
                    input.parentNode.title = input.checked ? 'On' : 'Off';
                }
                formBackup[i] = input.checked;
            }
        }
    }

    function backupFormFields() {
        for (let i = 0; i < form.elements.length; i++) {
            const input = form.elements[i];
            if (input.nodeName.toLowerCase() === 'select' || fieldTypes.includes(input.type)) {
                formBackup[i] = input.value;
                input.previousValue = input.value;
            }
        }
    }

    function restoreFormChecks() {
        for (let i = 0; i < form.elements.length; i++) {
            if (checkTypes.includes(form.elements[i].type)) {
                form.elements[i].checked = formBackup[i];
            }
        }
    }

    function restoreFormFields() {
        for (let i = 0; i < form.elements.length; i++) {
            const input = form.elements[i];
            if (input.nodeName.toLowerCase() === 'select' || fieldTypes.includes(input.type)) {
                input.value = formBackup[i];
            }
        }
    }

    function handleSuccess() {
        form.querySelector('.validation-errors').innerHTML = '';
        form.classList.toggle('has-errors', false);

        backupFormChecks();
        backupFormFields();

        for (let i = 0; i < form.elements.length; i++) {
            if (fieldTypes.includes(form.elements[i].type)) {
                const input = form.elements[i];
                const holder = document.getElementById(formId + '-' + input.name);
                if (holder) {
                    // set the content for the spacer element so that it determines the width of the cell
                    holder.textContent = input.value;
                }
                input.blur();
            }
        }

        form.classList.add('success');
        setTimeout(() => {
            form.classList.remove('success');
        }, 3000);
    }

    function handleFailure(errors) {
        form.querySelector('.validation-errors').innerHTML = errors.map(error => '<span class="validation-error">' + error + '</span>').join('');
        form.classList.toggle('has-errors', true);

        restoreFormChecks();
    }

    for (let i = 0; i < form.elements.length; i++) {
        const input = form.elements[i];
        if (input.nodeName.toLowerCase() === 'select' || checkTypes.includes(input.type) || input.type === 'color') {
            input.addEventListener('change', sendData);
        } else if (fieldTypes.includes(input.type)) {
            input.addEventListener('keyup', function (event) {
                if (event.key === 'Escape' || event.key === 'Enter' && input.value === input.previousValue) {
                    restoreFormFields();
                    input.blur();
                    form.querySelector('.validation-errors').innerHTML = '';
                    form.classList.toggle('has-errors', false);
                } else if (event.key === 'Enter') {
                    if (input.value !== input.previousValue) {
                        sendData();
                    }
                }
            });
            input.addEventListener('blur', function () {
                if (input.value !== input.previousValue) {
                    sendData();
                }
            });
        }
    }

    backupFormChecks();
    backupFormFields();
}
