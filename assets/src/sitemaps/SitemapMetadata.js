import './sitemap-metadata.css';

/* global Craft */

/**
 * Manages the dynamic updating of Sitemap attributes from the Sitemap page.
 */
class SproutSitemapMetadataIndex {

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
        let row = changedElement.closest('tr');
        let sourceKey = row.dataset.sourceKey;
        let isNew = row.dataset.isNew;
        let enabled = row.querySelector('.enabled-status input').value;
        let siteId = document.getElementById('current-site-id').value;
        let uri = row.querySelector('.sitemap-metadata-uri').value;
        let status = row.querySelector('span.status');

        let data = {
            'sitemapMetadataId': row.dataset.sitemapMetadataId,
            'sourceKey': sourceKey,
            'type': row.dataset.type,
            'elementGroupId': row.dataset.elementGroupId,
            'uri': uri,
            'priority': row.querySelector('.sitemap-priority select').value,
            'changeFrequency': row.querySelector('.sitemap-change-frequency select').value,
            'enabled': enabled,
            'siteId': siteId,
        };

        Craft.postActionRequest('sprout-module-sitemaps/sitemap-metadata/save-sitemap-metadata', data, $.proxy(function(response, textStatus) {
            if (textStatus === 'success') {
                if (response.success) {

                    let keys = sourceKey.split('-');
                    let type = keys[0];
                    let newSourceKey = null;

                    if (response.sitemapMetadata.elementGroupId) {
                        newSourceKey = type + '-' + response.sitemapMetadata.elementGroupId;
                    } else {
                        newSourceKey = type + '-' + response.sitemapMetadata.id;
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

                    let $sectionInputBase = 'input[name="sitemaps[' + newSourceKey + ']';

                    $($sectionInputBase + '[id]"]').val(newSourceKey);
                    $($sectionInputBase + '[id]"]').attr('name', 'sitemaps[' + newSourceKey + '][id]');
                    $($sectionInputBase + '[elementGroupId]"]').attr('name', 'sitemaps[' + newSourceKey + '][elementGroupId]');
                    $($sectionInputBase + '[priority]"]').attr('name', 'sitemaps[' + newSourceKey + '][priority]');
                    $($sectionInputBase + '[changeFrequency]"]').attr('name', 'sitemaps[' + newSourceKey + '][changeFrequency]');
                    $($sectionInputBase + '[enabled]"]').attr('name', 'sitemaps[' + newSourceKey + '][enabled]');

                    Craft.cp.displayNotice(Craft.t('sprout-module-sitemaps', 'Sitemap metadata saved.'));
                } else {
                    Craft.cp.displayError(Craft.t('sprout-module-sitemaps', 'Unable to save Sitemap metadata.'));
                }
            }
        }, this));

        if (enabled) {
            status.classList.remove('disabled');
            status.classList.add('live');
        } else {
            status.classList.remove('live');
            status.classList.add('disabled');
        }
    }

    deleteCustomPage(event) {

        let linkElement = event.target;
        let row = linkElement.parentElement.parentElement;
        let customPageId = row.getAttribute('data-sitemap-metadata-id');

        let data = {
            id: customPageId,
        };

        Craft.postActionRequest('sprout-module-sitemaps/sitemap-metadata/delete-sitemap-metadata-by-id', data, $.proxy(function(response, textStatus) {
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

window.SproutSitemapMetadataIndex = SproutSitemapMetadataIndex;
