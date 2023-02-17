import Alpine from 'alpinejs';

window.Alpine = Alpine;

export const SproutAdminTable = (settings) => ({

    tableRowsAction: settings.tableRowsAction,
    slideoutNewAction: settings.slideoutNewAction,
    slideoutEditAction: settings.slideoutEditAction,
    deleteAction: settings.deleteAction,

    rows: [],

    init() {
        this.refreshTable();
    },

    refreshTable() {

        Craft.sendActionRequest('POST', this.tableRowsAction,
        ).then((response) => {
            this.rows = response.data;
        });

    },

    getSlideoutEditor(action) {
        const slideout = new Craft.CpScreenSlideout(action);

        slideout.on('submit', ev => {
            this.refreshTable();
        });

        slideout.on('close', () => {
            this.refreshTable();
        });
    },

    copyButton(text) {
        let thing = $('<div>').append(
            Craft.ui.createCopyTextBtn({
                value: text,
                class: 'code small light',
            }),
        ).html();

        return thing;
    },

    newItem() {
        this.getSlideoutEditor(this.slideoutNewAction);
    },

    editItem(id) {
        this.getSlideoutEditor(this.slideoutEditAction + '?id=' + id);
    },

    deleteItem(id) {

        Craft.sendActionRequest('POST', this.deleteAction, {
            data: {
                id: id,
            },
        }).then((response) => {
            this.refreshTable();
        }).catch(() => {
            console.log('Nope.');
        });

    },
});

Alpine.data('SproutAdminTable', SproutAdminTable);

Alpine.start();
