<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ChatService
{
    private $baseUrl;
    private $apiKey;
    private $client;
    public const DEFAULT_MODEL = 'meta-llama/llama-3.2-11b-vision-instruct:free';

    public function __construct()
    {
        $this->baseUrl = config('services.openrouter.base_url', 'https://openrouter.ai/api/v1');
        $this->apiKey = config('services.openrouter.api_key');
        $this->client = $this->createOpenAIClient();
    }

    /**
     * @return array<array-key, array{
     *     id: string,
     *     name: string,
     *     context_length: int,
     *     max_completion_tokens: int,
     *     pricing: array{prompt: int, completion: int}
     * }>
     */
    public function getModels(): array
    {
        return cache()->remember('openai.models', now()->addHour(), function () {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
            ])->get($this->baseUrl . '/models');

            return collect($response->json()['data'])
                ->filter(function ($model) {
                    return str_ends_with($model['id'], ':free');
                })
                ->sortBy('name')
                ->map(function ($model) {
                    return [
                        'id' => $model['id'],
                        'name' => $model['name'],
                        'context_length' => $model['context_length'],
                        'max_completion_tokens' => $model['top_provider']['max_completion_tokens'],
                        'pricing' => $model['pricing'],
                    ];
                })
                ->values()
                ->all()
            ;
        });
    }

    /**
     * @param array{role: 'user'|'assistant'|'system'|'function', content: string} $messages
     * @param string|null $model
     * @param float $temperature
     *
     * @return string
     */
    public function sendMessage(array $messages, string $model = null, float $temperature = 0.7): string
    {
        try {
            logger()->info('Envoi du message', [
                'model' => $model,
                'temperature' => $temperature,
            ]);

            $models = collect($this->getModels());
            if (!$model || !$models->contains('id', $model)) {
                $model = self::DEFAULT_MODEL;
                logger()->info('Modèle par défaut utilisé:', ['model' => $model]);
            }

            $messages = [$this->getChatSystemPrompt(), ...$messages];

            // Traiter les commandes personnalisées
            $messages = $this->processCustomCommands($messages);

            $response = $this->client->chat()->create([
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
            ]);

            logger()->info('Réponse reçue:', ['response' => $response]);

            $content = $response->choices[0]->message->content;

            return $content;
        } catch (\Exception $e) {
            if ($e->getMessage() === 'Undefined array key "choices"') {
                throw new \Exception("Limite de messages atteinte");
            }

            logger()->error('Erreur dans sendMessage:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    private function createOpenAIClient(): \OpenAI\Client
    {
        return \OpenAI::factory()
            ->withApiKey($this->apiKey)
            ->withBaseUri($this->baseUrl)
            ->make()
        ;
    }

    /**
     * @return array{role: 'system', content: string}
     */
    private function getChatSystemPrompt(): array
    {
        $user = auth()->user();
        $now = now()->locale('fr')->format('l d F Y H:i');

        $systemPrompt = <<<EOT
Tu es un assistant de chat. La date et l'heure actuelle est le {$now}.
Tu es actuellement utilisé par {$user->name}.
EOT;

        // Ajouter les instructions personnalisées si elles sont actives
        $customInstructions = $user->customInstruction;
        if ($customInstructions && $customInstructions->is_active) {
            if (!empty($customInstructions->about_you)) {
                $systemPrompt .= "\n\nÀ propos de l'utilisateur : " . $customInstructions->about_you;
            }

            if (!empty($customInstructions->assistant_behavior)) {
                $systemPrompt .= "\n\nComportement préféré : " . $customInstructions->assistant_behavior;
            }

            if (!empty($customInstructions->custom_commands)) {
                $systemPrompt .= "\n\nCommandes personnalisées disponibles :";
                foreach ($customInstructions->custom_commands as $command) {
                    $systemPrompt .= "\n- /{$command['command']} : {$command['description']}";
                }
            }
        }

        return [
            'role' => 'system',
            'content' => $systemPrompt,
        ];
    }

    /**
     * Vérifie si un message contient une commande personnalisée et la traite.
     *
     * @param array $messages Les messages à envoyer
     * @return array Les messages modifiés si une commande est détectée
     */
    private function processCustomCommands(array $messages): array
    {
        // Vérifier si le dernier message est une commande
        $lastMessage = end($messages);
        if ($lastMessage['role'] !== 'user' || !str_starts_with($lastMessage['content'], '/')) {
            return $messages;
        }

        $user = auth()->user();
        $customInstructions = $user->customInstruction;

        if (!$customInstructions || !$customInstructions->is_active || empty($customInstructions->custom_commands)) {
            return $messages;
        }

        // Extraire la commande (premier mot après le /)
        $parts = explode(' ', $lastMessage['content']);
        $commandText = substr($parts[0], 1); // Enlever le /
        $params = array_slice($parts, 1); // Paramètres supplémentaires

        // Rechercher la commande dans les commandes personnalisées
        foreach ($customInstructions->custom_commands as $command) {
            if ($command['command'] === $commandText) {
                // Modifier le message pour indiquer qu'il s'agit d'une commande
                $messages[] = [
                    'role' => 'system',
                    'content' => "L'utilisateur a utilisé la commande /{$commandText}. Description: {$command['description']}. Paramètres: " . implode(' ', $params)
                ];
                break;
            }
        }

        return $messages;
    }

    /**
     * Stream a conversation with the LLM.
     *
     * @param array $messages
     * @param string|null $model
     * @param float $temperature
     * @return \OpenAI\Responses\StreamResponse
     */
    public function streamConversation(array $messages, ?string $model = null, float $temperature = 0.7)
    {
        try {
            logger()->info('Début streamConversation', [
                'model' => $model,
                'temperature' => $temperature,
            ]);

            $models = collect($this->getModels());
            if (!$model || !$models->contains('id', $model)) {
                $model = self::DEFAULT_MODEL;
                logger()->info('Modèle par défaut utilisé:', ['model' => $model]);
            }

            $messages = [$this->getChatSystemPrompt(), ...$messages];

            // Méthode "createStreamed" qui renvoie un flux "StreamResponse"
            return $this->client->chat()->createStreamed([
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
            ]);
        } catch (\Exception $e) {
            logger()->error('Erreur dans streamConversation:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
