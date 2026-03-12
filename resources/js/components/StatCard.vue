<template>
    <div class="bg-white rounded-lg shadow-sm border p-4 text-center">
        <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">{{ label }}</p>
        <p class="text-xl font-bold text-gray-800">{{ value }}</p>
    </div>
</template>

<script setup>
defineProps({
    label: String,
    value: String,
});
</script>