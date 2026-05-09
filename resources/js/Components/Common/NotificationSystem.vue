<template>
  <!-- Système de notifications style Outlook -->
  <div>
    <!-- Bouton notifications dans header -->
    <button 
      @click="togglePanel"
      class="relative p-2 text-gray-500 hover:text-orange-600 transition-colors"
    >
      <BellIcon class="w-6 h-6" />
      <span 
        v-if="unreadCount > 0" 
        class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center animate-pulse"
      >
        {{ unreadCount > 99 ? '99+' : unreadCount }}
      </span>
    </button>

    <!-- Panel notifications -->
    <div 
      v-show="isOpen"
      class="fixed inset-y-0 right-0 z-50 w-80 bg-white shadow-2xl transform transition-transform duration-300 ease-in-out border-l border-gray-200"
      :class="{ 'translate-x-full': !isOpen, 'translate-x-0': isOpen }"
    >
      <div class="h-full flex flex-col">
        <!-- Header -->
        <div class="flex items-center justify-between p-4 border-b bg-gradient-to-r from-orange-50 to-green-50">
          <div class="flex items-center">
            <BellIcon class="w-5 h-5 text-orange-600 mr-2" />
            <h3 class="text-lg font-semibold text-gray-900">Notifications</h3>
          </div>
          <div class="flex items-center space-x-2">
            <span class="bg-orange-100 text-orange-800 text-xs font-medium px-2 py-1 rounded-full">
              {{ unreadCount }} nouvelles
            </span>
            <button @click="isOpen = false" class="text-gray-400 hover:text-gray-600">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>
        </div>

        <!-- Actions rapides -->
        <div class="p-3 border-b bg-gray-50">
          <div class="flex space-x-2">
            <button 
              @click="markAllAsRead"
              class="flex-1 text-xs bg-orange-100 text-orange-700 px-3 py-1 rounded-md hover:bg-orange-200 transition-colors"
            >
              Tout marquer lu
            </button>
            <button 
              @click="clearAll"
              class="flex-1 text-xs bg-gray-100 text-gray-700 px-3 py-1 rounded-md hover:bg-gray-200 transition-colors"
            >
              Tout effacer
            </button>
          </div>
        </div>

        <!-- Liste notifications -->
        <div class="flex-1 overflow-y-auto">
          <div v-if="notifications.length === 0" class="p-8 text-center">
            <BellIcon class="w-12 h-12 text-gray-300 mx-auto mb-4" />
            <p class="text-gray-500">Aucune notification</p>
          </div>
          
          <div v-else class="divide-y divide-gray-100">
            <div 
              v-for="notification in notifications" 
              :key="notification.id"
              class="p-4 hover:bg-gray-50 cursor-pointer transition-colors relative"
              :class="{ 
                'bg-orange-25 border-l-4 border-orange-500': !notification.read,
                'opacity-75': notification.read 
              }"
              @click="markAsRead(notification)"
            >
              <div class="flex items-start space-x-3">
                <!-- Icône type -->
                <div class="flex-shrink-0">
                  <div 
                    class="w-8 h-8 rounded-full flex items-center justify-center"
                    :class="getNotificationIconClass(notification.type)"
                  >
                    <component :is="getNotificationIcon(notification.type)" class="w-4 h-4" />
                  </div>
                </div>

                <!-- Contenu -->
                <div class="flex-1 min-w-0">
                  <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-900 truncate">
                      {{ notification.title }}
                    </p>
                    <div v-if="!notification.read" class="w-2 h-2 bg-orange-500 rounded-full"></div>
                  </div>
                  <p class="text-sm text-gray-600 mt-1 line-clamp-2">
                    {{ notification.message }}
                  </p>
                  <div class="flex items-center justify-between mt-2">
                    <span class="text-xs text-gray-400">
                      {{ formatNotificationDate(notification.created_at) }}
                    </span>
                    <span 
                      class="text-xs px-2 py-1 rounded-full"
                      :class="getNotificationTypeClass(notification.type)"
                    >
                      {{ getNotificationTypeLabel(notification.type) }}
                    </span>
                  </div>
                </div>
              </div>

              <!-- Actions -->
              <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                <button 
                  @click.stop="removeNotification(notification.id)"
                  class="text-gray-400 hover:text-red-500"
                >
                  <XMarkIcon class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
           <!-- :href="route('notifications.index')"  -->
        <div class="border-t p-4 bg-gray-50">
          <Link 
          
            class="w-full text-center text-sm text-orange-600 hover:text-orange-500 block"
          >
            Voir toutes les notifications
          </Link>
        </div>
      </div>
    </div>

    <!-- Overlay -->
    <div 
      v-show="isOpen" 
      @click="isOpen = false" 
      class="fixed inset-0 z-40 bg-gray-600 bg-opacity-50"
    ></div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Link } from '@inertiajs/vue3'
import {
  BellIcon, XMarkIcon, CheckCircleIcon, ExclamationTriangleIcon,
  InformationCircleIcon, XCircleIcon, UserPlusIcon, ShoppingCartIcon
} from '@heroicons/vue/24/outline'

const isOpen = ref(false)

const notifications = ref([
  {
    id: 1,
    title: 'Nouveau producteur inscrit',
    message: 'Jean Kouassi s\'est inscrit dans la zone d\'Abidjan Nord. Profil en attente de validation.',
    created_at: new Date(),
    read: false,
    type: 'user'
  },
  {
    id: 2,
    title: 'Commande urgente',
    message: 'Commande #CMD-001 de 500kg de riz nécessite une validation immédiate.',
    created_at: new Date(Date.now() - 1800000), // 30 min ago
    read: false,
    type: 'warning'
  },
  {
    id: 3,
    title: 'Stock critique',
    message: 'Le stock de maïs est en dessous du seuil minimum (10kg restants).',
    created_at: new Date(Date.now() - 3600000), // 1h ago
    read: false,
    type: 'error'
  },
  {
    id: 4,
    title: 'Livraison terminée',
    message: 'La livraison #LIV-045 a été effectuée avec succès à Yamoussoukro.',
    created_at: new Date(Date.now() - 7200000), // 2h ago
    read: true,
    type: 'success'
  },
  {
    id: 5,
    title: 'Nouveau prix de marché',
    message: 'Les prix du cacao ont été mis à jour sur le marché de San Pedro.',
    created_at: new Date(Date.now() - 14400000), // 4h ago
    read: true,
    type: 'info'
  }
])

const unreadCount = computed(() => notifications.value.filter(n => !n.read).length)

const togglePanel = () => {
  isOpen.value = !isOpen.value
}

const markAsRead = (notification) => {
  if (!notification.read) {
    notification.read = true
    // Ici on ferait un appel API
  }
}

const markAllAsRead = () => {
  notifications.value.forEach(n => n.read = true)
  // Ici on ferait un appel API
}

const clearAll = () => {
  if (confirm('Êtes-vous sûr de vouloir effacer toutes les notifications ?')) {
    notifications.value = []
    // Ici on ferait un appel API
  }
}

const removeNotification = (id) => {
  const index = notifications.value.findIndex(n => n.id === id)
  if (index > -1) {
    notifications.value.splice(index, 1)
    // Ici on ferait un appel API
  }
}

const getNotificationIcon = (type) => {
  const icons = {
    'success': CheckCircleIcon,
    'error': XCircleIcon,
    'warning': ExclamationTriangleIcon,
    'info': InformationCircleIcon,
    'user': UserPlusIcon,
    'order': ShoppingCartIcon,
  }
  return icons[type] || InformationCircleIcon
}

const getNotificationIconClass = (type) => {
  const classes = {
    'success': 'bg-green-100 text-green-600',
    'error': 'bg-red-100 text-red-600',
    'warning': 'bg-orange-100 text-orange-600',
    'info': 'bg-blue-100 text-blue-600',
    'user': 'bg-purple-100 text-purple-600',
    'order': 'bg-indigo-100 text-indigo-600',
  }
  return classes[type] || 'bg-gray-100 text-gray-600'
}

const getNotificationTypeClass = (type) => {
  const classes = {
    'success': 'bg-green-100 text-green-800',
    'error': 'bg-red-100 text-red-800',
    'warning': 'bg-orange-100 text-orange-800',
    'info': 'bg-blue-100 text-blue-800',
    'user': 'bg-purple-100 text-purple-800',
    'order': 'bg-indigo-100 text-indigo-800',
  }
  return classes[type] || 'bg-gray-100 text-gray-800'
}

const getNotificationTypeLabel = (type) => {
  const labels = {
    'success': 'Succès',
    'error': 'Erreur',
    'warning': 'Attention',
    'info': 'Info',
    'user': 'Utilisateur',
    'order': 'Commande',
  }
  return labels[type] || 'Info'
}

const formatNotificationDate = (date) => {
  const now = new Date()
  const diff = now - new Date(date)
  const minutes = Math.floor(diff / 60000)
  const hours = Math.floor(diff / 3600000)
  const days = Math.floor(diff / 86400000)

  if (minutes < 1) return 'À l\'instant'
  if (minutes < 60) return `Il y a ${minutes} min`
  if (hours < 24) return `Il y a ${hours}h`
  if (days < 7) return `Il y a ${days}j`
  
  return new Intl.DateTimeFormat('fr-FR', {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit'
  }).format(new Date(date))
}

// Simuler l'arrivée de nouvelles notifications
onMounted(() => {
  // Notification de bienvenue
  setTimeout(() => {
    addNotification({
      title: 'Bienvenue dans OCPV !',
      message: 'Votre session a été initialisée avec succès.',
      type: 'success'
    })
  }, 2000)

  // Simulation notifications périodiques
  setInterval(() => {
    if (Math.random() > 0.7) { // 30% de chance
      const types = ['info', 'warning', 'user', 'order']
      const messages = [
        { title: 'Nouvelle commande', message: 'Commande reçue pour 200kg de bananes', type: 'order' },
        { title: 'Prix mis à jour', message: 'Les prix du marché d\'Abidjan ont été actualisés', type: 'info' },
        { title: 'Transport en retard', message: 'Le transport #T-123 accuse 30min de retard', type: 'warning' },
        { title: 'Nouveau client', message: 'Marie Traoré a créé son compte acheteur', type: 'user' }
      ]
      
      const randomMessage = messages[Math.floor(Math.random() * messages.length)]
      addNotification(randomMessage)
    }
  }, 30000) // Toutes les 30 secondes
})

const addNotification = (notificationData) => {
  const newNotification = {
    id: Date.now(),
    created_at: new Date(),
    read: false,
    ...notificationData
  }
  
  notifications.value.unshift(newNotification)
  
  // Limiter à 50 notifications max
  if (notifications.value.length > 50) {
    notifications.value = notifications.value.slice(0, 50)
  }
  
  // Toast notification
  showToast(newNotification)
}

const showToast = (notification) => {
  const container = document.getElementById('notifications-container')
  if (!container) return

  const toast = document.createElement('div')
  toast.className = `
    max-w-sm w-full bg-white shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 
    transform transition-all duration-300 ease-in-out notification-slide-in
    border-l-4 ${
      notification.type === 'success' ? 'border-green-500' :
      notification.type === 'error' ? 'border-red-500' :
      notification.type === 'warning' ? 'border-orange-500' :
      'border-blue-500'
    }
  `
  
  toast.innerHTML = `
    <div class="p-4">
      <div class="flex items-start">
        <div class="flex-shrink-0">
          <div class="w-8 h-8 rounded-full flex items-center justify-center ${
            notification.type === 'success' ? 'bg-green-100 text-green-600' :
            notification.type === 'error' ? 'bg-red-100 text-red-600' :
            notification.type === 'warning' ? 'bg-orange-100 text-orange-600' :
            'bg-blue-100 text-blue-600'
          }">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
          </div>
        </div>
        <div class="ml-3 w-0 flex-1">
          <p class="text-sm font-medium text-gray-900">${notification.title}</p>
          <p class="mt-1 text-sm text-gray-500">${notification.message}</p>
        </div>
        <div class="ml-4 flex-shrink-0 flex">
          <button class="bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500" onclick="this.parentElement.parentElement.parentElement.parentElement.remove()">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
            </svg>
          </button>
        </div>
      </div>
    </div>
  `

  container.appendChild(toast)

  // Auto-remove après 5 secondes
  setTimeout(() => {
    if (toast.parentNode) {
      toast.classList.add('notification-slide-out')
      setTimeout(() => toast.remove(), 300)
    }
  }, 5000)
}

// Exposer pour utilisation globale
defineExpose({
  addNotification,
  showToast,
  unreadCount
})
</script>