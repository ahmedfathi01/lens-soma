importScripts('https://www.gstatic.com/firebasejs/8.10.1/firebase-app.js');
importScripts('https://www.gstatic.com/firebasejs/8.10.1/firebase-messaging.js');

// Strict origin validation
self.addEventListener('message', function(event) {
    // Define trusted origins explicitly
    const trustedOrigins = [
        'https://lens-soma.com',
        'https://www.lens-soma.com',
        'http://127.0.0.1:8000'
    ];

    // Add current origin to trusted origins if it exists
    if (self.location && self.location.origin) {
        trustedOrigins.push(self.location.origin);
    }

    // Strict origin checking - reject messages from untrusted origins
    if (!trustedOrigins.includes(event.origin)) {
        console.error('[Firebase Messaging SW] Message rejected - untrusted origin:', event.origin);
        return;
    }

    // Strict validation of message structure and type
    if (!event.data || typeof event.data !== 'object' || !event.data.type) {
        console.error('[Firebase Messaging SW] Message rejected - invalid format');
        return;
    }

    // Process only specific message types
    if (event.data.type === 'FIREBASE_CONFIG') {
        if (!event.data.config || typeof event.data.config !== 'object') {
            console.error('[Firebase Messaging SW] Config data missing or invalid');
            return;
        }

        // Initialize Firebase only with validated config
        firebase.initializeApp(event.data.config);
        const messaging = firebase.messaging();

        messaging.setBackgroundMessageHandler(function(payload) {
            const notificationTitle = payload.notification.title;
            const notificationOptions = {
                body: payload.notification.body,
                vibrate: [100, 50, 100],
                data: payload.data,
                actions: [
                    {
                        action: 'open_order',
                        title: 'عرض الطلب'
                    }
                ],
                requireInteraction: true,
                dir: 'rtl',
                lang: 'ar',
                tag: Date.now().toString()
            };

            return self.registration.showNotification(notificationTitle, notificationOptions);
        });
    }
});

self.addEventListener('notificationclick', function(event) {
    event.notification.close();

    const notificationData = event.notification.data || {};
    const link = notificationData.link;
    const uuid = notificationData.uuid;
    const targetUrl = link || '/admin/dashboard';

    event.waitUntil(
        clients.matchAll({
            type: 'window',
            includeUncontrolled: true
        })
        .then(function(clientList) {
            for (var i = 0; i < clientList.length; i++) {
                var client = clientList[i];
                if (client.url === targetUrl && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});

self.addEventListener('push', function(event) {
    if (event.data) {
        const payload = event.data.json();

        event.waitUntil(
            self.registration.showNotification(payload.notification.title, {
                body: payload.notification.body,
                vibrate: [100, 50, 100],
                data: payload.data,
                actions: [
                    {
                        action: 'open_order',
                        title: 'عرض الطلب'
                    }
                ],
                requireInteraction: true,
                dir: 'rtl',
                lang: 'ar',
                tag: Date.now().toString()
            })
        );
    }
});

self.addEventListener('install', function(event) {
    event.waitUntil(self.skipWaiting());
});

self.addEventListener('activate', function(event) {
    event.waitUntil(self.clients.claim());
});
