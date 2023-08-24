export const FormField = (element) => ({

    element: element,
    dragHover: false,

    init() {

    },

    // exampleHtml() {
    //   // let fieldData = window.formBuilder.getFieldByType(this.field.type);
    //
    //   let editText = Craft.t('sprout-module-forms', 'Edit');
    //
    //   return '<div ' +
    //     'class="layout-field"\n' +
    //     'draggable="true"\n' +
    //     'x-on:dragstart="handleDragStart"\n' +
    //     'x-on:dblclick="editFormField"\n' +
    //     'x-bind:data-type="field.type"' +
    //     '>' +
    //     '<div class="layout-field-header">' +
    //     '<h2 x-bind:class="field.required" x-text="field.name"></h2>' +
    //     '<code class="light" x-text="field.handle"></code>' +
    //     '<p x-show="field.instructions" x-text="field.instructions"></p>' +
    //     '</div>' +
    //     '<div class="body" x-html="field.exampleInputHtml"></div>' +
    //     '<div class="layout-field-overlay">' +
    //     '<div x-on:click="editFormField" class="edit-field-button">'+editText+'</div>' +
    //     '</div>' +
    //     '</div>';
    // },

    // @todo - somehow outputting this collapses padding to left of main body area
    sourceHtml() {
        return '<div\n' +
            'class="source-field"\n' +
            'draggable="true"\n' +
            'x-bind:class="[dragHover ? \'drag-hover\' : \'\', element.field.sortOrder % 2 == 0 ? \'even\' : \'odd\']"\n' +
            'x-on:mouseover="dragHover = true"\n' +
            'x-on:mouseleave="dragHover = false"\n' +
            'x-on:dragstart.self="dragStartSourceField"\n' +
            'x-on:dragend.self="dragEndSourceField"\n' +
            'x-bind:data-type="element.field.type"' +
            '>' +
            '<h3>' +
            '<span x-html="element.uiSettings.icon" class="sproutforms-icon"></span>' +
            '<span x-text="element.field.name" x-bind:data-handle="element.field.name" class="sproutforms-sourcefield-name"></span>' +
            '</h3>' +
            '</div>';
    },
});
