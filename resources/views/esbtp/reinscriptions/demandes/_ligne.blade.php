{{-- Une ligne de la corbeille des demandes de reinscription. Rendue par la page
     et par chaque tranche du defilement ; ses boutons appellent le composant
     Alpine corbeilleDemandes() de la page, qui initialise aussi les lignes ajoutees. --}}
<tr data-li-cle="{{ $demande->id }}">
    <td>
        <div class="rd-eleve">{{ $demande->etudiant?->nom }} {{ $demande->etudiant?->prenoms }}</div>
        <div class="rd-matricule">{{ $demande->etudiant?->matricule }}</div>
    </td>
    <td>{{ $demande->classeSouhaitee?->name ?? '—' }}</td>
    <td>{{ $demande->anneeUniversitaire?->name ?? '—' }}</td>
    <td>{{ $demande->created_at?->format('d/m/Y H:i') }}</td>
    <td>
        @php
            $ton = match($demande->statut) {
                'convertie' => 'convertie',
                'rejetee' => 'rejetee',
                default => 'attente',
            };
        @endphp
        <span class="rd-badge rd-badge--{{ $ton }}">{{ $demande->libelleStatut() }}</span>
        <x-demande-contact-badge :demande="$demande" route="esbtp.reinscription-demandes.confirmer-contact" permission="reinscriptions.demandes.process" />
        @if($demande->traitePar)
            <div style="font-size:.72rem;color:#64748b;margin-top:.2rem;">
                par {{ $demande->traitePar->name }}
            </div>
        @endif
    </td>
    <td style="text-align:right;">
        @if($demande->estTraitable())
            @can('reinscriptions.demandes.process')
                <button type="button" class="rd-btn rd-btn--convertir"
                        @click="ouvrirConversion(@js(route('esbtp.reinscription-demandes.convertir', $demande)), @js(trim($demande->etudiant?->nom.' '.$demande->etudiant?->prenoms)), {{ $demande->classe_souhaitee_id ?? 'null' }})">
                    <i class="fas fa-user-check"></i>Convertir
                </button>
                <button type="button" class="rd-btn rd-btn--rejeter"
                        @click="ouvrirRejet(@js(route('esbtp.reinscription-demandes.rejeter', $demande)))">
                    Rejeter
                </button>
            @endcan
        @elseif($demande->motif_rejet)
            <span style="font-size:.78rem;color:#64748b;" title="{{ $demande->motif_rejet }}">
                {{ \Illuminate\Support\Str::limit($demande->motif_rejet, 40) }}
            </span>
        @else
            <span style="font-size:.78rem;color:#94a3b8;">Traitée</span>
        @endif
    </td>
</tr>
