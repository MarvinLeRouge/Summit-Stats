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

router.beforeEach((to) => {
    const token = localStorage.getItem('sanctum_token');
    if (to.meta.requiresAuth && !token) return '/login';
});

export default router;