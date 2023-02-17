/** global: Craft */
/** global: Garnish */
/**
 * Redirect index class
 */
Craft.SproutRedirectElementIndex = Craft.BaseElementIndex.extend({

    init: function(elementType, $container, settings) {
        this.on('selectSite', this.onChangeSite.bind(this));
        this.base(elementType, $container, settings);
    },

    onChangeSite: function() {
        if (this.settings.context === 'index') {
            let newRedirectBtn = document.getElementById('sprout-redirects-new-button');
            newRedirectBtn.setAttribute('href', this.getNewRedirectUrl());
        }
    },

    getNewRedirectUrl: function() {
        const uri = `sprout/redirects/new`;
        const site = this.getSite();
        const params = site ? {site: site.handle} : undefined;

        return Craft.getUrl(uri, params);
    },
});

// Register it!
Craft.registerElementIndexClass('BarrelStrength\\Sprout\\redirects\\components\\elements\\RedirectElement', Craft.SproutRedirectElementIndex);

// ES Modules get deferred in Vite Dev so this
// makes sure things are loaded before running
window.SproutRedirectElementIndexInit();
