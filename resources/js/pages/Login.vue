<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50">
        <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Summit Stats</h1>
            <p class="text-gray-500 text-sm mb-6">Entrez votre mot de passe pour continuer.</p>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Mot de passe</label>
                <input
                    v-model="password"
                    type="password"
                    placeholder="Mot de passe"
                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    @keyup.enter="login"
                />
            </div>

            <p v-if="error" class="text-red-500 text-sm mb-4">{{ error }}</p>

            <button
                :disabled="loading || !password"
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
 * Password-based login page for single-user access.
 *
 * Sends the password to POST /api/login, retrieves a Sanctum token on success,
 * persists it to localStorage, and redirects to the dashboard.
 */
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const password = ref('');
const error = ref('');
const loading = ref(false);
const router = useRouter();

/**
 * Submits the password to the login endpoint, stores the returned token on success.
 *
 * @returns {Promise<void>}
 */
const login = async () => {
    if (!password.value) return;

    loading.value = true;
    error.value = '';

    try {
        const { data } = await axios.post('/api/login', { password: password.value });
        const token = data.data.token;
        localStorage.setItem('sanctum_token', token);
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        router.push('/');
    } catch (err) {
        error.value =
            err.response?.status === 401 ? 'Mot de passe incorrect.' : 'Une erreur est survenue. Veuillez réessayer.';
    } finally {
        loading.value = false;
    }
};
</script>
