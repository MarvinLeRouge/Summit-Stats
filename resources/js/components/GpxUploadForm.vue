<template>
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Importer une sortie GPX</h2>

            <div v-if="!uploading">
                <!-- Champ fichier -->
                <div
                    class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer hover:border-blue-400 mb-4"
                    @click="$refs.fileInput.click()"
                    @dragover.prevent
                    @drop.prevent="onDrop"
                >
                    <p v-if="!file" class="text-gray-500 text-sm">Glissez un fichier GPX ou cliquez pour sélectionner</p>
                    <p v-else class="text-blue-600 text-sm font-medium">{{ file.name }}</p>
                </div>
                <input ref="fileInput" type="file" accept=".gpx,.xml" class="hidden" @change="onFileChange" />

                <!-- Métadonnées -->
                <div class="space-y-3 mb-4">
                    <input v-model="title" type="text" placeholder="Titre *" class="w-full border rounded px-3 py-2 text-sm" />
                    <select v-model="type" class="w-full border rounded px-3 py-2 text-sm">
                        <option value="">Type *</option>
                        <option value="randonnee">Randonnée</option>
                        <option value="trail">Trail</option>
                    </select>
                    <select v-model="environment" class="w-full border rounded px-3 py-2 text-sm">
                        <option value="">Milieu *</option>
                        <option value="urbain">Urbain</option>
                        <option value="campagne">Campagne</option>
                        <option value="montagne">Montagne</option>
                    </select>
                    <input v-model="date" type="date" class="w-full border rounded px-3 py-2 text-sm" />
                    <textarea v-model="comment" placeholder="Commentaire" class="w-full border rounded px-3 py-2 text-sm" rows="2" />
                </div>

                <p v-if="error" class="text-red-500 text-sm mb-3">{{ error }}</p>

                <!-- Actions -->
                <div class="flex justify-end gap-3">
                    <button class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800" @click="$emit('close')">
                        Annuler
                    </button>
                    <button
                        :disabled="!canSubmit"
                        class="px-4 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
                        @click="submit"
                    >
                        Importer
                    </button>
                </div>
            </div>

            <!-- Progression SSE -->
            <div v-else class="py-4">
                <p class="text-sm font-medium text-gray-700 mb-3">{{ statusLabel }}</p>

                <!-- Barre de progression (enrichissement uniquement) -->
                <div v-if="step === 'enriching'" class="w-full bg-gray-200 rounded-full h-2 mb-2">
                    <div
                        class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                        :style="{ width: `${progress}%` }"
                    />
                </div>
                <p v-if="step === 'enriching'" class="text-xs text-gray-500">{{ progress }}%</p>

                <!-- Spinner pour les autres étapes -->
                <div v-else class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                    </svg>
                    <span class="text-sm text-gray-500">Veuillez patienter...</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const emit = defineEmits(['uploaded', 'close']);

const file        = ref(null);
const title       = ref('');
const type        = ref('');
const environment = ref('');
const date        = ref('');
const comment     = ref('');
const error       = ref('');
const uploading   = ref(false);
const step        = ref('');
const progress    = ref(0);

const canSubmit = computed(() =>
    file.value && title.value && type.value && environment.value && date.value
);

const statusLabel = computed(() => {
    switch (step.value) {
        case 'parsing':   return 'Lecture du fichier GPX...';
        case 'enriching': return 'Récupération des données d\'altitude...';
        case 'analyzing': return 'Calcul des statistiques...';
        default:          return 'Traitement en cours...';
    }
});

const onFileChange = (e) => { file.value = e.target.files[0] ?? null; };
const onDrop       = (e) => { file.value = e.dataTransfer.files[0] ?? null; };

const submit = async () => {
    if (!canSubmit.value) return;

    uploading.value = true;
    error.value     = '';
    step.value      = '';
    progress.value  = 0;

    const form = new FormData();
    form.append('gpx_file',    file.value);
    form.append('title',       title.value);
    form.append('type',        type.value);
    form.append('environment', environment.value);
    form.append('date',        date.value);
    if (comment.value) form.append('comment', comment.value);

    try {
        const token    = localStorage.getItem('sanctum_token');
        const response = await fetch('/api/activities', {
            method:  'POST',
            headers: { 'Authorization': `Bearer ${token}` },
            body:    form,
        });

        if (!response.ok) {
            const data = await response.json();
            throw new Error(data.message ?? 'Erreur lors de l\'import.');
        }

        const reader       = response.body.getReader();
        const decoder      = new TextDecoder();
        let   buffer       = '';
        let   currentEvent = ''; // persiste entre les chunks
        let   streamDone   = false;

        while (!streamDone) {
            const { done, value } = await reader.read();

            if (done) {
                break;
            }

            buffer += decoder.decode(value, { stream: true });
            const lines = buffer.split('\n');
            buffer = lines.pop(); // garde la ligne potentiellement incomplète

            for (const line of lines) {
                if (line.startsWith('event: ')) {
                    currentEvent = line.slice(7).trim();
                } else if (line.startsWith('data: ')) {
                    let data = null;
                    let parseError = null;
                    try {
                        data = JSON.parse(line.slice(6));
                    } catch (e) {
                        console.error('[SSE] Erreur de parsing JSON:', e, '| ligne:', line);
                        continue;
                    }

                    console.log('[SSE] data parsée — event:', currentEvent, '| data:', data);

                    if (currentEvent === 'status') {
                        step.value = data.step;
                        if (data.step === 'enriching') {
                            progress.value = data.progress ?? 0;
                        }
                    } else if (currentEvent === 'done') {
                        uploading.value = false;
                        emit('uploaded', data.activity);
                        streamDone = true;
                        break;
                    } else if (currentEvent === 'close') {
                        streamDone = true;
                        break;
                    } else if (currentEvent === 'error') {
                        throw new Error(data.message);
                    }
                }

                // Ignorer les lignes vides (séparateurs SSE)
            }
        }
    } catch (e) {
        console.error('[SSE] Erreur globale:', e);
        error.value     = e.message;
        uploading.value = false;
    }
};
</script>
