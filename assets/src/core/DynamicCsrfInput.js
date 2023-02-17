/*
 * @link https://sprout.barrelstrengthdesign.com
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license https://craftcms.github.io/license
 */

let Sprout = window.Sprout || {};

Sprout.renderDynamicCsrfInputs = function() {

    let csrfInputs = document.querySelectorAll('input[name="SPROUT_CSRF_TOKEN"]');

    let params = {
        headers: {
            'Accept': 'application/json',
        }
    };

    fetch(Sprout.sessionInfoActionUrl, params)
        .then(response => response.json())
        .then(data => {
            csrfInputs.forEach(function(csrfInput) {
                csrfInput.name = data.csrfTokenName;
                csrfInput.value = data.csrfTokenValue;
            });
        });
};

if (document.readyState !== 'loading') {
    Sprout.renderDynamicCsrfInputs();
} else {
    document.addEventListener('DOMContentLoaded', Sprout.renderDynamicCsrfInputs);
}








