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
                name: 'emailThemeType',
                title: Craft.t('sprout-module-mailer', 'Email Theme'),
            },
        ];

        new Craft.VueAdminTable({
            columns: columns,
            container: this.adminTableId,
            deleteAction: 'sprout-module-mailer/email-themes/delete',
            deleteConfirmationMessage: Craft.t('sprout-module-mailer', 'Are you sure you want to delete the Submission Status “{name}”?'),
            deleteSuccessMessage: Craft.t('sprout-module-mailer', 'Email theme deleted'),
            deleteFailMessage: Craft.t('sprout-module-mailer', 'Unable to delete email theme.'),
            emptyMessage: Craft.t('sprout-module-mailer', 'No email themes exist yet.'),
            minItems: 1,
            padded: true,
            reorderAction: 'sprout-module-mailer/email-themes/reorder',
            reorderSuccessMessage: Craft.t('sprout-module-mailer', 'Submission statuses reordered.'),
            reorderFailMessage: Craft.t('sprout-module-mailer', 'Couldn’t reorder statuses.'),
            tableData: this.tableData,
        });
    }
}

window.EmailThemesSettings = EmailThemesSettings;



