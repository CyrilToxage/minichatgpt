<script setup>
// Imports existants
import { ref, watch, computed, onMounted, onBeforeUnmount, nextTick } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import MessageList from '../../Components/MessageList.vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import ConversationList from '../../Components/ConversationList.vue';
import ModelSelector from '../../Components/ModelSelector.vue';

// Props avec toutes les données nécessaires
const props = defineProps({
    conversations: {
        type: Array,
        default: () => [],
    },
    currentConversation: {
        type: Object,
        default: null,
    },
    models: {
        type: Array,
        required: true,
    },
    selectedModel: {
        type: String,
        required: true,
    },
    flash: {
        type: Object,
        default: () => ({}),
    },
});

// Refs pour gérer l'état du composant
const messageInput = ref('');
const isLoading = ref(false);
const selectedModel = ref(props.selectedModel);
const messageContainer = ref(null);
const localMessages = ref([]);
const channelSubscription = ref(null);

// Formulaire pour créer une nouvelle conversation
const newConversationForm = useForm({
    model: selectedModel.value,
});

// Fonction pour créer une nouvelle conversation
const createNewConversation = () => {
    newConversationForm.model = selectedModel.value;
    newConversationForm.post(route('chat.store'));
};

// Au montage, on copie les messages de la conversation dans la ref locale
onMounted(() => {
    console.log("Component mounted");
    if (props.currentConversation && props.currentConversation.messages) {
        console.log("Setting local messages:", props.currentConversation.messages);
        localMessages.value = JSON.parse(JSON.stringify(props.currentConversation.messages));
        subscribeToChannel();
    }
    scrollToBottom();
});

// Watch pour détecter un changement de conversation
watch(
    () => props.currentConversation,
    (newConversation, oldConversation) => {
        console.log("Conversation changed", newConversation);
        // Si on change de conversation
        if (newConversation && (!oldConversation || newConversation.id !== oldConversation.id)) {
            // On désinscrit de l'ancien canal si nécessaire
            if (channelSubscription.value) {
                channelSubscription.value.unsubscribe();
                channelSubscription.value = null;
            }

            // On copie les nouveaux messages
            if (newConversation.messages) {
                localMessages.value = JSON.parse(JSON.stringify(newConversation.messages));
            } else {
                localMessages.value = [];
            }

            // On s'inscrit au nouveau canal
            subscribeToChannel();
        }

        nextTick(() => scrollToBottom());
    },
    { immediate: true }
);

// Watch pour mettre à jour le modèle lorsqu'il change
watch(selectedModel, (newModel) => {
    if (props.currentConversation) {
        router.put(route('chat.update-model', props.currentConversation.id), {
            model: newModel,
        }, {
            preserveState: true,
        });
    }
});

// Nettoyage au démontage
onBeforeUnmount(() => {
    if (channelSubscription.value) {
        channelSubscription.value.unsubscribe();
    }
});

// S'abonner au canal de la conversation courante
function subscribeToChannel() {
    if (!props.currentConversation) return;

    const channel = `chat.${props.currentConversation.id}`;
    console.log("🔌 Tentative de connexion au canal:", channel);

    channelSubscription.value = window.Echo.private(channel)
        .subscribed(() => {
            console.log("✅ Connecté avec succès au canal:", channel);
        })
        .error(error => {
            console.error("❌ Erreur de connexion au canal:", error);
        })
        .listen(".message.streamed", (event) => {
            console.log("📨 Message reçu:", event);

            // Vérifier qu'on ait bien un message assistant en cours
            const lastMessage = localMessages.value[localMessages.value.length - 1];
            if (!lastMessage || lastMessage.role !== "assistant") {
                console.log("⚠️ Aucun message assistant trouvé pour concaténer");
                return;
            }

            // Gestion d'erreur éventuelle
            if (event.error) {
                console.error("❌ Erreur reçue:", event.content);
                localMessages.value.pop(); // Retirer le message d'erreur
                isLoading.value = false;
                return;
            }

            // Si c'est le premier chunk, on désactive le loading
            if (lastMessage.content === '' && event.content) {
                console.log("🔄 Premier chunk reçu");
                isLoading.value = false;
            }

            // Ajouter le chunk reçu au message
            if (!event.isComplete) {
                // Ajouter le chunk au contenu existant (concaténation)
                lastMessage.content += event.content;
            }

            // Scroll automatique
            nextTick(() => scrollToBottom());

            // Si c'est la fin, on peut faire des actions supplémentaires
            if (event.isComplete) {
                console.log("✅ Message complet reçu");
            }
        });
}

// Fonction pour envoyer un message avec streaming
const sendMessage = () => {
    if (!messageInput.value.trim() || isLoading.value) return;

    isLoading.value = true;

    // Ajouter le message utilisateur localement
    localMessages.value.push({
        id: Date.now(), // ID temporaire
        role: 'user',
        content: messageInput.value,
        created_at: new Date().toISOString()
    });

    // Ajouter un message assistant vide (sera rempli par le streaming)
    localMessages.value.push({
        id: Date.now() + 1, // ID temporaire
        role: 'assistant',
        content: '',
        created_at: new Date().toISOString()
    });

    // Nettoyer l'input
    const message = messageInput.value;
    messageInput.value = '';

    // Scroller en bas
    nextTick(() => scrollToBottom());

    // Envoyer la requête de streaming
    router.post(route('chat.stream', props.currentConversation.id), {
        message: message,
        model: selectedModel.value
    }, {
        preserveState: true,
        onError: (errors) => {
            console.error("Erreur d'envoi:", errors);
            // Retirer le message assistant vide en cas d'erreur
            if (localMessages.value.length > 0 && localMessages.value[localMessages.value.length - 1].role === 'assistant') {
                localMessages.value.pop();
            }
            isLoading.value = false;
        }
    });
};

// Calcul pour déterminer si nous sommes dans une conversation
const isInConversation = computed(() => {
    return !!props.currentConversation;
});

// Calcul pour déterminer si c'est une nouvelle conversation sans messages
const isNewConversation = computed(() => {
    return props.currentConversation && props.currentConversation.messages.length === 0;
});

// Fonction pour défiler vers le bas
const scrollToBottom = async () => {
    await nextTick();
    if (messageContainer.value) {
        messageContainer.value.scrollTop = messageContainer.value.scrollHeight;
    }
};
</script>

<template>
    <AppLayout title="Chat">

        <Head title="Chat" />

        <div class="flex h-[calc(100vh-4rem)] bg-white dark:bg-gray-900">
            <!-- Sidebar des conversations -->
            <div class="w-64 border-r border-gray-200 dark:border-gray-700">
                <div class="p-4">
                    <button @click="createNewConversation"
                        class="w-full py-2 px-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md transition duration-150 ease-in-out">
                        Nouvelle conversation
                    </button>
                </div>
                <ConversationList :conversations="conversations" :current-id="currentConversation?.id" />
            </div>

            <!-- Contenu principal -->
            <div class="flex-1 flex flex-col">
                <div v-if="isInConversation" class="flex flex-col h-full">
                    <!-- En-tête avec le titre et le sélecteur de modèle -->
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                        <h2 class="text-xl font-semibold">
                            {{ currentConversation.title || 'Nouvelle conversation' }}
                        </h2>
                        <ModelSelector :models="models" v-model="selectedModel" />
                    </div>

                    <!-- Liste des messages - utiliser localMessages au lieu de currentConversation.messages -->
                    <div ref="messageContainer" class="flex-1 overflow-auto p-4">
                        <MessageList :messages="localMessages" />
                    </div>

                    <!-- Message en cours de chargement -->
                    <div v-if="isLoading" class="p-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex items-start">
                            <div
                                class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-500 dark:text-gray-300"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-4l-4 4z" />
                                </svg>
                            </div>
                            <div class="ml-3 max-w-2xl">
                                <div class="bg-gray-100 dark:bg-gray-800 p-3 rounded-lg">
                                    <div class="animate-pulse flex space-x-4">
                                        <div class="flex-1 space-y-2 py-1">
                                            <div class="h-2 bg-gray-300 dark:bg-gray-600 rounded"></div>
                                            <div class="h-2 bg-gray-300 dark:bg-gray-600 rounded"></div>
                                            <div class="h-2 bg-gray-300 dark:bg-gray-600 rounded w-5/6"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Zone de saisie du message -->
                    <div class="p-4 border-t border-gray-200 dark:border-gray-700">
                        <form @submit.prevent="sendMessage" class="flex">
                            <textarea v-model="messageInput" placeholder="Écrivez votre message..."
                                class="flex-1 p-2 border border-gray-300 dark:border-gray-600 rounded-l-md focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:bg-gray-800 dark:text-white"
                                rows="1" @keydown.enter.prevent="sendMessage"></textarea>
                            <button type="submit"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-r-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50"
                                :disabled="isLoading || !messageInput.trim()">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20"
                                    fill="currentColor">
                                    <path
                                        d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- État vide (pas de conversation sélectionnée) -->
                <div v-else class="flex items-center justify-center h-full">
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
                            Commencez une nouvelle conversation pour interagir avec l'IA.
                        </p>
                        <button @click="createNewConversation"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Nouvelle conversation
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
