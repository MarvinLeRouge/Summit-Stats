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
            <select v-model="store.filters.type" class="border rounded px-3 py-1.5 text-sm" @change="store.fetch()">
                <option :value="null">Tous les types</option>
                <option value="randonnee">Randonnée</option>
                <option value="trail">Trail</option>
            </select>

            <select v-model="store.filters.environment" class="border rounded px-3 py-1.5 text-sm" @change="store.fetch()">
                <option :value="null">Tous les milieux</option>
                <option value="urbain">Urbain</option>
                <option value="campagne">Campagne</option>
                <option value="montagne">Montagne</option>
            </select>

            <input
                v-model="store.filters.date_from"
                type="date"
                class="border rounded px-3 py-1.5 text-sm"
                @change="store.fetch()"
            />
            <input
                v-model="store.filters.date_to"
                type="date"
                class="border rounded px-3 py-1.5 text-sm"
                @change="store.fetch()"
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
                        <td class="px-4 py-3 text-right text-gray-600">{{ formatElevation(activity.elevation_gain) }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ formatDuration(activity.duration_seconds) }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ formatSpeed(activity.avg_speed_kmh) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <GpxUploadForm v-if="showForm" @close="showForm = false" @uploaded="onUploaded" />
    <Toast ref="toast" />
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useActivitiesStore } from '@/stores/activities';
import { formatDate, formatDistance, formatDuration, formatElevation, formatSpeed } from '@/helpers/format';
import Toast from '@/components/Toast.vue';

const toast = ref(null);
const store    = useActivitiesStore();
const showForm = ref(false);

onMounted(() => store.fetch());

const resetFilters = () => {
    store.filters = { type: null, environment: null, date_from: null, date_to: null };
    store.fetch();
};

import GpxUploadForm from '@/components/GpxUploadForm.vue';

const onUploaded = () => {
    showForm.value = false;
    store.fetch();
    toast.value?.show('Sortie importée avec succès !');    
};
</script>