import { createApp } from 'vue';
import { createPinia } from 'pinia';
import router from './router';
import App from './App.vue';
import { initErrorTracking } from './observability';
import './bootstrap';
import '../css/app.css';

const app = createApp(App);
initErrorTracking(app);
app.use(createPinia());
app.use(router);
app.mount('#app');
// @see resources/js/bootstrap.js
