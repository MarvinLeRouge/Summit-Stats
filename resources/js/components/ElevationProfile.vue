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
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import axios from 'axios';
import { Chart, registerables } from 'chart.js';
import 'hammerjs';
import zoomPlugin from 'chartjs-plugin-zoom';

Chart.register(...registerables, zoomPlugin);

const props = defineProps({
    activityId: { type: Number, required: true },
});

const canvas   = ref(null);
const loading  = ref(true);
const points   = ref([]);
let chart      = null;

const hasElevation = computed(() =>
    points.value.length > 0 && points.value.some(p => p.ele !== null)
);

const fetchPoints = async () => {
    loading.value = true;
    try {
        const { data } = await axios.get(`/activities/${props.activityId}/track`);
        points.value = data.data;
    } finally {
        loading.value = false;
    }
};

const buildChart = () => {
    console.log('buildChart called');
    console.log('canvas.value:', canvas.value);
    console.log('hasElevation:', hasElevation.value);
    console.log('points count:', points.value.length);
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
                pointHoverRadius: 4,
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

onMounted(async () => {
    await fetchPoints();
    await nextTick();
    buildChart();
    canvas.value?.addEventListener('dblclick', onDblClick);
});

onUnmounted(() => {
    canvas.value?.removeEventListener('dblclick', onDblClick);
    chart?.destroy();
});
</script>