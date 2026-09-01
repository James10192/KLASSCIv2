<?php

namespace App\Http\Controllers;

use App\Models\ESBTPDocumentApproval;
use App\Services\DocumentPrintGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ESBTPDocumentApprovalController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:documents.approve')->only(['approve', 'reject']);
        $this->middleware('permission:documents.view|documents.approve|documents.print')->only(['store']);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'document_type' => 'required|in:certificat,attestation,bulletin',
            'etudiant_id' => 'required|exists:esbtp_etudiants,id',
            'document_id' => 'nullable|integer',
        ]);

        $etudiantId = (int) $validated['etudiant_id'];
        $guard = app(DocumentPrintGuard::class);

        if ($guard->requiresApproval() && $guard->soldeImpaye($etudiantId) > 0) {
            return back()->with('error', $guard->message(DocumentPrintGuard::DENY_SOLDE, $etudiantId));
        }

        ESBTPDocumentApproval::create([
            'document_type' => $validated['document_type'],
            'etudiant_id' => $etudiantId,
            'document_id' => $validated['document_id'] ?? null,
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