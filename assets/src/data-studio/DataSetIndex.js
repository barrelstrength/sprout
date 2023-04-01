/** global: Craft */
/** global: Garnish */
/**
 * DataSet index class
 */
Craft.SproutDataSetElementIndex = Craft.BaseElementIndex.extend({

    init: function(elementType, $container, settings) {
        this.on('selectSite', this.onChangeSite.bind(this));
        this.base(elementType, $container, settings);
    },

    onChangeSite: function() {
        // Oh, how I wish Garnish was documented.
        Craft.postActionRequest('sprout-module-data-studio/data-set/get-new-data-sets-button-html', {}, function(response) {
            let newButtons = document.getElementById('sprout-new-dataset-btn');
            let newHtml = document.createElement('div');
            newHtml.innerHTML = response.html;
            newButtons.parentNode.replaceChild(newHtml.firstChild, newButtons);

            Craft.initUiElements(newButtons.parentNode);
        }, []);
    },
});

// Register it!
Craft.registerElementIndexClass('BarrelStrength\\Sprout\\datastudio\\components\\elements\\DataSetElement', Craft.SproutDataSetElementIndex);

// ES Modules get deferred in Vite Dev so this
// makes sure things are loaded before running
window.SproutDataSetElementIndexInit();
