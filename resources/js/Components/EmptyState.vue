<script setup>
import { ref, watchEffect } from 'vue';
import ModelSelector from './ModelSelector.vue';

const props = defineProps({
    models: {
        type: Array,
        required: true
    },
    modelValue: {
        type: String,
        required: true
    }
});

const emit = defineEmits(['update:modelValue', 'create']);

const selectedModel = ref(props.modelValue);

watchEffect(() => {
    emit('update:modelValue', selectedModel.value);
});
</script>

<template>
    <div class="text-center max-w-md mx-auto p-6">
        <div
            class="rounded-full bg-indigo-100 dark:bg-indigo-900 p-3 w-16 h-16 mx-auto mb-4 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-indigo-600 dark:text-indigo-300"
                viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd"
                    d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z"
                    clip-rule="evenodd" />
            </svg>
        </div>
        <h2 class="text-2xl font-bold mb-2 text-gray-900 dark:text-white">Bienvenue dans le Chat IA</h2>
        <p class="text-gray-600 dark:text-gray-300 mb-6">
            Choisissez un modèle et commencez une nouvelle conversation avec l'IA.
        </p>

        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 text-left mb-1">
                Modèle d'IA
            </label>
            <ModelSelector :models="models" v-model="selectedModel" />
        </div>

        <button @click="$emit('create')"
            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd"
                    d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                    clip-rule="evenodd" />
            </svg>
            Nouvelle conversation
        </button>
    </div>
</template>
