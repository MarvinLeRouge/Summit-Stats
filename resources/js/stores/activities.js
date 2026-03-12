import { defineStore } from 'pinia';
import axios from 'axios';

export const useActivitiesStore = defineStore('activities', {
    state: () => ({
        activities: [],
        total:      0,
        loading:    false,
        filters: {
            type:        null,
            environment: null,
            date_from:   null,
            date_to:     null,
        },
    }),

    actions: {
        async fetch(page = 1) {
            this.loading = true;
            try {
                const { data } = await axios.get('/activities', {
                    params: { ...this.filters, page },
                });
                this.activities = data.data.data;
                this.total      = data.data.total;
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