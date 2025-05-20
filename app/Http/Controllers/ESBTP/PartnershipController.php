<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ESBTP\Partnership;

class PartnershipController extends Controller
{
    public function index()
    {
        $partnerships = Partnership::latest()->get();
        return view('esbtp.partnerships.index', compact('partnerships'));
    }

    public function create()
    {
        return view('esbtp.partnerships.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'organization' => 'required|string|max:255',
            'type' => 'required|string|in:academic,industry,research,other',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'description' => 'nullable|string',
            'contact_person' => 'required|string|max:255',
            'contact_email' => 'required|email',
            'contact_phone' => 'nullable|string|max:20',
            'status' => 'required|string|in:active,pending,expired',
        ]);

        Partnership::create($validated);

        return redirect()->route('esbtp.partnerships.index')
            ->with('success', 'Partenariat créé avec succès.');
    }

    public function show(Partnership $partnership)
    {
        return view('esbtp.partnerships.show', compact('partnership'));
    }

    public function edit(Partnership $partnership)
    {
        return view('esbtp.partnerships.edit', compact('partnership'));
    }

    public function update(Request $request, Partnership $partnership)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'organization' => 'required|string|max:255',
            'type' => 'required|string|in:academic,industry,research,other',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'description' => 'nullable|string',
            'contact_person' => 'required|string|max:255',
            'contact_email' => 'required|email',
            'contact_phone' => 'nullable|string|max:20',
            'status' => 'required|string|in:active,pending,expired',
        ]);

        $partnership->update($validated);

        return redirect()->route('esbtp.partnerships.index')
            ->with('success', 'Partenariat mis à jour avec succès.');
    }

    public function destroy(Partnership $partnership)
    {
        $partnership->delete();
        return redirect()->route('esbtp.partnerships.index')
            ->with('success', 'Partenariat supprimé avec succès.');
    }
}
