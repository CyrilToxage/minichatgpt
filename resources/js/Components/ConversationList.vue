<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    conversations: {
        type: Array,
        default: () => [],
    },
    currentId: {
        type: Number,
        default: null,
    },
});

// Tronquer le contenu du dernier message pour l'aperçu
const truncateContent = (content) => {
    if (!content) return '';
    return content.length > 40 ? content.substring(0, 40) + '...' : content;
};

// Formatage de la date
const formatDate = (date) => {
    if (!date) return '';
    const d = new Date(date);
    const now = new Date();

    // Si c'est aujourd'hui, afficher l'heure
    if (d.toDateString() === now.toDateString()) {
        return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    // Sinon afficher la date
    return d.toLocaleDateString();
};
</script>

<template>
    <div class="overflow-y-auto h-[calc(100vh-8rem)]">
        <div v-if="conversations.length === 0" class="p-4 text-center text-gray-500 dark:text-gray-400">
            Aucune conversation
        </div>
        <div v-else>
            <div v-for="conversation in conversations" :key="conversation.id"
                class="p-3 hover:bg-gray-100 dark:hover:bg-gray-800 cursor-pointer border-b border-gray-200 dark:border-gray-700"
                :class="{ 'bg-gray-100 dark:bg-gray-800': conversation.id === currentId }">
                <Link :href="route('chat.show', conversation.id)" class="block">
                <div class="flex justify-between items-start">
                    <div class="font-medium text-gray-900 dark:text-white">
                        {{ conversation.title || 'Nouvelle conversation' }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        {{ formatDate(conversation.updated_at) }}
                    </div>
                </div>
                <div v-if="conversation.latest_message" class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ truncateContent(conversation.latest_message.content) }}
                </div>
                </Link>
            </div>
        </div>
    </div>
</template>
