<template>
    <div class="max-w-6xl mx-auto px-4 py-8">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Mes sorties</h1>
            <button
                class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700"
                @click="showForm = true"
            >
                + Importer une sortie
            </button>
        </div>

        <!-- Filtres -->
        <div class="bg-white rounded-lg shadow-sm border p-4 mb-6 flex flex-wrap gap-4">
            <select
                :value="filters.type"
                class="border rounded px-3 py-1.5 text-sm"
                @change="setFilter('type', $event.target.value || null)"
            >
                <option value="">Tous les types</option>
                <option value="randonnee">Randonnée</option>
                <option value="trail">Trail</option>
            </select>

            <select
                :value="filters.environment"
                class="border rounded px-3 py-1.5 text-sm"
                @change="setFilter('environment', $event.target.value || null)"
            >
                <option value="">Tous les milieux</option>
                <option value="urbain">Urbain</option>
                <option value="campagne">Campagne</option>
                <option value="montagne">Montagne</option>
            </select>

            <input
                :value="filters.date_from"
                type="date"
                class="border rounded px-3 py-1.5 text-sm"
                @change="setFilter('date_from', $event.target.value || null)"
            />
            <input
                :value="filters.date_to"
                type="date"
                class="border rounded px-3 py-1.5 text-sm"
                @change="setFilter('date_to', $event.target.value || null)"
            />

            <button class="text-sm text-gray-500 hover:text-gray-700 underline" @click="resetFilters">
                Réinitialiser
            </button>
        </div>

        <!-- Tableau -->
        <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
            <div v-if="store.loading" class="p-8 text-center text-gray-400">Chargement...</div>

            <div v-else-if="store.activities.length === 0" class="p-8 text-center text-gray-400">
                Aucune sortie trouvée. Importez votre première trace GPX !
            </div>

            <table v-else class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Titre</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Type</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Milieu</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Date</th>
                        <th class="text-right px-4 py-3 font-medium text-gray-600">Distance</th>
                        <th class="text-right px-4 py-3 font-medium text-gray-600">D+</th>
                        <th class="text-right px-4 py-3 font-medium text-gray-600">Durée</th>
                        <th class="text-right px-4 py-3 font-medium text-gray-600">Vit. moy.</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr
                        v-for="activity in store.activities"
                        :key="activity.id"
                        class="hover:bg-gray-50 cursor-pointer"
                        @click="$router.push(`/activities/${activity.id}`)"
                    >
                        <td class="px-4 py-3 font-medium text-blue-600">{{ activity.title }}</td>
                        <td class="px-4 py-3 text-gray-600 capitalize">{{ activity.type }}</td>
                        <td class="px-4 py-3 text-gray-600 capitalize">{{ activity.environment }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ formatDate(activity.date) }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ formatDistance(activity.distance_km) }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">
                            {{ formatElevation(activity.elevation_gain) }}
                        </td>
                        <td class="px-4 py-3 text-right text-gray-600">
                            {{ formatDuration(activity.duration_seconds) }}
                        </td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ formatSpeed(activity.avg_speed_kmh) }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Pagination -->
            <div
                v-if="store.lastPage > 1"
                class="flex items-center justify-between px-4 py-3 border-t text-sm text-gray-600"
            >
                <span
                    >{{ store.total }} sortie{{ store.total > 1 ? 's' : '' }} — page {{ store.currentPage }} /
                    {{ store.lastPage }}</span
                >
                <div class="flex gap-2">
                    <button
                        :disabled="store.currentPage <= 1"
                        class="px-3 py-1 border rounded hover:bg-gray-50 disabled:opacity-40"
                        @click="goToPage(store.currentPage - 1)"
                    >
                        ← Précédente
                    </button>
                    <button
                        :disabled="store.currentPage >= store.lastPage"
                        class="px-3 py-1 border rounded hover:bg-gray-50 disabled:opacity-40"
                        @click="goToPage(store.currentPage + 1)"
                    >
                        Suivante →
                    </button>
                </div>
            </div>
        </div>
    </div>

    <GpxUploadForm v-if="showForm" @close="showForm = false" @uploaded="onUploaded" />
    <Toast ref="toast" />
</template>

<script setup>
/**
 * Activity list page with URL-driven filters and pagination.
 *
 * Filter state is stored in the URL query string (type, environment, date_from, date_to, page),
 * allowing browser history navigation. The Pinia store is re-fetched on every route query change.
 * Includes the GPX upload modal and a success Toast notification.
 */
import { computed, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useActivitiesStore } from '@/stores/activities';
import { formatDate, formatDistance, formatDuration, formatElevation, formatSpeed } from '@/helpers/format';
import GpxUploadForm from '@/components/GpxUploadForm.vue';
import Toast from '@/components/Toast.vue';

const route = useRoute();
const router = useRouter();
const store = useActivitiesStore();
const toast = ref(null);
const showForm = ref(false);

// Filtres lus depuis l'URL
const filters = computed(() => ({
    type: route.query.type || null,
    environment: route.query.environment || null,
    date_from: route.query.date_from || null,
    date_to: route.query.date_to || null,
    page: parseInt(route.query.page) || 1,
}));

// Recharge les données à chaque changement d'URL
watch(
    () => route.query,
    () => {
        store.fetch(filters.value);
    },
    { immediate: true }
);

/**
 * Updates a single filter in the URL query and resets pagination to page 1.
 *
 * @param {string} key - Query parameter name (e.g. 'type', 'environment').
 * @param {string|null} value - New value, or null/empty to remove the parameter.
 */
const setFilter = (key, value) => {
    router.push({
        query: {
            ...route.query,
            [key]: value || undefined,
            page: undefined, // reset page à 1 sur changement de filtre
        },
    });
};

/**
 * Navigates to the given page number by updating the URL query.
 *
 * @param {number} page - Target page number.
 */
const goToPage = (page) => {
    router.push({
        query: { ...route.query, page: page > 1 ? page : undefined },
    });
};

const resetFilters = () => {
    router.push({ query: {} });
};

/** Closes the upload form, refreshes the list, and shows a success toast. */
const onUploaded = () => {
    showForm.value = false;
    store.fetch(filters.value);
    toast.value?.show('Sortie importée avec succès !');
};
</script>
