import Alpine from 'alpinejs';

window.Alpine = Alpine;

export const SproutSubscribers = () => ({
    addUser() {
        const slideout = new Craft.CpScreenSlideout('sprout-module-audience/subscribers/edit-user');

        slideout.on('submit', ev => {
            console.log('submit');
        });

        slideout.on('close', () => {
            console.log('close');
        });
    },
});

Alpine.data('SproutSubscribers', SproutSubscribers);

Alpine.start();
