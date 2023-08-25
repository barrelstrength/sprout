class EmailTypesSettings {

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
                name: 'emailType',
                title: Craft.t('sprout-module-mailer', 'Email Type'),
            },
        ];

        new Craft.VueAdminTable({
            columns: columns,
            container: this.adminTableId,
            deleteAction: 'sprout-module-mailer/email-types/delete',
            deleteConfirmationMessage: Craft.t('sprout-module-mailer', 'Are you sure you want to delete the Email Theme “{name}”?'),
            deleteSuccessMessage: Craft.t('sprout-module-mailer', 'Email theme deleted'),
            deleteFailMessage: Craft.t('sprout-module-mailer', 'Unable to delete email theme. Remove theme from all emails before deleting.'),
            emptyMessage: Craft.t('sprout-module-mailer', 'No email themes exist yet.'),
            minItems: 1,
            padded: true,
            reorderAction: 'sprout-module-mailer/email-types/reorder',
            reorderSuccessMessage: Craft.t('sprout-module-mailer', 'Email themes reordered.'),
            reorderFailMessage: Craft.t('sprout-module-mailer', 'Couldn’t reorder themes.'),
            tableData: this.tableData,
        });
    }
}

window.EmailTypesSettings = EmailTypesSettings;



