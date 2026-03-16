<template>
    <div class="relative h-64">
        <canvas ref="canvasRef"></canvas>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { Chart, registerables } from 'chart.js';
import 'chartjs-adapter-date-fns';
import zoomPlugin from 'chartjs-plugin-zoom';
import { fr } from 'date-fns/locale';

Chart.register(...registerables, zoomPlugin);

const props = defineProps({
    data: { type: Object, required: true },
    unit: { type: String, default: '' },
});

const canvasRef    = ref(null);
let   chartInstance = null;

const buildOptions = () => ({
    responsive:          true,
    maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: {
        legend: { display: false },
        tooltip: {
            callbacks: {
                label: (ctx) => `${ctx.parsed.y.toFixed(1)} ${props.unit}`,
            },
        },
    },
    scales: {
        x: {
            type: 'time',
            time: {
                unit:           'day',
                displayFormats: { day: 'dd/MM/yy' },
            },
            adapters: { date: { locale: fr } },
            ticks: { maxTicksLimit: 8 },
        },
        y: {
            beginAtZero: false,
            title: {
                display: true,
                text:    props.unit,
            },
        },
    },
});

onMounted(() => {
    chartInstance = new Chart(canvasRef.value, {
        type:    'line',
        data:    props.data,
        options: buildOptions(),
    });
});

onUnmounted(() => chartInstance?.destroy());

watch(() => props.data, (newData) => {
    if (chartInstance) {
        chartInstance.data    = newData;
        chartInstance.options = buildOptions();
        chartInstance.update();
    }
}, { deep: true });
</script>