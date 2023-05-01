import './sitemaps.css';

/* global Craft */

/**
 * Manages the dynamic updating of Sitemap attributes from the Sitemap page.
 */
class SproutSeoSitemapIndex {

    constructor() {
        const lightswitches = document.querySelectorAll('.sitemap-settings .lightswitch');
        const selectDropdowns = document.querySelectorAll('.sitemap-settings select');
        const customSectionUrls = document.querySelectorAll('.sitemap-settings input.sitemap-custom-url');
        const customPageDeleteLinks = document.querySelectorAll('#custom-pages tbody tr td a.delete');

        for (const lightswitch of lightswitches) {
            lightswitch.addEventListener('click', this.updateSitemap);
        }

        for (const selectDropdown of selectDropdowns) {
            selectDropdown.addEventListener('change', this.updateSitemap);
        }

        for (const customSectionUrl of customSectionUrls) {
            customSectionUrl.addEventListener('change', this.updateSitemap);
        }

        for (const customPageDeleteLink of customPageDeleteLinks) {
            customPageDeleteLink.addEventListener('click', this.deleteCustomPage);
        }
    }

    updateSitemap(event) {
        let changedElement = event.target;
        let $row = $(changedElement).closest('tr');
        let rowId = $row.data('rowid');
        let isNew = $row.data('isNew');
        let enabled = $('input[name="sitemaps[sections][' + rowId + '][enabled]"]').val();
        let siteId = $('input[name="siteId"]').val();
        let uri = $('input[name="sitemaps[sections][' + rowId + '][uri]"]').val();
        let status = $('tr[data-rowid="' + rowId + '"] td span.status');

        let data = {
            'sitemapMetadataId': $row.data('sitemap-metadata-id'),
            'type': $row.data('type'),
            'elementGroupId': $row.data('elementGroupId'),
            'uri': uri,
            'priority': $('select[name="sitemaps[sections][' + rowId + '][priority]"]').val(),
            'changeFrequency': $('select[name="sitemaps[sections][' + rowId + '][changeFrequency]"]').val(),
            'enabled': enabled,
            'siteId': siteId,
        };

        Craft.postActionRequest('sprout-module-sitemaps/sitemaps/save-sitemap-metadata', data, $.proxy(function(response, textStatus) {
            if (textStatus === 'success') {
                if (response.success) {

                    let keys = rowId.split('-');
                    let type = keys[0];
                    let newRowId = null;

                    if (response.sitemapMetadata.elementGroupId) {
                        newRowId = type + '-' + response.sitemapMetadata.elementGroupId;
                    } else {
                        newRowId = type + '-' + response.sitemapMetadata.id;
                    }

                    let $changedElementRow = $(changedElement).closest('tr');
                    let $changedElementTitleLink = $changedElementRow.find('a.sprout-sectiontitle');

                    if ($changedElementRow.data('isNew')) {
                        $changedElementTitleLink.attr('href', 'sections/' + response.sitemapMetadata.id);
                        $changedElementRow.removeClass('sitemapsection-isnew');
                        $changedElementRow.data('isNew', 0);
                        $changedElementRow.data('sitemapMetadataId', response.sitemapMetadata.id);

                        $changedElementTitleLink.unbind('click');
                    }

                    let $sectionInputBase = 'input[name="sitemaps[sections][' + rowId + ']';

                    $($sectionInputBase + '[id]"]').val(newRowId);
                    $($sectionInputBase + '[id]"]').attr('name', 'sitemaps[sections][' + newRowId + '][id]');
                    $($sectionInputBase + '[elementGroupId]"]').attr('name', 'sitemaps[sections][' + newRowId + '][elementGroupId]');
                    $($sectionInputBase + '[priority]"]').attr('name', 'sitemaps[sections][' + newRowId + '][priority]');
                    $($sectionInputBase + '[changeFrequency]"]').attr('name', 'sitemaps[sections][' + newRowId + '][changeFrequency]');
                    $($sectionInputBase + '[enabled]"]').attr('name', 'sitemaps[sections][' + newRowId + '][enabled]');

                    Craft.cp.displayNotice(Craft.t('sprout-module-sitemaps', 'Sitemap metadata saved.'));
                } else {
                    Craft.cp.displayError(Craft.t('sprout-module-sitemaps', 'Unable to save Sitemap metadata.'));
                }
            }
        }, this));

        if (enabled) {
            status.removeClass('disabled');
            status.addClass('live');
        } else {
            status.removeClass('live');
            status.addClass('disabled');
        }
    }

    deleteCustomPage(event) {

        let linkElement = event.target;
        let row = linkElement.parentElement.parentElement;
        let customPageId = row.getAttribute('data-sitemap-metadata-id');

        let data = {
            id: customPageId,
        };

        Craft.postActionRequest('sprout-module-sitemaps/sitemaps/delete-sitemap-by-id', data, $.proxy(function(response, textStatus) {
            if (response.success) {
                row.remove();
            }

            let customPageRows = document.querySelectorAll('#custom-pages tbody tr').length;

            if (customPageRows <= 0) {
                let customPagesTable = document.getElementById('custom-pages');
                customPagesTable.remove();
            }
        }, this));
    }
}

window.SproutSeoSitemapIndex = SproutSeoSitemapIndex;
