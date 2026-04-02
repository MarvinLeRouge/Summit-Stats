<template>
    <div class="flex items-center gap-3">
        <span class="text-xs text-gray-500 w-16 shrink-0">{{ label }}</span>
        <div class="flex-1 bg-gray-100 rounded-full h-3 overflow-hidden">
            <div
                :class="color"
                class="h-3 rounded-full transition-all duration-500"
                :style="{ width: `${Math.min(value, 100)}%` }"
            ></div>
        </div>
        <span class="text-xs font-medium text-gray-700 w-24 text-right">{{ value }}% · {{ km }}</span>
    </div>
</template>

<script setup>
/**
 * Horizontal progress bar for displaying a slope-class percentage alongside its distance.
 *
 * @prop {string} label - Slope class label (e.g. "5–15%").
 * @prop {number} [value=0] - Percentage value (0–100); capped at 100 in the bar width.
 * @prop {string} [km=''] - Formatted distance string displayed to the right of the bar.
 * @prop {string} [color='bg-blue-400'] - Tailwind background color class applied to the filled bar.
 */
defineProps({
    label: String,
    value: { type: Number, default: 0 },
    km: { type: String, default: '' },
    color: { type: String, default: 'bg-blue-400' },
});
</script>
