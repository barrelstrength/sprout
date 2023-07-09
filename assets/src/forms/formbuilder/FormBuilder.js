export const FormBuilder = (formId) => ({

    formId: formId,

    newFormTabIncrement: 0,
    newFormFieldIncrement: 0,

    lastUpdatedFormFieldId: null,

    /**
     * Array of field data
     * [
     *  id: 123,
     *  type: className,
     *  etc.
     * ]
     */
    sourceFields: [],

    /**
     * [
     *   {
     *     id: 123,
     *     label: 'X',
     *     fields: [
     *        {
     *          id: 123,
     *          name: 'Y',
     *          required,
     *          getExampleHtml(),
     *          getSettingsHtml(),
     *        },
     *        {},
     *        {}
     *      ]
     *   }
     * ]
     */
    tabs: [],
    selectedTabId: null,
    editTabId: null,
    editFieldId: null,

    dragOrigin: null,

    DragOrigins: {
        sourceField: 'source-field',
        layoutField: 'layout-field',
        layoutTabNav: 'layout-tab-nav',
    },

    isDraggingTabId: null,
    isDragOverTabId: null,

    isDraggingFormFieldId: null,
    isDragOverFormFieldId: null,

    init() {

        let self = this;
        // Get the saved fieldLayout data
        Craft.sendActionRequest('POST', 'sprout-module-forms/forms/get-submission-field-layout', {
            data: {
                formId: this.formId,
            },
        }).then((response) => {

            // self.tabs = [
            //   {
            //     id: 123,
            //     label: 'Tab 1',
            //     fields: [],
            //   },
            // ];
            self.tabs = response.data.tabs;
            self.selectedTabId = response.data.selectedTabId;

            self.ensureTabIds();
        }).catch(() => {
            console.log('No form data found.');
        });

        window.formBuilder = this;

        let sourceFields = JSON.parse(this.$refs.formBuilder.dataset.sourceFields);

        for (const field of sourceFields) {

            // const {type, name, icon, groupName, order, exampleInputHtml, ...fieldAttributes} = field;
            this.sourceFields.push(field);
            // this.sourceFields.push({
            //   type: type,
            //   name: name,
            //   icon: icon,
            //   groupName: groupName,
            //   order: order,
            //   exampleInputHtml: exampleInputHtml,
            // });
            //
            // this.defaultFieldAttributes[type] = fieldAttributes;
            // this.defaultFieldAttributes[type].name = name;
        }
    },

    ensureTabIds() {
        for (const tab of this.tabs) {
            if (tab.id === null) {
                tab.id = this.getNewFormTabId();

                // Saved tabs will already have an ID
                if (this.selectedTabId === null) {
                    this.selectedTabId = tab.id;
                }
            }
        }
    },

    get fieldLayoutInputValue() {

        let fieldLayout = {};

        if (this.tabs.length && !this.tabs[0].fields.length) {
            return [];
        }

        let fieldLayoutTabs = [];

        for (const tab of this.tabs) {

            let fieldLayoutFields = [];

            for (const fieldData of tab.fields) {

                // let field = this.defaultFieldAttributes[layoutField.type] ?? null;

                let field = this.getFormFieldAttributes(fieldData);

                fieldLayoutFields.push(field);
            }

            fieldLayoutTabs.push({
                id: tab.id,
                uid: tab.uid,
                name: tab.name,
                sortOrder: null,
                userCondition: null,
                elementCondition: null,
                fields: fieldLayoutFields,
            });
        }

        fieldLayout['tabs'] = fieldLayoutTabs;

        return JSON.stringify(fieldLayout);
    },

    getFormFieldAttributes(fieldData) {

        const {
            uiSettings,
            ...fieldAttributes
        } = fieldData;

        return fieldAttributes;
    },

    // Drag Actions

    dragStartLayoutTabNav(e) {
        console.log('dragStartLayoutTabNav');

        e.dataTransfer.setData('sprout/origin-page-tab-id', e.target.dataset.tabId);
        this.dragOrigin = this.DragOrigins.layoutTabNav;
        this.isDraggingTabId = this.normalizeTypes(e.target.dataset.tabId);

        // e.dataTransfer.dropEffect = 'link';
        // e.dataTransfer.effectAllowed = 'copyLink';
    },

    dragEndLayoutTabNav(e) {
        console.log('dragEndLayoutTabNav');

        this.dragOrigin = null;
        this.isDraggingTabId = null;
        this.isDragOverTabId = null;
    },

    dragEnterLayoutTabNav(e) {
        console.log('dragEnterLayoutTabNav');
        e.target.classList.add('no-pointer-events');
    },

    dragLeaveLayoutTabNav(e) {
        console.log('dragLeaveLayoutTabNav');
        e.target.classList.remove('no-pointer-events');
    },

    dragOverLayoutTabNav(e) {
        let self = this;

        if (this.dragOrigin === this.DragOrigins.layoutTabNav) {

        }

        if (this.dragOrigin === this.DragOrigins.sourceField || this.dragOrigin === this.DragOrigins.layoutField) {
            setTimeout(function() {
                self.selectedTabId = self.normalizeTypes(e.target.dataset.tabId);
            }, 1000);
        }
    },

    dropOnLayoutTabNav(e) {
        console.log('dropOnLayoutTabNav');

        let self = this;

        let originTabId = self.normalizeTypes(e.dataTransfer.getData('sprout/origin-page-tab-id'));
        let targetTabId = self.normalizeTypes(e.target.dataset.tabId);

        if (this.dragOrigin === this.DragOrigins.layoutTabNav) {
            this.updateTabPosition(originTabId, targetTabId);
            this.selectedTabId = originTabId;
        }

        if (this.dragOrigin === this.DragOrigins.sourceField) {
            let type = e.dataTransfer.getData('sprout/field-type');
            this.addFieldToLayoutTab(type);
        }

        if (this.dragOrigin === this.DragOrigins.layoutField) {

            this.updateFieldPositionOnNewTab(self.isDraggingFormFieldId, originTabId, targetTabId);
        }
    },

    dragStartSourceField(e) {
        console.log('dragStartSourceField');

        this.dragOrigin = this.DragOrigins.sourceField;

        e.dataTransfer.setData('sprout/field-type', e.target.dataset.type);
        // e.dataTransfer.dropEffect = 'link';
        // e.dataTransfer.effectAllowed = 'copyLink';
    },

    dragEndSourceField(e) {
        console.log('dragEndSourceField');

        this.isDraggingFormFieldId = null;
        this.isDragOverFormFieldId = null;
    },

    dragStartLayoutField(e) {
        console.log('dragStartLayoutField');

        let self = this;

        // Store selected tab in drag object as it might change before the drop event
        e.dataTransfer.setData('sprout/origin-page-tab-id', this.selectedTabId);
        e.dataTransfer.setData('sprout/field-type', e.target.dataset.type);
        this.dragOrigin = this.DragOrigins.layoutField;
        self.isDraggingFormFieldId = e.target.dataset.fieldId;

        // Need setTimeout before manipulating dom:
        // https://stackoverflow.com/questions/19639969/html5-dragend-event-firing-immediately
        // setTimeout(function() {
        //   self.isDraggingFormFieldId = isDraggingFormFieldId;
        // }, 10);

        // e.dataTransfer.dropEffect = 'move';
        // e.dataTransfer.effectAllowed = 'move';

        // Handle scroll stuff: https://stackoverflow.com/a/72807140/1586560
        // On drag scroll, prevents page from growing with mobile safari rubber-band effect
        // let VerticalMaxed = (window.innerHeight + window.scrollY) >= document.body.offsetHeight;
        //
        // this.scrollActive = true;
        //
        // if (e.clientY < 150) {
        //   this.scrollActive = false;
        //   this.scrollFieldLayout(-1);
        // }
        //
        // if ((e.clientY > (document.documentElement.clientHeight - 150)) && !VerticalMaxed) {
        //   this.scrollActive = false;
        //   this.scrollFieldLayout(1)
        // }
    },

    dragEndLayoutField(e) {
        console.log('dragEndLayoutField');

        // Reset scrolling
        // this.scrollActive = false;

        this.isDraggingFormFieldId = null;
        this.isDragOverFormFieldId = null;
    },

    dragEnterLayoutTabBody(e) {
        console.log('dragEnterLayoutTabBody');

        this.isDragOverTabId = this.selectedTabId;
    },

    dragLeaveLayoutTabBody(e) {
        console.log('dragLeaveLayoutTabBody');

        this.isDragOverTabId = null;
    },

    dropOnLayoutTabBody(e) {
        console.log('dropOnLayoutTabBody');
        let self = this;

        let type = e.dataTransfer.getData('sprout/field-type');

        if (this.dragOrigin === this.DragOrigins.sourceField) {
            this.addFieldToLayoutTab(type);
        }

        if (this.dragOrigin === this.DragOrigins.layoutField) {

            let dropBeforeTargetFieldId = this.normalizeTypes(e.target.dataset.fieldId);

            this.updateFieldPosition(self.isDraggingFormFieldId, dropBeforeTargetFieldId);
        }
    },

    dragEnterLayoutField(e) {
        console.log('dragEnterLayoutField');
        e.target.classList.add('no-pointer-events');

    },

    dragLeaveLayoutField(e) {
        console.log('dragLeaveLayoutField');
        e.target.classList.remove('no-pointer-events');
    },

    dropOnExistingLayoutField(e) {
        console.log('dropOnExistingLayoutField');
        let self = this;

        // let fieldId = e.dataTransfer.getData('sprout/field-id');
        let type = e.dataTransfer.getData('sprout/field-type');

        let dropBeforeTargetFieldId = e.target.dataset.fieldId;

        if (this.dragOrigin === this.DragOrigins.layoutField) {
            this.updateFieldPosition(self.isDraggingFormFieldId, dropBeforeTargetFieldId);
        } else {
            this.addFieldToLayoutTab(type, dropBeforeTargetFieldId);
        }
    },

    dragEnterFieldLayoutEndZone(e) {
        console.log('dragEnterFieldLayoutEndZone');

        // this.isMouseOverEndZone = true;
    },

    dragLeaveFieldLayoutEndZone(e) {
        console.log('dragLeaveFieldLayoutEndZone');

        // this.isMouseOverEndZone = false;
    },

    dropOnLayoutEndZone(e) {
        console.log('dropOnLayoutEndZone');
        let self = this;

        let type = e.dataTransfer.getData('sprout/field-type');
        // let fieldId = e.dataTransfer.getData('sprout/field-id');

        if (this.dragOrigin === this.DragOrigins.sourceField) {
            console.log('addFieldToLayoutTab');
            this.addFieldToLayoutTab(type);
        } else {
            console.log('updateFieldPosition');
            this.updateFieldPosition(self.isDraggingFormFieldId);
        }
    },

    // See specifying drop targets docs:
    // https://developer.mozilla.org/en-US/docs/Web/API/HTML_Drag_and_Drop_API/Drag_operations#specifying_drop_targets
    dragOverLayoutTabBody(e) {
        const isDraggingFormField = e.dataTransfer.types.includes('sprout/field-type');

        if (isDraggingFormField) {
            console.log('dragOverLayoutTabBody');
            event.preventDefault();
        }
    },

    dragOverLayoutField(e) {
        const isDraggingLayoutField = e.dataTransfer.types.includes('sprout/field-type');

        if (isDraggingLayoutField) {
            // console.log('dragOverLayoutField');
            event.preventDefault();
        }
    },

    isOverFieldLayoutEndZone(e) {
        const sproutFormField = e.dataTransfer.types.includes('sprout/field-type');

        // this.isDragOverTabId = this.selectedTabId;
        // this.isDragOverFormFieldId = e.target.parentNode.dataset.fieldId;


        if (sproutFormField) {
            console.log('isOverFieldLayoutEndZone');
            event.preventDefault();
        }
    },

    // Helper Methods

    // Covert numbers to numbers and leave other data types as is
    normalizeTypes(value) {
        return Number.isNaN(parseInt(value)) ? value : parseInt(value);
    },

    getFieldsByGroup(handle) {
        return this.sourceFields.filter(item => item.groupName === handle);
    },

    // Returns the field data for a given type
    getFieldByType(type) {
        return this.sourceFields.filter(item => item.type === type)[0] ?? null;
    },

    getCurrentTabIdFromFieldId(fieldId) {
        // loop through this.tabs and find the this.tabs.fields.id that matches
        // return the field.tabId
        let self = this;
        this.tabs.forEach(tab => {
            let fieldIndex = self.getFieldIndexByFieldId(tab, fieldId);
            if (fieldIndex > -1) {
                return tab.fields[fieldIndex].tabId;
            }
        });
    },

    getTabIndexByTabId(id) {
        return this.tabs.findIndex(item => item.id === id);
    },

    getFieldIndexByFieldId(tab, fieldId) {
        // @todo - testing for == because we might have strings or numbers, fix at origin.
        return tab.fields.findIndex(item => item.id == fieldId);
    },

    getNewFormTabId() {
        let nextId = ++this.newFormTabIncrement;
        return 'new' + nextId;
    },

    getNewFormFieldId() {
        let nextId = ++this.newFormFieldIncrement;
        return 'new' + nextId;
    },

    updateTabSettings(tabId, formData) {
        let tabIndex = this.getTabIndexByTabId(tabId);

        for (const [index, value] of formData.entries()) {
            if (index.startsWith('userCondition')) {
                // @todo - capture settings
                this.tabs[tabIndex]['userCondition'] = value;
            } else if (index.startsWith('elementCondition')) {
                // @todo - capture settings
                this.tabs[tabIndex]['elementCondition'] = value;
            } else {
                this.tabs[tabIndex][index] = value;
            }
        }

        if (!this.tabs[tabIndex]['name']) {
            this.tabs[tabIndex]['name'] = 'Page';
        }
    },

    updateFieldSettings(fieldId, fieldSettings) {

        let tabIndex = this.getTabIndexByTabId(this.selectedTabId);
        let tab = this.tabs[tabIndex];

        let fieldIndex = this.getFieldIndexByFieldId(tab, fieldId);
        let targetField = tab.fields[fieldIndex];
        //
        // console.log(targetField);
        // console.log(fieldSettings);

        targetField.name = fieldSettings.name;
        targetField.instructions = fieldSettings.instructions;
        targetField.required = fieldSettings.required;
        // // targetField.name = fieldSettings.name;
        // // targetField.name = fieldSettings.name;
        // console.log(targetField);

        // loop through fieldSettings.settings and update the targetField.settings
        // console.log(fieldSettings);

        tab.fields[fieldIndex] = targetField;
    },

    updateTabPosition(tabId, beforeTabId = null) {

        let beforeTabIndex = this.getTabIndexByTabId(beforeTabId);
        let tabIndex = this.getTabIndexByTabId(tabId);
        let targetTab = this.tabs[tabIndex];

        // console.log(this.tabs);
        // Remove the updated tab
        this.tabs.splice(tabIndex, 1);

        if (beforeTabId) {

            // console.log('target' + targetTab);
            // Insert the updated tab before the target tab
            this.tabs.splice(beforeTabIndex, 0, targetTab);
        } else {
            this.tabs.push(targetTab);
        }

        this.lastUpdatedTabId = targetTab.id;
    },

    updateFieldPositionOnNewTab(fieldId, originTabId, targetTabId) {

        let self = this;

        let targetTabIndex = this.getTabIndexByTabId(targetTabId);
        let targetTab = this.tabs[targetTabIndex];

        let originTabIndex = this.getTabIndexByTabId(originTabId);
        let originTab = this.tabs[originTabIndex];

        let fieldIndex = this.getFieldIndexByFieldId(originTab, fieldId);
        let targetField = originTab.fields[fieldIndex];

        // Remove the updated field from origin tab
        originTab.fields.splice(fieldIndex, 1);

        // Push field onto target tab
        targetTab.fields.push(targetField);

        // Update tab
        this.tabs[targetTabIndex] = targetTab;

        this.lastUpdatedFormFieldId = targetField.id;

        // The timeout here needs to match the time of the 'drop-highlight' css transition effect
        setTimeout(function() {
            self.lastUpdatedFormFieldId = null;
        }, 300);
    },

    updateFieldPosition(fieldId, beforeFieldId = null) {

        let self = this;

        let tabIndex = this.getTabIndexByTabId(this.selectedTabId);
        let tab = this.tabs[tabIndex];

        let fieldIndex = this.getFieldIndexByFieldId(tab, fieldId);
        let targetField = tab.fields[fieldIndex];

        // Remove the updated field
        tab.fields.splice(fieldIndex, 1);

        if (beforeFieldId) {
            // let beforeFieldIndex = tab.fields.length + 1;
            let beforeFieldIndex = this.getFieldIndexByFieldId(tab, beforeFieldId);

            // Insert the updated field before the target field
            tab.fields.splice(beforeFieldIndex, 0, targetField);
        } else {
            tab.fields.push(targetField);
        }

        // Update tab
        this.tabs[tabIndex] = tab;

        this.lastUpdatedFormFieldId = targetField.id;

        // The timeout here needs to match the time of the 'drop-highlight' css transition effect
        setTimeout(function() {
            self.lastUpdatedFormFieldId = null;
        }, 300);
    },

    addFormPageButton() {
        let newId = this.getNewFormTabId();

        let tab = {
            id: newId,
            uid: null,
            name: 'Page 2',
            elementCondition: null,
            tabCondition: null,
            fields: [],
        };

        this.tabs.push(tab);
        this.selectedTabId = newId;
    },

    addFieldToLayoutTab(type, beforeFieldId) {

        let fieldData = this.getFieldByType(type);
        fieldData.type = type;


        if (this.dragOrigin === this.DragOrigins.sourceField) {
            fieldData.id = this.getNewFormFieldId();
        }

        if (this.dragOrigin === this.DragOrigins.layoutField) {
            // fieldData.id = this.getNewFormFieldId();
        }

        let tabIndex = this.getTabIndexByTabId(this.selectedTabId);
        this.tabs[tabIndex].fields.push(fieldData);

        if (beforeFieldId) {
            this.updateFieldPosition(fieldData.id, beforeFieldId);
        }
    },

    // scrollFieldLayout(stepY) {
    //   let scrollY = document.documentElement.scrollTop || document.body.scrollTop;
    //   window.scrollTo(0, (scrollY + stepY));
    //
    //   if (this.scrollActive) {
    //     setTimeout(function() {
    //       scroll(0, stepY);
    //     }, 20);
    //   }
    // },

    isBefore(element1, element2) {
        if (element2.parentNode === element1.parentNode) {
            for (let currentElement = element1.previousSibling; currentElement && currentElement.nodeType !== 9; currentElement = currentElement.previousSibling) {
                if (currentElement === element2) {
                    return true;
                }
            }
        }
        return false;
    },

    // Field Stuff

    editFormTabX(tab) {
        let self = this;

        this.editTabId = tab.id;

        let $slideoutHtml = $('#tab-settings-slideout form');

        const $footer = $('<div/>', {class: 'fld-element-settings-footer'});

        // Copied from Craft's FieldLayoutDesigner.js
        const $cancelBtn = Craft.ui.createButton({
            label: Craft.t('app', 'Close'),
            spinner: true,
        });

        const $applyButton = Craft.ui.createSubmitButton({
            class: 'secondary',
            label: Craft.t('app', 'Apply'),
            spinner: true,
        });

        $('<div/>', {class: 'flex-grow'}).appendTo($footer);
        $cancelBtn.appendTo($footer);
        $applyButton.appendTo($footer);

        $footer.appendTo($slideoutHtml);

        const slideout = new Craft.Slideout($slideoutHtml);
        // const slideout = new Craft.Slideout($slideoutHtml, {
        //   containerElement: 'form',
        //   containerAttributes: {
        //     method: 'post',
        //     action: "",
        //     class: 'fld-element-settings slideout',
        //   },
        // });

        // const $form = slideout.$container;

        Craft.initUiElements($slideoutHtml);

        slideout.on('submit', function(event) {
            event.preventDefault();

            let formData = new FormData($form[0]);

            self.updateTabSettings(self.editTabId, formData);

            slideout.close();
        });

        $cancelBtn.on('click', () => {
            slideout.close();
            self.editFieldId = null;
        });
    },

    editFormTab(tab) {

        let self = this;

        this.editTabId = tab.id;

        Craft.sendActionRequest('POST', 'sprout-module-forms/forms/get-form-tab-settings-html', {
            data: {
                formId: this.formId,
                tab: tab,
            },
        }).then((response) => {

            const $body = $('<div/>', {class: 'fld-element-settings-body'});
            const $fields = $('<div/>', {class: 'fields'}).appendTo($body);
            const $footer = $('<div/>', {class: 'fld-element-settings-footer'});

            // Copied from Craft's FieldLayoutDesigner.js
            const $cancelBtn = Craft.ui.createButton({
                label: Craft.t('app', 'Close'),
                spinner: true,
            });

            const $applyButton = Craft.ui.createSubmitButton({
                class: 'secondary',
                label: Craft.t('app', 'Apply'),
                spinner: true,
            });

            $('<div/>', {class: 'flex-grow'}).appendTo($footer);

            $cancelBtn.appendTo($footer);
            $applyButton.appendTo($footer);

            // Craft.appendHeadHtml(response.data.headHtml);

            let settingsHtml = self.swapPlaceholders(response.data.settingsHtml, response.data.tabId);

            $(settingsHtml).appendTo($fields);

            const $contents = $body.add($footer);

            const slideout = new Craft.Slideout($contents, {
                containerElement: 'form',
                containerAttributes: {
                    method: 'post',
                    action: '',
                    class: 'fld-element-settings slideout',
                    id: 'cat-dog',
                },
            });

            const $form = slideout.$container;

            let conditionBuilderJs = self.swapPlaceholders(response.data.conditionBuilderJs, response.data.tabId);
            Craft.appendBodyHtml(conditionBuilderJs);

            $form.on('submit', function(event) {
                event.preventDefault();

                let formData = new FormData($form[0]);

                self.updateTabSettings(self.editTabId, formData);

                slideout.close();
            });

            $cancelBtn.on('click', () => {
                slideout.close();
                self.editFieldId = null;
            });

        }).catch(() => {
            console.log('No form data found.');
        });

    },

    // The js output by the condition builder
    // "<script>
    // const conditionBuilderJs = "<script>Garnish.requestAnimationFrame(() => {
    //   const $button = $('#sources-__SOURCE_KEY__-condition-type-btn');
    //   $button.menubtn().data('menubtn').on('optionSelect', event => {
    //     const $option = $(event.option);
    //     $button.text($option.text()).removeClass('add');
    // // Don\'t use data(\'value\') here because it could result in an object if data-value is JSON
    //     const $input = $('#sources-__SOURCE_KEY__-condition-type-input').val($option.attr('data-value'));
    //     htmx.trigger($input[0], 'change');
    //   });
    // });
    // htmx.process(htmx.find('#__ID__'));
    // htmx.trigger(htmx.find('#__ID__'), 'htmx:load');
    // </script>";
    swapPlaceholders(str, sourceKey) {
        const defaultId = `condition${Math.floor(Math.random() * 1000000)}`;

        return str
            .replace(/__ID__/g, defaultId)
            .replace(/__SOURCE_KEY__(?=-)/g, Craft.formatInputId('"' + sourceKey + '"'))
            .replace(/__SOURCE_KEY__/g, sourceKey);
    },

    editFormField(field) {
        // let bodyHtmlz = document.getElementById('field-settings-slideout-' + field.id);
        // new Craft.Slideout(bodyHtmlz);
        //
        // return;

        let self = this;

        this.editFieldId = field.id;

        let tabIndex = self.getTabIndexByTabId(this.selectedTabId);
        let tab = self.tabs[tabIndex];
        let fieldIndex = self.getFieldIndexByFieldId(tab, self.editFieldId);
        let currentFieldData = self.tabs[tabIndex].fields[fieldIndex];

        Craft.sendActionRequest('POST', 'sprout-module-forms/forms/get-form-field-settings-html', {
            data: {
                field: currentFieldData,
            },
        }).then((response) => {

            const $form = $('<form/>', {method: 'post', style: 'flex-grow: 1;display: flex;flex-direction: column;', data: 'form-field-' + this.editFieldId});
            const $body = $('<div/>', {class: 'fld-element-settings-body fields'}).appendTo($form);
            const $footer = $('<div/>', {class: 'fld-element-settings-footer'}).appendTo($form);

            // Copied from Craft's FieldLayoutDesigner.js
            const $cancelBtn = Craft.ui.createButton({
                label: Craft.t('app', 'Close'),
                spinner: true,
            });

            const $applyButton = Craft.ui.createSubmitButton({
                class: 'secondary',
                label: Craft.t('app', 'Apply'),
                spinner: true,
            });

            $('<div/>', {class: 'flex-grow'}).appendTo($footer);

            $cancelBtn.appendTo($footer);
            $applyButton.appendTo($footer);

            $(response.data.settingsHtml).appendTo($body);

            const slideout = new Craft.Slideout($form);

            Craft.initUiElements($body);

            $form.on('submit', function(event) {
                event.preventDefault();

                let formData = new FormData($form[0]);
                let fieldSettings = JSON.stringify(Object.fromEntries(formData));
                console.log(fieldSettings);

                Craft.sendActionRequest('POST', 'sprout-module-forms/forms/get-form-field-settings-data', {
                    data: {
                        fieldSettings: fieldSettings,
                    },
                }).then((response) => {
                    self.updateFieldSettings(self.editFieldId, JSON.parse(response.data.fieldSettings));
                });

                slideout.close();
            });

            $cancelBtn.on('click', () => {
                slideout.close();
                self.editFieldId = null;
            });

        }).catch(() => {
            console.log('No form data found.');
        });
    },
});
