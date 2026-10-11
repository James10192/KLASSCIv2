<?php

namespace App\Services\Enseignants;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPBulletin;
use App\Models\ESBTPClasse;
use App\Models\Setting;
use App\Services\BulletinInlineConfigurationService;
use Illuminate\Support\Str;

/**
 * Annuaire annuel BTS : comptes enseignants + noms libres saisis sur les bulletins.
 * Lecture seule : ne cree jamais de comptes User/ESBTPTeacher.
 */
final class AnnuaireEnseignantsBulletins
{
    public function __construct(private readonly BulletinInlineConfigurationService $configuration) {}

    /** @return array<int, array{classe: string, semestre: string, matiere: string, nom: string}> */
    public function affectations(int $anneeId): array
    {
        // Les classes peuvent avoir ete rattachees a une autre annee entre-temps :
        // les reglages de bulletins historiques restent une source valable.
        $ids = ESBTPClasse::query()->where('annee_universitaire_id', $anneeId)->pluck('id')->all();
        $ids = array_merge($ids, ESBTPBulletin::query()
            ->where('annee_universitaire_id', $anneeId)->distinct()->pluck('classe_id')->all());

        $cles = Setting::query()->where('is_active', true)
            ->where('key', 'like', 'bulletin_professeurs_template.%.'.$anneeId.'.semestre%')
            ->pluck('key');
        foreach ($cles as $cle) {
            if (preg_match('/^bulletin_professeurs_template\.(\d+)\.'.preg_quote((string) $anneeId, '/').'\.semestre[12]$/', $cle, $m)) {
                $ids[] = (int) $m[1];
            }
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === []) {
            return [];
        }

        // Ne pas inclure de classes supprimees ; les periodes sont toujours
        // resolues par le lecteur canonique (setting puis bulletin historique).
        $classes = ESBTPClasse::query()->whereIn('id', $ids)->get(['id', 'name'])->keyBy('id');
        $brutes = [];
        $matiereIds = [];
        foreach ($classes as $classe) {
            foreach ([1, 2] as $semestre) {
                $professeurs = $this->configuration->loadProfesseursTemplate(
                    (int) $classe->id, $anneeId, 'semestre'.$semestre
                );
                foreach ($professeurs as $matiereId => $nom) {
                    $nom = preg_replace('/\s+/u', ' ', trim((string) $nom));
                    if (! ctype_digit((string) $matiereId) || (int) $matiereId < 1 || $nom === '') {
                        continue;
                    }
                    $matiereIds[] = (int) $matiereId;
                    $brutes[] = [
                        'classe' => (string) $classe->name, 'semestre' => 'S'.$semestre,
                        'matiere_id' => (int) $matiereId, 'nom' => $nom,
                    ];
                }
            }
        }

        if ($brutes === []) {
            return [];
        }

        $matieres = \App\Models\ESBTPMatiere::query()
            ->whereIn('id', array_unique($matiereIds))->pluck('name', 'id');
        $lignes = [];
        foreach ($brutes as $ligne) {
            $nomMatiere = $matieres->get($ligne['matiere_id'], 'Matière #'.$ligne['matiere_id']);
            $lignes[] = [
                'classe' => $ligne['classe'],
                'semestre' => $ligne['semestre'],
                'matiere' => (string) $nomMatiere,
                'nom' => $ligne['nom'],
            ];
        }

        // Plusieurs bulletins de la meme classe ne doivent pas multiplier les lignes.
        $distinctes = [];
        foreach ($lignes as $ligne) {
            $cle = implode('|', array_map(fn ($v) => self::cle((string) $v), $ligne));
            $distinctes[$cle] = $ligne;
        }
        $lignes = array_values($distinctes);
        usort($lignes, fn ($a, $b) => strnatcasecmp(
            implode('|', [$a['classe'], $a['semestre'], $a['matiere'], $a['nom']]),
            implode('|', [$b['classe'], $b['semestre'], $b['matiere'], $b['nom']])
        ));

        return $lignes;
    }

    /**
     * Fusionne uniquement les noms identifiables, sans fusionner des enseignants
     * differents sur un identifiant inexistant et sans creer de compte.
     * @param array<int, array<string, string>> $enregistres
     * @param array<int, array{classe: string, semestre: string, matiere: string, nom: string}> $affectations
     * @return array{rows: array, affectations: array}
     */
    public function consolider(array $enregistres, array $affectations, bool $inclureManuels = true, ?string $recherche = null): array
    {
        $parNom = [];
        foreach ($enregistres as $ligne) {
            $cle = self::cle($ligne['nom']);
            if ($cle === '') {
                continue;
            }
            $ligne['origine'] = 'Enregistré';
            $ligne['classes'] = '';
            $parNom[$cle] = $ligne;
        }

        $retenues = [];
        $classes = [];
        foreach ($affectations as $affectation) {
            $cle = self::cle($affectation['nom']);
            if ($cle === '' || (! isset($parNom[$cle]) && ! $inclureManuels)) {
                continue;
            }
            if (! isset($parNom[$cle])) {
                if ($recherche !== null && $recherche !== '' && mb_stripos($affectation['nom'], $recherche, 0, 'UTF-8') === false) {
                    continue;
                }
                $parNom[$cle] = [
                    'matricule' => '—', 'nom' => $affectation['nom'],
                    'telephone' => '—', 'email' => '—',
                    'specialisation' => '—', 'regime' => '—',
                    'statut' => '—', 'origine' => 'Bulletin (saisie manuelle)',
                    'classes' => '',
                ];
            } else {
                $parNom[$cle]['origine'] = $parNom[$cle]['origine'] === 'Enregistré'
                    ? 'Enregistré + bulletin' : $parNom[$cle]['origine'];
            }
            $classes[$cle][$affectation['classe']] = true;
            $retenues[] = $affectation;
        }
        foreach ($classes as $cle => $valeurs) {
            $parNom[$cle]['classes'] = implode(', ', array_keys($valeurs));
        }
        $rows = array_values($parNom);
        usort($rows, fn ($a, $b) => strnatcasecmp($a['nom'], $b['nom']));

        return ['rows' => $rows, 'affectations' => $retenues];
    }

    private static function cle(string $nom): string
    {
        // Uniformisation orthographique legere ; ne pas effacer les prenoms.
        $nom = Str::ascii(mb_strtolower(trim($nom), 'UTF-8'));
        return trim(preg_replace('/\s+/u', ' ', preg_replace('/[.,;:]+/u', ' ', $nom)));
    }
}
