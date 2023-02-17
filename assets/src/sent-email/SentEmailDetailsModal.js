/**
 * Initialize any buttons using the `.sprout-sent-email-details-btn` class
 */
class SentEmailDetailsModal {

    constructor() {
        let self = this;

        $('.sprout-sent-email-details-btn').on('click', function(e) {
            e.preventDefault();

            let $target = $(e.target);

            self.emailId = $target.data('email-id');

            Craft.sendActionRequest('POST', 'sprout-module-sent-email/sent-email/get-sent-email-details-modal-html', {
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
        let $container = $('<div/>', {
            class: 'modal',
        }).appendTo(
            Garnish.$bod,
        );

        // Header
        let $header = $('<div/>', {class: 'header'}).appendTo($container);
        $('<h2/>', {
            text: Craft.t('sprout-module-sent-email', 'Sent email details'),
        }).appendTo($header);

        // Body (provided by Mailer)
        let $body = $('<div/>', {
            class: 'body',
            style: 'overflow: scroll;height: 100%;padding-bottom:10em;',
        })
            .append(response.data.html)
            .appendTo($container);
        let $hiddenInput = $('<input/>', {
            type: 'hidden',
            name: 'emailId',
            value: self.emailId,
        }).appendTo($body);

        // Footer
        let $footer = $('<div/>', {
            class: 'footer sprout-modal-footer-absolute',
        }).appendTo($container);
        let $buttons = $('<div/>', {
            class: 'buttons right',
        }).appendTo($footer);
        let $closeBtn = $('<button/>', {
            type: 'button',
            class: 'btn',
            text: Craft.t('sprout-module-mailer', 'Close'),
        }).appendTo($buttons);

        // Additional JS (optionally provided by Mailer)
        Craft.appendBodyHtml(response.data.js);

        let success = false;

        let modal = new Garnish.Modal($container, {
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

        $closeBtn.on('click', () => {
            modal.hide();
            modal.destroy();
        });
    }
}

window.SentEmailDetailsModal = SentEmailDetailsModal;

new SentEmailDetailsModal();
