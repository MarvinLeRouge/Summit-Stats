<template>
    <div class="max-w-5xl mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Progression</h1>

        <!-- Filtres -->
        <div class="bg-white rounded-lg shadow-sm border p-4 mb-6">
            <div class="flex flex-wrap gap-4 mb-4">
                <!-- Métrique -->
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Métrique</label>
                    <select v-model="filters.metric" class="border rounded px-3 py-1.5 text-sm">
                        <option value="avg_ascent_speed_mh">Vitesse ascensionnelle</option>
                        <option value="avg_speed_kmh">Vitesse moyenne</option>
                        <option value="elevation_gain">Dénivelé positif</option>
                        <option value="distance_km">Distance</option>
                    </select>
                </div>

                <!-- Type -->
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Type</label>
                    <select v-model="filters.type" class="border rounded px-3 py-1.5 text-sm">
                        <option :value="null">Tous</option>
                        <option value="randonnee">Randonnée</option>
                        <option value="trail">Trail</option>
                    </select>
                </div>

                <!-- Milieu -->
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Milieu</label>
                    <select v-model="filters.environment" class="border rounded px-3 py-1.5 text-sm">
                        <option :value="null">Tous</option>
                        <option value="urbain">Urbain</option>
                        <option value="campagne">Campagne</option>
                        <option value="montagne">Montagne</option>
                    </select>
                </div>

                <!-- Période -->
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Du</label>
                    <input v-model="filters.date_from" type="date" class="border rounded px-3 py-1.5 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Au</label>
                    <input v-model="filters.date_to" type="date" class="border rounded px-3 py-1.5 text-sm" />
                </div>
            </div>

            <!-- Filtres pente -->
            <div class="flex flex-wrap gap-4 items-end border-t pt-4">
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Pente ≥</label>
                    <input v-model.number="filters.slope_min" type="number" min="0" max="100" placeholder="0"
                        class="border rounded px-3 py-1.5 text-sm w-24" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Pente ≤</label>
                    <input v-model.number="filters.slope_max" type="number" min="0" max="100" placeholder="100"
                        class="border rounded px-3 py-1.5 text-sm w-24" />
                </div>
                <button @click="resetFilters" class="text-sm text-gray-500 hover:text-gray-700 underline">
                    Réinitialiser
                </button>
            </div>
        </div>

        <!-- Stats résumées -->
        <div v-if="stats.length > 0" class="grid grid-cols-3 gap-4 mb-6">
            <StatCard label="Sorties" :value="String(stats.length)" />
            <StatCard label="Moyenne" :value="`${avg} ${meta.unit}`" />
            <StatCard label="Maximum" :value="`${max} ${meta.unit}`" />
        </div>

        <!-- Graphe -->
        <div class="bg-white rounded-lg shadow-sm border p-4">
            <div v-if="loading" class="h-64 flex items-center justify-center text-gray-400">
                Chargement...
            </div>
            <div v-else-if="stats.length === 0" class="h-64 flex items-center justify-center text-gray-400">
                Aucune donnée pour ces critères.
            </div>
            <ProgressionChart v-else :data="chartData" :unit="meta.unit" />
        </div>
    </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import axios from 'axios';
import StatCard from '@/components/StatCard.vue';
import ProgressionChart from '@/components/ProgressionChart.vue';

const loading = ref(false);
const stats   = ref([]);
const meta    = ref({ metric: '', unit: '', count: 0 });

const filters = ref({
    metric:      'avg_ascent_speed_mh',
    type:        null,
    environment: null,
    date_from:   null,
    date_to:     null,
    slope_min:   null,
    slope_max:   null,
});

const fetchStats = async () => {
    loading.value = true;
    try {
        const params = Object.fromEntries(
            Object.entries(filters.value).filter(([, v]) => v !== null && v !== '')
        );
        const { data } = await axios.get('/stats', { params });
        stats.value = data.data;
        meta.value  = data.meta;
    } catch {
        stats.value = [];
    } finally {
        loading.value = false;
    }
};

const avg = computed(() => {
    if (!stats.value.length) return '--';
    return (stats.value.reduce((s, d) => s + d.value, 0) / stats.value.length).toFixed(1);
});

const max = computed(() => {
    if (!stats.value.length) return '--';
    return Math.max(...stats.value.map(d => d.value)).toFixed(1);
});

const chartData = computed(() => ({
    labels: stats.value.map(d => d.date),
    datasets: [{
        label: meta.value.metric,
        data:  stats.value.map(d => ({ x: d.date, y: d.value })),
        borderColor:     '#3B82F6',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        tension:          0.3,
        fill:             true,
        pointRadius:      5,
        pointHoverRadius: 7,
    }],
}));

// Debounce pour les inputs numériques
let debounceTimer = null;
watch(filters, () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(fetchStats, 300);
}, { deep: true });

fetchStats();

const resetFilters = () => {
    filters.value = {
        metric:      'avg_ascent_speed_mh',
        type:        null,
        environment: null,
        date_from:   null,
        date_to:     null,
        slope_min:   null,
        slope_max:   null,
    };
};
</script>