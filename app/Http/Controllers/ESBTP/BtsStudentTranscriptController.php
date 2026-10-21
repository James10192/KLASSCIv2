<?php

namespace App\Http\Controllers\ESBTP;

use App\Http\Controllers\Controller;
use App\Helpers\SettingsHelper;
use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPInscription;
use App\Services\ESBTP\BtsStudentAcademicRecords;
use App\Services\ESBTP\BtsProvisionalTranscript;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Relevé interne BTS selon l'état de publication :
 *  - consultatif et non officiel depuis les notes disponibles sans bulletin publié ;
 *  - publié exclusivement depuis les bulletins publiés, sans recalcul.
 *
 * Distinct du relevé national de l'examen BTS (DEXCO).
 * Aucune écriture de données ou publication n'a lieu ici.
 */
final class BtsStudentTranscriptController extends Controller
{
    public function preview(
        Request $request,
        ESBTPEtudiant $etudiant,
        ESBTPAnneeUniversitaire $annee,
        BtsStudentAcademicRecords $records,
        BtsProvisionalTranscript $provisional,
    ): Response {
        return $this->render($request, $etudiant, $annee, $records, $provisional, false);
    }

    public function download(
        Request $request,
        ESBTPEtudiant $etudiant,
        ESBTPAnneeUniversitaire $annee,
        BtsStudentAcademicRecords $records,
        BtsProvisionalTranscript $provisional,
    ): Response {
        return $this->render($request, $etudiant, $annee, $records, $provisional, true);
    }

    private function render(
        Request $request,
        ESBTPEtudiant $etudiant,
        ESBTPAnneeUniversitaire $annee,
        BtsStudentAcademicRecords $records,
        BtsProvisionalTranscript $provisional,
        bool $download,
    ): Response {
        // Les droits individuels distinguent consultation et téléchargement :
        // l'accès général aux étudiants ou aux bulletins n'autorise pas ce PDF.
        abort_unless(
            $request->user()?->can($download ? 'bts.releve.export' : 'bts.releve.view'),
            403
        );

        $validated = $request->validate([
            'periode' => ['nullable', 'in:annuel,semestre1,semestre2'],
        ]);
        $periode = $validated['periode'] ?? 'annuel';

        // Ne jamais inférer une inscription depuis la dernière classe de
        // l'étudiant : un transfert ou une réinscription fausserait le relevé.
        $inscriptions = ESBTPInscription::query()
            ->with(['classe', 'anneeUniversitaire'])
            ->where('etudiant_id', $etudiant->id)
            ->where('annee_universitaire_id', $annee->id)
            ->whereHas('classe', fn ($query) => $query
                ->where('systeme_academique', 'BTS')
                ->orWhereNull('systeme_academique'))
            ->get();

        abort_if($inscriptions->isEmpty(), 404, 'Aucune inscription BTS pour cette année.');
        abort_if($inscriptions->count() !== 1, 409,
            "Plusieurs inscriptions BTS existent cette année. Vérifiez le dossier académique avant d'émettre un relevé.");

        $inscription = $inscriptions->first();
        $bulletins = $records->bulletins((int) $etudiant->id, $inscription)
            ->filter(fn ($bulletin) => (bool) $bulletin->is_published)
            ->filter(fn ($bulletin) => $periode === 'annuel'
                || in_array((string) $bulletin->periode, [
                    $periode,
                    $periode === 'semestre1' ? '1' : '2',
                ], true))
            ->values();

        // Le relevé interne publié ne reprend QUE les bulletins publiés.
        // Avant la publication, un document provisoire peut lire les notes
        // courantes du moteur BTS, sans publier ni recalculer les bulletins.
        if ($bulletins->isEmpty()) {
            $sections = $provisional->sections((int) $etudiant->id, $inscription, $periode, $records);
            if (! BtsProvisionalTranscript::hasResults($sections)) {
                return response()->view('esbtp.bts.releves.indisponible', [
                    'annee' => $annee,
                    'periode' => $periode,
                ], 422);
            }

            $pdf = Pdf::loadView('pdf.bts-releve-provisoire', [
                'etudiant' => $etudiant,
                'annee' => $annee,
                'sections' => $sections,
                'periode' => $periode,
                'generatedAt' => now(),
            ])->setPaper('a4', 'portrait');

            $filename = 'releve-PROVISOIRE-bts-'.$etudiant->id.'-'.$annee->id.'-'.$periode.'.pdf';

            return $download ? $pdf->download($filename) : $pdf->stream($filename);
        }

        $bulletins->load(['classe', 'resultats.matiere']);

        // Un seul bulletin publié par semestre. Des doublons historiques
        // nécessitent une correction, jamais une fusion silencieuse.
        $periods = $bulletins->map(fn ($b) =>
            in_array((string) $b->periode, ['semestre1', '1'], true) ? 1 : 2
        );
        abort_if($periods->unique()->count() !== $periods->count(), 409,
            'Plusieurs bulletins publiés existent pour un même semestre. Vérifiez le dossier.');

        foreach ($bulletins as $bulletin) {
            abort_if($bulletin->resultats->isEmpty(), 422,
                "Un bulletin publié n'a pas de matières : relevé non généré.");
            abort_if(($bulletin->classe?->systeme_academique ?? 'BTS') === 'LMD', 422,
                'Un bulletin non BTS a été trouvé dans ce dossier.');
            abort_if($bulletin->resultats->contains(
                fn ($ligne) => $ligne->matiere && $ligne->matiere->unite_enseignement_id !== null
            ), 422, "Une matière LMD figure sur un bulletin BTS. Corrigez le bulletin avant l'export.");
        }

        $schoolName = SettingsHelper::get('school_name', config('app.name', 'KLASSCI'));
        $pdf = Pdf::loadView('pdf.bts-releve-interne', [
            'etudiant' => $etudiant,
            'annee' => $annee,
            'bulletins' => $bulletins,
            'periode' => $periode,
            'schoolName' => $schoolName,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        $filename = 'releve-interne-bts-'.$etudiant->id.'-'.$annee->id.'-'.$periode.'.pdf';

        return $download ? $pdf->download($filename) : $pdf->stream($filename);
    }
}
