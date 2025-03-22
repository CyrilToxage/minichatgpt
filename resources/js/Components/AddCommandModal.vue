<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';

const emit = defineEmits(['close']);

// Formulaire pour ajouter une commande
const form = useForm({
    command: '',
    description: '',
});

// Soumettre le formulaire
const submit = () => {
    form.post(route('instructions.add-command'), {
        preserveScroll: true,
        onSuccess: () => {
            emit('close');
        },
    });
};

// Calculer la taille du texte restant
const commandRemaining = computed(() => {
    return 50 - (form.command?.length || 0);
});

const descriptionRemaining = computed(() => {
    return 500 - (form.description?.length || 0);
});
</script>

<template>
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-medium text-gray-900 dark:text-gray-100">
                    Ajouter une commande personnalisée
                </h3>
                <button @click="$emit('close')" class="text-gray-400 hover:text-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form @submit.prevent="submit">
                <!-- Champ de commande -->
                <div class="mb-4">
                    <div class="flex justify-between items-center mb-2">
                        <label for="command" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Commande (sans le /)
                        </label>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ commandRemaining }} caractères restants
                        </span>
                    </div>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <span
                            class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 dark:text-gray-400 pointer-events-none">
                            /
                        </span>
                        <input type="text" id="command" v-model="form.command"
                            class="pl-6 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            placeholder="meteo" maxlength="50" required>
                    </div>
                    <div v-if="form.errors.command" class="mt-1 text-sm text-red-600 dark:text-red-400">
                        {{ form.errors.command }}
                    </div>
                </div>

                <!-- Champ de description -->
                <div class="mb-4">
                    <div class="flex justify-between items-center mb-2">
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Description de la commande
                        </label>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ descriptionRemaining }} caractères restants
                        </span>
                    </div>
                    <textarea id="description" v-model="form.description" rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        placeholder="Affiche la météo actuelle et les prévisions pour votre localisation ou une localisation spécifiée."
                        maxlength="500" required></textarea>
                    <div v-if="form.errors.description" class="mt-1 text-sm text-red-600 dark:text-red-400">
                        {{ form.errors.description }}
                    </div>
                </div>

                <!-- Boutons d'action -->
                <div class="mt-6 flex justify-end space-x-3">
                    <button type="button" @click="$emit('close')"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 dark:text-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700">
                        Annuler
                    </button>
                    <button type="submit"
                        class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        :disabled="form.processing">
                        Ajouter
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
