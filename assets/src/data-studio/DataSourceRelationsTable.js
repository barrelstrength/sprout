class DataSourceRelationsTable {

    constructor(elementId, siteId) {
        this.elementId = elementId;
        this.siteId = siteId;

        this.initLinkElementSlideout();
        this.initNewElementSlideout();
    }

    initLinkElementSlideout() {
        console.log('initialize existing report element slideouts');

        let self = this;

        let editableElements = document.querySelectorAll('#data-source-relations-field .edit-element-col a');

        editableElements.forEach(function(editableElement) {
            editableElement.addEventListener('click', function(event) {
                event.preventDefault();

                let elementId = event.target.closest('tr').getAttribute('data-element-id');
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

    initNewElementSlideout() {
        console.log('initialize new report element slideouts');

        let self = this;

        let newSelectField = document.getElementById('new-data-set');

        if (newSelectField) {
            newSelectField.addEventListener('change', function(event) {
                Craft.sendActionRequest('POST', 'sprout-module-data-studio/data-set/create-data-set', {
                        data: {
                            type: event.target.value,
                        },
                    })
                    .then((response) => {
                        console.log('create slideout response', response);

                        if (response.data.success) {
                            let slideout = Craft.createElementEditor('BarrelStrength\\Sprout\\datastudio\\components\\elements\\DataSetElement', {
                                elementId: response.data.elementId,
                                siteId: response.data.siteId,
                                draftId: response.data.draftId,
                                elementType: 'BarrelStrength\\Sprout\\datastudio\\components\\elements\\DataSetElement',
                            });

                            slideout.on('submit', () => {
                                console.log('on slideout submit', response);

                                self.replaceTable();
                            });
                        }
                    });
            });
        }
    }

    replaceTable() {
        let self = this;

        Craft.sendActionRequest('POST', 'sprout-module-forms/forms/get-data-source-relations-table', {
                data: {
                    elementId: self.elementId,
                },
            })
            .then((response) => {
                console.log('reports table html response', response);

                $('#data-source-relations-field').html(response.data.html);

                self.initLinkElementSlideout();
                self.initNewElementSlideout();
            });
    }
}

window.DataSourceRelationsTable = DataSourceRelationsTable;



