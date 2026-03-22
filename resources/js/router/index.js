import { createRouter, createWebHistory } from 'vue-router';

const router = createRouter({
    history: createWebHistory(),
    routes: [
        {
            path: '/',
            component: () => import('@/pages/Dashboard.vue'),
            meta: { requiresAuth: true },
        },
        {
            path: '/activities',
            component: () => import('@/pages/Activities.vue'),
            meta: { requiresAuth: true },
        },
        {
            path: '/activities/:id',
            component: () => import('@/pages/ActivityDetail.vue'),
            meta: { requiresAuth: true },
        },
        {
            path: '/login',
            component: () => import('@/pages/Login.vue'),
        },
    ],
});

/**
 * Global navigation guard — redirects unauthenticated users to /login.
 *
 * Reads the Sanctum token from localStorage. Routes with `meta.requiresAuth`
 * are protected; all others (e.g. /login) are publicly accessible.
 *
 * @param {import('vue-router').RouteLocationNormalized} to - Target route.
 * @returns {string|undefined} '/login' redirect path, or undefined to proceed.
 */
router.beforeEach((to) => {
    const token = localStorage.getItem('sanctum_token');
    if (to.meta.requiresAuth && !token) return '/login';
});

export default router;
