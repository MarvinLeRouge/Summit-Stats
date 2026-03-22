<template>
    <div class="bg-white rounded-lg shadow-sm border p-4 text-center">
        <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">{{ label }}</p>
        <p class="text-xl font-bold text-gray-800">{{ value }}</p>
        <p v-if="sub" class="text-xl font-bold text-gray-800">{{ sub }}</p>
    </div>
</template>

<script setup>
/**
 * Displays a single labeled metric in a card (label + primary value + optional secondary value).
 *
 * @prop {string} label - Metric label displayed above the value.
 * @prop {string} value - Primary metric value.
 * @prop {string} [sub] - Optional secondary value displayed below the primary one.
 */
defineProps({
    label: String,
    value: String,
    sub:   { type: String, default: null },
});
</script>