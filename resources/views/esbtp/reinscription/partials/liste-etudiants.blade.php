@if(count($etudiants) > 0)
<div class="table-moderne">
    <table>
        <thead>
            <tr>
                <th>Photo</th>
                <th>Étudiant</th>
                <th>Classe Actuelle</th>
                <th class="text-center">Moyenne Générale</th>
                @if($type === 'rattrapage')
                <th class="text-center">Matières Échouées</th>
                @endif
                <th class="text-center">Solde & Réinscription</th>
                <th class="text-center">Décision</th>
                <th class="text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($etudiants as $analyse)
            <tr>
                <td>
                    <img src="{{ $analyse['etudiant']->photo_url ?? asset('images/default-avatar.png') }}" 
                         alt="Photo" 
                         style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                </td>
                <td>
                    <div>
                        <div style="font-weight: 600; color: var(--text-primary); margin-bottom: var(--space-xs);">{{ $analyse['etudiant']->prenoms }} {{ $analyse['etudiant']->nom }}</div>
                        <div style="font-size: var(--text-small); color: var(--text-secondary);">{{ $analyse['etudiant']->matricule ?? 'N/A' }}</div>
                    </div>
                </td>
                <td>
                    <span class="table-badge primary">{{ $analyse['classe']->name ?? $analyse['inscription']->classe->name ?? 'N/A' }}</span>
                    <br>
                    <div style="font-size: var(--text-small); color: var(--text-secondary); margin-top: var(--space-xs);">
                        {{ $analyse['classe']->niveau->name ?? $analyse['inscription']->classe->niveau->name ?? 'N/A' }} - 
                        {{ $analyse['classe']->filiere->name ?? $analyse['inscription']->classe->filiere->name ?? 'N/A' }}
                    </div>
                </td>
                <td style="text-align: center;">
                    <span class="table-badge 
                        @if($analyse['moyenne_generale'] >= 10) success
                        @elseif($analyse['moyenne_generale'] >= 8) warning
                        @else danger
                        @endif">
                        {{ number_format($analyse['moyenne_generale'], 2) }}/20
                    </span>
                </td>
                @if($type === 'rattrapage')
                <td style="text-align: center;">
                    @if(count($analyse['matieres_echouees']) > 0)
                        <div style="display: flex; flex-wrap: wrap; gap: var(--space-xs); justify-content: center;">
                        @foreach($analyse['matieres_echouees'] as $matiere)
                        <span class="table-badge danger" style="margin-bottom: var(--space-xs);">
                            {{ $matiere['matiere']->name ?? 'N/A' }}
                            ({{ number_format($matiere['moyenne'], 2) }})
                        </span>
                        @endforeach
                        </div>
                    @else
                        <span style="color: var(--text-muted);">Aucune</span>
                    @endif
                </td>
                @endif
                <td style="text-align: center;">
                    @php
                        $etudiant = $analyse['etudiant'];
                        $soldeValide = $etudiant->solde_valide ?? 0;
                        $soldeTotal = $etudiant->solde_total ?? 0;
                        $peutReinscrire = $etudiant->peut_reinscrire ?? false;
                    @endphp
                    
                    <div style="display: flex; flex-direction: column; align-items: center; gap: var(--space-xs);">
                        <div style="font-size: var(--text-small);">
                            <span style="font-weight: 600; color: var(--success);">{{ number_format($soldeValide, 0, ',', ' ') }} FCFA</span>
                            <div style="color: var(--text-secondary); font-size: 10px;">validé</div>
                        </div>
                        
                        @if($soldeTotal > $soldeValide)
                        <div style="font-size: var(--text-small);">
                            <span style="color: var(--warning);">{{ number_format($soldeTotal - $soldeValide, 0, ',', ' ') }} FCFA</span>
                            <div style="color: var(--text-secondary); font-size: 10px;">en attente</div>
                        </div>
                        @endif
                        
                        <span class="table-badge {{ $peutReinscrire ? 'success' : 'danger' }}" style="font-size: 10px;">
                            <i class="fas {{ $peutReinscrire ? 'fa-check-circle' : 'fa-times-circle' }}"></i>
                            {{ $peutReinscrire ? 'Éligible' : 'Non éligible' }}
                        </span>
                    </div>
                </td>
                <td style="text-align: center;">
                    @switch($analyse['decision'])
                        @case('passage')
                            <span class="table-badge success">
                                <i class="fas fa-arrow-up"></i> Passage
                            </span>
                            @break
                        @case('rattrapage')
                            <span class="table-badge warning">
                                <i class="fas fa-exclamation-triangle"></i> Rattrapage
                            </span>
                            @break
                        @case('redoublement')
                            <span class="table-badge danger">
                                <i class="fas fa-redo"></i> Redoublement
                            </span>
                            @break
                    @endswitch
                </td>
                <td style="text-align: center;">
                    <div class="table-actions">
                        <a href="{{ route('esbtp.reinscription.show', $analyse['etudiant']->id) }}?annee_academique={{ request('annee_academique') }}" 
                           class="btn-table-action primary" title="Voir détails">
                            <i class="fas fa-eye"></i>
                        </a>
                        <button type="button" class="btn-table-action success" 
                                onclick="confirmerReinscription({{ $analyse['etudiant']->id }}, '{{ $analyse['decision'] }}'))" title="Confirmer réinscription">
                            <i class="fas fa-check"></i>
                        </button>
                        <button type="button" class="btn-table-action danger" 
                                onclick="marquerAbandon({{ $analyse['etudiant']->id }})" title="Marquer comme abandon">
                            <i class="fas fa-user-times"></i>
                        </button>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@else
<div class="text-center py-4">
    <div class="mb-3">
        <i class="fas fa-info-circle fa-3x text-muted"></i>
    </div>
    <h5 class="text-muted">Aucun étudiant dans cette catégorie</h5>
    <p class="text-muted">Tous les étudiants ont été traités ou il n'y a pas de données pour cette période.</p>
</div>
@endif

<script>
function confirmerReinscription(etudiantId, decision) {
    if (confirm(`Êtes-vous sûr de vouloir confirmer cette décision de ${decision} ?`)) {
        // Rediriger vers la page de détails pour finaliser
        window.location.href = `{{ url('esbtp/reinscription') }}/${etudiantId}?annee_academique={{ request('annee_academique') }}`;
    }
}

function marquerAbandon(etudiantId) {
    const motif = prompt('Motif de l\'abandon (optionnel):');
    
    if (confirm('Êtes-vous sûr de vouloir marquer cet étudiant comme ayant abandonné ? Cette action peut être annulée.')) {
        fetch(`{{ url('esbtp/reinscription') }}/${etudiantId}/abandon`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                motif_abandon: motif
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload(); // Recharger la page pour voir les changements
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de l\'enregistrement de l\'abandon');
        });
    }
}
</script>