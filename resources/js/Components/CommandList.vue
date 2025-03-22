<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    commands: {
        type: Array,
        default: () => [],
    },
});

// Formulaire pour supprimer une commande
const removeCommandForm = useForm({
    index: null,
});

// Supprimer une commande
const removeCommand = (index) => {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette commande ?')) {
        removeCommandForm.index = index;
        removeCommandForm.delete(route('instructions.remove-command'), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <div>
        <div v-if="commands.length === 0" class="text-center p-8 bg-gray-50 dark:bg-gray-700 rounded-lg">
            <p class="text-gray-500 dark:text-gray-400">
                Vous n'avez pas encore de commandes personnalisées. Commencez par en ajouter une !
            </p>
        </div>

        <div v-else class="space-y-4">
            <div v-for="(command, index) in commands" :key="index"
                class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg flex justify-between items-start">
                <div>
                    <div class="font-medium text-gray-900 dark:text-gray-100">
                        /{{ command.command }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        {{ command.description }}
                    </div>
                </div>
                <button @click="removeCommand(index)"
                    class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                            clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>
