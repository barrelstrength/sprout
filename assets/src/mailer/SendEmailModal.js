import './send-email-modal.scss';

/**
 * Initialize any buttons using the `.sprout-send-email-btn` class to trigger a
 * Mailer's Send Modal as defined by the Mailer's getSendEmailHtml and sendEmail methods.
 */
class SendEmailModal {

    constructor() {
        let self = this;

        $('.sprout-send-email-btn').on('click', function(e) {
            e.preventDefault();

            let $target = $(e.target);

            self.emailId = $target.data('email-id');

            self.getSendEmailHtmlAction = $target.data('get-send-email-html-action');
            self.sendEmailAction = $target.data('send-email-action');

            self.modalTitle = $target.data('modal-title');
            self.modalActionBtnLabel = $target.data('modal-action-btn-label');

            self.successMessage = Craft.t('sprout-module-mailer', 'Email sent.');
            self.failMessage = Craft.t('sprout-module-mailer', 'Unable to send email.');

            Craft.sendActionRequest('POST', self.getSendEmailHtmlAction, {
                    data: {
                        emailId: self.emailId,
                    },
                })
                .then((response) => {
                    if (response.data.success) {
                        self.initModal(response);
                    }
                });
        });
    }

    initModal(response) {

        let self = this;

        // Form Modal
        let $form = $('<form/>', {
            class: 'modal prompt',
        }).appendTo(
            Garnish.$bod,
        );

        // Header
        let $header = $('<div/>', {class: 'header'}).appendTo($form);
        $('<h2/>', {
            text: self.modalTitle,
        }).appendTo($header);

        // Body (provided by Mailer)
        let $body = $('<div/>', {class: 'body'})
            .append(response.data.html)
            .appendTo($form);
        // Hidden Email ID field
        $('<input/>', {
            type: 'hidden',
            name: 'emailId',
            value: self.emailId,
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
            text: self.modalActionBtnLabel,
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
                if (!success) {
                    reject();
                }
            },
        });

        $form.on('submit', (e) => {
            e.preventDefault();

            let postData = Garnish.getPostData(event.target);

            Craft.sendActionRequest('POST', self.sendEmailAction, {
                    data: Craft.expandPostArray(postData),
                })
                .then((response) => {
                    if (response.data.success) {
                        Craft.cp.displayNotice(self.successMessage);

                        modal.hide();
                        modal.destroy();
                    } else {
                        Craft.cp.displayError(self.failMessage);

                        if (response.data.errors) {
                            self.$errorList = $('<ul/>', {class: 'errors'}).appendTo($body);
                            for (let key of Object.keys(response.data.errors)) {
                                $('<li/>', {
                                    text: response.data.errors[key],
                                }).appendTo(self.$errorList);
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
}

window.SendEmailModal = SendEmailModal;

new SendEmailModal();
