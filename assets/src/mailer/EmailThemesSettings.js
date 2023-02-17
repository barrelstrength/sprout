class EmailThemesSettings {

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
                title: Craft.t('sprout-module-mailer', 'Name'),
                callback: function(value) {
                    return '<a class="cell-bold" href="' + value.url + '">' + value.name + '</a>';
                },
            },
            {
                name: 'emailTemplate',
                title: Craft.t('sprout-module-forms', 'Template'),
            },
        ];

        new Craft.VueAdminTable({
            columns: columns,
            container: this.adminTableId,
            deleteAction: 'sprout-module-forms/submission-statuses/delete',
            deleteConfirmationMessage: Craft.t('sprout-module-forms', 'Are you sure you want to delete the Submission Status “{name}”?'),
            deleteSuccessMessage: Craft.t('sprout-module-forms', 'Email theme deleted'),
            deleteFailMessage: Craft.t('sprout-module-forms', 'Unable to delete email theme.'),
            emptyMessage: Craft.t('sprout-module-forms', 'No email themes exist yet.'),
            minItems: 2,
            padded: true,
            tableData: this.tableData,
        });
    }
}

window.EmailThemesSettings = EmailThemesSettings;



