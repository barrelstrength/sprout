import './phone-field.scss';

/* global Craft */

class SproutPhoneField {

    constructor(namespaceInputId, countryId) {
        this.sproutPhoneFieldId = namespaceInputId;
        this.sproutPhoneCountryId = countryId;
        this.sproutPhoneFieldButtonClass = '#' + this.sproutPhoneFieldId + '-field .compoundSelectText-text .sprout-phone-button';

        this.checkValidation();
        this.addListeners();
    }

    checkValidation() {
        let self = this;
        // We use setTimeout to make sure our function works every time
        setTimeout(function() {
            let phoneNumber = document.getElementById(self.sproutPhoneFieldId).value;
            let country = document.getElementById(self.sproutPhoneCountryId).value;

            let data = {
                value: {
                    'country': country,
                    'phone': phoneNumber,
                },
            };

            // Determine if we should show Phone link on initial load
            self.validatePhoneNumber(document.getElementById(self.sproutPhoneFieldId), data);
        }, 500);
    }

    addListeners() {
        let self = this;

        document.getElementById(this.sproutPhoneFieldId).addEventListener('input', function() {
            let currentPhoneField = this;
            let phoneNumber = this.value;
            let country = document.getElementById(self.sproutPhoneCountryId).value;
            let data = {
                value: {
                    'country': country,
                    'phone': phoneNumber,
                },
            };
            self.validatePhoneNumber(currentPhoneField, data);
        });
    }

    validatePhoneNumber(currentPhoneField, data) {
        let self = this;

        Craft.sendActionRequest('POST', 'sprout-module-fields/fields/validate-phone', {
            data: data,
        }).then((response) => {
            console.log(response.data);
            if (response.data.success) {
                document.querySelector(self.sproutPhoneFieldButtonClass).classList.add('fade');
                document.querySelector(self.sproutPhoneFieldButtonClass + ' a').setAttribute('href', 'tel:' + data.phone);
            } else {
                document.querySelector(self.sproutPhoneFieldButtonClass).classList.remove('fade');
            }
        });
    }
}

window.SproutPhoneField = SproutPhoneField;

