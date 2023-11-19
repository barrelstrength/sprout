/*
 * @link https://sprout.barrelstrengthdesign.com
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license https://craftcms.github.io/license
 */

/* global Craft */
/* global Garnish */

if (typeof Craft.SproutForms === typeof undefined) {
    Craft.SproutForms = {};
}

(function($) {
    Craft.SproutForms.FormSettings = Garnish.Base.extend({

        options: null,
        modal: null,
        $lightswitches: null,

        /**
         * The constructor.
         */
        init: function() {
            // init method
            this.initButtons();
        },

        /**
         * Adds edit buttons to existing integrations.
         */
        initButtons: function() {
            const that = this;

            // Add listeners to all the items that start with sproutform-field-
            $('a[id^=\'sproutform-integration\']').each(function(i, el) {
                const integrationId = $(el).data('integrationid');

                if (integrationId) {
                    that.addListener($('#sproutform-integration-' + integrationId), 'activate', 'editIntegration');
                }
            });

            this.$lightswitches = $('.sproutforms-integration-row .lightswitch');

            this.addListener(this.$lightswitches, 'click', 'onEnableIntegration');

            this.modal = Craft.SproutForms.IntegrationModal.getInstance();

            this.modal.on('saveIntegration', $.proxy(function(e) {
                const integration = e.integration;
                // Let's update the name if the integration is updated
                this.resetIntegration(integration);
            }, this));

            this.addListener($('#integrationsOptions'), 'change', 'createDefaultIntegration');
        },

        onEnableIntegration: function(ev) {
            const lightswitch = ev.currentTarget;
            const integrationId = lightswitch.id;
            let enabled = $(lightswitch).attr('aria-checked');
            enabled = enabled === 'true' ? 1 : 0;
            const formId = $('#formId').val();

            const data = {integrationId: integrationId, enabled: enabled, formId: formId};

            Craft.postActionRequest('sprout-module-forms/form-integration-settings/enable-integration', data, $.proxy(function(response, textStatus) {
                if (textStatus === 'success' && response.success) {
                    Craft.cp.displayNotice(Craft.t('sprout', 'Integration updated.'));
                } else {
                    Craft.cp.displayError(Craft.t('sprout', 'Unable to update integration'));
                }
            }, this));

        },

        /**
         * Renames | update icon |
         * of an existing integration after edit it
         *
         * @param integration
         */
        resetIntegration: function(integration) {
            const $integrationDiv = $('#sproutform-integration-' + integration.id);

            const $container = $('#integration-enabled-' + integration.id);

            const currentValue = integration.enabled === '1' ? true : false;
            const settingsValue = $container.attr('aria-checked') === 'true' ? true : false;
            if (currentValue !== settingsValue) {
                $container.attr('aria-checked', '' + currentValue);
                if (currentValue) {
                    $container.addClass('on');
                } else {
                    $container.removeClass('on');
                }
            }
            $integrationDiv.html(integration.name);
        },

        createDefaultIntegration: function(type) {

            const that = this;
            const integrationTableBody = $('#sproutforms-integrations-table tbody');
            const currentIntegration = $('#integrationsOptions').val();
            const formId = $('#formId').val();

            if (currentIntegration === '') {
                return;
            }

            const data = {
                type: currentIntegration,
                formId: formId,
                sendRule: '*',
            };

            Craft.postActionRequest('sprout-module-forms/form-integration-settings/save-integration', data, $.proxy(function(response, textStatus) {
                if (textStatus === 'success') {
                    const integration = response.integration;

                    integrationTableBody.append('<tr class="field sproutforms-integration-row" id ="sproutforms-integration-row-' + integration.id + '">' +
                        '<td class="heading">' +
                        '<a href="#" id ="sproutform-integration-' + integration.id + '" data-integrationid="' + integration.id + '">' + integration.name + '</a>' +
                        '</td>' +
                        '<td>' +
                        '<div class="lightswitch small" tabindex="0" data-value="1" role="checkbox" aria-checked="false" id ="integration-enabled-' + integration.id + '">' +
                        '<div class="lightswitch-container">' +
                        '<div class="handle"></div>' +
                        '</div>' +
                        '<input type="hidden" name="" value="">' +
                        '</div>' +
                        '</td>' +
                        '</tr>');

                    that.addListener($('#sproutform-integration-' + integration.id), 'activate', 'editIntegration');

                    $('#integrationsOptions').val('');
                    const $container = $('#integration-enabled-' + integration.id);
                    $container.lightswitch();
                    that.addListener($container, 'click', 'onEnableIntegration');
                } else {
                    // something went wrong
                }
            }, this));

        },

        editIntegration: function(currentOption) {
            const option = currentOption.currentTarget;

            const integrationId = $(option).data('integrationid');
            // Make our field available to our parent function
            //this.$field = $(option);
            this.base($(option));

            this.modal.editIntegration(integrationId);
        },

    });

})(jQuery);
