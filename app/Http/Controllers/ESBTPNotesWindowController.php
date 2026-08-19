<?php

namespace App\Http\Controllers;

use App\Models\ESBTPNotesWindow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ESBTPNotesWindowController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:notes.window.manage']);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'classe_id' => 'required|exists:esbtp_classes,id',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after_or_equal:starts_at',
        ]);

        ESBTPNotesWindow::create([
            'classe_id' => $validated['classe_id'],
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'],
            'opened_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Fenetre de notes ouverte.');
    }

    public function close(ESBTPNotesWindow $notesWindow): RedirectResponse
    {
        $notesWindow->update([
            'closed_at' => now(),
            'closed_by' => auth()->id(),
        ]);

        return back()->with('success', 'Fenetre de notes fermee.');
    }
}