<script setup>
import { ref, onMounted } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import MarkdownIt from 'markdown-it'
import hljs from 'highlight.js'
import 'highlight.js/styles/github-dark.css'

const props = defineProps({
    models: {
        type: Array,
        required: true
    },
    selectedModel: {
        type: String,
        required: true
    },
    flash: {
        type: Object,
        required: true
    }
})

// Initialisation de markdown-it avec highlight.js
const md = new MarkdownIt({
    html: true,
    highlight: function (str, lang) {
        if (lang && hljs.getLanguage(lang)) {
            try {
                return hljs.highlight(str, { language: lang }).value
            } catch (__) { }
        }
        return '' // Utiliser le surlignage par défaut
    }
})

const form = useForm({
    message: '',
    model: props.selectedModel
})

const submit = () => {
    form.post(route('ask.post'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('message')
        }
    })
}

// Fonction pour convertir le markdown en HTML
const renderMarkdown = (text) => {
    if (!text) return ''
    return md.render(text)
}
</script>

<template>
    <AppLayout title="Poser une question">

        <Head title="Poser une question" />

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <!-- Formulaire -->
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Sélection du modèle -->
                        <div>
                            <label for="model" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Modèle d'IA
                            </label>
                            <select id="model" v-model="form.model"
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md dark:bg-gray-700 dark:border-gray-600">
                                <option v-for="model in models" :key="model.id" :value="model.id">
                                    {{ model.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Zone de texte pour la question -->
                        <div>
                            <label for="message" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                Votre question
                            </label>
                            <textarea id="message" v-model="form.message" rows="4"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                placeholder="Posez votre question ici..."></textarea>
                        </div>

                        <!-- Bouton d'envoi -->
                        <div class="flex justify-end">
                            <button type="submit" :disabled="form.processing"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                <span v-if="form.processing">Envoi en cours...</span>
                                <span v-else>Envoyer</span>
                            </button>
                        </div>
                    </form>

                    <!-- Affichage des erreurs -->
                    <div v-if="flash.error"
                        class="mt-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative"
                        role="alert">
                        <span class="block sm:inline">{{ flash.error }}</span>
                    </div>

                    <!-- Affichage de la réponse -->
                    <div v-if="flash.message" class="mt-6">
                        <div class="prose dark:prose-invert max-w-none" v-html="renderMarkdown(flash.message)"></div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
