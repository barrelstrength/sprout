class DataSourceRelationsTable {

    constructor(elementId) {
        this.elementId = elementId;

        this.initElementSlideout();
    }

    initElementSlideout() {
        console.log('initialize report element slideouts');

        let self = this;

        let newSelectField = document.getElementById('new-data-set');

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

                self.initElementSlideout();
            });
    }
}

window.DataSourceRelationsTable = DataSourceRelationsTable;



