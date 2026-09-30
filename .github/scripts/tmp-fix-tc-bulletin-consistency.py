from pathlib import Path

# 1) Expose the exact evaluation-class perimeter used by official bulletin generation.
service = Path('app/Services/BulletinService.php')
text = service.read_text()
anchor = "    private function evaluationClassIdsForBulletin(int $etudiantId, int $classeId, int $anneeUniversitaireId, string $periode): array\n"
if anchor not in text:
    raise SystemExit('BulletinService anchor not found')
helper = '''    /**
     * Perimetre des evaluations que le snapshot "Courant" doit lire.
     *
     * Il doit rester strictement identique a celui de la generation officielle :
     * classes TC/specialite resolues par phase, moins les classes pas encore
     * ouvertes pour la periode. Sans cette source unique, le bandeau
     * Officiel/Courant peut annoncer un ecart impossible a effacer.
     *
     * @return list<int>
     */
    public function evaluationClassIdsForSnapshot(int $etudiantId, int $classeId, int $anneeUniversitaireId, string $periode): array
    {
        $ids = $this->evaluationClassIdsForBulletin($etudiantId, $classeId, $anneeUniversitaireId, $periode);
        $fermees = $this->classesPasEncoreOuvertes($periode);

        return $fermees === []
            ? $ids
            : array_values(array_diff($ids, $fermees));
    }

'''
text = text.replace(anchor, helper + anchor, 1)
service.write_text(text)

# 2) Make the current snapshot mirror both the evaluation perimeter and the
#    bulletin average composition mode (weighted vs general/technical blocks).
snapshot = Path('app/Services/ESBTP/BtsCurrentResultSnapshotService.php')
text = snapshot.read_text()
old = '''    private function buildSemesterSnapshot(int $etudiantId, int $classeId, int $anneeUniversitaireId, string $periode): array
    {
        $notes = ESBTPNote::query()
'''
new = '''    private function buildSemesterSnapshot(int $etudiantId, int $classeId, int $anneeUniversitaireId, string $periode): array
    {
        // Le controle "Courant" doit lire exactement les memes classes que la
        // generation officielle. C'est particulierement important en BTS 1 :
        // TC au S1 puis specialite au S2.
        $evaluationClassIds = $this->bulletinService->evaluationClassIdsForSnapshot(
            $etudiantId,
            $classeId,
            $anneeUniversitaireId,
            $periode
        );

        $notes = ESBTPNote::query()
'''
if old not in text:
    raise SystemExit('snapshot method anchor not found')
text = text.replace(old, new, 1)
old = '''            ->whereHas('evaluation', function ($query) use ($anneeUniversitaireId, $classeId, $periode) {
                // Aligné sur la génération réelle (buildDonneesBulletin) : mêmes
                // aliases de période ET exclusion des évaluations annulées, sinon
                // le pré-contrôle voit des notes que la génération ignore.
                $query->where('annee_universitaire_id', $anneeUniversitaireId)
                    ->where('classe_id', $classeId)
                    ->where('status', '!=', 'cancelled')
'''
new = '''            ->whereHas('evaluation', function ($query) use ($anneeUniversitaireId, $evaluationClassIds, $periode) {
                // Aligné sur la génération réelle (buildDonneesBulletin) : mêmes
                // aliases de période, mêmes classes TC/spécialité, et exclusion
                // des évaluations annulées.
                $query->where('annee_universitaire_id', $anneeUniversitaireId)
                    ->whereIn('classe_id', $evaluationClassIds)
                    ->where('status', '!=', 'cancelled')
'''
if old not in text:
    raise SystemExit('snapshot evaluation query anchor not found')
text = text.replace(old, new, 1)
old = '''            $subjects[$matiereId]['coefficient'] = $coefficient !== null ? round((float) $coefficient, 2) : null;

            if ($subject['moyenne'] !== null && $coefficient !== null) {
'''
new = '''            $subjects[$matiereId]['coefficient'] = $coefficient !== null ? round((float) $coefficient, 2) : null;
            $subjects[$matiereId]['type_formation'] = $this->bulletinService->resolveMatiereTypeFormation(
                (int) $matiereId,
                $classeId,
                $periode,
                $anneeUniversitaireId
            );

            if ($subject['moyenne'] !== null && $coefficient !== null) {
'''
if old not in text:
    raise SystemExit('snapshot coefficient anchor not found')
text = text.replace(old, new, 1)
old = '''        $coefficientsMissing = false;
        if ($weightedCoefficients > 0) {
            $rawTotal = round($weightedPoints / $weightedCoefficients, 2);
            $state = 'semester_complete';
'''
new = '''        $coefficientsMissing = false;
        if ($weightedCoefficients > 0) {
            // Ne pas recalculer une moyenne "parallele" ici. Le bulletin officiel
            // peut etre configure en composition par blocs General / Technique.
            // Une moyenne ponderee globale donne alors un autre chiffre et cree
            // un faux ecart permanent apres regeneration.
            $lignes = collect($subjects)
                ->filter(fn (array $subject) => $subject['moyenne'] !== null && $subject['coefficient'] !== null)
                ->map(fn (array $subject) => (object) [
                    'moyenne' => (float) $subject['moyenne'],
                    'coefficient' => (float) $subject['coefficient'],
                    'type_formation' => $subject['type_formation'] ?? null,
                    'statut' => 'note',
                ]);
            $generales = $lignes->filter(fn ($ligne) => $ligne->type_formation === 'generale');
            $techniques = $lignes->filter(fn ($ligne) => $ligne->type_formation === 'technologique_professionnelle');
            $moyenneGenerale = $this->bulletinService->calculerMoyennePonderee($generales);
            $moyenneTechnique = $this->bulletinService->calculerMoyennePonderee($techniques);

            $rawTotal = round($this->bulletinService->composerLaMoyenneDuSemestre(
                $lignes,
                $generales,
                $techniques,
                $moyenneGenerale,
                $moyenneTechnique
            ), 2);
            $state = 'semester_complete';
'''
if old not in text:
    raise SystemExit('snapshot raw total anchor not found')
text = text.replace(old, new, 1)
snapshot.write_text(text)

# 3) Regression test: in block mode, regeneration must immediately align the card.
test = Path('tests/Feature/Bulletin/BtsTcBulletinConsistencyRegenerationTest.php')
test.write_text(r'''<?php

namespace Tests\Feature\Bulletin;

use App\Models\ESBTPConfigMatiere;
use App\Models\ESBTPMatiereCoefficient;
use App\Models\Setting;
use App\Services\ESBTP\BulletinConsistencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Bts\Concerns\MonteUneClasseBts;
use Tests\Feature\Bts\Concerns\SeedsConfiguredBulletin;
use Tests\TestCase;

class BtsTcBulletinConsistencyRegenerationTest extends TestCase
{
    use RefreshDatabase, MonteUneClasseBts, SeedsConfiguredBulletin;

    public function test_regeneration_aligne_immediatement_officiel_et_courant_en_mode_blocs(): void
    {
        $this->monterLaClasse();
        $etudiant = $this->etudiantInscrit();

        Setting::updateOrCreate(['key' => 'bulletin_moyenne_mode'], $this->setting('blocs'));
        Setting::updateOrCreate(['key' => 'bulletin_bloc_general_coef'], $this->setting('1'));
        Setting::updateOrCreate(['key' => 'bulletin_bloc_professionnel_coef'], $this->setting('1'));
        Setting::updateOrCreate(['key' => 'bulletin_show_attendance_note'], $this->setting('0'));

        $generale = $this->matiereAvecType('general', 1);
        $technique = $this->matiereAvecType('technique', 4);

        $evalGenerale = $this->evaluationDe($generale);
        $evalTechnique = $this->evaluationDe($technique);
        $this->noter($etudiant, $evalGenerale, 10);
        $this->noter($etudiant, $evalTechnique, 20);

        $this->seedConfiguredBulletin(
            $etudiant->id,
            $this->classe->id,
            $this->annee->id,
            'semestre1',
            [$generale->id],
            [$technique->id]
        );

        $snapshot = app(BulletinConsistencyService::class)->regenerateOfficialBulletin(
            $etudiant->id,
            $this->classe->id,
            $this->annee->id,
            'semestre1'
        );

        self::assertSame('aligned', $snapshot['status']);
        self::assertFalse($snapshot['has_divergence']);
        self::assertSame(15.0, (float) $snapshot['official_effective_total']);
        self::assertSame(15.0, (float) $snapshot['current_recomputed_effective_total']);
        self::assertSame(0.0, (float) $snapshot['difference_value']);
    }

    private function matiereAvecType(string $type, int $coefficient)
    {
        $matiere = \App\Models\ESBTPMatiere::factory()->create(['unite_enseignement_id' => null]);

        ESBTPConfigMatiere::create([
            'matiere_id' => $matiere->id,
            'classe_id' => $this->classe->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'config' => ['type' => $type, 'coefficient' => $coefficient],
        ]);
        ESBTPMatiereCoefficient::create([
            'matiere_id' => $matiere->id,
            'filiere_id' => $this->filiere->id,
            'niveau_etude_id' => $this->niveau->id,
            'annee_universitaire_id' => $this->annee->id,
            'periode' => 'semestre1',
            'coefficient' => $coefficient,
        ]);

        return $matiere;
    }

    private function setting(string $value): array
    {
        return [
            'value' => $value,
            'type' => 'string',
            'group' => 'bulletin',
            'category' => 'bulletin',
            'is_active' => true,
        ];
    }
}
''')
