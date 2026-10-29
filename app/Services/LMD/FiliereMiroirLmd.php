<?php

namespace App\Services\LMD;

use App\Models\ESBTPFiliere;
use App\Models\ESBTPLMDMention;
use App\Models\ESBTPLMDParcours;
use Illuminate\Support\Facades\DB;

/**
 * Donne a une entite LMD la filiere sur laquelle une classe peut s'ancrer.
 *
 * `esbtp_classes.filiere_id` est NOT NULL et pointe sur `esbtp_filieres`. En
 * LMD, la classe se rattache pourtant a une mention ou a un parcours. Jusqu'ici
 * le rattachement tenait par un hasard d'identifiants : on ecrivait l'id de la
 * mention dans la colonne filiere, et cela ne marchait que tant que les deux
 * suites d'identifiants coincidaient. USAT a huit mentions pour cinq filieres :
 * ses trois mentions d'agronomie n'ont donc AUCUNE classe possible, et l'ecole
 * ouvre en 2026-2027.
 *
 * Ce service remplace le hasard par une regle : si l'entite LMD n'a pas de
 * filiere, on lui en cree une qui la reflete, portant son nom et son code, et
 * marquee comme reflet. La colonne reste donc toujours vraie, sans qu'aucune
 * lecture BTS existante n'ait a changer.
 *
 * Ce que ce service ne fait PAS : il ne touche jamais a une filiere que l'ecole
 * a creee elle-meme. Si un parcours pointe deja vers une filiere, c'est celle-la
 * qui sert, reflet ou pas.
 */
class FiliereMiroirLmd
{
    /**
     * La filiere d'ancrage d'un parcours, creee si elle manque.
     */
    public function pourParcours(ESBTPLMDParcours $parcours): ESBTPFiliere
    {
        if ($parcours->filiere_id !== null) {
            $existante = ESBTPFiliere::find($parcours->filiere_id);

            if ($existante !== null) {
                return $existante;
            }
            // filiere_id pointe dans le vide (suppression douce d'une filiere,
            // reprise de donnees incomplete). On refait un reflet plutot que
            // de laisser la creation de classe echouer.
        }

        // withTrashed : l'index unique compte les lignes supprimees en douceur.
        // Un reflet efface depuis /esbtp/filieres — ce qui arrive, une ecole ne
        // sait pas ce qu'est un reflet — rendrait sinon la mention definitivement
        // incapable de porter une classe, sur violation d'unicite.
        $miroir = ESBTPFiliere::withTrashed()->where('lmd_parcours_id', $parcours->id)->first();

        if ($miroir === null) {
            $miroir = $this->creer([
                'name' => $parcours->name,
                'code' => $this->codeLibre($parcours->code, 'PARC-'.$parcours->id),
                'description' => 'Reflet du parcours LMD « '.$parcours->name.' ».',
                'lmd_parcours_id' => $parcours->id,
            ]);
        }

        $miroir = $this->ranime($miroir);

        if ((int) $parcours->filiere_id !== (int) $miroir->id) {
            $parcours->forceFill(['filiere_id' => $miroir->id])->save();
        }

        return $miroir;
    }

    /**
     * La filiere d'ancrage d'une mention, creee si elle manque.
     *
     * Sert au tronc commun : une classe ouverte a toute la mention, sans
     * parcours choisi.
     */
    public function pourMention(ESBTPLMDMention $mention): ESBTPFiliere
    {
        // withTrashed : voir pourParcours().
        $miroir = ESBTPFiliere::withTrashed()->where('lmd_mention_id', $mention->id)->first();

        if ($miroir !== null) {
            return $this->ranime($miroir);
        }

        return $this->creer([
            'name' => $mention->name,
            'code' => $this->codeLibre($mention->code, 'MENT-'.$mention->id),
            'description' => 'Reflet de la mention LMD « '.$mention->name.' ».',
            'lmd_mention_id' => $mention->id,
        ]);
    }

    private function creer(array $attributs): ESBTPFiliere
    {
        return DB::transaction(function () use ($attributs) {
            return ESBTPFiliere::create($attributs + [
                'is_active' => true,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
        });
    }

    /**
     * Remet en service un reflet que quelqu'un avait efface.
     */
    private function ranime(ESBTPFiliere $miroir): ESBTPFiliere
    {
        if ($miroir->trashed()) {
            $miroir->restore();
        }

        return $miroir;
    }

    /**
     * Un code de filiere qui ne heurte pas l'unicite de la colonne.
     *
     * Le code du parcours est repris tel quel quand il est libre : c'est celui
     * que l'ecole reconnait. S'il est deja porte par une filiere BTS, on suffixe
     * plutot que d'echouer ou d'ecraser.
     */
    private function codeLibre(?string $souhaite, string $repli): string
    {
        $base = trim((string) $souhaite);

        if ($base === '') {
            $base = $repli;
        }

        $base = mb_substr($base, 0, 40);

        if (! ESBTPFiliere::withTrashed()->where('code', $base)->exists()) {
            return $base;
        }

        // Deux essais suffisent rarement ; on borne pour ne pas boucler sans fin
        // sur une base de donnees anormale.
        for ($i = 2; $i <= 50; $i++) {
            $candidat = $base.'-'.$i;

            if (! ESBTPFiliere::withTrashed()->where('code', $candidat)->exists()) {
                return $candidat;
            }
        }

        return $base.'-'.uniqid();
    }
}
