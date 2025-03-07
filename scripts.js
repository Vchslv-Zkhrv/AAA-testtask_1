async function rpc(method, params) {
     return await fetch("rpc.php", {
        method: 'POST',
        body: JSON.stringify({
            method: method,
            params: params,
            id: Math.floor(Math.random() * 1_000_000)
        })
    }).then(
        (response) => response.json()
    );
}


function getCreateFormValues() {
    const orders = Array.from(
        document.querySelectorAll('.rule-inputs').values().filter(
            (i) => {
                const order = i.getAttribute('data-order');
                return document.getElementById(`rule-inputs-type-select-${order}`).selectedIndex > 0;
            }
        ).map(
            (i) => i.getAttribute('data-order')
        )
    );

    return orders.map((order) => {
        const valueSelect = document.getElementById(`create-form-${order}-value`);

        let result = {
            order: order,
            type: document.getElementById(`rule-inputs-type-select-${order}`).value,
            operator: document.getElementById(`create-form-${order}-operator`).value,
            value: valueSelect.value,
        };

        const meta = Array.from(
            valueSelect.selectedOptions[0].attributes
        ).filter(
            (a) => a.name.startsWith('data-') && a.name !== 'data-order'
        ).forEach(
            (a) => {
                result[a.name.replace('data-', '')] = a.value;
            }
        );

        return result;
    });
}


function checkSubmitButtonAwailable() {
    if (document.getElementById('create-form-name').value != '' && getCreateFormValues().length > 0) {
        document.getElementById('create-form-submit-button').removeAttribute('disabled');
    } else {
        document.getElementById('create-form-submit-button').setAttribute('disabled', 1);
    }
}

function onCreateFormNameChange(event) {
    checkSubmitButtonAwailable();
}


async function onCreateFormNewFieldsetDelete(event) {
    event.preventDefault();
    const order = event.target.getAttribute('data-order');
    document.getElementById(`rule-inputs-${order}`).remove();
    document.getElementById('create-form-add-button').removeAttribute('disabled');

    checkSubmitButtonAwailable();
}


async function onCreateFormSubmitButtonClick(event) {
    event.preventDefault();
    const rules = getCreateFormValues();
    const response = await rpc(
        'createRule',
        {
            name: document.getElementById('create-form-name').value,
            agency: (new URLSearchParams(window.location.search)).get('agency'),
            rules: rules
        }
    );

    if (response.success === true) {
        window.location.reload();
    } else {
        document.getElementById('create-form-error').innerHTML = response.message;
    }
}


async function onCreateFormAddButtonClick(event) {
    event.preventDefault();
    event.target.setAttribute('disabled', 1);

    const oldInputs = document.querySelectorAll('.rule-inputs');
    const oldValues = Array.from(oldInputs.values().map((i) => {
        const order = i.getAttribute('data-order');
        return {
            order: order,
            type: document.getElementById(`rule-inputs-type-select-${order}`).selectedIndex,
            operator: document.getElementById(`create-form-${order}-operator`)?.selectedIndex,
            value: document.getElementById(`create-form-${order}-value`)?.selectedIndex,
        };
    }));

    const order = Math.floor(Math.random() * 1_000_000);

    document.getElementById("create-form").innerHTML += `
        <fieldset class="rule-inputs" id="rule-inputs-${order}" data-order="${order}">
            <button id="rule-inputs-delete-${order}" onclick="onCreateFormNewFieldsetDelete(event)" data-order="${order}">❌</button>
            <select
                name="type-${order}"
                id="rule-inputs-type-select-${order}"
                data-order="${order}"
                onchange="onCreateFormNewFieldsetTypeSelect(event)"
                class="rule-inputs-type-select"
            >
                <option value="" selected disabled></option>
                <option value="country">Страна</option>
                <option value="city">Город</option>
                <option value="stars">Звездность</option>
                <option value="percent">Комиссия или скидка</option>
                <option value="default_contract">Договор по умолчанию</option>
                <option value="company">Компания</option>
                <option value="whitelist">Белый список</option>
                <option value="blacklist">Черный список</option>
                <option value="recommended">Список рекомендованных</option>
            </select>
            <div class="rule-inputs-variants" id="rule-inputs-variants-${order}" data-order="${order}">
            </div>
        </fieldset>
    `;

    oldValues.forEach(({order, type, operator, value}) => {
        const typeSelect = document.getElementById(`rule-inputs-type-select-${order}`);
        if (typeSelect) {
            typeSelect.selectedIndex = type;
        }

        const operatorSelect = document.getElementById(`create-form-${order}-operator`);
        if (operatorSelect) {
            operatorSelect.selectedIndex = operator;
        }

        const valueSelect = document.getElementById(`create-form-${order}-value`);
        if (valueSelect) {
            valueSelect.selectedIndex = value;
        }
    });

    checkSubmitButtonAwailable();
}


function buildInputs(operator, value, order) {
    return `
        <div class="inputs-variant" data-order="${order}" id="inputs-variant-${order}">
            <select name="operator-${order}" data-order="${order}" id="create-form-${order}-operator">
                ` + operator.map((o) => `<option value="${o.id}">${o.text}</option>`).join('') + `
            </select>
            <select name="value-${order}" data-order="${order}" id="create-form-${order}-value">
                ` + value.map((v) => `<option value="${v.id}" `
                    + Object.entries(v.meta).map((m) => `data-${m[0]}="${m[1]}"`).join(' ')
                + ` >${v.text}</option>`).join('') + `
            </select>
        </div>
    `;
}


async function onCreateFormNewFieldsetTypeSelect(event) {
    event.preventDefault();
    const order = event.target.getAttribute('data-order');
    document.getElementById('create-form-add-button').removeAttribute('disabled');
    const inputsValues = await rpc(
        'getVariants',
        {
            order: 0,
            rule: event.target.value
        },
    );

    const newInputs = buildInputs(inputsValues['operator'], inputsValues['value'], order);

    document.getElementById(`rule-inputs-variants-${order}`).innerHTML = newInputs;

    checkSubmitButtonAwailable();
}

async function onFilterRuleDeleteButtonClick(event) {
    const ruleId = event.target.getAttribute('data-id');
    await rpc(
        'deleteRule',
        {
            id: ruleId
        }
    );
    window.location.reload();
}
