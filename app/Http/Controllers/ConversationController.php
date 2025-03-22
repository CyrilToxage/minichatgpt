<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ConversationController extends Controller
{
    /**
     * Display the chat interface with all conversations.
     */
    public function index()
    {
        $user = Auth::user();

        $conversations = $user->conversations()
            ->with('latestMessage')
            ->orderBy('updated_at', 'desc')
            ->get();

        $models = (new ChatService())->getModels();
        $selectedModel = $user->preferred_model ?? ChatService::DEFAULT_MODEL;

        return Inertia::render('Chat/Index', [
            'conversations' => $conversations,
            'models' => $models,
            'selectedModel' => $selectedModel,
        ]);
    }

    /**
     * Display a specific conversation.
     */
    public function show(Conversation $conversation)
    {
        // Vérification que l'utilisateur est bien le propriétaire de la conversation
        $this->authorize('view', $conversation);

        $user = Auth::user();
        $conversations = $user->conversations()
            ->with('latestMessage')
            ->orderBy('updated_at', 'desc')
            ->get();

        $messages = $conversation->messages()->orderBy('created_at')->get();
        $models = (new ChatService())->getModels();
        $selectedModel = $conversation->model;

        return Inertia::render('Chat/Index', [
            'conversations' => $conversations,
            'currentConversation' => $conversation->load('messages'),
            'models' => $models,
            'selectedModel' => $selectedModel,
        ]);
    }

    /**
     * Create a new conversation.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $model = $request->input('model', $user->preferred_model ?? ChatService::DEFAULT_MODEL);

        $conversation = $user->conversations()->create([
            'model' => $model,
        ]);

        return redirect()->route('chat.show', $conversation);
    }

    /**
     * Update the model used for a conversation.
     */
    public function updateModel(Request $request, Conversation $conversation)
    {
        $this->authorize('update', $conversation);

        $request->validate([
            'model' => 'required|string',
        ]);

        $conversation->update([
            'model' => $request->model,
        ]);

        // Mettre à jour le modèle préféré de l'utilisateur
        $user = Auth::user();
        $user->update([
            'preferred_model' => $request->model,
        ]);

        return back();
    }

    /**
     * Generate a title for the conversation.
     */
    public function generateTitle(Conversation $conversation)
{
    $this->authorize('update', $conversation);

    // Log de début
    \Log::info("Début génération titre pour conversation {$conversation->id}");

    // Récupérer les messages de la conversation pour générer un titre
    $messages = $conversation->messages()->orderBy('created_at')->get();

    if ($messages->count() < 2) {
        \Log::warning("Pas assez de messages pour générer un titre ({$messages->count()})");
        return back();
    }

    // Log pour vérifier le contenu des messages
    \Log::info("Contenu du message utilisateur", ['message' => $messages[0]->content]);
    \Log::info("Contenu de la réponse assistant", ['message' => $messages[1]->content]);

    $chatService = new ChatService();

    // Système de tentatives multiples
    $maxAttempts = 3;
    $validTitle = false;
    $finalTitle = '';

    for ($attempt = 1; $attempt <= $maxAttempts && !$validTitle; $attempt++) {
        \Log::info("Tentative #{$attempt} de génération de titre");

        // Ajuster le prompt pour être encore plus direct et spécifique
        $systemPrompt = "Tu dois créer un titre court (3 à 5 mots) pour cette conversation. IMPORTANT: Ne réponds QUE par le titre lui-même, sans aucun autre texte, sans guillemets, sans points.";

        if ($attempt > 1) {
            // Rendre le prompt plus insistant aux tentatives suivantes
            $systemPrompt .= " C'est très important. NE DIS RIEN D'AUTRE QUE LE TITRE.";
        }

        $messagesToSend = [
            [
                'role' => 'system',
                'content' => $systemPrompt,
            ],
            [
                'role' => 'user',
                'content' => $messages[0]->content,
            ],
            [
                'role' => 'assistant',
                'content' => $messages[1]->content,
            ],
        ];

        try {
            \Log::info("Tentative #{$attempt}: envoi requête avec modèle: {$conversation->model}");

            // Ajuster la température pour obtenir des résultats différents à chaque tentative
            $temperature = 0.2 + (($attempt - 1) * 0.1); // 0.2, 0.3, 0.4

            $title = $chatService->sendMessage(
                messages: $messagesToSend,
                model: $conversation->model,
                temperature: $temperature
            );

            \Log::info("Tentative #{$attempt}: réponse brute: " . $title);

            // Nettoyage plus agressif du titre
            $cleanTitle = trim($title);
            $cleanTitle = preg_replace('/^(titre|title)\s*[:;-]\s*/i', '', $cleanTitle);
            $cleanTitle = preg_replace('/["""\'\'`]/', '', $cleanTitle);
            $cleanTitle = preg_replace('/\.$/', '', $cleanTitle); // Enlever point final
            $cleanTitle = trim($cleanTitle);

            \Log::info("Tentative #{$attempt}: titre nettoyé: " . $cleanTitle);

            // Vérifier si le titre est valide
            if (!empty($cleanTitle) && strlen($cleanTitle) >= 3 && strlen($cleanTitle) <= 50) {
                $validTitle = true;
                $finalTitle = $cleanTitle;
                \Log::info("Tentative #{$attempt}: titre valide trouvé!");
                break;
            } else {
                \Log::warning("Tentative #{$attempt}: titre invalide, nouvelle tentative");
            }
        } catch (\Exception $e) {
            \Log::error("Tentative #{$attempt}: exception", [
                'error_message' => $e->getMessage()
            ]);
        }
    }

    // Si aucun titre valide après toutes les tentatives
    if (!$validTitle) {
        $finalTitle = 'Conversation du ' . now()->format('d/m/Y H:i');
        \Log::warning("Aucun titre valide généré après {$maxAttempts} tentatives, utilisation du titre par défaut");
    }

    \Log::info("Titre final à enregistrer: " . $finalTitle);

    $result = $conversation->update([
        'title' => $finalTitle
    ]);

    \Log::info("Résultat de la mise à jour dans la base de données: " . ($result ? 'Succès' : 'Échec'));

    // Vérification finale
    $conversation->refresh();
    \Log::info("Titre enregistré en base de données: " . $conversation->title);

    return back();
}
}
