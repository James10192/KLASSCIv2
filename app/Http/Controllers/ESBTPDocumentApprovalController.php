<?php

namespace App\Http\Controllers;

use App\Models\ESBTPDocumentApproval;
use App\Services\DocumentPrintGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ESBTPDocumentApprovalController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:documents.approve')->only(['approve', 'reject']);
        $this->middleware('permission:documents.view|documents.approve|documents.print')->only(['store']);
        $this->middleware('permission:documents.approve')->only(['index']);
    }

    /**
     * La file des demandes en attente.
     *
     * Elle n'existait que sur le tableau de bord de la responsable scolarite,
     * garde par `identity.registrar`. Or ce role n'existe que si l'ecole a
     * active `scolarite.split_roles` : sur Yakro, six secretaires portent
     * `documents.approve` et aucune ne voyait jamais une demande. Une boite aux
     * lettres morte, alors que l'accord est la seule sortie du refus.
     *
     * C'est le droit qui gouverne l'action qui garde l'ecran : `documents.approve`.
     */
    public function index(Request $request): View
    {
        $demandes = ESBTPDocumentApproval::query()
            ->enAttente()
            ->with(['etudiant:id,nom,prenoms,matricule', 'demandeur:id,name'])
            ->latest()
            ->paginate(25);

        return view('esbtp.documents.approvals.index', compact('demandes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'document_type' => 'required|in:certificat,attestation,bulletin',
            'etudiant_id' => 'required|exists:esbtp_etudiants,id',
            'document_id' => 'nullable|integer',
        ]);

        $etudiantId = (int) $validated['etudiant_id'];
        $decision = app(DocumentPrintGuard::class)->decide(
            $request->user(),
            $validated['document_type'],
            $etudiantId,
            isset($validated['document_id']) ? (int) $validated['document_id'] : null
        );

        if ($decision->isUnpaid()) {
            return back()->with('error', $decision->message());
        }

        $documentId = isset($validated['document_id']) ? (int) $validated['document_id'] : null;

        // Redemander ne cree pas une seconde ligne : chaque clic en aurait pose
        // une, et la file de qui doit accorder se serait remplie de doublons.
        $existante = ESBTPDocumentApproval::demandeEnAttentePour(
            $validated['document_type'],
            $etudiantId,
            $documentId
        );

        if ($existante) {
            return back()->with('info', 'Une demande est deja en attente pour ce document.');
        }

        ESBTPDocumentApproval::create([
            'document_type' => $validated['document_type'],
            'etudiant_id' => $etudiantId,
            'document_id' => $documentId,
            'status' => ESBTPDocumentApproval::STATUS_PENDING,
            'requested_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Demande d impression envoyee.');
    }

    public function approve(ESBTPDocumentApproval $approval): RedirectResponse
    {
        $approval->update([
            'status' => ESBTPDocumentApproval::STATUS_APPROVED,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Document approuve.');
    }

    public function reject(ESBTPDocumentApproval $approval): RedirectResponse
    {
        $approval->update([
            'status' => ESBTPDocumentApproval::STATUS_REJECTED,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Document refuse.');
    }
}