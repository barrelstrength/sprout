class NotificationEventsRelationsTable {

    constructor(elementId, siteId) {
        this.elementId = elementId;
        this.siteId = siteId;

        this.initLinkElementSlideout();
        this.initNewElementSlideout();
    }

    initLinkElementSlideout() {
        console.log('initialize existing email element slideouts');

        let self = this;

        let editableElements = document.querySelectorAll('#notification-event-relations-field .edit-element-col');

        editableElements.forEach(function(editableElement) {
            editableElement.addEventListener('click', function(event) {
                event.preventDefault();

                let elementId = event.target.closest('tr').getAttribute('data-element-id');
                let slideout = Craft.createElementEditor('BarrelStrength\\Sprout\\mailer\\components\\elements\\email\\EmailElement', {
                    elementId: elementId,
                    siteId: this.siteId,
                    elementType: 'BarrelStrength\\Sprout\\mailer\\components\\elements\\email\\EmailElement',
                });

                slideout.on('submit', () => {
                    self.replaceTable();
                });
            });
        });
    }

    initNewElementSlideout() {
        console.log('initialize email element slideouts');

        let self = this;

        let newSelectField = document.getElementById('new-transactional-email');

        newSelectField.addEventListener('change', function(event) {
            console.log('on change email type event', event);

            Craft.sendActionRequest('POST', 'sprout-module-mailer/email/create-email', {
                    data: {
                        emailTypeUid: event.target.value,
                        emailVariant: 'BarrelStrength\\Sprout\\transactional\\components\\emailvariants\\TransactionalEmailVariant',
                        emailVariantSettings: {
                            eventId: 'BarrelStrength\\Sprout\\forms\\components\\notificationevents\\SaveSubmissionNotificationEvent',
                            // emailTypeUid: event.target.value,
                        },
                    },
                })
                .then((response) => {
                    console.log('create slideout response', response);

                    if (response.data.success) {
                        let slideout = Craft.createElementEditor('BarrelStrength\\Sprout\\mailer\\components\\elements\\email\\EmailElement', {
                            elementId: response.data.elementId,
                            siteId: response.data.siteId,
                            draftId: response.data.draftId,
                            elementType: 'BarrelStrength\\Sprout\\mailer\\components\\elements\\email\\EmailElement',
                        });

                        slideout.on('submit', () => {
                            console.log('on slideout submit', response);

                            self.replaceTable();
                        });
                    }
                });
        });
    }

    replaceTable() {
        let self = this;

        Craft.sendActionRequest('POST', 'sprout-module-forms/forms/get-notification-events-relations-table', {
                data: {
                    elementId: self.elementId,
                },
            })
            .then((response) => {
                console.log('reports table html response', response);

                $('#notification-event-relations-field').html(response.data.html);

                self.initLinkElementSlideout();
                self.initNewElementSlideout();
            });
    }
}

window.NotificationEventsRelationsTable = NotificationEventsRelationsTable;



