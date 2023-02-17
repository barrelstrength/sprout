import './email-field.scss';

/* global Craft */

class SproutEmailField {

    constructor(namespaceInputId, id, elementId, fieldHandle, fieldContext) {
        this.sproutEmailFieldId = namespaceInputId;
        this.sproutEmailButtonClass = '.' + id;

        this.checkSproutEmailField(elementId, fieldHandle, fieldContext);
    }

    checkSproutEmailField(elementId, fieldHandle, fieldContext) {
        let self = this;

        // We use setTimeout to make sure our function works every time
        setTimeout(function() {
            // Set up data for the controller.
            let data = {
                'elementId': elementId,
                'fieldContext': fieldContext,
                'fieldHandle': fieldHandle,
                'value': document.getElementById(self.sproutEmailFieldId).value,
            };

            // Query the controller so the regex validation is all done through PHP.
            Craft.sendActionRequest('POST', 'sprout-module-fields/fields/validate-email', {
                data: data,
            }).then((response) => {
                if (response.data.success) {
                    document.querySelector(self.sproutEmailButtonClass).classList.add('fade');
                    document.querySelector(self.sproutEmailButtonClass + ' a').setAttribute('href', 'mailto:' + data.value);
                } else {
                    document.querySelector(self.sproutEmailButtonClass).classList.remove('fade');
                }
            });

        }, 500);
    }
}

window.SproutEmailField = SproutEmailField;
