<script setup>
import { ref, watchEffect } from 'vue';

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

const emit = defineEmits(['update:modelValue']);

const selectedModel = ref(props.modelValue);

// Émettre l'événement lorsque le modèle sélectionné change
watchEffect(() => {
    emit('update:modelValue', selectedModel.value);
});

// Surveiller les changements du modelValue externe
watchEffect(() => {
    selectedModel.value = props.modelValue;
});

// Formater les informations sur les limites et les coûts du modèle
const formatModelInfo = (model) => {
    const selectedModelData = props.models.find(m => m.id === model);
    if (!selectedModelData) return '';

    return `${selectedModelData.name} (${selectedModelData.context_length} tokens max)`;
};
</script>

<template>
    <div>
        <select v-model="selectedModel"
            class="block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
            <option v-for="model in models" :key="model.id" :value="model.id">
                {{ model.name }}
            </option>
        </select>
        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            {{ formatModelInfo(selectedModel) }}
        </div>
    </div>
</template>
