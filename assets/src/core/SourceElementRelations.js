let $relationModal = $('#sprout-relations-modal');

let $modal = new Garnish.Modal($relationModal, {
    autoShow: false,
    closeOtherModals: false,
    resizable: true,
    onShow: function() {
        let relationBtn = document.getElementById('sprout-relations-btn');

        let data = {
            elementId: relationBtn.dataset.elementId,
        };

        Craft.sendActionRequest('POST', 'sprout-module-core/source-element-relations/get-relations', {
            data: data,
        }).then((response) => {
            if (response.data.success) {
                $relationModal.html(response.data.html);
            }
        }).catch(() => {

        });
    },
    onHide: function() {

    },
});

let sproutRelationBtn = document.querySelector('#sprout-relations-btn');

if (sproutRelationBtn !== null) {
    sproutRelationBtn.addEventListener('click', function(event) {
        event.preventDefault();
        $modal.show();
    }, false);
}
