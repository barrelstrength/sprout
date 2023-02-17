class SubmissionStatusSettings {

    constructor(id) {
        this.container = document.querySelector(id);
        this.tableData = JSON.parse(this.container.dataset.tableData);

        this.initVueAdminTable();
    }

    initVueAdminTable() {

        let columns = [
            {
                name: 'labelHtml',
                title: Craft.t('sprout-module-forms', 'Name'),
                callback: function(value) {
                    return '<a class="cell-bold sproutFormsStatusLabel" href="' + value.url + '"><span class="status ' + value.color + '"></span>' + value.name + '</a>';
                },
            },
            {
                name: '__slot:handle',
                title: Craft.t('sprout-module-forms', 'Handle'),
            },
            {
                name: 'isDefault',
                title: Craft.t('sprout-module-forms', 'Default Status'), callback: function(value) {
                    if (value) {
                        return '<span data-icon="check" title="' + Craft.t('sprout-module-forms', 'Yes') + '"></span>';
                    }
                    return '';
                },
            },
        ];

        new Craft.VueAdminTable({
            columns: columns,
            container: '#submission-statuses-admin-table',
            deleteAction: 'sprout-module-forms/submission-statuses/delete',
            deleteConfirmationMessage: Craft.t('sprout-module-forms', 'Are you sure you want to delete the Submission Status “{name}”?'),
            deleteSuccessMessage: Craft.t('sprout-module-forms', 'Submission status deleted'),
            deleteFailMessage: Craft.t('sprout-module-forms', 'Unable to delete status. Status must not be used on existing submissions.'),
            emptyMessage: Craft.t('sprout-module-forms', 'No submission statuses exist yet.'),
            minItems: 2,
            padded: true,
            reorderAction: 'sprout-module-forms/submission-statuses/reorder',
            reorderSuccessMessage: Craft.t('sprout-module-forms', 'Submission statuses reordered.'),
            reorderFailMessage: Craft.t('sprout-module-forms', 'Couldn’t reorder statuses.'),
            tableData: this.tableData,
        });
    }
}

window.SubmissionStatusSettings = SubmissionStatusSettings;



