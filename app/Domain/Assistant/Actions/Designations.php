<?php

namespace App\Domain\Assistant\Actions;

use App\Models\ESBTPAnneeUniversitaire;
use App\Models\ESBTPClasse;
use App\Models\ESBTPEtudiant;
use App\Models\ESBTPMatiere;
use App\Services\LMD\CodeDeMaquette;
use Illuminate\Support\Str;

/**
 * Retrouver ce que la personne désigne — un étudiant, une classe, une année,
 * une matière, une période — sans jamais choisir à sa place : zéro ou
 * plusieurs correspondances rendent un manque, la question à poser.
 *
 * Chaque méthode rend [entité, manque] : l'une des deux est nulle.
 */
trait Designations
{
    /** @return array{0: ?ESBTPEtudiant, 1: ?string} */
    protected function designerEtudiant(array $args): array
    {
        if (($id = (int) ($args['etudiant_id'] ?? 0)) > 0) {
            $e = ESBTPEtudiant::find($id);

            return [$e, $e ? null : "Étudiant #{$id} introuvable."];
        }
        $matricule = trim((string) ($args['matricule'] ?? ''));
        if ($matricule === '') {
            return [null, "Quel étudiant ? Donne son matricule (search_students le retrouve)."];
        }
        $trouves = ESBTPEtudiant::whereRaw('UPPER(matricule) = ?', [mb_strtoupper($matricule)])->get();

        return $trouves->count() === 1
            ? [$trouves->first(), null]
            : [null, $trouves->isEmpty() ? "Aucun étudiant au matricule {$matricule}." : "Plusieurs étudiants portent le matricule {$matricule} : donne son identifiant."];
    }

    /** @return array{0: ?ESBTPClasse, 1: ?string} */
    protected function designerClasse(mixed $designation): array
    {
        if (is_int($designation) || (is_string($designation) && ctype_digit(trim($designation)))) {
            $c = ESBTPClasse::find((int) $designation);

            return [$c, $c ? null : "Classe #{$designation} introuvable."];
        }
        $libelle = trim((string) $designation);
        if ($libelle === '') {
            return [null, 'Quelle classe (code ou identifiant) ?'];
        }
        $trouvees = ESBTPClasse::query()
            ->where(fn ($q) => $q->whereRaw('LOWER(code) = ?', [mb_strtolower($libelle)])->orWhereRaw('LOWER(name) = ?', [mb_strtolower($libelle)]))
            ->get();

        return $trouvees->count() === 1
            ? [$trouvees->first(), null]
            : [null, $trouvees->isEmpty() ? "Classe introuvable : {$libelle}." : "Plusieurs classes correspondent à {$libelle} : donne son code ou son identifiant."];
    }

    /** @return array{0: ?ESBTPAnneeUniversitaire, 1: ?string} */
    protected function designerAnnee(array $args): array
    {
        if (($id = (int) ($args['annee_universitaire_id'] ?? 0)) > 0) {
            $a = ESBTPAnneeUniversitaire::find($id);

            return [$a, $a ? null : 'Année universitaire introuvable.'];
        }
        $libelle = trim((string) ($args['annee'] ?? ''));
        if ($libelle === '') {
            $courante = ESBTPAnneeUniversitaire::where('is_current', true)->first();

            return [$courante, $courante ? null : "Aucune année universitaire courante n'est définie : laquelle ?"];
        }
        $trouvees = ESBTPAnneeUniversitaire::whereRaw('LOWER(name) = ?', [mb_strtolower($libelle)])->get();

        return $trouvees->count() === 1
            ? [$trouvees->first(), null]
            : [null, $trouvees->isEmpty() ? "Année universitaire introuvable : {$libelle}." : "Plusieurs années correspondent à {$libelle}."];
    }

    /**
     * Par identifiant, ou par code imprimé / intitulé exact. Un code imprimé
     * peut désigner plusieurs éléments (un par parcours) : on demande alors.
     *
     * @return array{0: ?ESBTPMatiere, 1: ?string}
     */
    protected function designerMatiere(mixed $id, mixed $libelle): array
    {
        if ((int) $id > 0) {
            $m = ESBTPMatiere::find((int) $id);

            return [$m, $m ? null : "Matière #{$id} introuvable."];
        }
        $libelle = trim((string) $libelle);
        if ($libelle === '') {
            return [null, 'Quelle matière (code ou identifiant) ?'];
        }
        $bas = mb_strtolower($libelle);
        $trouvees = ESBTPMatiere::query()
            ->where(fn ($q) => $q->whereRaw('LOWER(code) = ?', [$bas])
                ->orWhereRaw('LOWER(code) LIKE ?', [$bas.CodeDeMaquette::SEPARATEUR.'%'])
                ->orWhereRaw('LOWER(name) = ?', [$bas]))
            ->get();

        return $trouvees->count() === 1
            ? [$trouvees->first(), null]
            : [null, $trouvees->isEmpty()
                ? "Matière introuvable : {$libelle}."
                : "« {$libelle} » désigne plusieurs matières (" . $trouvees->take(5)->map(fn ($m) => "#{$m->id} {$m->name}")->implode(', ') . ') : laquelle (identifiant) ?'];
    }

    /** semestre1 / semestre2, ou null si la valeur ne dit pas clairement l'un des deux. */
    protected function designerSemestre(mixed $valeur): ?string
    {
        return match (Str::lower(trim((string) $valeur))) {
            's1', '1', 'semestre 1', 'semestre1' => 'semestre1',
            's2', '2', 'semestre 2', 'semestre2' => 'semestre2',
            default => null,
        };
    }

    protected function libelleSemestre(?string $periode): string
    {
        return match ((string) $periode) {
            'semestre1' => 'S1',
            'semestre2' => 'S2',
            default => (string) $periode,
        };
    }

    protected function nombre(mixed $n): string
    {
        return $n === null ? '—' : rtrim(rtrim(number_format((float) $n, 2, ',', ' '), '0'), ',');
    }

    protected function seulManque(string $titre, string $manque): Proposition
    {
        return new Proposition(titre: $titre, resume: '', manques: [$manque]);
    }
}
