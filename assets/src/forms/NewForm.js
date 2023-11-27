
let Sprout = window.Sprout || {};

Sprout.formNameForm = function() {

    let $form = $('#form-name-form');
    let name = document.getElementById('name-field');

    $form.on('submit', () =>  {
        if (name.value.length === 0) {
            Garnish.shake($form);
            name.focus();
            return false;
        } else {
            $form.submit();
            return true;
        }
    });
};

if (document.readyState !== 'loading') {
    Sprout.formNameForm();
} else {
    document.addEventListener('DOMContentLoaded', Sprout.formNameForm);
}
