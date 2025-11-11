@if(count($etudiants) > 0)
<div class="table-responsive" style="width: 100% !important; margin: 0 !important; padding: 0 !important;">
    <table class="table table-hover" style="width: 100% !important; border-collapse: separate; border-spacing: 0; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin: 0 !important;">
        <thead style="background-color: #0453cb !important; color: white !important;">
            <tr>
                <th style="padding: 16px !important; font-weight: 600 !important; text-transform: uppercase !important; letter-spacing: 0.5px !important; border: none !important;">
                    <i class="fas fa-user"></i> Étudiant
                </th>
                <th style="padding: 16px !important; font-weight: 600 !important; text-transform: uppercase !important; letter-spacing: 0.5px !important; border: none !important;">
                    <i class="fas fa-school"></i> Classe
                </th>
                <th style="padding: 16px !important; font-weight: 600 !important; text-transform: uppercase !important; letter-spacing: 0.5px !important; border: none !important; text-align: center !important;">
                    <i class="fas fa-chart-line"></i> Moyenne
                </th>
                @if($type === 'rattrapages')
                <th style="padding: 16px !important; font-weight: 600 !important; text-transform: uppercase !important; letter-spacing: 0.5px !important; border: none !important; text-align: center !important;">
                    <i class="fas fa-exclamation-triangle"></i> Matières Échouées
                </th>
                @endif
                <th style="padding: 16px !important; font-weight: 600 !important; text-transform: uppercase !important; letter-spacing: 0.5px !important; border: none !important; text-align: center !important;">
                    <i class="fas fa-euro-sign"></i> Solde
                </th>
                <th style="padding: 16px !important; font-weight: 600 !important; text-transform: uppercase !important; letter-spacing: 0.5px !important; border: none !important; text-align: center !important;">
                    <i class="fas fa-flag"></i> Décision
                </th>
                <th style="padding: 16px !important; font-weight: 600 !important; text-transform: uppercase !important; letter-spacing: 0.5px !important; border: none !important; text-align: center !important;">
                    <i class="fas fa-cogs"></i> Actions
                </th>
            </tr>
        </thead>
        <tbody style="background-color: white;">
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
                        
                        <div style="font-weight: 600; color: #10b981;">{{ number_format($montantPaye, 0, ',', ' ') }} FCFA</div>
                        <small style="color: #64748b;">Payé</small>
                        
                        @if($soldeRestant > 0)
                        <span class="badge danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            {{ number_format($soldeRestant, 0, ',', ' ') }} FCFA à payer
                        </span>
                        @elseif($soldeRestant < 0)
                        <span class="badge warning">
                            <i class="fas fa-plus-circle"></i>
                            {{ number_format(abs($soldeRestant), 0, ',', ' ') }} FCFA trop-perçu
                        </span>
                        @else
                        <span class="badge success">
                            <i class="fas fa-check-circle"></i> Soldé
                        </span>
                        @endif
                        
                        @if($peutReinscrire)
                            <span class="badge success" style="font-size: 12px;">
                                <i class="fas fa-check-circle"></i> Éligible
                            </span>
                        @else
                            <span class="badge danger" style="font-size: 12px;">
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
                    <div style="display: flex; gap: 4px; justify-content: center; align-items: center;">
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
    // Demander le type d'abandon
    const typeOptions = `
        <select id="abandon-type" style="width: 100%; padding: 8px; margin: 10px 0;">
            <option value="">-- Choisir le type d'abandon --</option>
            <option value="annee_scolaire">Abandon de l'année scolaire (n'a pas soldé, ne vient plus)</option>
            <option value="ecole">Abandon de l'école (année réussie mais quitte l'établissement)</option>
        </select>
    `;
    
    const motif = prompt(`Type d'abandon:\n\n${typeOptions.replace(/<[^>]*>/g, '')}\n\nVeuillez préciser le motif de l'abandon:`);
    
    if (motif === null) return; // Annulé
    
    // Simuler le choix du type (en réalité il faudrait une modal)
    const typeAbandon = confirm('Type d\'abandon:\n\nOUI = Abandon année scolaire (n\'a pas soldé, ne vient plus)\nNON = Abandon école (année réussie mais quitte l\'établissement)') 
        ? 'annee_scolaire' : 'ecole';
    
    if (confirm(`Confirmer l'abandon de type "${typeAbandon === 'annee_scolaire' ? 'Année scolaire' : 'École'}" ?\n\nMotif: ${motif || 'Non précisé'}`)) {
        fetch(`{{ url('esbtp/reinscription') }}/${etudiantId}/abandon`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                motif_abandon: motif,
                abandon_type: typeAbandon
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
            debugError('Erreur:', error);
            alert('Erreur lors de l\'enregistrement de l\'abandon');
        });
    }
}

function validerReinscription(etudiantId, decision) {
    const observations = prompt(`Valider la réinscription avec décision: ${decision}\n\nObservations (optionnel):`);
    
    if (observations === null) return; // Annulé
    
    if (confirm(`Confirmer la validation de la réinscription ?\n\nDécision: ${decision}\nObservations: ${observations || 'Aucune'}`)) {
        fetch(`{{ url('esbtp/reinscription') }}/${etudiantId}/valider`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                decision: decision,
                observations: observations
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
            debugError('Erreur:', error);
            alert('Erreur lors de la validation');
        });
    }
}
</script>