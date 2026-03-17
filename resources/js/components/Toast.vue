<template>
    <Transition name="toast">
        <div
            v-if="visible"
            :class="[
                'fixed bottom-6 right-6 z-50 px-4 py-3 rounded-lg shadow-lg text-sm font-medium flex items-center gap-2',
                type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'
            ]"
        >
            <span>{{ type === 'success' ? '✓' : '✕' }}</span>
            <span>{{ message }}</span>
        </div>
    </Transition>
</template>

<script setup>
import { ref } from 'vue';

const visible = ref(false);
const message = ref('');
const type    = ref('success');

const show = (msg, toastType = 'success', duration = 3000) => {
    message.value = msg;
    type.value    = toastType;
    visible.value = true;
    setTimeout(() => { visible.value = false; }, duration);
};

defineExpose({ show });
</script>

<style scoped>
.toast-enter-active, .toast-leave-active { transition: all 0.3s ease; }
.toast-enter-from, .toast-leave-to       { opacity: 0; transform: translateY(1rem); }
</style>