@foreach($etudiants as $analyse)
<tr style="border-bottom: 1px solid #f3f4f6;">
    <td style="padding: 16px;">
        <div style="display: flex; align-items: center;">
            <div style="width: 44px; height: 44px; border-radius: 50%; background-color: #0453cb; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; margin-right: 16px;">
                {{ strtoupper(substr($analyse['etudiant']->prenoms ?? 'N', 0, 1) . substr($analyse['etudiant']->nom ?? 'A', 0, 1)) }}
            </div>
            <div>
                <div style="font-weight: 600; color: #1f2937;">{{ $analyse['etudiant']->prenoms ?? 'N/A' }} {{ $analyse['etudiant']->nom ?? 'N/A' }}</div>
                <small style="color: #64748b;">{{ $analyse['etudiant']->matricule ?? 'Matricule non disponible' }}</small>
            </div>
        </div>
    </td>
    <td style="padding: 16px;">
        @if($analyse['classe'] ?? $analyse['inscription']->classe ?? null)
            <span class="badge success">
                <i class="fas fa-users"></i>
                {{ $analyse['classe']->name ?? $analyse['inscription']->classe->name ?? 'N/A' }}
            </span>
            <div style="font-weight: 600; color: #1f2937; margin-top: 4px;">
                {{ $analyse['classe']->niveau->name ?? $analyse['inscription']->classe->niveau->name ?? 'N/A' }}
            </div>
            <small style="color: #64748b;">
                {{ $analyse['classe']->filiere->name ?? $analyse['inscription']->classe->filiere->name ?? 'N/A' }}
            </small>
        @else
            <span class="badge" style="background-color: rgba(107, 114, 128, 0.1); color: #6b7280;">
                <i class="fas fa-question"></i> Non assignée
            </span>
        @endif
    </td>
    <td style="padding: 16px; text-align: center;">
        @if($analyse['moyenne_generale'] >= 10)
            <span class="badge success">
                <i class="fas fa-check-circle"></i> {{ number_format($analyse['moyenne_generale'], 2) }}/20
            </span>
        @elseif($analyse['moyenne_generale'] >= 8)
            <span class="badge warning">
                <i class="fas fa-exclamation-triangle"></i> {{ number_format($analyse['moyenne_generale'], 2) }}/20
            </span>
        @else
            <span class="badge danger">
                <i class="fas fa-times-circle"></i> {{ number_format($analyse['moyenne_generale'], 2) }}/20
            </span>
        @endif
    </td>
    @if($type === 'rattrapages')
    <td style="padding: 16px; text-align: center;">
        @if(count($analyse['matieres_echouees']) > 0)
            <div style="display: flex; flex-wrap: wrap; gap: 4px; justify-content: center;">
            @foreach($analyse['matieres_echouees'] as $matiere)
            <span class="badge danger" style="margin-bottom: 4px;">
                <i class="fas fa-times"></i>
                {{ $matiere['matiere']->name ?? 'N/A' }}
                ({{ number_format($matiere['moyenne'], 2) }})
            </span>
            @endforeach
            </div>
        @else
            <span class="badge success">
                <i class="fas fa-check"></i> Aucune
            </span>
        @endif
    </td>
    @endif
    <td style="padding: 16px; text-align: center;">
        @php
            $etudiant = $analyse['etudiant'];
            $montantAttendu = $etudiant->montant_attendu ?? 0;
            $montantPaye = $etudiant->montant_paye ?? 0;
            $soldeRestant = $etudiant->solde_restant ?? 0;
            $peutReinscrire = $etudiant->peut_reinscrire ?? false;
        @endphp
        
        <div style="display: flex; flex-direction: column; align-items: center; gap: 4px;">
            <div style="font-weight: 600; color: #1f2937;">{{ number_format($montantAttendu, 0, ',', ' ') }} FCFA</div>
            <small style="color: #64748b;">Attendu</small>
            
            <div style="font-weight: 600; color: #059669;">{{ number_format($montantPaye, 0, ',', ' ') }} FCFA</div>
            <small style="color: #64748b;">Payé</small>
            
            @if($soldeRestant > 0)
                <div style="font-weight: 600; color: #dc2626;">{{ number_format($soldeRestant, 0, ',', ' ') }} FCFA</div>
                <small style="color: #64748b;">Reste</small>
            @elseif($soldeRestant < 0)
                <div style="font-weight: 600; color: #f59e0b;">{{ number_format(abs($soldeRestant), 0, ',', ' ') }} FCFA</div>
                <small style="color: #64748b;">Trop-perçu</small>
            @else
                <div style="font-weight: 600; color: #059669;">Soldé</div>
                <small style="color: #64748b;">✓</small>
            @endif
            
            @if($peutReinscrire)
                <span class="badge success" style="margin-top: 4px;">
                    <i class="fas fa-check-circle"></i> Éligible
                </span>
            @else
                <span class="badge danger" style="margin-top: 4px;">
                    <i class="fas fa-times-circle"></i> Non éligible
                </span>
            @endif
        </div>
    </td>
    <td style="padding: 16px; text-align: center;">
        @switch($analyse['decision'])
            @case('passage')
                <span class="badge success">
                    <i class="fas fa-arrow-up"></i> Passage
                </span>
                @break
            @case('rattrapage')
                <span class="badge warning">
                    <i class="fas fa-exclamation-triangle"></i> Rattrapage
                </span>
                @break
            @case('redoublement')
                <span class="badge danger">
                    <i class="fas fa-redo"></i> Redoublement
                </span>
                @break
        @endswitch
    </td>
    <td style="padding: 16px; text-align: center;">
        <div style="display: flex; gap: 8px; justify-content: center;">
            <a href="{{ route('esbtp.reinscription.show', $analyse['etudiant']->id) }}?annee_academique={{ request('annee_academique') }}" 
               class="btn btn-primary btn-sm" title="Voir détails"
               style="padding: 8px; border-radius: 6px; min-width: 36px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-eye"></i>
            </a>
            <button type="button" class="btn btn-success btn-sm" 
                    onclick="validerReinscription({{ $analyse['etudiant']->id }}, '{{ $analyse['decision'] }}')" title="Valider réinscription"
                    style="padding: 8px; border-radius: 6px; min-width: 36px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-check-double"></i>
            </button>
            <button type="button" class="btn btn-warning btn-sm" 
                    onclick="marquerAbandonModal({{ $analyse['etudiant']->id }})" title="Marquer comme abandon"
                    style="padding: 8px; border-radius: 6px; min-width: 36px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-user-times"></i>
            </button>
        </div>
    </td>
</tr>
@endforeach