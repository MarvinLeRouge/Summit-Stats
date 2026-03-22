<template>
    <nav class="bg-white border-b shadow-sm">
        <div class="max-w-5xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-6">
                <span class="font-bold text-gray-800 text-lg">⛰ Summit Stats</span>
                <RouterLink to="/"
                    class="text-sm text-gray-600 hover:text-blue-600"
                    :class="{ 'text-blue-600 font-semibold': $route.path === '/' }">
                    Dashboard
                </RouterLink>
                <RouterLink to="/activities"
                    class="text-sm text-gray-600 hover:text-blue-600"
                    :class="{ 'text-blue-600 font-semibold': $route.path.startsWith('/activities') }">
                    Sorties
                </RouterLink>
            </div>
            <button class="text-sm text-gray-400 hover:text-red-500" @click="logout">
                Déconnexion
            </button>
        </div>
    </nav>
</template>

<script setup>
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();

/**
 * Clears the Sanctum token from localStorage and Axios headers, then redirects to /login.
 */
const logout = () => {
    localStorage.removeItem('sanctum_token');
    delete axios.defaults.headers.common['Authorization'];
    router.push('/login');
};
</script>