<?php

namespace App\Services\Enseignants;

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
    public function affectations(int $anneeId, ?int $classeId = null, ?int $semestreFiltre = null, ?array $classeIds = null, ?array $matiereIds = null): array
    {
        // Les classes peuvent avoir ete rattachees a une autre annee entre-temps :
        // les reglages de bulletins historiques restent une source valable.
        // Uniquement les classes disposant d'au moins une source : parcourir
        // toutes les classes de l'annee causerait 2 lectures inutiles par classe.
        $ids = ESBTPBulletin::query()
            ->where('annee_universitaire_id', $anneeId)->distinct()->pluck('classe_id')->all();

        $cles = Setting::query()->where('is_active', true)
            ->where('key', 'like', 'bulletin_professeurs_template.%.'.$anneeId.'.semestre%')
            ->pluck('key');
        foreach ($cles as $cle) {
            if (preg_match('/^bulletin_professeurs_template\.(\d+)\.'.preg_quote((string) $anneeId, '/').'\.semestre[12]$/', $cle, $m)) {
                $ids[] = (int) $m[1];
            }
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($classeId !== null) {
            // Le filtre de classe s'applique avant le chargement des modeles et
            // des configurations de chaque semestre, pas apres un scan global.
            $ids = array_values(array_filter($ids, fn (int $id): bool => $id === $classeId));
        }
        if ($classeIds !== null) {
            // [] est un perimetre vide, jamais « toutes les classes ».
            $ids = array_values(array_intersect($ids, array_map('intval', $classeIds)));
        }
        if ($ids === []) {
            return [];
        }

        // Ne pas inclure de classes supprimees ; les periodes sont toujours
        // resolues par le lecteur canonique (setting puis bulletin historique).
        $classes = ESBTPClasse::query()->with(['filiere:id,name', 'niveau:id,name'])
            ->whereIn('id', $ids)
            ->where(fn ($q) => $q->where('systeme_academique', 'BTS')->orWhereNull('systeme_academique'))
            ->get(['id', 'name', 'filiere_id', 'niveau_etude_id', 'systeme_academique'])->keyBy('id');
        $brutes = [];
        $idsMatieresPourNoms = [];
        foreach ($classes as $classe) {
            foreach ([1, 2] as $semestre) {
                if ($semestreFiltre !== null && $semestre !== $semestreFiltre) {
                    continue;
                }
                $professeurs = $this->configuration->loadProfesseursTemplate(
                    (int) $classe->id, $anneeId, 'semestre'.$semestre
                );
                foreach ($professeurs as $matiereId => $nom) {
                    $nom = preg_replace('/\s+/u', ' ', trim((string) $nom));
                    if (! ctype_digit((string) $matiereId) || (int) $matiereId < 1 || $nom === ''
                        || ($matiereIds !== null && ! in_array((int) $matiereId, $matiereIds, true))) {
                        continue;
                    }
                    $idsMatieresPourNoms[] = (int) $matiereId;
                    $brutes[] = [
                        'classe' => (string) $classe->name, 'semestre' => 'S'.$semestre,
                        'matiere_id' => (int) $matiereId, 'nom' => $nom,
                        'classe_id' => (int) $classe->id,
                        'filiere' => (string) ($classe->filiere?->name ?? 'Non renseignée'),
                        'niveau' => (string) ($classe->niveau?->name ?? 'Non renseigné'),
                    ];
                }
            }
        }

        if ($brutes === []) {
            return [];
        }

        $matieres = \App\Models\ESBTPMatiere::query()
            ->whereIn('id', array_unique($idsMatieresPourNoms))->pluck('name', 'id');
        $lignes = [];
        foreach ($brutes as $ligne) {
            $nomMatiere = $matieres->get($ligne['matiere_id'], 'Matière #'.$ligne['matiere_id']);
            $lignes[] = [
                'systeme' => 'BTS',
                'ue' => '—',
                'parcours' => '—',
                'source' => 'Bulletin BTS',
                'classe' => $ligne['classe'],
                'semestre' => $ligne['semestre'],
                'matiere' => (string) $nomMatiere,
                'nom' => $ligne['nom'],
                'classe_id' => $ligne['classe_id'],
                'matiere_id' => $ligne['matiere_id'],
                'filiere' => $ligne['filiere'],
                'niveau' => $ligne['niveau'],
            ];
        }

        // Plusieurs bulletins de la meme classe ne doivent pas multiplier les lignes.
        $distinctes = [];
        foreach ($lignes as $ligne) {
            $cle = implode('|', [
                $ligne['classe_id'], $ligne['matiere_id'], $ligne['semestre'], self::cle($ligne['nom']),
            ]);
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
