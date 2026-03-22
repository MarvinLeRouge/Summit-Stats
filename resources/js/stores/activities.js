import { defineStore } from 'pinia';
import axios from 'axios';

/**
 * Pinia store for the activity list.
 *
 * Manages pagination state and exposes actions to fetch or delete activities
 * from the REST API. Filters are passed as query parameters to the fetch action.
 */
export const useActivitiesStore = defineStore('activities', {
    state: () => ({
        /** @type {Array<Object>} Current page of activities. */
        activities: [],
        /** @type {number} Total number of activities matching the current filters. */
        total:      0,
        /** @type {number} Current page number. */
        currentPage: 1,
        /** @type {number} Last available page number. */
        lastPage:    1,
        /** @type {boolean} True while an API request is in progress. */
        loading:    false,
    }),

    actions: {
        /**
         * Fetches a paginated list of activities from the API.
         *
         * Updates activities, total, currentPage, and lastPage on success.
         *
         * @param {Object} [filters={}] - Query parameters (type, environment, date_from, date_to, page).
         * @returns {Promise<void>}
         */
        async fetch(filters = {}) {
            this.loading = true;
            try {
                const { data } = await axios.get('/activities', {
                    params: filters,
                });
                this.activities  = data.data.data;
                this.total       = data.data.total;
                this.currentPage = data.data.current_page;
                this.lastPage    = data.data.last_page;
            } finally {
                this.loading = false;
            }
        },

        /**
         * Deletes an activity by ID and removes it from the local list.
         *
         * @param {number} id - Activity ID to delete.
         * @returns {Promise<void>}
         */
        async destroy(id) {
            await axios.delete(`/activities/${id}`);
            this.activities = this.activities.filter(a => a.id !== id);
        },
    },
});
