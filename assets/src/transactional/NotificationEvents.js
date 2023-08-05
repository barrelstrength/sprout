import './notification-events.scss';
import './notification-events.scss';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

export const NotificationEventTip = (selectedNotificationEvent) => ({

    selectedNotificationEvent: selectedNotificationEvent,

    init() {

    },
});

Alpine.data('NotificationEventTip', NotificationEventTip);

Alpine.start();

