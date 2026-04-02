<template>
    <div class="chart-wrapper">
        <canvas ref="canvasRef"></canvas>
    </div>
</template>

<script setup>
/**
 * Generic Chart.js wrapper component.
 *
 * Instantiates a Chart.js chart on mount, destroys it on unmount, and reactively
 * updates the chart data when the `data` prop changes.
 *
 * @prop {string} type - Chart.js chart type (e.g. 'line', 'bar').
 * @prop {Object} data - Chart.js data object (labels + datasets).
 * @prop {Object} [options={}] - Chart.js options object.
 */
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

const props = defineProps({
    type: { type: String, required: true },
    data: { type: Object, required: true },
    options: { type: Object, default: () => ({}) },
});

const canvasRef = ref(null);
let chartInstance = null;

onMounted(() => {
    chartInstance = new Chart(canvasRef.value, {
        type: props.type,
        data: props.data,
        options: props.options,
    });
});

onUnmounted(() => {
    chartInstance?.destroy();
});

watch(
    () => props.data,
    (newData) => {
        if (chartInstance) {
            chartInstance.data = newData;
            chartInstance.update();
        }
    },
    { deep: true }
);
</script>
