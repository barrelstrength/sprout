import './send-email-modal.scss';

window.SendEmailModal = function(emailId) {

    let $target = $('.sprout-send-email-btn[data-email-id="'+emailId+'"]');

    let getSendEmailHtmlAction = $target.data('get-send-email-html-action');
    let sendEmailAction = $target.data('send-email-action');

    let modalTitle = $target.data('modal-title');
    let modalActionBtnLabel = $target.data('modal-action-btn-label');

    let successMessage = Craft.t('sprout-module-mailer', 'Email sent.');
    let failMessage = Craft.t('sprout-module-mailer', 'Unable to send email.');

    Craft.sendActionRequest('POST', getSendEmailHtmlAction, {
            data: {
                emailId: emailId,
            },
        })
        .then((response) => {
            if (response.data.success) {

                // Form Modal
                let $form = $('<form/>', {
                    class: 'modal prompt',
                }).appendTo(
                    Garnish.$bod,
                );

                // Header
                let $header = $('<div/>', {class: 'header'}).appendTo($form);
                $('<h2/>', {
                    text: modalTitle,
                }).appendTo($header);

                // Body (provided by Mailer)
                let $body = $('<div/>', {class: 'body'})
                    .append(response.data.html)
                    .appendTo($form);
                // Hidden Email ID field
                $('<input/>', {
                    type: 'hidden',
                    name: 'emailId',
                    value: emailId,
                }).appendTo($body);

                // Footer
                let $footer = $('<div/>', {
                    class: 'footer sprout-modal-footer-absolute',
                }).appendTo($form);
                let $buttons = $('<div/>', {
                    class: 'buttons right',
                }).appendTo($footer);
                let $cancelBtn = $('<button/>', {
                    type: 'button',
                    class: 'btn',
                    text: Craft.t('sprout-module-mailer', 'Cancel'),
                }).appendTo($buttons);
                $('<button/>', {
                    type: 'submit',
                    class: 'btn submit sprout-send-btn-bg',
                    text: modalActionBtnLabel,
                }).appendTo($buttons);

                // Additional JS (optionally provided by Mailer)
                Craft.appendBodyHtml(response.data.js);

                let success = false;

                let modal = new Garnish.Modal($form, {
                    onShow: () => {
                        setTimeout(() => {
                            Craft.setFocusWithin($body);
                        }, 100);
                    },
                    onHide: () => {
                        // if (!success) {
                        //     reject();
                        // }
                    },
                });

                $form.on('submit', (e) => {
                    e.preventDefault();

                    let postData = Garnish.getPostData(event.target);

                    Craft.sendActionRequest('POST', sendEmailAction, {
                            data: Craft.expandPostArray(postData),
                        })
                        .then((response) => {
                            if (response.data.success) {
                                Craft.cp.displayNotice(successMessage);

                                modal.hide();
                                modal.destroy();
                            } else {
                                Craft.cp.displayError(failMessage);

                                if (response.data.errors) {
                                    let $errorList = $('<ul/>', {class: 'errors'}).appendTo($body);
                                    for (let key of Object.keys(response.data.errors)) {
                                        $('<li/>', {
                                            text: response.data.errors[key],
                                        }).appendTo($errorList);
                                    }
                                }
                            }
                        });
                });

                $cancelBtn.on('click', () => {
                    modal.hide();
                    modal.destroy();
                });
            }
        });
};