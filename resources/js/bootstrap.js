import axios from 'axios';
window.axios = axios;

// Définir la baseURL dynamiquement selon l'env
// window.axios.defaults.baseURL = import.meta.env.VITE_APP_URL; // force HTTPS

// Headers par défaut
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';

const token = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute('content');

if (token) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
} else {
    console.error('CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token');
}
