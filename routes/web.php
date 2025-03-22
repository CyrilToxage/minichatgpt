<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\AskController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\CustomInstructionController;
use Illuminate\Support\Facades\Broadcast;
use App\Models\Conversation;
use App\Models\User;
use App\Http\Controllers\MessageStreamController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
    Route::get('/chat', [ConversationController::class, 'index'])->name('chat.index');
    Route::get('/instructions', [CustomInstructionController::class, 'index'])->name('instructions.index');


    // Routes existantes pour Ask
    Route::get('/ask', [AskController::class, 'index'])->name('ask.index');
    Route::post('/ask', [AskController::class, 'ask'])->name('ask.post');

    // Routes pour le chat
    Route::get('/chat', [ConversationController::class, 'index'])->name('chat.index');
    Route::get('/chat/{conversation}', [ConversationController::class, 'show'])->name('chat.show');
    Route::post('/chat', [ConversationController::class, 'store'])->name('chat.store');
    Route::put('/chat/{conversation}/model', [ConversationController::class, 'updateModel'])->name('chat.update-model');
    Route::post('/chat/{conversation}/generate-title', [ConversationController::class, 'generateTitle'])->name('chat.generate-title');
    Route::post('/chat/{conversation}/messages', [MessageController::class, 'store'])->name('chat.messages.store');

    // Routes pour les instructions personnalisées
    Route::get('/instructions', [CustomInstructionController::class, 'index'])->name('instructions.index');
    Route::put('/instructions', [CustomInstructionController::class, 'update'])->name('instructions.update');
    Route::post('/instructions/commands', [CustomInstructionController::class, 'addCommand'])->name('instructions.add-command');
    Route::delete('/instructions/commands', [CustomInstructionController::class, 'removeCommand'])->name('instructions.remove-command');
    Route::post('/instructions/toggle', [CustomInstructionController::class, 'toggleActive'])->name('instructions.toggle');

    // Route pour le streaming
    Route::post('/chat/{conversation}/stream', [MessageStreamController::class, 'streamMessage'])->name('chat.stream');
});

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chat.{conversation}', function (User $user, Conversation $conversation) {
    return $conversation && $conversation->user_id === $user->id;
});
