class IntegrationTypesSettings {

    constructor(id) {
        this.adminTableId = id;
        this.container = document.querySelector(this.adminTableId);
        this.tableData = JSON.parse(this.container.dataset.tableData);

        this.initVueAdminTable();
    }

    initVueAdminTable() {

        let columns = [
            {
                name: 'labelHtml',
                title: Craft.t('sprout-module-forms', 'Name'),
                callback: function(value) {
                    return '<a class="cell-bold" href="' + value.url + '">' + value.name + '</a>';
                },
            },
            {
                name: 'integrationTypeType',
                title: Craft.t('sprout-module-forms', 'Integration Type'),
            },
        ];

        new Craft.VueAdminTable({
            columns: columns,
            container: this.adminTableId,
            deleteAction: 'sprout-module-forms/form-integration-settings/delete',
            deleteConfirmationMessage: Craft.t('sprout-module-forms', 'Are you sure you want to delete the Form Integration Type “{name}”?'),
            deleteSuccessMessage: Craft.t('sprout-module-forms', 'Form integration deleted'),
            deleteFailMessage: Craft.t('sprout-module-forms', 'Unable to delete form integration type. Remove integration type from all forms before deleting.'),
            emptyMessage: Craft.t('sprout-module-forms', 'No integration types exist yet.'),
            minItems: 1,
            padded: true,
            reorderAction: 'sprout-module-forms/form-integration-settings/reorder',
            reorderSuccessMessage: Craft.t('sprout-module-forms', 'Form integration types reordered.'),
            reorderFailMessage: Craft.t('sprout-module-forms', 'Couldn’t reorder form integration types.'),
            tableData: this.tableData,
        });
    }
}

window.IntegrationTypesSettings = IntegrationTypesSettings;



