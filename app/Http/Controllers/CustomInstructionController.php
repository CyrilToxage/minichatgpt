<?php

namespace App\Http\Controllers;

use App\Models\CustomInstruction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CustomInstructionController extends Controller
{
    /**
     * Display the custom instructions settings page.
     */
    public function index()
    {
        $user = Auth::user();
        $instructions = $user->getActiveInstructions();

        return Inertia::render('CustomInstructions/Index', [
            'instructions' => $instructions,
        ]);
    }

    /**
     * Update the user's custom instructions.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'about_you' => 'nullable|string|max:2000',
            'assistant_behavior' => 'nullable|string|max:2000',
            'custom_commands' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $instructions = $user->getActiveInstructions();
        $instructions->update($validated);

        return back()->with('success', 'Instructions personnalisées mises à jour avec succès.');
    }

    /**
     * Add a new custom command.
     */
    public function addCommand(Request $request)
    {
        $user = Auth::user();
        $instructions = $user->getActiveInstructions();

        $validated = $request->validate([
            'command' => 'required|string|max:50',
            'description' => 'required|string|max:500',
        ]);

        $commands = $instructions->custom_commands ?? [];
        $commands[] = [
            'command' => $validated['command'],
            'description' => $validated['description'],
        ];

        $instructions->update([
            'custom_commands' => $commands,
        ]);

        return back()->with('success', 'Commande personnalisée ajoutée avec succès.');
    }

    /**
     * Remove a custom command.
     */
    public function removeCommand(Request $request)
    {
        $user = Auth::user();
        $instructions = $user->getActiveInstructions();

        $validated = $request->validate([
            'index' => 'required|integer|min:0',
        ]);

        $commands = $instructions->custom_commands ?? [];

        if (isset($commands[$validated['index']])) {
            array_splice($commands, $validated['index'], 1);

            $instructions->update([
                'custom_commands' => $commands,
            ]);

            return back()->with('success', 'Commande personnalisée supprimée avec succès.');
        }

        return back()->with('error', 'Commande introuvable.');
    }

    /**
     * Toggle the active state of custom instructions.
     */
    public function toggleActive()
    {
        $user = Auth::user();
        $instructions = $user->getActiveInstructions();

        $instructions->update([
            'is_active' => !$instructions->is_active,
        ]);

        $status = $instructions->is_active ? 'activées' : 'désactivées';

        return back()->with('success', "Instructions personnalisées $status avec succès.");
    }
}
