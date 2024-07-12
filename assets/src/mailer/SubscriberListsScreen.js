/* global Craft, $ */

$(document).ready(function() {
    // vanilla js, listen to changes on all #sproutSubscriberListIds-field input fields
    document.querySelectorAll('#sproutSubscriberListIds-field input').forEach(function(input) {
        input.addEventListener('change', function(event) {
            let subscribeAction = event.target.checked
                ? 'sprout-module-mailer/subscriber-lists/add'
                : 'sprout-module-mailer/subscriber-lists/remove';

            let targetAudienceId = event.target.value;

            Craft.sendActionRequest('POST', subscribeAction, {
                data: {
                    audience: {
                        id: targetAudienceId,
                    },
                    useLoggedInUserInfo: true,
                },
            }).then((response) => {
                if (response.data.success === true) {
                    Craft.cp.displayNotice(Craft.t('sprout-module-mailer', 'Subscriptions updated.'));
                } else {
                    Craft.cp.displayError(Craft.t('sprout-module-mailer', 'Unable to update subscriptions.'));
                }
            });
        });
    });
});