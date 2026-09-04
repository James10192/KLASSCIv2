<?php

namespace App\Services;

use App\Enums\EcheanceDocumentRequis;
use App\Enums\FormeDocumentRequis;
use App\Models\ESBTPDocumentRequis;
use App\Models\ESBTPInscription;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Lecture et amorçage du catalogue des pieces a fournir.
 *
 * Point d'entree unique pour les lots suivants : personne ne doit interroger
 * esbtp_documents_requis directement, sinon la regle « portee vide = tout le
 * monde » finira par etre reecrite differemment a chaque appel.
 */
class CatalogueDocumentsRequis
{
    /**
     * Une ecole qui n'a rien configure ne doit voir AUCUN changement dans les
     * ecrans existants. Tous les affichages de dossier se gardent derriere ce
     * test : catalogue vide, aucun signal, aucun compteur.
     */
    public function estConfigure(): bool
    {
        return ESBTPDocumentRequis::actives()->exists();
    }

    /** Catalogue complet, actives ou non, pour l'ecran de configuration. */
    public function tout(): Collection
    {
        return ESBTPDocumentRequis::ordonne()->get();
    }

    /**
     * Pieces reclamees a un etudiant de cette filiere et de ce niveau.
     *
     * Le filtrage de portee se fait en memoire, pas en SQL : le catalogue tient
     * en quelques dizaines de lignes, et la regle « tableau vide = toutes » est
     * beaucoup plus lisible en PHP qu'en JSON_CONTAINS avec un OR sur NULL.
     */
    public function pourScope(?int $filiereId, ?int $niveauId): Collection
    {
        return ESBTPDocumentRequis::actives()
            ->ordonne()
            ->get()
            ->filter(fn (ESBTPDocumentRequis $piece) => $piece->sApplique($filiereId, $niveauId))
            ->values();
    }

    /**
     * Pieces reclamees pour une inscription donnee.
     *
     * On lit la filiere et le niveau de la CLASSE quand elle existe : c'est elle
     * qui porte la verite apres une orientation ou un changement de classe en
     * cours d'annee, l'inscription pouvant garder le voeu initial.
     */
    public function pourInscription(ESBTPInscription $inscription): Collection
    {
        $inscription->loadMissing('classe');

        $filiereId = $inscription->classe?->filiere_id ?? $inscription->filiere_id;
        $niveauId = $inscription->classe?->niveau_etude_id ?? $inscription->niveau_id;

        return $this->pourScope(
            $filiereId ? (int) $filiereId : null,
            $niveauId ? (int) $niveauId : null
        );
    }

    /** Jeu propose a l'ouverture du catalogue (cf. config/documents_requis.php). */
    public function jeuParDefaut(): array
    {
        return (array) config('documents_requis.pieces_par_defaut', []);
    }

    /**
     * Installe le jeu propose. Idempotent : une piece dont le code existe deja
     * — meme archivee — n'est ni recreee ni ecrasee, pour qu'un second clic ne
     * rende pas au secretariat une piece qu'il venait de retirer.
     *
     * @return int nombre de pieces reellement creees
     */
    public function installerJeuParDefaut(?int $userId = null): int
    {
        $codesExistants = ESBTPDocumentRequis::withTrashed()->pluck('code')->all();
        $ordre = (int) ESBTPDocumentRequis::withTrashed()->max('ordre');
        $creees = 0;

        foreach ($this->jeuParDefaut() as $piece) {
            $code = (string) ($piece['code'] ?? '');
            if ($code === '' || in_array($code, $codesExistants, true)) {
                continue;
            }

            $ordre += 10;

            ESBTPDocumentRequis::create([
                'code'               => $code,
                'libelle'            => (string) ($piece['libelle'] ?? $code),
                'description'        => $piece['description'] ?? null,
                'is_obligatoire'     => (bool) ($piece['is_obligatoire'] ?? true),
                'forme_attendue'     => FormeDocumentRequis::fromLibre($piece['forme_attendue'] ?? null)->value,
                'nombre_exemplaires' => max(1, (int) ($piece['nombre_exemplaires'] ?? 1)),
                'echeance'           => EcheanceDocumentRequis::fromLibre($piece['echeance'] ?? null)->value,
                'filiere_ids'        => $piece['portee_filieres'] ?? null,
                'niveau_ids'         => $piece['portee_niveaux'] ?? null,
                'is_active'          => true,
                'ordre'              => $ordre,
                'created_by'         => $userId,
                'updated_by'         => $userId,
            ]);

            $codesExistants[] = $code;
            $creees++;
        }

        return $creees;
    }

    /**
     * Code technique derive du libelle, unique dans le catalogue.
     *
     * Le code n'est pas saisi par le secretariat : lui demander d'inventer un
     * identifiant stable serait lui demander de faire de l'informatique.
     */
    public function genererCode(string $libelle, ?int $ignorerId = null): string
    {
        $base = Str::slug($libelle, '_');
        if ($base === '') {
            $base = 'piece';
        }
        $base = Str::limit($base, 50, '');

        $code = $base;
        $suffixe = 1;

        while ($this->codeExiste($code, $ignorerId)) {
            $suffixe++;
            $code = Str::limit($base, 55, '') . '_' . $suffixe;
        }

        return $code;
    }

    private function codeExiste(string $code, ?int $ignorerId): bool
    {
        return ESBTPDocumentRequis::withTrashed()
            ->where('code', $code)
            ->when($ignorerId, fn ($q) => $q->where('id', '!=', $ignorerId))
            ->exists();
    }
}
