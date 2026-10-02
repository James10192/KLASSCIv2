<?php

namespace App\Http\Controllers\Notes;

use App\Domain\Notes\Reclamations\PresentationReclamation;
use App\Domain\Notes\Reclamations\ReclamationsDeNotes;
use App\Domain\Notes\Reclamations\ReglagesReclamations;
use App\Http\Controllers\Controller;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPNote;
use App\Models\ESBTPReclamationNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * « Mes réclamations » : l'élève conteste une note et suit la réponse.
 */
class MesReclamationsController extends Controller
{
    public function __construct(
        private readonly ReclamationsDeNotes $reclamations,
        private readonly ReglagesReclamations $reglages,
    ) {}

    public function index(Request $request)
    {
        $etudiant = $this->etudiant();

        $reclamations = ESBTPReclamationNote::with(PresentationReclamation::relations())
            ->where('etudiant_id', $etudiant->id)
            ->latest()
            ->get()
            ->map(fn ($r) => PresentationReclamation::pour($r, false))
            ->values();

        return view('esbtp.reclamations-notes.mes-reclamations', [
            'reclamations' => $reclamations,
            'notesContestables' => $this->notesContestables($etudiant),
            'noteChoisie' => $request->integer('note') ?: null,
            'actives' => $this->reglages->actives(),
            'delaiJours' => $this->reglages->delaiJours(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $valide = $request->validate([
            'note_id' => ['required', 'integer'],
            'motif' => ['required', 'string', 'min:10', 'max:2000'],
            'photo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,heic,heif,pdf', 'max:8192'],
        ], [
            'motif.min' => 'Expliquez votre réclamation en au moins 10 caractères.',
            'photo.required' => 'La photo de votre copie corrigée est obligatoire.',
            'photo.mimes' => 'La photo doit être une image (JPG, PNG, WEBP, HEIC) ou un PDF.',
            'photo.max' => 'La photo ne doit pas dépasser 8 Mo.',
        ]);

        $etudiant = $this->etudiant();
        $note = ESBTPNote::with('evaluation')->findOrFail($valide['note_id']);

        $reclamation = $this->reclamations->deposer($note, $etudiant, $valide['motif'], $request->file('photo'), $request->user());
        $reclamation->load(PresentationReclamation::relations());

        return response()->json([
            'message' => 'Réclamation envoyée. L\'enseignant et le personnel habilité sont prévenus.',
            'reclamation' => PresentationReclamation::pour($reclamation, false),
        ], 201);
    }

    public function photo(int $id)
    {
        $r = ESBTPReclamationNote::where('etudiant_id', $this->etudiant()->id)->findOrFail($id);
        abort_unless($r->photo_path && Storage::disk(ReclamationsDeNotes::DISQUE)->exists($r->photo_path), 404);

        return Storage::disk(ReclamationsDeNotes::DISQUE)->response($r->photo_path);
    }

    private function etudiant(): ESBTPEtudiant
    {
        return ESBTPEtudiant::where('user_id', auth()->id())->firstOrFail();
    }

    /**
     * Les notes de l'année en cours encore contestables, pour le sélecteur.
     *
     * @return list<array{id:int, label:string, sous_titre:string}>
     */
    private function notesContestables(ESBTPEtudiant $etudiant): array
    {
        $annee = ESBTPAnneeUniversitaire::where('is_current', true)->first();
        if (! $annee || ! $this->reglages->actives()) {
            return [];
        }

        $ouvertes = ESBTPReclamationNote::where('etudiant_id', $etudiant->id)->ouvertes()->pluck('note_id')->all();
        $limite = now()->subDays($this->reglages->delaiJours());

        return ESBTPNote::with(['evaluation:id,titre,type,bareme,date_evaluation,annee_universitaire_id,matiere_id', 'evaluation.matiere:id,name'])
            ->where('etudiant_id', $etudiant->id)
            ->whereNotIn('id', $ouvertes)
            ->where('updated_at', '>=', $limite)
            ->whereHas('evaluation', fn ($q) => $q->where('annee_universitaire_id', $annee->id))
            ->latest('updated_at')
            ->get()
            ->map(fn (ESBTPNote $n) => [
                'id' => (int) $n->id,
                'label' => ($n->evaluation->matiere->name ?? 'Matière').' — '.($n->evaluation->titre ?? 'Évaluation'),
                'sous_titre' => ($n->is_absent ? 'Absent' : number_format((float) $n->note, 2, ',', ' ').' / '.rtrim(rtrim(number_format((float) ($n->evaluation->bareme ?: 20), 2, '.', ''), '0'), '.'))
                    .' · contestable jusqu\'au '.$n->updated_at->copy()->addDays($this->reglages->delaiJours())->format('d/m/Y'),
            ])
            ->values()
            ->all();
    }
}
