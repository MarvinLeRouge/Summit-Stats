<template>
    <div class="max-w-5xl mx-auto px-4 py-8">
        <!-- Retour -->
        <button @click="$router.push('/activities')" class="text-sm text-blue-600 hover:underline mb-6 flex items-center gap-1">
            ← Retour aux sorties
        </button>

        <div v-if="loading" class="text-center text-gray-400 py-12">Chargement...</div>

        <div v-else-if="activity">
            <!-- En-tête -->
            <div class="flex items-start justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">{{ activity.title }}</h1>
                    <p class="text-gray-500 text-sm mt-1 capitalize">
                        {{ activity.type }} · {{ activity.environment }} · {{ formatDate(activity.date) }}
                    </p>
                    <p v-if="activity.comment" class="text-gray-600 text-sm mt-2 italic">{{ activity.comment }}</p>
                </div>
                <button
                    @click="confirmDelete"
                    class="text-red-500 hover:text-red-700 text-sm"
                >
                    Supprimer
                </button>
            </div>

            <!-- Stats globales -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
                <StatCard label="Distance"     :value="formatDistance(activity.distance_km)" />
                <StatCard label="D+"           :value="formatElevation(activity.elevation_gain)" />
                <StatCard label="D-"           :value="formatElevation(activity.elevation_loss)" />
                <StatCard label="Durée"        :value="formatDuration(activity.duration_seconds)" />
                <StatCard label="Vit. moy."    :value="formatSpeed(activity.avg_speed_kmh)" />
                <StatCard label="Vit. asc."    :value="activity.avg_ascent_speed_mh ? `${Math.round(activity.avg_ascent_speed_mh)} m/h` : '--'" />
            </div>

            <!-- Tableau des segments -->
            <div class="bg-white rounded-lg shadow-sm border overflow-hidden mb-8">
                <div class="px-4 py-3 border-b bg-gray-50">
                    <h2 class="font-semibold text-gray-700 text-sm">Segments</h2>
                </div>
                <table class="w-full text-sm">
                    <thead class="border-b">
                        <tr>
                            <th class="text-left px-4 py-2 font-medium text-gray-600">#</th>
                            <th class="text-left px-4 py-2 font-medium text-gray-600">Type</th>
                            <th class="text-left px-4 py-2 font-medium text-gray-600">Pente</th>
                            <th class="text-right px-4 py-2 font-medium text-gray-600">Distance</th>
                            <th class="text-right px-4 py-2 font-medium text-gray-600">Dénivelé</th>
                            <th class="text-right px-4 py-2 font-medium text-gray-600">Durée</th>
                            <th class="text-right px-4 py-2 font-medium text-gray-600">Vit. moy.</th>
                            <th class="text-right px-4 py-2 font-medium text-gray-600">Vit. asc.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="segment in activity.segments" :key="segment.id" class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-gray-500">{{ segment.order }}</td>
                            <td class="px-4 py-2">
                                <span :class="typeClass(segment.type)" class="px-2 py-0.5 rounded-full text-xs font-medium">
                                    {{ segment.type }}
                                </span>
                            </td>
                            <td class="px-4 py-2 text-gray-600">{{ slopeLabel(segment.slope_class) }}</td>
                            <td class="px-4 py-2 text-right text-gray-600">{{ formatDistance(segment.distance_km) }}</td>
                            <td class="px-4 py-2 text-right" :class="segment.elevation_delta >= 0 ? 'text-green-600' : 'text-red-500'">
                                {{ segment.elevation_delta >= 0 ? '+' : '' }}{{ segment.elevation_delta }} m
                            </td>
                            <td class="px-4 py-2 text-right text-gray-600">{{ formatDuration(segment.duration_seconds) }}</td>
                            <td class="px-4 py-2 text-right text-gray-600">{{ formatSpeed(segment.avg_speed_kmh) }}</td>
                            <td class="px-4 py-2 text-right text-gray-600">
                                {{ segment.avg_ascent_speed_mh ? `${Math.round(segment.avg_ascent_speed_mh)} m/h` : '--' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { formatDate, formatDistance, formatDuration, formatElevation, formatSpeed } from '@/helpers/format';

const route    = useRoute();
const router   = useRouter();
const activity = ref(null);
const loading  = ref(true);

const slopeLabels = {
    lt5:   'Plat < 5%',
    '5_15':  '5–15%',
    '15_25': '15–25%',
    '25_35': '25–35%',
    gt35:  '> 35%',
};

const slopeLabel = (key) => slopeLabels[key] ?? key;

const typeClass = (type) => ({
    'bg-green-100 text-green-700': type === 'montee',
    'bg-blue-100 text-blue-700':   type === 'descente',
    'bg-gray-100 text-gray-600':   type === 'plat',
});

onMounted(async () => {
    try {
        const { data } = await axios.get(`/activities/${route.params.id}`);
        activity.value = data.data;
    } finally {
        loading.value = false;
    }
});

const confirmDelete = async () => {
    if (!confirm(`Supprimer "${activity.value.title}" ?`)) return;
    await axios.delete(`/activities/${activity.value.id}`);
    router.push('/activities');
};
</script>