{{-- Les eleves que la saisie designe et qui n'ont aucun rendez-vous
     (RechercheRdv::elevesSansRendezVous). --}}
@php
    $_voitEleve = auth()->user()?->can('students.view') ?? false;
    $_voitDemandes = auth()->user()?->can('reinscriptions.demandes.view') ?? false;
@endphp
<ul class="rdr-lignes">
    @foreach($elevesSansRdv->take(\App\Services\RendezVous\RechercheRdv::ELEVES_MAX) as $_eleve)
        @php $_demande = $_eleve->derniereDemandeRdv; @endphp
        <li class="rdr-eleve">
            <div class="rdr-eleve-qui">
                <strong>{{ trim($_eleve->nom.' '.$_eleve->prenoms) }}</strong>
                <div class="rdr-eleve-meta">
                    <span><i class="fas fa-id-card"></i>{{ $_eleve->matricule ?: 'Sans matricule' }}</span>
                    <span><i class="fas fa-folder"></i>
                        @if($_demande)
                            Demande de réinscription {{ mb_strtolower($_demande->libelleStatut(), 'UTF-8') }}, sans créneau réservé
                        @else
                            Aucune demande de réinscription déposée
                        @endif
                    </span>
                </div>
            </div>
            <div class="rdr-actions">
                @if($_demande && $_demande->reference_publique && $_voitDemandes)
                    <a class="rdv-btn rdv-btn--ghost rdv-btn--sm" href="{{ route('esbtp.reinscription-demandes.index', ['reference' => $_demande->referencePubliqueAffichee()]) }}"><i class="fas fa-folder-open"></i>Demande</a>
                @endif
                @if($_voitEleve)
                    <a class="rdv-btn rdv-btn--ghost rdv-btn--sm" href="{{ route('esbtp.etudiants.show', $_eleve->id) }}"><i class="fas fa-user"></i>Fiche élève</a>
                @endif
            </div>
        </li>
    @endforeach
</ul>
@if($elevesSansRdv->count() > \App\Services\RendezVous\RechercheRdv::ELEVES_MAX)
    {{-- On n'en montre que quelques-uns : l'eleve cherche peut etre plus loin. --}}
    <p class="rdr-plus">D'autres élèves répondent aussi à cette recherche sans avoir de rendez-vous : précisez avec le prénom ou le matricule.</p>
@endif
