import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createRouter, createWebHistory } from 'vue-router';
import NavBar from '@/components/NavBar.vue';
import axios from 'axios';

vi.mock('axios', () => ({
    default: {
        defaults: { headers: { common: {} } },
    },
}));

/** Creates a minimal router with the routes NavBar links to. */
const makeRouter = (currentPath = '/') => {
    const router = createRouter({
        history: createWebHistory(),
        routes: [
            { path: '/', component: { template: '<div />' } },
            { path: '/activities', component: { template: '<div />' } },
            { path: '/login', component: { template: '<div />' } },
        ],
    });
    router.push(currentPath);
    return router;
};

describe('NavBar', () => {
    beforeEach(() => {
        localStorage.clear();
        axios.defaults.headers.common = {};
    });

    it('renders the application title', async () => {
        const router = makeRouter();
        await router.isReady();
        const wrapper = mount(NavBar, { global: { plugins: [router] } });
        expect(wrapper.text()).toContain('Summit Stats');
    });

    it('renders the Dashboard and Sorties navigation links', async () => {
        const router = makeRouter();
        await router.isReady();
        const wrapper = mount(NavBar, { global: { plugins: [router] } });
        expect(wrapper.text()).toContain('Dashboard');
        expect(wrapper.text()).toContain('Sorties');
    });

    it('renders a logout button', async () => {
        const router = makeRouter();
        await router.isReady();
        const wrapper = mount(NavBar, { global: { plugins: [router] } });
        expect(wrapper.find('button').text()).toContain('Déconnexion');
    });

    it('clears localStorage and axios auth header on logout', async () => {
        localStorage.setItem('sanctum_token', 'tok-123');
        axios.defaults.headers.common['Authorization'] = 'Bearer tok-123';

        const router = makeRouter();
        await router.isReady();
        const wrapper = mount(NavBar, { global: { plugins: [router] } });

        await wrapper.find('button').trigger('click');

        expect(localStorage.getItem('sanctum_token')).toBeNull();
        expect(axios.defaults.headers.common['Authorization']).toBeUndefined();
    });

    it('redirects to /login after logout', async () => {
        const router = makeRouter('/');
        await router.isReady();
        const wrapper = mount(NavBar, { global: { plugins: [router] } });

        await wrapper.find('button').trigger('click');
        await flushPromises();

        expect(router.currentRoute.value.path).toBe('/login');
    });
});
