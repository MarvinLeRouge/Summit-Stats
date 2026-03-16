<template>
    <div class="max-w-5xl mx-auto px-4 py-8">
        <button class="text-sm text-blue-600 hover:underline mb-6 flex items-center gap-1" @click="$router.push('/activities')">
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
                <div class="flex gap-3">
                    <button :disabled="recalculating" class="text-sm text-blue-600 hover:text-blue-800 disabled:opacity-50"
                        @click="recalculate">
                        {{ recalculating ? 'Recalcul...' : '↺ Recalculer' }}
                    </button>
                    <button class="text-sm text-red-500 hover:text-red-700" @click="confirmDelete">
                        Supprimer
                    </button>
                </div>
            </div>

            <!-- Stats globales -->
            <div class="mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wide">Général</div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <StatCard label="Distance" :value="formatDistance(activity.distance_km)" />
                <StatCard label="Dénivelé" :value="`+${activity.elevation_gain} m / -${activity.elevation_loss} m`" />
                <StatCard label="Durée" :value="`TTL : ${formatDuration(activity.duration_seconds)}`" :sub="`MVT : ${formatDuration(activity.moving_duration_seconds)}`" />
                <StatCard label="Vitesse moy." :value="`TTL : ${formatSpeed(activity.avg_speed_kmh)}`" :sub="`MVT : ${formatSpeed(activity.avg_speed_moving_kmh)}`" />
            </div>

            <!-- Vitesses ascensionnelles -->
            <div class="mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wide">Vitesses ascensionnelles</div>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                <StatCard label="Vit. asc. moy."         :value="formatAscentSpeed(activity.avg_ascent_speed_mh)" />
                <StatCard label="Vit. asc. → sommet"     :value="formatAscentSpeed(activity.summit_ascent_speed_mh)" />
                <StatCard label="Vit. asc. long tronçon" :value="formatAscentSpeed(activity.longest_ascent_speed_mh)" :sub="activity.longest_ascent_distance_km ? `${formatDistance(activity.longest_ascent_distance_km)}` : `aze`" />
            </div>

            <!-- Vitesses à plat et en descente -->
            <div class="mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wide">Plat & descente</div>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                <StatCard label="Vit. moy. à plat"   :value="formatSpeed(activity.avg_flat_speed_kmh)" />
                <StatCard label="Vit. moy. descente" :value="formatSpeed(activity.avg_descent_speed_kmh)" />
                <StatCard label="Vit. desc. (D-/h)"  :value="formatAscentSpeed(activity.avg_descent_rate_mh)" />
            </div>

            <!-- Répartition du trajet -->
            <div class="mb-2 text-xs font-semibold text-gray-400 uppercase tracking-wide">Répartition du trajet</div>
            <div class="bg-white rounded-lg shadow-sm border p-4 mb-6">
            <!-- Montée / Plat / Descente -->
            <div class="grid grid-cols-3 gap-4 mb-6">
                <div v-for="row in globalRows" :key="row.type"
                    class="rounded-lg p-3 text-center"
                    :class="{
                        'bg-green-50 border border-green-200': row.type === 'ascent',
                        'bg-gray-50 border border-gray-200':   row.type === 'flat',
                        'bg-blue-50 border border-blue-200':   row.type === 'descent',
                    }">
                    <p class="text-xs text-gray-500 mb-1">{{ row.label }}</p>
                    <p class="text-xl font-bold text-gray-800">{{ roundedPct(row.type) }}%</p>
                    <p class="text-l text-gray-600 mt-1">{{ pctToKm(row.type) }}</p>
                </div>
            </div>

                <!-- Toggle mode pour le détail par classe de pente -->
                <div class="flex gap-4 mb-4 text-sm border-t pt-4">
                    <button v-for="mode in pctModes" :key="mode.value"
                        :class="pctMode === mode.value ? 'text-blue-600 font-semibold border-b-2 border-blue-600' : 'text-gray-400 hover:text-gray-600'"
                        class="pb-1"
                        @click="pctMode = mode.value">
                        {{ mode.label }}
                    </button>
                </div>

                <!-- Détail par classe de pente -->
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase mb-2">Montée par pente</p>
                        <div class="space-y-2">
                            <PctBar v-for="cls in slopeClasses" :key="'a'+cls.key"
                                :label="cls.label"
                                :value="pctSlope('ascent', cls.key)"
                                :km="pctSlopeKm('ascent', cls.key)"
                                color="bg-green-300" />
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase mb-2">Descente par pente</p>
                        <div class="space-y-2">
                            <PctBar v-for="cls in slopeClasses" :key="'d'+cls.key"
                                :label="cls.label"
                                :value="pctSlope('descent', cls.key)"
                                :km="pctSlopeKm('descent', cls.key)"
                                color="bg-blue-300" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profil altimétrique -->
            <div class="bg-white rounded-lg shadow-sm border mb-6">
                <button
                    class="w-full flex items-center justify-between px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    @click="showProfile = !showProfile"
                >
                    <span>Profil altimétrique</span>
                    <span>{{ showProfile ? '▲' : '▼' }}</span>
                </button>
                <div v-if="showProfile" class="px-4 pb-4">
                    <ElevationProfile :activity-id="activity.id" />
                </div>
            </div>

            <!-- Carte OSM -->
            <div class="bg-white rounded-lg shadow-sm border mb-6">
                <button
                    class="w-full flex items-center justify-between px-4 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    @click="showMap = !showMap"
                >
                    <span>Carte du tracé</span>
                    <span>{{ showMap ? '▲' : '▼' }}</span>
                </button>
                <div v-if="showMap" class="px-4 pb-4">
                    <TrackMap :activity-id="activity.id" />
                </div>
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
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { formatDate, formatDistance, formatDuration, formatSpeed } from '@/helpers/format';
import StatCard from '@/components/StatCard.vue';
import PctBar from '@/components/PctBar.vue';
import ElevationProfile from '@/components/ElevationProfile.vue';
import TrackMap from '@/components/TrackMap.vue';

const route         = useRoute();
const router        = useRouter();
const activity      = ref(null);
const loading       = ref(true);
const recalculating = ref(false);
const showProfile = ref(false);
const showMap = ref(false);
const pctMode       = ref('total');

const pctModes = [
    { value: 'total', label: '% du trajet total' },
    { value: 'slope', label: '% de la pente' },
];

const globalRows = [
    { type: 'ascent',  label: 'Montée',   color: 'bg-green-400' },
    { type: 'flat',    label: 'Plat',     color: 'bg-gray-300' },
    { type: 'descent', label: 'Descente', color: 'bg-blue-400' },
];

const slopeClasses = [
    { key: '5_15',  label: '5–15%' },
    { key: '15_25', label: '15–25%' },
    { key: '25_35', label: '25–35%' },
    { key: 'gt35',  label: '> 35%' },
];

const slopeLabels = { lt5: '< 5%', '5_15': '5–15%', '15_25': '15–25%', '25_35': '25–35%', gt35: '> 35%' };
const slopeLabel  = (key) => slopeLabels[key] ?? key;

const typeClass = (type) => ({
    'bg-green-100 text-green-700': type === 'montee',
    'bg-blue-100 text-blue-700':   type === 'descente',
    'bg-gray-100 text-gray-600':   type === 'plat',
});

const formatAscentSpeed = (val) => val ? `${Math.round(val)} m/h` : '--';

// Arrondis corrects pour que montée + plat + descente = 100%
const roundedPct = computed(() => {
    if (!activity.value) return () => 0;
    const raw = {
        ascent:  activity.value.pct_ascent  ?? 0,
        flat:    activity.value.pct_flat    ?? 0,
        descent: activity.value.pct_descent ?? 0,
    };
    // Arrondir à l'entier avec correction du reste
    const floored = { ascent: Math.floor(raw.ascent), flat: Math.floor(raw.flat), descent: Math.floor(raw.descent) };
    const remainder = 100 - floored.ascent - floored.flat - floored.descent;
    const sorted = ['ascent', 'flat', 'descent'].sort((a, b) => (raw[b] - Math.floor(raw[b])) - (raw[a] - Math.floor(raw[a])));
    for (let i = 0; i < remainder; i++) floored[sorted[i]]++;
    return (type) => floored[type];
});

const pctToKm = (type) => {
    if (!activity.value) return '0 km';
    const km = (activity.value[`pct_${type}`] ?? 0) / 100 * activity.value.distance_km;
    return `${km.toFixed(1)} km`;
};

const pctSlope = (type, cls) => {
    if (!activity.value) return 0;
    const raw  = activity.value[`pct_${type}_${cls}`] ?? 0;
    const base = activity.value[`pct_${type}`] ?? 0;
    if (pctMode.value === 'slope') return base > 0 ? Math.round(raw / base * 100) : 0;
    return Math.round(raw);
};

const pctSlopeKm = (type, cls) => {
    if (!activity.value) return '0 km';
    const km = (activity.value[`pct_${type}_${cls}`] ?? 0) / 100 * activity.value.distance_km;
    return `${km.toFixed(1)} km`;
};

onMounted(async () => {
    try {
        const { data } = await axios.get(`/activities/${route.params.id}`);
        activity.value = data.data;
    } finally {
        loading.value = false;
    }
});

const recalculate = async () => {
    recalculating.value = true;
    try {
        const { data } = await axios.post(`/activities/${activity.value.id}/recalculate`);
        activity.value = data.data;
    } finally {
        recalculating.value = false;
    }
};

const confirmDelete = async () => {
    if (!confirm(`Supprimer "${activity.value.title}" ?`)) return;
    await axios.delete(`/activities/${activity.value.id}`);
    router.push('/activities');
};
</script>