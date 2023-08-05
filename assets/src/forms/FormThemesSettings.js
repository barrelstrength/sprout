class FormThemesSettings {

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
                name: 'formThemeType',
                title: Craft.t('sprout-module-forms', 'Form Theme'),
            },
        ];

        new Craft.VueAdminTable({
            columns: columns,
            container: this.adminTableId,
            deleteAction: 'sprout-module-forms/form-themes/delete',
            deleteConfirmationMessage: Craft.t('sprout-module-forms', 'Are you sure you want to delete the Form Theme “{name}”?'),
            deleteSuccessMessage: Craft.t('sprout-module-forms', 'Form theme deleted'),
            deleteFailMessage: Craft.t('sprout-module-forms', 'Unable to delete form theme. Remove theme from all forms before deleting.'),
            emptyMessage: Craft.t('sprout-module-forms', 'No form themes exist yet.'),
            minItems: 1,
            padded: true,
            reorderAction: 'sprout-module-forms/form-themes/reorder',
            reorderSuccessMessage: Craft.t('sprout-module-forms', 'Form themes reordered.'),
            reorderFailMessage: Craft.t('sprout-module-forms', 'Couldn’t reorder themes.'),
            tableData: this.tableData,
        });
    }
}

window.FormThemesSettings = FormThemesSettings;



