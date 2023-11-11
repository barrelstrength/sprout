class IntegrationsRelationsTable {

    constructor(formId, siteId) {
        this.formId = formId;
        this.siteId = siteId;

        this.newSelectField = document.getElementById('new-integration');

        this.initLinkSlideout();
        this.initNewSlideout();
    }

    initLinkSlideout() {
        console.log('initialize existing integrations slideouts');

        let self = this;

        let editableItems = document.querySelectorAll('#integrations-field .edit-element-col a');

        editableItems.forEach(function(editableItem) {
            editableItem.addEventListener('click', function(event) {
                event.preventDefault();

                let integrationId = event.target.closest('tr').getAttribute('data-element-id');
                let slideout = Craft.createElementEditor('BarrelStrength\\Sprout\\datastudio\\components\\elements\\DataSetElement', {
                    elementId: elementId,
                    siteId: this.siteId,
                    elementType: 'BarrelStrength\\Sprout\\datastudio\\components\\elements\\DataSetElement',
                });

                slideout.on('submit', () => {
                    self.replaceTable();
                });
            });
        });
    }

    initNewSlideout() {
        console.log('initialize new integrations slideouts');

        let self = this;

        if (this.newSelectField) {
            let integrationUid = Craft.uuid();

            this.newSelectField.addEventListener('change', function(event) {
                if (event.target.value) {

                    self.createSlideout(integrationUid, event.target.value);
                }
            });
        }
    }

    createSlideout(integrationUid, integrationTypeUid) {
        let self = this;

        let integrationsFormFieldMetadata = formBuilder.integrationsFormFieldMetadata
            ? JSON.parse(formBuilder.integrationsFormFieldMetadata)
            : [];

        Craft.sendActionRequest('POST', 'sprout-module-forms/forms/edit-integration-slideout', {
                data: {
                    formId: self.formId,
                    integrationUid: integrationUid,
                    integrationTypeUid: integrationTypeUid,
                    integrationsFormFieldMetadata: integrationsFormFieldMetadata,
                },
            })
            .then((response) => {
                console.log('create slideout response', response);

                if (response.data.success) {
                    // let slideout = Craft.createElementEditor('BarrelStrength\\Sprout\\datastudio\\components\\elements\\DataSetElement', {
                    //     elementId: response.data.elementId,
                    //     siteId: response.data.siteId,
                    //     draftId: response.data.draftId,
                    //     elementType: 'BarrelStrength\\Sprout\\datastudio\\components\\elements\\DataSetElement',
                    // });

                    const slideout = new Craft.Slideout(response.data.html, {
                        containerElement: 'form',
                        containerAttributes: {
                            action: 'sprout-module-forms/form-integrations/save-integration',
                            method: 'post',
                            novalidate: '',
                            class: 'fld-element-settings',
                        },
                    });
                    Craft.appendBodyHtml(response.data.slideoutJs);

                    slideout.on('submit', () => {
                        console.log('on slideout submit', response);

                        self.replaceTable();
                    });

                    slideout.on('close', () => {
                        console.log('on slideout close', response);
                        if (this.newSelectField) {
                            this.newSelectField.value = '';
                        }
                    });
                }
            });
    }

    replaceTable() {
        let self = this;

        Craft.sendActionRequest('POST', 'sprout-module-forms/forms/edit-integration-slideout', {
                data: {
                    elementId: self.elementId,
                },
            })
            .then((response) => {
                console.log('integrations table html response', response);

                $('#integrations-field').html(response.data.html);

                self.initLinkSlideout();
                self.initNewSlideout();
            });
    }
}

window.IntegrationsRelationsTable = IntegrationsRelationsTable;



