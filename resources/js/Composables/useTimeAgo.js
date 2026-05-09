import { ref } from 'vue';

/**
 * Composable pour afficher le temps relatif (il y a X minutes, etc.)
 */
export function useTimeAgo() {
    const locale = ref('fr');

    function formatTimeAgo(dateString) {
        if (!dateString) return '';
        
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now - date;
        const diffSeconds = Math.floor(diffMs / 1000);
        const diffMinutes = Math.floor(diffSeconds / 60);
        const diffHours = Math.floor(diffMinutes / 60);
        const diffDays = Math.floor(diffHours / 24);
        const diffMonths = Math.floor(diffDays / 30);
        const diffYears = Math.floor(diffDays / 365);

        if (locale.value === 'fr') {
            if (diffYears > 0) return `il y a ${diffYears} an${diffYears > 1 ? 's' : ''}`;
            if (diffMonths > 0) return `il y a ${diffMonths} mois`;
            if (diffDays > 0) return `il y a ${diffDays} jour${diffDays > 1 ? 's' : ''}`;
            if (diffHours > 0) return `il y a ${diffHours} heure${diffHours > 1 ? 's' : ''}`;
            if (diffMinutes > 0) return `il y a ${diffMinutes} minute${diffMinutes > 1 ? 's' : ''}`;
            return 'il y a quelques secondes';
        } else {
            if (diffYears > 0) return `${diffYears} year${diffYears > 1 ? 's' : ''} ago`;
            if (diffMonths > 0) return `${diffMonths} month${diffMonths > 1 ? 's' : ''} ago`;
            if (diffDays > 0) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
            if (diffHours > 0) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
            if (diffMinutes > 0) return `${diffMinutes} minute${diffMinutes > 1 ? 's' : ''} ago`;
            return 'just now';
        }
    }

    function setLocale(newLocale) {
        locale.value = newLocale;
    }

    return {
        formatTimeAgo,
        setLocale,
        locale
    };
}
