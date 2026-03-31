import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount } from '@vue/test-utils';
import Toast from '@/components/Toast.vue';

describe('Toast', () => {
    beforeEach(() => {
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    it('is hidden by default', () => {
        const wrapper = mount(Toast);
        expect(wrapper.find('[class*="fixed"]').exists()).toBe(false);
    });

    it('becomes visible after show() is called', async () => {
        const wrapper = mount(Toast);
        wrapper.vm.show('Operation successful');
        await wrapper.vm.$nextTick();
        expect(wrapper.text()).toContain('Operation successful');
    });

    it('applies success styles by default', async () => {
        const wrapper = mount(Toast);
        wrapper.vm.show('Done');
        await wrapper.vm.$nextTick();
        expect(wrapper.find('.bg-green-600').exists()).toBe(true);
    });

    it('applies error styles when type is "error"', async () => {
        const wrapper = mount(Toast);
        wrapper.vm.show('Something went wrong', 'error');
        await wrapper.vm.$nextTick();
        expect(wrapper.find('.bg-red-600').exists()).toBe(true);
    });

    it('hides automatically after the specified duration', async () => {
        const wrapper = mount(Toast);
        wrapper.vm.show('Fading out', 'success', 2000);
        await wrapper.vm.$nextTick();
        expect(wrapper.find('.bg-green-600').exists()).toBe(true);

        vi.advanceTimersByTime(2000);
        await wrapper.vm.$nextTick();
        expect(wrapper.find('.bg-green-600').exists()).toBe(false);
    });

    it('uses 3000 ms as the default duration', async () => {
        const wrapper = mount(Toast);
        wrapper.vm.show('Persisting');
        await wrapper.vm.$nextTick();

        vi.advanceTimersByTime(2999);
        await wrapper.vm.$nextTick();
        expect(wrapper.find('.bg-green-600').exists()).toBe(true);

        vi.advanceTimersByTime(1);
        await wrapper.vm.$nextTick();
        expect(wrapper.find('.bg-green-600').exists()).toBe(false);
    });

    it('exposes the show method via defineExpose', () => {
        const wrapper = mount(Toast);
        expect(typeof wrapper.vm.show).toBe('function');
    });
});
