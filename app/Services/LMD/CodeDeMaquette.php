<?php

namespace App\Services\LMD;

use App\Models\ESBTPLMDParcours;
use App\Models\ESBTPMatiere;
use App\Models\ESBTPUniteEnseignement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Le code qu'imprime la maquette, distinct de la cle qui identifie la ligne.
 *
 * Une ecole peut donner le MEME code a deux elements totalement differents,
 * pourvu qu'ils vivent dans deux parcours differents : USAT imprime AGR21031
 * « Genetique animale » sur le releve de Productions Animales, et AGR21031
 * « Genetique vegetale » sur celui de Productions Vegetales. Un releve ne montre
 * qu'un parcours, le code n'y est donc jamais ambigu. Il n'est unique que DANS
 * un parcours.
 *
 * `esbtp_matieres.code` et `esbtp_unites_enseignement.code` restent, eux,
 * uniques dans l'ecole : une dizaine de lecteurs retrouvent une ligne par son
 * code et deviendraient ambigus. Le second element recoit donc une cle
 * suffixee du parcours — `AGR21031~LPA` — et le releve imprime ce qui precede
 * le tilde.
 *
 * Pourquoi le tilde, et pas une colonne `code_maquette` : une seconde colonne
 * reste fausse des qu'un autre ecran modifie `code` (ecran BTS, CLI, fusion de
 * doublons). Le tilde est deja la convention du depot pour « suffixe interne,
 * jamais imprime » (`CodeDeMatiere::SUFFIXE_ARCHIVE`), aucun code de maquette
 * reel n'en porte, et il est refuse a la saisie. Pourquoi pas le tiret : de
 * vrais codes officiels en portent deja un (`ENA4005-AGRO`, cf.
 * LMDEnseignantsImporter), le releve ne saurait plus lequel couper.
 *
 * La derivation n'est jamais devinee : c'est l'ecole qui dit « cette unite est
 * propre a ce parcours » (case du formulaire, cle `propre_au_parcours` de
 * l'import). Un meme code a intitule different, sans ce choix, est refuse.
 */
class CodeDeMaquette
{
    public const SEPARATEUR = '~';

    /** Refuse a la saisie : le separateur ne doit jamais venir de l'utilisateur. */
    public const REGLE_SAISIE = 'not_regex:/~/';

    /** Ce qu'imprime le releve : tout ce qui precede le premier tilde. */
    public static function affiche(?string $code): ?string
    {
        if ($code === null) {
            return null;
        }

        return Str::before($code, self::SEPARATEUR);
    }

    /**
     * Le parcours auquel une cle est propre, ou null pour un code ordinaire.
     * Le suffixe d'archivage (`~suppr-<id>`) n'est pas un parcours.
     */
    public static function suffixe(?string $code): ?string
    {
        if ($code === null || ! str_contains($code, self::SEPARATEUR)) {
            return null;
        }

        $segment = explode(self::SEPARATEUR, $code)[1] ?? '';

        return $segment === '' || str_starts_with($segment, 'suppr-') ? null : $segment;
    }

    /** Le suffixe d'un parcours : son code, en capitales, sans separateur. */
    public static function suffixePour(string $codeParcours): string
    {
        return str_replace(self::SEPARATEUR, '', mb_strtoupper(trim($codeParcours)));
    }

    /**
     * `AGR21031~LPA`, puis `AGR21031~LPA~2`… si la cle est prise.
     *
     * @param  callable(string): bool  $estPrise
     */
    public static function deriver(string $codeImprime, string $suffixe, callable $estPrise): string
    {
        $base = $codeImprime . self::SEPARATEUR . $suffixe;
        $cle = $base;
        for ($rang = 2; $estPrise($cle); $rang++) {
            $cle = $base . self::SEPARATEUR . $rang;
        }

        return $cle;
    }

    /**
     * Deux intitules designent-ils le meme enseignement ?
     *
     * Casse, accents et espaces ne comptent pas : « Genetique  vegetale » et
     * « Génétique végétale » sont le meme element. `Str::ascii` plutot que
     * `iconv(//TRANSLIT)`, qui rend des « ? » selon la locale du serveur.
     */
    public static function memeIntitule(?string $a, ?string $b): bool
    {
        return self::normaliser($a) === self::normaliser($b);
    }

    private static function normaliser(?string $texte): string
    {
        $texte = mb_strtolower(Str::ascii((string) $texte));

        return trim((string) preg_replace('/\s+/', ' ', $texte));
    }

    /**
     * L'element sert-il ailleurs que dans cette unite, pour ce parcours ?
     *
     * Le renommer depuis ici le renommerait aussi la-bas — c'est le renommage
     * silencieux qui imprimait « Genetique vegetale » sur les releves de
     * Productions Animales. Ailleurs, c'est :
     *  - une autre unite (pivot ou cle etrangere) ;
     *  - dans cette unite, une ligne reservee a un autre parcours ;
     *  - dans cette unite, la composition commune, si l'unite sert aussi un
     *    autre parcours (quand `$parcoursId` est connu).
     */
    public function servieAilleurs(int $matiereId, int $ueId, ?int $parcoursId = null): bool
    {
        $lignes = DB::table('esbtp_ue_matiere')->where('matiere_id', $matiereId)->get(['unite_enseignement_id', 'parcours_id']);

        if ($lignes->contains(fn ($l) => (int) $l->unite_enseignement_id !== $ueId)) {
            return true;
        }

        $cle = DB::table('esbtp_matieres')->where('id', $matiereId)->value('unite_enseignement_id');
        if ($cle !== null && (int) $cle !== $ueId) {
            return true;
        }

        $parcoursAdmis = [CompositionUe::COMMUN, (int) ($parcoursId ?? CompositionUe::COMMUN)];
        if ($lignes->contains(fn ($l) => ! in_array((int) $l->parcours_id, $parcoursAdmis, true))) {
            return true;
        }

        if ($parcoursId === null) {
            return false;
        }

        $communeIci = $lignes->contains(fn ($l) => (int) $l->parcours_id === CompositionUe::COMMUN)
            || ($lignes->isEmpty() && (int) $cle === $ueId);

        return $communeIci && DB::table('esbtp_lmd_parcours_ue')
            ->where('unite_enseignement_id', $ueId)
            ->where('parcours_id', '!=', $parcoursId)
            ->exists();
    }

    /**
     * Une autre unite de ce parcours imprime-t-elle deja ce code ?
     *
     * C'est la seule unicite que le releve exige. Elle se controle ici, au
     * point de passage commun du formulaire, de l'import et de l'ecran de
     * rattachement : sans elle, un parcours pourrait porter a la fois l'unite
     * MML2103 d'un autre parcours et sa propre MML2103~LPA, et imprimer deux
     * fois le meme code.
     */
    public function autreUniteDuParcours(int $parcoursId, ?string $code, ?int $saufUeId = null): ?object
    {
        $imprime = self::affiche($code);
        if ($imprime === null || $imprime === '') {
            return null;
        }

        return DB::table('esbtp_unites_enseignement as ue')
            ->join('esbtp_lmd_parcours_ue as pu', 'pu.unite_enseignement_id', '=', 'ue.id')
            ->where('pu.parcours_id', $parcoursId)
            ->whereNull('ue.deleted_at')
            ->where(fn ($q) => $q->where('ue.code', $imprime)->orWhere('ue.code', 'like', $imprime . self::SEPARATEUR . '%'))
            ->when($saufUeId, fn ($q) => $q->where('ue.id', '!=', $saufUeId))
            ->select('ue.id', 'ue.code', 'ue.name')
            ->get()
            // Le LIKE attrape aussi les archives (`~suppr-`) : on ne garde que
            // les cles qui impriment exactement ce code.
            ->first(fn ($ue) => $this->imprimeLeMeme($ue->code, $imprime));
    }

    /**
     * La cle de l'unite propre a ce parcours pour ce code imprime : celle dont
     * la fiche le nomme deja, sinon le code suffixe du parcours (`AGR2103~LPA`).
     */
    public function cleUnitePropre(string $codeImprime, ESBTPLMDParcours $parcours, ?int $saufUeId = null): string
    {
        // Seule une unite dont la fiche nomme ce parcours lui est propre. Une
        // unite partagee qu'il porte sous ce code n'est pas la sienne : le
        // controle d'unicite du rattachement la signalera.
        $parSaFiche = ESBTPUniteEnseignement::where('parcours_id', $parcours->id)
            // Une unite que sert aussi un autre parcours n'est pas propre a
            // celui-ci, meme si sa fiche le nomme (premier parcours importe).
            ->whereNotExists(fn ($q) => $q->from('esbtp_lmd_parcours_ue')
                ->whereColumn('esbtp_lmd_parcours_ue.unite_enseignement_id', 'esbtp_unites_enseignement.id')
                ->where('esbtp_lmd_parcours_ue.parcours_id', '!=', $parcours->id))
            ->when($saufUeId, fn ($q) => $q->where('id', '!=', $saufUeId))
            ->where(fn ($q) => $q->where('code', $codeImprime)->orWhere('code', 'like', $codeImprime . self::SEPARATEUR . '%'))
            ->get(['code'])
            ->first(fn ($ue) => $this->imprimeLeMeme($ue->code, $codeImprime));
        if ($parSaFiche !== null) {
            return (string) $parSaFiche->code;
        }

        // Toujours suffixee, meme si le code est libre : le suffixe est la marque
        // durable « propre a ce parcours », que relisent le formulaire et la
        // modale des elements pour deriver a leur tour.
        $prise = fn (string $cle) => ESBTPUniteEnseignement::withTrashed()->where('code', $cle)->exists();

        return self::deriver($codeImprime, self::suffixePour($parcours->code), $prise);
    }

    /**
     * La cle d'un element d'une unite propre a un parcours : celui que l'unite
     * porte deja sous ce code imprime, sinon le code s'il est libre, sinon le
     * code suffixe (`AGR21031~LPA`).
     */
    public function cleElementPropre(string $codeImprime, ESBTPUniteEnseignement $ue, string $suffixe, ?int $saufMatiereId = null): string
    {
        $ids = DB::table('esbtp_ue_matiere')->where('unite_enseignement_id', $ue->id)->pluck('matiere_id')->all();
        $dansLUnite = ESBTPMatiere::where(fn ($q) => $q->whereIn('id', $ids)->orWhere('unite_enseignement_id', $ue->id))
            ->when($saufMatiereId, fn ($q) => $q->where('id', '!=', $saufMatiereId))
            ->get(['code'])
            ->first(fn ($m) => $this->imprimeLeMeme($m->code, $codeImprime));
        if ($dansLUnite !== null) {
            return (string) $dansLUnite->code;
        }

        // Une archive ne bloque pas : `CodeDeMatiere::libererSiArchive` lui rend le code.
        $prise = fn (string $cle) => ESBTPMatiere::where('code', $cle)
            ->when($saufMatiereId, fn ($q) => $q->where('id', '!=', $saufMatiereId))
            ->exists();

        return $prise($codeImprime) ? self::deriver($codeImprime, $suffixe, $prise) : $codeImprime;
    }

    private function imprimeLeMeme(?string $cle, string $codeImprime): bool
    {
        return ! str_contains((string) $cle, CodeDeMatiere::SUFFIXE_ARCHIVE)
            && mb_strtoupper((string) self::affiche($cle)) === mb_strtoupper($codeImprime);
    }

}
