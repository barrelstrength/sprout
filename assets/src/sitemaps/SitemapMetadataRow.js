export const SitemapMetadataRow = () => ({

    row: null,
    sitemapMetadataId: null,
    siteId: null,
    sourceKey: null,
    type: null,
    uri: null,
    priority: null,
    changeFrequency: null,
    enabled: '0',
    status: null,

    init() {

        this.row = this.$refs.sitemapMetadataRow;
        this.status = this.row.querySelector('span.status');

        this.sitemapMetadataId = this.row.dataset.sitemapMetadataId ?? null;
        this.siteId = this.row.dataset.siteId;
        this.sourceKey = this.row.dataset.sourceKey;
        this.type = this.row.dataset.type;
        this.uri = this.row.dataset.uri;
        this.priority = this.row.dataset.priority;
        this.changeFrequency = this.row.dataset.changeFrequency;
        this.enabled = this.row.dataset.enabled;
    },

    saveSitemapMetadata() {
        let self = this;

        Craft.sendActionRequest('POST', 'sprout-module-sitemaps/sitemap-metadata/save-sitemap-metadata', {
            data: {
                sitemapMetadataId: this.sitemapMetadataId,
                siteId: this.siteId,
                sourceKey: this.sourceKey,
                type: this.type,
                priority: this.priority,
                changeFrequency: this.changeFrequency,
                enabled: this.enabled,
            },
        }).then((response) => {

            if (response.data.success === true) {
                Craft.cp.displayNotice(Craft.t('sprout-module-sitemaps', 'Sitemap metadata saved.'));

                this.sitemapMetadataId = response.data.sitemapMetadata.id;
            } else {
                if (response.data.errorMessage) {
                    Craft.cp.displayError(Craft.t('sprout-module-sitemaps', response.data.errorMessage));
                    // not a great experience but delaying the reload lets the user see the message for now
                    setTimeout(function () {
                        location.reload();
                    }, 1500);
                } else {
                    Craft.cp.displayError(Craft.t('sprout-module-sitemaps', 'Unable to save Sitemap metadata.'));
                }

            }

            if (self.enabled === '1') {
                self.status.classList.remove('disabled');
                self.status.classList.add('live');
            } else {
                self.status.classList.remove('live');
                self.status.classList.add('disabled');
            }

        }).catch(() => {
            Craft.cp.displayError(Craft.t('sprout-module-sitemaps', 'Unable to save Sitemap metadata.'));
        });
    },

    toggleEnabledAndSaveSitemapMetadata() {
        this.enabled = this.enabled === '1' ? '0' : '1';
        this.saveSitemapMetadata();
    },

    deleteSitemapMetadata() {
        let self = this;

        Craft.sendActionRequest('POST', 'sprout-module-sitemaps/sitemap-metadata/delete-sitemap-metadata-by-id', {
            data: {
                sitemapMetadataId: this.sitemapMetadataId,
            },
        }).then((response) => {

            if (response.data.success === true) {
                self.row.remove();
                Craft.cp.displayNotice(Craft.t('sprout-module-sitemaps', 'Sitemap metadata deleted.'));
            } else {
                Craft.cp.displayError(Craft.t('sprout-module-sitemaps', 'Unable to delete Sitemap metadata.'));
            }

            let customPageRows = document.querySelectorAll('#custom-pages tbody tr').length;

            if (customPageRows <= 0) {
                let customPagesTable = document.getElementById('custom-pages');
                customPagesTable.remove();
            }

        }).catch(() => {
            Craft.cp.displayError(Craft.t('sprout-module-sitemaps', 'Unable to delete Sitemap metadata.'));
        });
    },
});
