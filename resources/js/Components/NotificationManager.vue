<template>
    <Teleport to="body">
        <div class="notification-container">
            <PushNotification
                v-for="notification in notifications"
                :key="notification.id"
                :title="notification.title"
                :body="notification.body"
                :type="notification.type"
                :url="notification.url"
                :duration="notification.duration"
                @close="removeNotification(notification.id)"
                :style="{ top: `${20 + notifications.indexOf(notification) * 110}px` }"
            />
        </div>
    </Teleport>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { getMessaging, onMessage, getToken } from 'firebase/messaging';
import { initializeApp, getApps, getApp } from 'firebase/app';
import { router } from '@inertiajs/vue3';
import PushNotification from './PushNotification.vue';
import axios from 'axios';

const notifications = ref([]);
let notificationId = 0;

// Initialiser les variables globales
if (typeof window.__fcmNotificationCallbacks === 'undefined') {
    window.__fcmNotificationCallbacks = [];
    window.__fcmInitialized = false;
    window.__fcmMessaging = null;
}

const addNotification = (title, body, type = 'info', url = null, duration = 5000) => {
    const id = ++notificationId;
    notifications.value.push({
        id,
        title,
        body,
        type,
        url,
        duration
    });
};

const removeNotification = (id) => {
    const index = notifications.value.findIndex(n => n.id === id);
    if (index > -1) {
        notifications.value.splice(index, 1);
    }
};

const playNotificationSound = () => {
    try {
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();

        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);

        oscillator.frequency.value = 800;
        oscillator.type = 'sine';

        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);

        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.3);
    } catch (error) {
        // Silently fail
    }
};

// Callback pour ce composant
const onFcmMessage = (payload) => {
    const title = payload.data?.title || 'Nouvelle notification';
    const body = payload.data?.body || '';

    let type = 'info';
    if (title.includes('✅') || payload.data?.action === 'validation') {
        type = 'success';
    } else if (title.includes('❌')) {
        type = 'error';
    } else if (title.includes('⚠️')) {
        type = 'warning';
    }

    let url = null;
    if (payload.data?.transfert_id) {
        url = `/transfert-stock/${payload.data.transfert_id}`;
    }

    // Afficher notification in-app (violette)
    addNotification(title, body, type, url);

    // Jouer son
    playNotificationSound();

    // Recharger les données après 2 secondes
    setTimeout(() => {
        router.reload({ preserveScroll: true });
    }, 2000);
};

// Initialiser Firebase Messaging
onMounted(async () => {
    // Enregistrer le callback de ce composant
    window.__fcmNotificationCallbacks.push(onFcmMessage);

    // Si déjà initialisé, ne pas réinitialiser Firebase
    if (window.__fcmInitialized && window.__fcmMessaging) {
        return;
    }

    try {
        const firebaseConfig = {
            apiKey: document.querySelector('meta[name="firebase-api-key"]')?.content,
            authDomain: document.querySelector('meta[name="firebase-auth-domain"]')?.content,
            projectId: document.querySelector('meta[name="firebase-project-id"]')?.content,
            messagingSenderId: document.querySelector('meta[name="firebase-messaging-sender-id"]')?.content,
            appId: document.querySelector('meta[name="firebase-app-id"]')?.content,
        };

        if (!firebaseConfig.apiKey || !firebaseConfig.projectId) {
            return;
        }

        // Enregistrer le Service Worker
        if ('serviceWorker' in navigator) {
            try {
                await navigator.serviceWorker.register('/firebase-messaging-sw.js');
                await navigator.serviceWorker.ready;
            } catch (e) {
                // Service Worker déjà enregistré ou erreur
            }
        }

        // Initialiser Firebase (éviter les doublons)
        let app;
        if (getApps().length === 0) {
            app = initializeApp(firebaseConfig);
        } else {
            app = getApp();
        }

        const messaging = getMessaging(app);
        window.__fcmMessaging = messaging;

        // Demander permission si pas encore fait
        if (typeof Notification !== 'undefined') {
            if (Notification.permission === 'default') {
                await Notification.requestPermission();
            }

            if (Notification.permission === 'denied') {
                return;
            }
        }

        // Enregistrer le token FCM
        await registerFcmToken(messaging);

        // Écouter les messages - appeler tous les callbacks enregistrés
        onMessage(messaging, (payload) => {
            // Appeler le dernier callback enregistré (composant actif)
            const callbacks = window.__fcmNotificationCallbacks;
            if (callbacks.length > 0) {
                callbacks[callbacks.length - 1](payload);
            }
        });

        window.__fcmInitialized = true;

    } catch (error) {
        // Silently fail
    }
});

// Nettoyer le callback quand le composant est détruit
onUnmounted(() => {
    const index = window.__fcmNotificationCallbacks.indexOf(onFcmMessage);
    if (index > -1) {
        window.__fcmNotificationCallbacks.splice(index, 1);
    }
});

// Enregistrer le token FCM
async function registerFcmToken(messaging) {
    try {
        const vapidKey = document.querySelector('meta[name="firebase-vapid-key"]')?.content;
        if (!vapidKey) return;

        await navigator.serviceWorker.ready;

        const currentToken = await getToken(messaging, {
            vapidKey: vapidKey
        });

        if (currentToken) {
            await axios.post('/fcm/save-token', {
                fcm_token: currentToken
            });
        }
    } catch (error) {
        // Silently fail
    }
}

defineExpose({
    addNotification,
    removeNotification
});
</script>

<style scoped>
.notification-container {
    position: fixed;
    top: 0;
    right: 0;
    z-index: 9999;
    pointer-events: none;
}

.notification-container > * {
    pointer-events: auto;
}
</style>
