<?php

namespace App\Domain\Admissions;

use App\Models\ESBTPCandidature;
use App\Models\ESBTPInscription;
use App\Models\ESBTPReinscriptionDemande;
use Illuminate\Support\Collection;

/**
 * Les lignes de l'union (type, id) rechargees en modeles, avec tout ce que la
 * liste affiche, en un nombre fixe de requetes par tranche : aucune requete ne
 * part ligne par ligne.
 */
final class HydratationDesDossiers
{
    /**
     * @param  Collection<int, object>  $lignes
     * @return Collection<int, DemandeDInscription>
     */
    public function hydrater(Collection $lignes): Collection
    {
        $ids = $lignes->groupBy('type')->map(fn ($g) => $g->pluck('id')->all());
        $avecRdv = ['reservations' => fn ($r) => $r->occupantes()->with('creneau', 'accueilliPar:id,name')->latest('id')];

        $candidatures = ESBTPCandidature::query()
            ->with(['anneeUniversitaire:id,name', 'filiere:id,name', 'niveau:id,name', 'traitePar:id,name'] + $avecRdv)
            ->findMany($ids[FileDesDemandes::TYPE_NOUVELLE] ?? [])->keyBy('id');
        $demandes = ESBTPReinscriptionDemande::query()
            ->with(['etudiant:id,nom,prenoms,matricule,telephone,email,email_personnel,date_naissance', 'anneeUniversitaire:id,name,is_current', 'classeSouhaitee:id,name', 'traitePar:id,name', 'inscription.classe:id,name'] + $avecRdv)
            ->findMany($ids[FileDesDemandes::TYPE_REINSCRIPTION] ?? [])->keyBy('id');
        $inscrits = $this->dejaInscrits($demandes);

        return $lignes->map(fn ($l) => $l->type === FileDesDemandes::TYPE_NOUVELLE
            ? ($candidatures->has($l->id) ? DemandeDInscription::deCandidature($candidatures[$l->id]) : null)
            : ($demandes->has($l->id) ? DemandeDInscription::deReinscription($demandes[$l->id], isset($inscrits[$demandes[$l->id]->etudiant_id.'-'.$demandes[$l->id]->annee_universitaire_id])) : null))
            ->filter()->values();
    }

    /**
     * Les couples (etudiant, annee) deja inscrits, en une requete pour toute la
     * tranche : la meme regle que ESBTPInscription::aUneInscriptionVivantePour().
     *
     * @param  Collection<int, ESBTPReinscriptionDemande>  $demandes
     * @return array<string, true>
     */
    private function dejaInscrits(Collection $demandes): array
    {
        $ouvertes = $demandes->filter->estTraitable();
        if ($ouvertes->isEmpty()) {
            return [];
        }

        return ESBTPInscription::query()
            ->whereIn('etudiant_id', $ouvertes->pluck('etudiant_id')->unique())
            ->whereIn('annee_universitaire_id', $ouvertes->pluck('annee_universitaire_id')->unique())
            ->whereIn('status', ['en_attente', 'active'])
            ->get(['etudiant_id', 'annee_universitaire_id'])
            ->mapWithKeys(fn ($i) => [$i->etudiant_id.'-'.$i->annee_universitaire_id => true])
            ->all();
    }
}
