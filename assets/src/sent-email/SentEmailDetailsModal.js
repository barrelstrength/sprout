window.SproutSentEmailDetailsModal = function(emailId) {

    Craft.sendActionRequest('POST', 'sprout-module-sent-email/sent-email/get-sent-email-details-modal-html', {
            data: {
                emailId: emailId,
            },
        })
        .then((response) => {
            if (response.data.success) {

                const $body = $('<div/>', {class: 'so-body'});
                const $infoTable = $(response.data.html);
                $infoTable.appendTo($body);

                const $footer = $('<div/>', {class: 'so-footer'});

                // Copied from Craft's FieldLayoutDesigner.js
                const $cancelBtn = Craft.ui.createButton({
                    label: Craft.t('app', 'Close'),
                    spinner: true,
                });

                $('<div/>', {class: 'flex-grow'}).appendTo($footer);
                $cancelBtn.appendTo($footer);

                const $slideoutHtml = $body.add($footer);

                const slideout = new Craft.Slideout($slideoutHtml);

                Craft.initUiElements($slideoutHtml);

                $cancelBtn.on('click', () => {
                    slideout.close();
                });
            }
        });
};

