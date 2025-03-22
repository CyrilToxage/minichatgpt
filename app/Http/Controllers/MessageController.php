<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    /**
     * Store a new message and get AI response.
     */
    public function store(Request $request, Conversation $conversation)
    {
        $this->authorize('update', $conversation);

        $request->validate([
            'content' => 'required|string',
        ]);

        // Créer le message de l'utilisateur
        $userMessage = $conversation->messages()->create([
            'role' => 'user',
            'content' => $request->content,
        ]);

        // Mettre à jour la date de la conversation
        $conversation->touch();

        // Récupérer tous les messages précédents pour le contexte
        $previousMessages = $conversation->messages()
            ->orderBy('created_at')
            ->get()
            ->map(function ($message) {
                return [
                    'role' => $message->role,
                    'content' => $message->content,
                ];
            })
            ->toArray();

        try {
            $chatService = new ChatService();

            // Envoyer la requête à l'API
            $response = $chatService->sendMessage(
                messages: $previousMessages,
                model: $conversation->model
            );

            // Enregistrer la réponse de l'IA
            $aiMessage = $conversation->messages()->create([
                'role' => 'assistant',
                'content' => $response,
            ]);

            // Générer un titre pour la conversation si c'est le premier échange
            if ($conversation->messages()->count() === 2 && !$conversation->title) {
                return redirect()->to(
                    route('chat.show', $conversation)
                )->with('shouldGenerateTitle', true);
            }

            return back();
        } catch (\Exception $e) {
            // En cas d'erreur, supprimer le message de l'utilisateur pour éviter les incohérences
            $userMessage->delete();

            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }
}
