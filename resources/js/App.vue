<template>
    <div>
        <NavBar v-if="$route.path !== '/login'" />
        <RouterView />
    </div>
</template>

<script setup>
import NavBar from '@/components/NavBar.vue';
</script>