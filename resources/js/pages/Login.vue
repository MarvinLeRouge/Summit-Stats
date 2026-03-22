<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50">
        <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Summit Stats</h1>
            <p class="text-gray-500 text-sm mb-6">Entrez votre token d'accès pour continuer.</p>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Token d'accès</label>
                <input
                    v-model="token"
                    type="password"
                    placeholder="Votre token Sanctum"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    @keyup.enter="login"
                />
            </div>

            <p v-if="error" class="text-red-500 text-sm mb-4">{{ error }}</p>

            <button
                :disabled="loading || !token"
                class="w-full bg-blue-600 text-white py-2 px-4 rounded-md text-sm font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                @click="login"
            >
                {{ loading ? 'Connexion...' : 'Se connecter' }}
            </button>
        </div>
    </div>
</template>

<script setup>
/**
 * Token-based login page for single-user access.
 *
 * Validates the Sanctum token by making a test API request. On success, persists
 * the token to localStorage and redirects to the dashboard.
 */
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const token  = ref('');
const error  = ref('');
const loading = ref(false);
const router = useRouter();

/**
 * Validates the token against the API, persists it on success, or shows an error on 401.
 *
 * @returns {Promise<void>}
 */
const login = async () => {
    if (!token.value) return;

    loading.value = true;
    error.value   = '';

    try {
        axios.defaults.headers.common['Authorization'] = `Bearer ${token.value}`;
        await axios.get('/activities?page=1');
        localStorage.setItem('sanctum_token', token.value);
        router.push('/');
    } catch {
        error.value = 'Token invalide. Vérifiez votre token et réessayez.';
        delete axios.defaults.headers.common['Authorization'];
    } finally {
        loading.value = false;
    }
};
</script>