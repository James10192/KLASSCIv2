<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Models\ParentChatbotOnboardingBatch;
use App\Services\ParentChatbot\ParentChatbotOnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParentChatbotOnboardingController extends Controller
{
    public function index(): View
    {
        $batch = ParentChatbotOnboardingBatch::query()
            ->with('actor')
            ->latest('id')
            ->first();

        return view('esbtp.mailpulse.parent-chatbot-onboarding', compact('batch'));
    }

    public function start(Request $request, ParentChatbotOnboardingService $onboarding): RedirectResponse
    {
        try {
            $batch = $onboarding->start($request->user()?->id);
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        if ($batch->total_count === 0) {
            return back()->with('success', 'Aucun tuteur éligible à activer.');
        }

        return back()->with('success', 'Le lot d’activation a été créé. Le traitement se poursuit en arrière-plan.');
    }

    public function cancel(
        ParentChatbotOnboardingBatch $batch,
        ParentChatbotOnboardingService $onboarding,
    ): RedirectResponse {
        $batch = $onboarding->cancel($batch);

        if (! $batch->isCancelled()) {
            return back()->with('error', 'Ce lot ne peut plus être arrêté.');
        }

        return back()->with('success', 'L’arrêt du lot est enregistré. Les transmissions déjà soumises seront réconciliées.');
    }
}
