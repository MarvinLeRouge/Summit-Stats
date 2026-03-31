import { describe, it, expect, beforeEach, vi } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import axios from 'axios';
import { useActivitiesStore } from '@/stores/activities.js';

vi.mock('axios');

/** @returns {Object} A minimal activity payload matching the API shape. */
const makeActivity = (id) => ({ id, name: `Activity ${id}` });

/** @returns {Object} A minimal paginated API response. */
const makePaginatedResponse = (items, { total = items.length, currentPage = 1, lastPage = 1 } = {}) => ({
    data: {
        data: {
            data:         items,
            total,
            current_page: currentPage,
            last_page:    lastPage,
        },
    },
});

describe('useActivitiesStore', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    describe('initial state', () => {
        it('has empty activities list', () => {
            const store = useActivitiesStore();
            expect(store.activities).toEqual([]);
        });

        it('has loading set to false', () => {
            const store = useActivitiesStore();
            expect(store.loading).toBe(false);
        });

        it('has total and page counters set to defaults', () => {
            const store = useActivitiesStore();
            expect(store.total).toBe(0);
            expect(store.currentPage).toBe(1);
            expect(store.lastPage).toBe(1);
        });
    });

    describe('fetch', () => {
        it('populates activities from the API response', async () => {
            const store = useActivitiesStore();
            const items = [makeActivity(1), makeActivity(2)];
            axios.get.mockResolvedValue(makePaginatedResponse(items, { total: 2, lastPage: 1 }));

            await store.fetch();

            expect(store.activities).toEqual(items);
            expect(store.total).toBe(2);
            expect(store.currentPage).toBe(1);
            expect(store.lastPage).toBe(1);
        });

        it('sets loading to true during the request and false after', async () => {
            const store = useActivitiesStore();
            let capturedLoading;
            axios.get.mockImplementation(() => {
                capturedLoading = store.loading;
                return Promise.resolve(makePaginatedResponse([]));
            });

            await store.fetch();

            expect(capturedLoading).toBe(true);
            expect(store.loading).toBe(false);
        });

        it('sets loading to false even when the request fails', async () => {
            const store = useActivitiesStore();
            axios.get.mockRejectedValue(new Error('Network error'));

            await expect(store.fetch()).rejects.toThrow('Network error');
            expect(store.loading).toBe(false);
        });

        it('forwards filters as query params', async () => {
            const store = useActivitiesStore();
            axios.get.mockResolvedValue(makePaginatedResponse([]));

            await store.fetch({ type: 'trail', page: 2 });

            expect(axios.get).toHaveBeenCalledWith('/activities', {
                params: { type: 'trail', page: 2 },
            });
        });

        it('calls the API with empty params when no filters are provided', async () => {
            const store = useActivitiesStore();
            axios.get.mockResolvedValue(makePaginatedResponse([]));

            await store.fetch();

            expect(axios.get).toHaveBeenCalledWith('/activities', { params: {} });
        });
    });

    describe('destroy', () => {
        it('removes the deleted activity from the local list', async () => {
            const store = useActivitiesStore();
            store.activities = [makeActivity(1), makeActivity(2), makeActivity(3)];
            axios.delete.mockResolvedValue({});

            await store.destroy(2);

            expect(store.activities.map(a => a.id)).toEqual([1, 3]);
        });

        it('calls DELETE on the correct endpoint', async () => {
            const store = useActivitiesStore();
            store.activities = [makeActivity(5)];
            axios.delete.mockResolvedValue({});

            await store.destroy(5);

            expect(axios.delete).toHaveBeenCalledWith('/activities/5');
        });

        it('does not modify the list when the API call fails', async () => {
            const store = useActivitiesStore();
            store.activities = [makeActivity(1), makeActivity(2)];
            axios.delete.mockRejectedValue(new Error('Server error'));

            await expect(store.destroy(1)).rejects.toThrow('Server error');
            expect(store.activities).toHaveLength(2);
        });
    });
});
