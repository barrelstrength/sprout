/* global Craft */

/* global Garnish */

class SproutSourceGroupsAdmin {

    constructor(settings) {

        this.groupsSelector = settings.groupsSelector;
        this.groups = document.querySelector(this.groupsSelector);
        this.selectedGroup = this.groups.querySelector('a.sel:first-child');

        this.elementType = settings.elementType;

        // Use jQuery for Garnish MenuBtn
        this.groupSettingsSelector = settings.groupSettingsSelector;
        this.$groupSettingsBtn = $(this.groupSettingsSelector);

        this.newGroupButtonSelector = settings.newGroupButtonSelector;
        this.newGroupBtn = document.querySelector(this.newGroupButtonSelector);
        this.newGroupAction = settings.newGroupAction;
        this.newGroupOnSuccessUrlBase = settings.newGroupOnSuccessUrlBase;
        this.newGroupOnErrorMessage = settings.newGroupOnErrorMessage;

        this.renameGroupAction = settings.renameGroupAction;
        this.renameGroupOnSuccessMessage = settings.renameGroupOnSuccessMessage;
        this.renameGroupOnErrorMessage = settings.renameGroupOnErrorMessage;
        this.promptForGroupNameMessage = settings.promptForGroupNameMessage;

        this.deleteGroupAction = settings.deleteGroupAction;
        this.deleteGroupOnSuccessUrl = settings.deleteGroupOnSuccessUrl;
        this.deleteGroupConfirmMessage = settings.deleteGroupConfirmMessage;
        this.deleteGroupOnErrorMessage = settings.deleteGroupOnErrorMessage;

        this.initPage();
    }

    initPage() {
        let self = this;

        this.newGroupBtn.addEventListener('click', function() {
            self.addNewGroup();
        });

        // Should we display the Groups Setting Selector or not?
        self.toggleGroupSettingsSelector();
        self.groups.addEventListener('click', function() {
            self.toggleGroupSettingsSelector();
        });

        new Garnish.MenuBtn(self.$groupSettingsBtn, {
            onOptionSelect: function(elem) {

                const $elem = $(elem);

                if ($elem.hasClass('disabled')) {
                    return;
                }

                switch ($(elem).data('action')) {
                    case 'rename': {
                        self.renameSelectedGroup();
                        break;
                    }
                    case 'delete': {
                        self.deleteSelectedGroup();
                        break;
                    }
                }
            },
        });

        // Ensure that 'menubtn' classes get registered
        Craft.initUiElements();
    }

    addNewGroup() {
        let self = this;
        const name = self.promptForGroupName('');

        if (name) {
            const params = {
                name: name,
                type: self.elementType,
            };

            Craft.sendActionRequest('POST', self.newGroupAction, {
                data: params,
            }).then((response) => {
                if (response.data.success) {
                    location.href = Craft.getUrl(self.newGroupOnSuccessUrlBase);
                } else {
                    Craft.cp.displayError(self.newGroupOnErrorMessage);
                }
            });
        }
    }

    renameSelectedGroup() {
        let self = this;

        const oldName = self.selectedGroup.textContent,
            newName = self.promptForGroupName(oldName);

        if (newName && newName !== oldName) {
            const params = {
                id: self.selectedGroup.dataset.id,
                name: newName,
                type: self.elementType,
            };

            Craft.sendActionRequest('POST', self.renameGroupAction, {
                data: params,
            }).then((response) => {
                if (response.data.success) {
                    this.selectedGroup.textContent = response.data.group.name;
                    Craft.cp.displayNotice(self.renameGroupOnSuccessMessage);
                } else {
                    Craft.cp.displayError(self.renameGroupOnErrorMessage);
                }
            });
        }
    }

    promptForGroupName(oldName) {
        return prompt(self.promptForGroupNameMessage, oldName);
    }

    deleteSelectedGroup() {
        let self = this;
        if (confirm(self.deleteGroupConfirmMessage)) {
            const params = {
                id: self.selectedGroup.dataset.id,
            };

            Craft.sendActionRequest('POST', self.deleteGroupAction, {
                data: params,
            }).then((response) => {
                if (response.data.success) {
                    location.href = Craft.getUrl(self.deleteGroupOnSuccessUrl);
                } else {
                    Craft.cp.displayError(self.deleteGroupOnErrorMessage);
                }
            });
        }
    }

    toggleGroupSettingsSelector() {
        this.selectedGroup = this.groups.querySelector('a.sel:first-child');

        if (this.selectedGroup.dataset.key === '*' || this.selectedGroup.dataset.readonly) {
            $(this.$groupSettingsBtn).addClass('hidden');
        } else {
            $(this.$groupSettingsBtn).removeClass('hidden');
        }
    }
}

window.SproutSourceGroupsAdmin = SproutSourceGroupsAdmin;
