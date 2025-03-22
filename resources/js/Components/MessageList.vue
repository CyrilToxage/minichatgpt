<script setup>
import { ref, onMounted } from 'vue';
import MarkdownIt from 'markdown-it';
import hljs from 'highlight.js';
import 'highlight.js/styles/github-dark.css';

const props = defineProps({
    messages: {
        type: Array,
        default: () => [],
    },
});

// Configuration de markdown-it avec highlight.js
const md = new MarkdownIt({
    html: true,
    highlight: function (str, lang) {
        if (lang && hljs.getLanguage(lang)) {
            try {
                return hljs.highlight(str, { language: lang }).value;
            } catch (__) { }
        }
        return ''; // Utiliser le surlignage par défaut
    }
});

// Rendu du markdown en HTML
const renderMarkdown = (text) => {
    if (!text) return '';
    return md.render(text);
};

// Formatage de la date du message
const formatMessageTime = (date) => {
    if (!date) return '';
    const d = new Date(date);
    return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
};

onMounted(() => {
    // Charger les styles de highlight.js
    const style = document.createElement('link');
    style.rel = 'stylesheet';
    style.href = 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/github-dark.min.css';
    document.head.appendChild(style);
});
</script>

<template>
    <div>
        <div v-if="messages.length === 0" class="text-center p-8 text-gray-500 dark:text-gray-400">
            Commencez la conversation en posant une question ci-dessous.
        </div>
        <div v-else>
            <div v-for="message in messages" :key="message.id" class="mb-4">
                <!-- Message de l'utilisateur -->
                <div v-if="message.role === 'user'" class="flex items-start justify-end">
                    <div class="mr-2 max-w-2xl">
                        <div class="bg-indigo-100 dark:bg-indigo-900 p-3 rounded-lg">
                            <p class="text-gray-800 dark:text-gray-200">{{ message.content }}</p>
                        </div>
                        <div class="text-xs text-right mt-1 text-gray-500 dark:text-gray-400">
                            {{ formatMessageTime(message.created_at) }}
                        </div>
                    </div>
                    <div class="h-8 w-8 rounded-full bg-indigo-500 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>

                <!-- Message de l'assistant -->
                <div v-else-if="message.role === 'assistant'" class="flex items-start">
                    <div class="h-8 w-8 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-700 dark:text-gray-300"
                            viewBox="0 0 20 20" fill="currentColor">
                            <path
                                d="M8.707 7.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l2-2a1 1 0 00-1.414-1.414L11 7.586V3a1 1 0 10-2 0v4.586l-.293-.293z" />
                            <path
                                d="M3 5a2 2 0 012-2h1a1 1 0 010 2H5v7h2l1 2h4l1-2h2V5h-1a1 1 0 110-2h1a2 2 0 012 2v10.5a1.5 1.5 0 01-1.5 1.5h-9A1.5 1.5 0 013 15.5V5z" />
                        </svg>
                    </div>
                    <div class="ml-2 max-w-2xl">
                        <div class="bg-gray-100 dark:bg-gray-800 p-3 rounded-lg">
                            <div class="prose dark:prose-invert max-w-none" v-html="renderMarkdown(message.content)">
                            </div>
                        </div>
                        <div class="text-xs mt-1 text-gray-500 dark:text-gray-400">
                            {{ formatMessageTime(message.created_at) }}
                        </div>
                    </div>
                </div>

                <!-- Message système (rarement affiché) -->
                <div v-else-if="message.role === 'system'" class="flex items-start">
                    <div class="ml-10 mr-10 my-2 text-xs italic text-gray-500 dark:text-gray-400">
                        {{ message.content }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
