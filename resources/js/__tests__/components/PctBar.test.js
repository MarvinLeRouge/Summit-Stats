import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import PctBar from '@/components/PctBar.vue';

describe('PctBar', () => {
    it('renders the label', () => {
        const wrapper = mount(PctBar, { props: { label: '5–15%', value: 40, km: '4.9 km' } });
        expect(wrapper.text()).toContain('5–15%');
    });

    it('renders the percentage and distance', () => {
        const wrapper = mount(PctBar, { props: { label: '5–15%', value: 40, km: '4.9 km' } });
        expect(wrapper.text()).toContain('40%');
        expect(wrapper.text()).toContain('4.9 km');
    });

    it('applies the provided color class to the filled bar', () => {
        const wrapper = mount(PctBar, {
            props: { label: 'Flat', value: 60, km: '7.2 km', color: 'bg-green-400' },
        });
        const bar = wrapper.find('.bg-green-400');
        expect(bar.exists()).toBe(true);
    });

    it('uses bg-blue-400 as the default color', () => {
        const wrapper = mount(PctBar, { props: { label: 'Flat', value: 60, km: '7.2 km' } });
        expect(wrapper.find('.bg-blue-400').exists()).toBe(true);
    });

    it('caps bar width at 100% when value exceeds 100', () => {
        const wrapper = mount(PctBar, { props: { label: 'Over', value: 150, km: '1.0 km' } });
        const bar = wrapper.find('.bg-blue-400');
        expect(bar.attributes('style')).toContain('width: 100%');
    });

    it('sets bar width proportionally for normal values', () => {
        const wrapper = mount(PctBar, { props: { label: 'Mid', value: 55, km: '2.0 km' } });
        const bar = wrapper.find('.bg-blue-400');
        expect(bar.attributes('style')).toContain('width: 55%');
    });

    it('renders with default value of 0 when value is omitted', () => {
        const wrapper = mount(PctBar, { props: { label: 'Empty', km: '0.0 km' } });
        expect(wrapper.text()).toContain('0%');
    });
});
