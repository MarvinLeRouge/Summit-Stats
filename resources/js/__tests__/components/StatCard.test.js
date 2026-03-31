import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import StatCard from '@/components/StatCard.vue';

describe('StatCard', () => {
    it('renders the label', () => {
        const wrapper = mount(StatCard, { props: { label: 'Distance', value: '12.3 km' } });
        expect(wrapper.text()).toContain('Distance');
    });

    it('renders the primary value', () => {
        const wrapper = mount(StatCard, { props: { label: 'Distance', value: '12.3 km' } });
        expect(wrapper.text()).toContain('12.3 km');
    });

    it('does not render the sub element when sub prop is absent', () => {
        const wrapper = mount(StatCard, { props: { label: 'Distance', value: '12.3 km' } });
        const paragraphs = wrapper.findAll('p');
        expect(paragraphs).toHaveLength(2);
    });

    it('renders the sub value when provided', () => {
        const wrapper = mount(StatCard, {
            props: { label: 'Durée', value: '2h05', sub: '1h30 en mouvement' },
        });
        expect(wrapper.text()).toContain('1h30 en mouvement');
        const paragraphs = wrapper.findAll('p');
        expect(paragraphs).toHaveLength(3);
    });
});
