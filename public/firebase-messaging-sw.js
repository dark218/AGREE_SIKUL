// public/firebase-messaging-sw.js
// Version: 2.1.0 - Fix duplicate notifications

// Forcer l'activation immédiate du nouveau SW
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', () => self.clients.claim());

importScripts('https://www.gstatic.com/firebasejs/10.12.2/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.12.2/firebase-messaging-compat.js');

firebase.initializeApp({
    apiKey: "AIzaSyAR8VEfqHX-u2FYchsacb9n2Fs-P2jmPDU",
    authDomain: "qrpayme-b3407.firebaseapp.com",
    projectId: "qrpayme-b3407",
    messagingSenderId: "106155875494",
    appId: "1:106155875494:web:fc865fa83bd6edef54a867",
});

const messaging = firebase.messaging();

// Fonction pour afficher la notification
function showPushNotification(title, body, url) {
    const options = {
        body: body,
        icon: '/assets/images/smile/icone-3.png',
        badge: '/assets/images/smile/icone-3.png',
        tag: 'AGREE SIKUL-' + Date.now(),
        requireInteraction: true,
        vibrate: [200, 100, 200],
        data: {
            url: url || '/'
        }
    };

    return self.registration.showNotification(title, options);
}

// Gère les messages quand la page n'est PAS au premier plan (Firebase SDK)
messaging.onBackgroundMessage(function (payload) {
    const title = payload.data?.title || 'Nouvelle notification';
    const body = payload.data?.body || '';
    const url = payload.data?.transfert_id ? `/transfert-stock/${payload.data.transfert_id}` : '/';

    return showPushNotification(title, body, url);
});

// Gestion des clics sur les notifications
self.addEventListener('notificationclick', function(event) {
    event.notification.close();

    const url = event.notification.data?.url || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function(clientList) {
            // Chercher un onglet existant
            for (let i = 0; i < clientList.length; i++) {
                const client = clientList[i];
                if (client.url.includes(self.location.origin) && 'focus' in client) {
                    client.focus();
                    client.navigate(url);
                    return;
                }
            }
            // Sinon ouvrir un nouvel onglet
            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        })
    );
});
