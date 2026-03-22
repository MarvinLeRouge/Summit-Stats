<template>
    <div>
        <div v-if="loading" class="h-48 flex items-center justify-center text-gray-400 text-sm">
            Chargement du profil...
        </div>

        <div v-else-if="!hasElevation" class="h-48 flex items-center justify-center text-gray-400 text-sm">
            Données d'altitude non disponibles pour cette trace.
        </div>

        <div v-else>
            <div class="flex justify-end mb-2">
                <button
                    class="text-xs text-gray-500 hover:text-gray-700 underline"
                    @click="resetZoom"
                >
                    Réinitialiser le zoom
                </button>
            </div>
            <div style="position: relative; height: 220px;">
                <canvas ref="canvas" />
            </div>
        </div>
    </div>
</template>

<script setup>
/**
 * Interactive elevation profile for a GPX activity.
 *
 * Fetches track points from the API, renders a Chart.js area chart (distance vs altitude),
 * and emits the geographic coordinates of the hovered point so the parent can sync a map marker.
 * Supports scroll-wheel zoom and drag-to-zoom on the X axis; double-click resets the zoom.
 *
 * @prop {number} activityId - ID of the activity whose track points are fetched.
 *
 * @emits hover-point - Emitted on mousemove with `{ lat, lon }` of the hovered point,
 *                      or `null` on mouseleave.
 */
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import axios from 'axios';
import { Chart, registerables } from 'chart.js';
import 'hammerjs';
import zoomPlugin from 'chartjs-plugin-zoom';

Chart.register(...registerables, zoomPlugin);

const props = defineProps({
    activityId: { type: Number, required: true },
});

const emit = defineEmits(['hover-point']);

const canvas   = ref(null);
const loading  = ref(true);
const points   = ref([]);
let chart      = null;

const hasElevation = computed(() =>
    points.value.length > 0 && points.value.some(p => p.ele !== null)
);

/**
 * Fetches raw track points from the API and stores them in `points`.
 *
 * @returns {Promise<void>}
 */
const fetchPoints = async () => {
    loading.value = true;
    try {
        const { data } = await axios.get(`/activities/${props.activityId}/track`);
        points.value = data.data;
    } finally {
        loading.value = false;
    }
};

/**
 * Instantiates the Chart.js elevation chart after track points have been loaded.
 * No-op if the canvas ref is not ready or if no elevation data is available.
 */
const buildChart = () => {
    if (!canvas.value || !hasElevation.value) return;

    const labels = points.value.map(p => parseFloat(p.distance_from_start_km).toFixed(2));
    const data   = points.value.map(p => p.ele);

    chart = new Chart(canvas.value, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                data,
                borderColor:     '#3B82F6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderWidth:      1.5,
                pointRadius:      0,
                pointHoverRadius: 8,
                fill:             true,
                tension:          0.2,
            }],
        },
        options: {
            responsive:          true,
            maintainAspectRatio: false,
            animation:           false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        title:  (items) => `${items[0].label} km`,
                        label:  (item)  => `${Math.round(item.raw)} m`,
                    },
                },
                zoom: {
                    pan:  { 
                        enabled: false, 
                    },
                    zoom: {
                        wheel:  { enabled: true },
                        pinch:  { enabled: true },
                        drag: {
                            enabled: true,
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            borderColor: '#3B82F6',
                            borderWidth: 1,
                        },
                        mode:   'x',
                        onZoomComplete: () => {},
                    },
                },
            },
            scales: {
                x: {
                    ticks: {
                        maxTicksLimit: 8,
                        callback: (val, i) => `${labels[i]} km`,
                        font: { size: 11 },
                    },
                    grid: { display: false },
                },
                y: {
                    ticks: {
                        callback: (val) => `${val} m`,
                        font: { size: 11 },
                    },
                },
            },
        },
    });
};

const resetZoom = () => chart?.resetZoom();
const onDblClick = () => chart?.resetZoom();

/**
 * Resolves the track point under the cursor and emits its coordinates as `hover-point`.
 *
 * @param {MouseEvent} e - Native mousemove event from the canvas.
 */
const onMouseMove = (e) => {
    if (!chart) return;
    const elements = chart.getElementsAtEventForMode(e, 'index', { intersect: false }, false);
    if (elements.length > 0) {
        const idx   = elements[0].index;
        const point = points.value[idx];
        if (point) emit('hover-point', { lat: point.lat, lon: point.lon });
    }
};

const onMouseLeave = () => emit('hover-point', null);

onMounted(async () => {
    await fetchPoints();
    await nextTick();
    buildChart();
    canvas.value?.addEventListener('dblclick', onDblClick);
    canvas.value?.addEventListener('mousemove', onMouseMove);
    canvas.value?.addEventListener('mouseleave', onMouseLeave);
});

onUnmounted(() => {
    canvas.value?.removeEventListener('dblclick', onDblClick);
    canvas.value?.removeEventListener('mousemove', onMouseMove);
    canvas.value?.removeEventListener('mouseleave', onMouseLeave);
    chart?.destroy();
});
</script>