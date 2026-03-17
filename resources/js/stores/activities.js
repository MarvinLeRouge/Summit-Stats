import { defineStore } from 'pinia';
import axios from 'axios';

export const useActivitiesStore = defineStore('activities', {
    state: () => ({
        activities: [],
        total:      0,
        currentPage: 1,
        lastPage:    1,
        loading:    false,
    }),

    actions: {
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

        async destroy(id) {
            await axios.delete(`/activities/${id}`);
            this.activities = this.activities.filter(a => a.id !== id);
        },
    },
});