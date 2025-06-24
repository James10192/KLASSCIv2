<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ESBTP\Payment;
use App\Models\ESBTPCategoriePaiement;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTP\PaymentCategory;

class PaymentController extends Controller
{
    public function index()
    {
        $payments = Payment::with(['student', 'category', 'fee', 'inscription.etudiant'])
            ->latest()
            ->paginate(20);
        return view('esbtp.payments.index', compact('payments'));
    }

    public function create(Request $request)
    {
        $inscriptionId = $request->get('inscription_id');
        $categories = PaymentCategory::where('is_active', true)->orderBy('name')->get();
        $students = ESBTPEtudiant::orderBy('nom')->get();
        $fees = collect();
        if ($inscriptionId) {
            $fees = \App\Models\ESBTP\Fee::where('inscription_id', $inscriptionId)
                ->whereIn('status', ['pending', 'partially_paid'])
                ->orderBy('due_date')->get();
        }
        return view('esbtp.payments.create', compact('inscriptionId', 'categories', 'students', 'fees'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'inscription_id' => 'nullable|exists:esbtp_inscriptions,id',
            'fee_id' => 'nullable|exists:fees,id',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string|in:cash,bank_transfer,check,mobile_money',
            'reference_number' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:payment_categories,id',
            'status' => 'required|string|in:pending,completed,failed,refunded',
        ]);

        $payment = Payment::create($validated);

        // Si le paiement est lié à une échéance/frais, mettre à jour le statut du Fee
        if (!empty($validated['fee_id'])) {
            $fee = \App\Models\ESBTP\Fee::find($validated['fee_id']);
            if ($fee) {
                $totalPaid = $fee->payments()->where('status', 'completed')->sum('amount');
                if ($totalPaid >= $fee->amount) {
                    $fee->status = 'paid';
                } elseif ($totalPaid > 0) {
                    $fee->status = 'partially_paid';
                } else {
                    $fee->status = 'pending';
                }
                $fee->save();
            }
        }

        // Redirection intelligente : si paiement lié à une inscription, retour à la fiche d'inscription
        if ($validated['inscription_id'] ?? false) {
            return redirect()->route('esbtp.inscriptions.show', $validated['inscription_id'])
                ->with('success', 'Paiement enregistré avec succès.');
        }

        return redirect()->route('esbtp.payments.index')
            ->with('success', 'Paiement enregistré avec succès.');
    }

    public function show(Payment $payment)
    {
        $payment->load(['student', 'category', 'fee', 'inscription.etudiant']);
        return view('esbtp.payments.show', compact('payment'));
    }

    public function edit(Payment $payment)
    {
        $categories = PaymentCategory::where('is_active', true)->orderBy('name')->get();
        $students = ESBTPEtudiant::orderBy('nom')->get();
        return view('esbtp.payments.edit', compact('payment', 'categories', 'students'));
    }

    public function update(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string|in:cash,bank_transfer,check,mobile_money',
            'reference_number' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'category_id' => 'required|exists:payment_categories,id',
            'status' => 'required|string|in:pending,completed,failed,refunded',
        ]);

        $payment->update($validated);

        return redirect()->route('esbtp.payments.index')
            ->with('success', 'Paiement mis à jour avec succès.');
    }

    public function destroy(Payment $payment)
    {
        $payment->delete();
        return redirect()->route('esbtp.payments.index')
            ->with('success', 'Paiement supprimé avec succès.');
    }

    public function generateReceipt(Payment $payment)
    {
        // Logic to generate receipt
        return view('esbtp.payments.receipt', compact('payment'));
    }
}
