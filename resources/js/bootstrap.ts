import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Handle chunk load errors (e.g. after a deployment/rebuild)
// Vite will dispatch this event when a dynamic import fails.
window.addEventListener('vite:preloadError', (event) => {
    window.location.reload();
});
