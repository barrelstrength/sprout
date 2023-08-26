class FormTypesSettings {

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
                name: 'formType',
                title: Craft.t('sprout-module-forms', 'Form Type'),
            },
        ];

        new Craft.VueAdminTable({
            columns: columns,
            container: this.adminTableId,
            deleteAction: 'sprout-module-forms/form-types/delete',
            deleteConfirmationMessage: Craft.t('sprout-module-forms', 'Are you sure you want to delete the Form Type “{name}”?'),
            deleteSuccessMessage: Craft.t('sprout-module-forms', 'Form type deleted'),
            deleteFailMessage: Craft.t('sprout-module-forms', 'Unable to delete form type. Remove type from all forms before deleting.'),
            emptyMessage: Craft.t('sprout-module-forms', 'No form types exist yet.'),
            minItems: 1,
            padded: true,
            reorderAction: 'sprout-module-forms/form-types/reorder',
            reorderSuccessMessage: Craft.t('sprout-module-forms', 'Form types reordered.'),
            reorderFailMessage: Craft.t('sprout-module-forms', 'Couldn’t reorder form types.'),
            tableData: this.tableData,
        });
    }
}

window.FormTypesSettings = FormTypesSettings;



