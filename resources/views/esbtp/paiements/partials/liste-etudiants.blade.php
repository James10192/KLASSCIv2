{{-- Template pour la page 1 avec structure complète --}}
<div class="students-list" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 16px; margin-top: 20px;">
    @foreach($etudiants as $etudiant)
    @php
        $statusClass = 'danger';
        $statusIcon = 'fas fa-exclamation-triangle';
        $statusText = 'Aucun paiement';

        if ($etudiant['pourcentage'] == 100) {
            $statusClass = 'success';
            $statusIcon = 'fas fa-check-circle';
            $statusText = 'À jour';
        } elseif ($etudiant['pourcentage'] > 0) {
            $statusClass = 'warning';
            $statusIcon = 'fas fa-clock';
            $statusText = 'Paiement partiel';
        }

        // Générer les initiales pour l'avatar
        $prenoms = $etudiant['inscription']->etudiant->prenoms ?? 'N';
        $nom = $etudiant['inscription']->etudiant->nom ?? 'A';
        $initiales = strtoupper(substr($prenoms, 0, 1) . substr($nom, 0, 1));
    @endphp

    <div class="card-moderne student-card {{ $statusClass }}" style="transition: all 0.3s ease;">
        <div class="student-info" style="display: flex; align-items: center; gap: 16px; margin-bottom: 12px;">
            <div class="student-avatar" style="width: 44px; height: 44px; border-radius: 50%; background-color: #0453cb; color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; margin-right: 0;">
                {{ $initiales }}
            </div>
            <div class="student-details" style="flex: 1;">
                <h6 style="font-weight: 600; margin: 0 0 4px 0; color: #1f2937; font-size: 14px;">
                    {{ ($etudiant['inscription']->etudiant->prenoms ?? '') . ' ' . ($etudiant['inscription']->etudiant->nom ?? '') }}
                </h6>
                <p style="font-size: 12px; color: #6b7280; margin: 0;">
                    {{ $etudiant['inscription']->etudiant->matricule ?? 'Matricule non disponible' }}
                </p>
                <p style="font-size: 12px; color: #9ca3af; margin: 2px 0 0 0;">
                    {{ $etudiant['inscription']->filiere->name ?? 'Filière N/A' }} - {{ $etudiant['inscription']->niveauEtude->name ?? 'Niveau N/A' }}
                </p>
            </div>
        </div>

        <div class="payment-summary" style="display: flex; justify-content: space-between; align-items: center;">
            <span class="percentage-badge {{ $statusClass }}" style="padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 600;">
                <i class="{{ $statusIcon }} me-1"></i>{{ $etudiant['pourcentage'] }}%
            </span>
            <div class="amount-info" style="text-align: right; display: flex; flex-direction: column; gap: 6px;">
                <div class="amount-paid" style="background: #059669; color: white; padding: 6px 12px; border-radius: 20px; font-weight: 600; font-size: 12px; box-shadow: 0 2px 4px rgba(5, 150, 105, 0.2);">
                    {{ number_format($etudiant['montant_paye'], 0, ',', ' ') }} FCFA payé
                </div>
                <div class="amount-due" style="background: #dc2626; color: white; padding: 6px 12px; border-radius: 20px; font-weight: 600; font-size: 12px; box-shadow: 0 2px 4px rgba(220, 38, 38, 0.2);">
                    {{ number_format($etudiant['montant_attendu'], 0, ',', ' ') }} FCFA dû
                </div>
            </div>
        </div>

        @if($etudiant['solde'] > 0)
        <div style="margin-top: 8px; padding: 6px 8px; background: rgba(239, 68, 68, 0.1); border-radius: 6px; border-left: 3px solid #dc2626;">
            <small style="color: #dc2626; font-weight: 500;">
                <i class="fas fa-exclamation-circle me-1"></i>
                Reste à payer : {{ number_format($etudiant['solde'], 0, ',', ' ') }} FCFA
            </small>
        </div>
        @endif

        @if($etudiant['derniers_paiements']->count() > 0)
        <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #f3f4f6;">
            <small style="color: #6b7280; font-weight: 500; display: block; margin-bottom: 4px;">
                <i class="fas fa-history me-1"></i>Derniers paiements :
            </small>
            @foreach($etudiant['derniers_paiements']->take(2) as $paiement)
            <small style="display: block; color: #059669; font-size: 11px;">
                • {{ number_format($paiement->montant, 0, ',', ' ') }} FCFA - {{ $paiement->date_paiement ? \Carbon\Carbon::parse($paiement->date_paiement)->format('d/m/Y') : 'Date N/A' }}
            </small>
            @endforeach
        </div>
        @endif
    </div>
    @endforeach
</div>