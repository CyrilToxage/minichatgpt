<script setup>
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CommandList from '@/Components/CommandList.vue';
import AddCommandModal from '@/Components/AddCommandModal.vue';

const props = defineProps({
    instructions: {
        type: Object,
        required: true,
    }
});

const showAddCommandModal = ref(false);

// Formulaire pour la mise à jour des instructions personnalisées
const form = useForm({
    about_you: props.instructions.about_you || '',
    assistant_behavior: props.instructions.assistant_behavior || '',
    is_active: props.instructions.is_active,
});

// Soumission du formulaire
const updateInstructions = () => {
    form.put(route('instructions.update'));
};

// Toggle pour activer/désactiver les instructions
const toggleActive = () => {
    useForm({}).post(route('instructions.toggle'), {
        preserveScroll: true,
    });
};

// Calculer la taille du texte restant
const aboutYouRemaining = computed(() => {
    return 2000 - (form.about_you?.length || 0);
});

const behaviorRemaining = computed(() => {
    return 2000 - (form.assistant_behavior?.length || 0);
});
</script>

<template>
    <AppLayout title="Instructions Personnalisées">

        <Head title="Instructions Personnalisées" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <h1 class="text-2xl font-semibold mb-6 text-gray-900 dark:text-gray-100">
                        Instructions Personnalisées
                    </h1>

                    <!-- Toggle pour activer/désactiver -->
                    <div class="mb-6 flex items-center justify-between bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                        <div>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Statut des instructions personnalisées
                            </h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ form.is_active ? 'Vos instructions personnalisées sont actives.' : 'Vos instructions personnalisées sont désactivées.' }}
                            </p>
                        </div>
                        <button @click="toggleActive"
                            class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                            {{ form.is_active ? 'Désactiver' : 'Activer' }}
                        </button>
                    </div>

                    <form @submit.prevent="updateInstructions">
                        <!-- Section À propos de vous -->
                        <div class="mb-6">
                            <div class="flex justify-between items-center mb-2">
                                <label for="about_you"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    À propos de vous
                                </label>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ aboutYouRemaining }} caractères restants
                                </span>
                            </div>
                            <textarea id="about_you" v-model="form.about_you" rows="5"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                placeholder="Partagez des informations sur vous pour des réponses personnalisées..."></textarea>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Informez l'assistant sur qui vous êtes, vos intérêts, et votre domaine d'expertise.
                            </p>
                        </div>

                        <!-- Section Comportement de l'assistant -->
                        <div class="mb-6">
                            <div class="flex justify-between items-center mb-2">
                                <label for="assistant_behavior"
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    Comportement de l'assistant
                                </label>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ behaviorRemaining }} caractères restants
                                </span>
                            </div>
                            <textarea id="assistant_behavior" v-model="form.assistant_behavior" rows="5"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                placeholder="Précisez comment vous souhaitez que l'assistant interagisse avec vous..."></textarea>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Définissez le ton, le format et la manière dont vous souhaitez que l'assistant
                                communique avec vous.
                            </p>
                        </div>

                        <!-- Bouton de mise à jour -->
                        <div class="flex justify-end">
                            <button type="submit"
                                class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                :disabled="form.processing">
                                Mettre à jour
                            </button>
                        </div>
                    </form>

                    <!-- Section Commandes personnalisées -->
                    <div class="mt-10">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Commandes personnalisées
                            </h2>
                            <button @click="showAddCommandModal = true"
                                class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                                Ajouter une commande
                            </button>
                        </div>

                        <CommandList :commands="props.instructions.custom_commands || []" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal pour ajouter une commande -->
        <AddCommandModal v-if="showAddCommandModal" @close="showAddCommandModal = false" />
    </AppLayout>
</template>
