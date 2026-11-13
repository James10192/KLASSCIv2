{{-- Une ligne de la liste des relances. Rendue par _table et par chaque
     tranche du defilement (ESBTPComptabiliteRelanceController::gestionRelances). --}}
@php
    $etudiant  = optional($row->inscription->etudiant);
    $classe    = optional($row->inscription->classe);
    $filiere   = optional($classe->filiere);
    $nom       = $etudiant->nom ?? '';
    $prenoms   = $etudiant->prenoms ?? '';
    $initiales = strtoupper(mb_substr($nom, 0, 1) . mb_substr($prenoms, 0, 1));
    $nomComplet = trim("$nom $prenoms") ?: '(sans nom)';

    $pbClass = match(true) {
        $row->pourcentage >= 100 => 'full',
        $row->pourcentage >= 50  => 'partial',
        $row->pourcentage > 0    => 'low-pay',
        default                   => 'none',
    };
@endphp
<tr data-li-cle="{{ $row->inscription->id }}">
    {{-- Étudiant --}}
    <td>
        <div class="stud-cell">
            <div class="stud-avatar">{{ $initiales ?: '?' }}</div>
            <div>
                <div class="stud-name">{{ $nomComplet }}</div>
                <div class="stud-matricule">{{ $etudiant->matricule ?? '—' }}</div>
            </div>
        </div>
    </td>

    {{-- Classe --}}
    <td>{{ $classe->name ?? '—' }}</td>

    {{-- Filière --}}
    <td>{{ $filiere->name ?? '—' }}</td>

    {{-- Progression --}}
    <td>
        <div class="pbar-wrap">
            <div class="pbar-track">
                <div class="pbar-fill {{ $pbClass }}"
                     style="width:{{ max(4, $row->pourcentage) }}%"></div>
            </div>
            <span class="pbar-pct">{{ $row->pourcentage }}%</span>
        </div>
    </td>

    {{-- Total dû --}}
    <td class="amount-cell">
        {{ number_format($row->totalDu, 0, ',', ' ') }}
        <span class="amount-unit">FCFA</span>
    </td>

    {{-- À relancer : échu à ce jour + reliquat, paiements validés déduits.
         Le reste de l'année et le reliquat sont détaillés dessous pour que le
         comptable retrouve le montant qu'il connaît. --}}
    <td class="amount-cell amount-red">
        <div>
            {{ number_format($row->soldeRestant, 0, ',', ' ') }}
            <span class="amount-unit">FCFA</span>
        </div>
        @if(($row->reliquatAnterieur ?? 0) > 0)
        <div class="amount-detail" title="Dette reportée des années précédentes, comprise dans le montant à relancer">
            dont {{ number_format($row->reliquatAnterieur, 0, ',', ' ') }} de reliquat
        </div>
        @endif
        @if(($row->resteTotal ?? 0) > $row->soldeRestant)
        <div class="amount-detail" title="Tout ce que l'étudiant doit encore, y compris les tranches pas encore échues">
            Reste à payer : {{ number_format($row->resteTotal, 0, ',', ' ') }}
        </div>
        @endif
        @if(($row->totalPayeEnAttente ?? 0) > 0)
        <div class="pending-badge" title="Paiement enregistré mais en attente de validation par le secrétariat">
            <i class="fas fa-clock"></i>
            {{ number_format($row->totalPayeEnAttente, 0, ',', ' ') }} en attente
        </div>
        @endif
    </td>

    {{-- Situation --}}
    <td>
        <span class="rbadge {{ $row->risk }}">
            @if($row->risk === 'critical')
                <i class="fas fa-ban" style="font-size:.65em;"></i>
            @elseif($row->risk === 'high')
                <i class="fas fa-hourglass-half" style="font-size:.65em;"></i>
            @elseif($row->risk === 'medium')
                <i class="fas fa-tasks" style="font-size:.65em;"></i>
            @else
                <i class="fas fa-check" style="font-size:.65em;"></i>
            @endif
            {{ $row->riskLabel }}
        </span>
    </td>

    {{-- Actions --}}
    <td>
        <div style="display:flex;gap:.4rem;flex-wrap:wrap;">
            <a href="{{ route('esbtp.comptabilite.relances.etudiant', $row->inscription->id) }}"
               class="act-btn primary" title="Voir fiche relance">
                <i class="fas fa-file-invoice-dollar"></i>
                <span class="d-none d-xl-inline">Fiche</span>
            </a>
            <a href="{{ route('esbtp.inscriptions.show', $row->inscription->id) }}"
               class="act-btn ghost" title="Voir inscription">
                <i class="fas fa-external-link-alt"></i>
            </a>
        </div>
    </td>
</tr>
