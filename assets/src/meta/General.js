/* global Craft */
import 'jquery-tageditor';

class SproutMetaMetadataField {
    constructor(props) {
        this.fieldHandle = props.fieldHandle;
        this.metaBadgeInfo = props.metaBadgeInfo;
        this.maxDescriptionLength = props.maxDescriptionLength;

        this.initMetadataFieldButtons();
        this.addMetaBadgesToUi();
    }

    initMetadataFieldButtons() {
        let self = this;

        let metaDetailsTabsId = 'fields-' + this.fieldHandle + '-meta-details-tabs';
        this.metaDetailsTabs = document.querySelectorAll('#' + metaDetailsTabsId + ' div.btn');

        let metaDetailsBodyContainerId = 'fields-' + this.fieldHandle + '-meta-details-body';
        this.metaDetailsBodyContainers = document.querySelectorAll(
            '#' + metaDetailsBodyContainerId + ' div.matrixblock',
        );

        if (this.metaDetailsBodyContainers.length < 1) {
            return;
        }

        for (let metaTab of this.metaDetailsTabs) {
            metaTab.addEventListener('click', function(event) {
                let $tab = $(event.target);

                // If we don't have a div we are clicking on the svg or i tag within the div
                // so reassign what we clicked on to the parent div
                if (!$tab.is('div')) {
                    $tab = $tab.closest('div.btn');
                }

                // Do nothing if the active element is clicked
                let $selectedTab = $('#fields-projectsMetadata-meta-details-tabs .active');
                if ($tab.is($selectedTab)) {
                    return true;
                }

                let tabName = $tab.attr('data-type');
                let tabBodyClass = '#fields-' + self.fieldHandle + '-meta-details-body .fields-' + tabName;
                let targetBodyContainer = document.querySelector(tabBodyClass);

                for (let metaTab of self.metaDetailsTabs) {
                    metaTab.classList.remove('active');
                }

                for (let tabBody of self.metaDetailsBodyContainers) {
                    tabBody.style.display = 'none';
                }

                $(targetBodyContainer).show();
                $tab.addClass('active');
            });
        }

        // Display the first tab and block when first loaded
        $(this.metaDetailsBodyContainers[0]).show();
        this.metaDetailsTabs[0].classList.add('active');
    }

    addMetaBadgesToUi() {
        let self = this;

        for (let key in this.metaBadgeInfo) {
            let type = this.metaBadgeInfo[key]['type'];
            let fieldHandle = this.metaBadgeInfo[key]['handle'];
            let badgeClass = this.metaBadgeInfo[key]['badgeClass'];

            let metaButton = $('div.' + badgeClass).html();

            let metaLabelId = '#fields-' + fieldHandle + '-label';
            let metaInputId = '#fields-' + fieldHandle + '-field input';

            let metaInput = $(metaInputId);

            if (fieldHandle === 'title') {
                metaLabelId = '#title-label';
                metaInput = $('#title');
            }

            self.appendMetaBadge(metaLabelId, metaButton);
            Craft.initUiElements($(metaLabelId));

            if (type === 'optimizedTitleField') {
                metaInput.attr('maxlength', 60);
                new Garnish.NiceText(metaInput, {showCharsLeft: true});
            }

            if (type === 'optimizedDescriptionField') {
                let metaTextareaId = '#fields-' + fieldHandle + '-field textarea';
                let metaTextarea = $(metaTextareaId);
                metaTextarea.attr('maxlength', self.maxDescriptionLength);

                // triggers Double instantiating console error
                new Garnish.NiceText(metaTextarea, {showCharsLeft: true});
            }
        }
    }

    getCustomizationSettings(customKey) {
        return $('input[name=\'fields[' + this.fieldHandle + '][metadata][' + customKey + ']\']');
    }

    appendMetaBadge(targetLabelId, metaButton) {
        if ($(targetLabelId).find('.sprout-info').length === 0) {
            $(targetLabelId).append(metaButton).removeClass('hidden');
        }
    }
}

class SproutMetaKeywordsField {
    constructor(props) {
        this.keywordsFieldId = props.keywordsFieldId;

        this.initKeywordsField();
    }

    initKeywordsField() {
        // $(this.keywordsFieldId + ' input').tagEditor({
        //   animateDelete: 20,
        // });
    }
}

window.SproutMetaMetadataField = SproutMetaMetadataField;
window.SproutMetaKeywordsField = SproutMetaKeywordsField;
