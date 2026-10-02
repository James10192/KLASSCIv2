<?php

namespace App\Http\Controllers\Notes;

use App\Domain\Notes\Reclamations\DestinatairesReclamation;
use App\Domain\Notes\Reclamations\PresentationReclamation;
use App\Domain\Notes\Reclamations\ReclamationsDeNotes;
use App\Enums\StatutReclamationNote;
use App\Http\Controllers\Controller;
use App\Models\ESBTPReclamationNote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Le traitement des réclamations : l'enseignant de l'évaluation donne son
 * avis, le porteur de « notes.reclamations.traiter » tranche.
 *
 * L'enseignant ne voit que les réclamations sur SES évaluations ; la
 * personnel habilité les voit toutes.
 */
class ReclamationNoteController extends Controller
{
    public function __construct(
        private readonly ReclamationsDeNotes $reclamations,
        private readonly DestinatairesReclamation $destinataires,
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user->can(DestinatairesReclamation::PERMISSION_TRAITER) || $user->can('identity.teach'), 403);

        $lignes = $this->visibles()
            ->with(PresentationReclamation::relations())
            ->orderByRaw("CASE WHEN statut IN ('soumise','avis_donne') THEN 0 ELSE 1 END")
            ->latest()
            ->limit(500)
            ->get()
            ->map(fn ($r) => PresentationReclamation::pour($r, true))
            ->values();

        $base = $this->visibles();

        return view('esbtp.reclamations-notes.index', [
            'reclamations' => $lignes,
            'kpis' => [
                'a_traiter' => (clone $base)->where('statut', StatutReclamationNote::AVIS_DONNE->value)->count(),
                'sans_avis' => (clone $base)->where('statut', StatutReclamationNote::SOUMISE->value)->count(),
                'acceptees_30j' => (clone $base)->where('statut', StatutReclamationNote::ACCEPTEE->value)->where('decision_at', '>=', now()->subDays(30))->count(),
                'rejetees_30j' => (clone $base)->where('statut', StatutReclamationNote::REJETEE->value)->where('decision_at', '>=', now()->subDays(30))->count(),
            ],
            'peutTrancher' => $user->can(DestinatairesReclamation::PERMISSION_TRAITER),
            'moi' => (int) $user->id,
            'ouverte' => $request->integer('reclamation') ?: null,
        ]);
    }

    public function avis(Request $request, int $id): JsonResponse
    {
        $r = $this->trouver($id);
        $valide = $request->validate([
            'avis' => ['required', Rule::in([ESBTPReclamationNote::AVIS_CONFIRMER, ESBTPReclamationNote::AVIS_CORRIGER])],
            'note_proposee' => ['nullable', 'required_if:avis,'.ESBTPReclamationNote::AVIS_CORRIGER, 'numeric', 'min:0'],
            'commentaire' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'note_proposee.required_if' => 'Indiquez la note que vous proposez.',
            'commentaire.min' => 'Expliquez votre avis en au moins 10 caractères.',
        ]);

        $r = $this->reclamations->donnerAvis($r, $request->user(), $valide['avis'], isset($valide['note_proposee']) ? (float) $valide['note_proposee'] : null, $valide['commentaire']);

        return $this->reponse($r, 'Avis envoyé au personnel habilité.');
    }

    public function decision(Request $request, int $id): JsonResponse
    {
        abort_unless($request->user()->can(DestinatairesReclamation::PERMISSION_TRAITER), 403);
        $r = $this->trouver($id);
        $valide = $request->validate([
            'decision' => ['required', Rule::in(['accepter', 'refuser'])],
            'note_finale' => ['nullable', 'required_if:decision,accepter', 'numeric', 'min:0'],
            'commentaire' => ['nullable', 'required_if:decision,refuser', 'string', 'min:10', 'max:2000'],
        ], [
            'note_finale.required_if' => 'Indiquez la note corrigée.',
            'commentaire.required_if' => 'Expliquez à l\'élève pourquoi sa note est maintenue.',
            'commentaire.min' => 'Le motif doit faire au moins 10 caractères.',
        ]);

        $accepter = $valide['decision'] === 'accepter';
        $r = $this->reclamations->trancher($r, $request->user(), $accepter, $accepter ? (float) $valide['note_finale'] : null, $valide['commentaire'] ?? null);

        return $this->reponse($r, $accepter ? 'Note corrigée, moyennes recalculées. L\'élève est prévenu.' : 'Note maintenue. L\'élève est prévenu.');
    }

    public function photo(Request $request, int $id)
    {
        $r = $this->trouver($id);
        abort_unless($r->photo_path && Storage::disk(ReclamationsDeNotes::DISQUE)->exists($r->photo_path), 404);

        return Storage::disk(ReclamationsDeNotes::DISQUE)->response($r->photo_path);
    }

    private function visibles(): Builder
    {
        $user = auth()->user();
        $q = ESBTPReclamationNote::query();

        return $user->can(DestinatairesReclamation::PERMISSION_TRAITER) ? $q : $q->where('enseignant_id', $user->id);
    }

    private function trouver(int $id): ESBTPReclamationNote
    {
        $r = ESBTPReclamationNote::with(PresentationReclamation::relations())->findOrFail($id);
        abort_unless($this->destinataires->peutVoir(auth()->user(), $r), 403);

        return $r;
    }

    private function reponse(ESBTPReclamationNote $r, string $message): JsonResponse
    {
        $r = ESBTPReclamationNote::with(PresentationReclamation::relations())->findOrFail($r->id);

        return response()->json(['message' => $message, 'reclamation' => PresentationReclamation::pour($r, true)]);
    }
}
