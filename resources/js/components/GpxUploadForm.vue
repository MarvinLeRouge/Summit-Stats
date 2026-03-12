<template>
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-lg p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-bold text-gray-800">Importer une sortie</h2>
                <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 text-xl">✕</button>
            </div>

            <!-- Zone de drop GPX -->
            <div
                class="border-2 border-dashed rounded-lg p-6 text-center mb-4 transition-colors"
                :class="dragging ? 'border-blue-500 bg-blue-50' : 'border-gray-300'"
                @dragover.prevent="dragging = true"
                @dragleave="dragging = false"
                @drop.prevent="onDrop"
            >
                <div v-if="!file">
                    <p class="text-gray-500 text-sm mb-2">Glissez votre fichier GPX ici</p>
                    <p class="text-gray-400 text-xs mb-3">ou</p>
                    <label class="cursor-pointer bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm px-4 py-2 rounded">
                        Parcourir
                        <input type="file" accept=".gpx,.xml" class="hidden" @change="onFileSelect" />
                    </label>
                </div>
                <div v-else class="flex items-center justify-center gap-2">
                    <span class="text-green-600 text-sm font-medium">✓ {{ file.name }}</span>
                    <button @click="file = null" class="text-gray-400 hover:text-red-500 text-xs">✕</button>
                </div>
            </div>

            <!-- Métadonnées -->
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Titre *</label>
                    <input v-model="form.title" type="text" class="w-full border rounded px-3 py-2 text-sm" />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
                        <select v-model="form.type" class="w-full border rounded px-3 py-2 text-sm">
                            <option value="">-- Choisir --</option>
                            <option value="randonnee">Randonnée</option>
                            <option value="trail">Trail</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Milieu *</label>
                        <select v-model="form.environment" class="w-full border rounded px-3 py-2 text-sm">
                            <option value="">-- Choisir --</option>
                            <option value="urbain">Urbain</option>
                            <option value="campagne">Campagne</option>
                            <option value="montagne">Montagne</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date *</label>
                    <input v-model="form.date" type="date" class="w-full border rounded px-3 py-2 text-sm" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Commentaire</label>
                    <textarea v-model="form.comment" rows="2" class="w-full border rounded px-3 py-2 text-sm resize-none"></textarea>
                </div>
            </div>

            <!-- Erreur -->
            <p v-if="error" class="text-red-500 text-sm mt-3">{{ error }}</p>

            <!-- Résultat -->
            <div v-if="result" class="mt-4 bg-green-50 border border-green-200 rounded p-3 text-sm text-green-700">
                ✓ Sortie importée — {{ result.distance_km }} km · {{ result.elevation_gain }} m D+ · {{ formatDuration(result.duration_seconds) }}
            </div>

            <!-- Actions -->
            <div class="flex justify-end gap-3 mt-6">
                <button @click="$emit('close')" class="text-sm text-gray-500 hover:text-gray-700">Annuler</button>
                <button
                    @click="submit"
                    :disabled="loading || !file || !form.title || !form.type || !form.environment || !form.date"
                    class="bg-blue-600 text-white px-4 py-2 rounded text-sm font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    {{ loading ? 'Analyse en cours...' : 'Importer' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { formatDuration } from '@/helpers/format';

const emit = defineEmits(['close', 'imported']);

const file    = ref(null);
const dragging = ref(false);
const loading  = ref(false);
const error    = ref('');
const result   = ref(null);

const form = ref({
    title:       '',
    type:        '',
    environment: '',
    date:        '',
    comment:     '',
});

const onFileSelect = (e) => {
    file.value = e.target.files[0] || null;
};

const onDrop = (e) => {
    dragging.value = false;
    file.value = e.dataTransfer.files[0] || null;
};

const submit = async () => {
    loading.value = true;
    error.value   = '';
    result.value  = null;

    const formData = new FormData();
    formData.append('gpx_file', file.value);
    Object.entries(form.value).forEach(([k, v]) => {
        if (v) formData.append(k, v);
    });

    try {
        const { data } = await axios.post('/activities', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        result.value = data.data;
        emit('imported', data.data);
    } catch (e) {
        error.value = e.response?.data?.message || 'Erreur lors de l\'import.';
    } finally {
        loading.value = false;
    }
};
</script>