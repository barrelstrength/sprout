import './notification-events.scss';

let notificationEventSelectField = document.querySelector('[data-attribute="notificationEvent"] select');
let notificationEventTips = document.querySelectorAll('.notification-event-tip');

notificationEventSelectField.addEventListener('change', function(event) {
    // Hide all tips
    notificationEventTips.forEach(function(tip) {
        tip.classList.add('hidden');
    });

    showNotificationEventTip(event.target.value);
});

const showNotificationEventTip = function(className) {
    let tipId = 'notification-event-tip-' + className.replace(/\\/g, '-');
    let tip = document.getElementById(tipId);

    if (tip) {
        tip.classList.toggle('hidden');
    }
};

// Run on page load
let selectedNotificationEvent = notificationEventSelectField.options[notificationEventSelectField.selectedIndex].value;
showNotificationEventTip(selectedNotificationEvent);
