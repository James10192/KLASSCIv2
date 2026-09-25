{{-- Une inscription sous reserve : rendue par la page et par la suite chargee au defilement. Attend $inscription et $anneeEnCours. --}}
@php
    $paiementValide = $inscription->paiements->firstWhere('status', 'validé');
    $totalPaye = \App\Models\ESBTPPaiement::netStudentPaidFrom($inscription->paiements);
    $fullName = trim(($inscription->etudiant->nom ?? '') . ' ' . ($inscription->etudiant->prenoms ?? ''));
    $initials = strtoupper(substr($inscription->etudiant->nom ?? '?', 0, 1));
    $hue = crc32($fullName) % 360;
    $photoUrl = $inscription->etudiant->photo_url ?? null;
@endphp
<tr data-inscription-id="{{ $inscription->id }}" data-li-cle="{{ $inscription->id }}"
    data-href="{{ route('esbtp.inscriptions.show', $inscription) }}"
    data-student-label="{{ $fullName }}">
    <td data-no-row-click>
        <input class="form-check-input isr-row-checkbox" type="checkbox" value="{{ $inscription->id }}" aria-label="Sélectionner cette inscription">
    </td>
    <td>
        <div class="ii-student">
            @if($photoUrl)
                <img src="{{ $photoUrl }}" alt="" class="ii-student-photo">
            @else
                <div class="ii-student-photo" style="background:hsl({{ $hue }}, 60%, 55%);">{{ $initials }}</div>
            @endif
            <div>
                <div class="ii-student-name">{{ $fullName }}</div>
                <div class="ii-student-meta">{{ $inscription->etudiant->matricule ?? '—' }}</div>
            </div>
        </div>
    </td>
    <td>
        <div>{{ $inscription->classe->name ?? '—' }}</div>
        @if($inscription->filiere)
            <div class="ii-student-meta">{{ $inscription->filiere->name }}</div>
        @endif
    </td>
    <td>
        <strong>{{ $inscription->anneeUniversitaire->name ?? '—' }}</strong>
        @if($anneeEnCours && $inscription->annee_universitaire_id == $anneeEnCours->id)
            <div class="ii-student-meta" style="color:var(--ii-success);"><i class="fas fa-check"></i> Courante</div>
        @endif
    </td>
    <td>
        <span class="isr-condition-badge">
            <i class="fas fa-clock"></i> {{ $inscription->condition_reserve ?? 'Non précisé' }}
        </span>
    </td>
    <td>
        @if($totalPaye > 0)
            <span class="ii-paiement-chip ii-paiement-chip--paye">
                <i class="fas fa-check"></i> @can('finances.etudiants.voir'){{ number_format($totalPaye, 0, ',', ' ') }} F @else Payé @endcan
            </span>
        @else
            <span class="ii-paiement-chip ii-paiement-chip--aucun">
                <i class="fas fa-times"></i> Aucun
            </span>
        @endif
    </td>
    <td>
        <x-workflow-step-badge :inscription="$inscription" />
    </td>
    <td>{{ optional($inscription->created_at)->format('d/m/Y') }}</td>
    <td style="text-align:right;" data-no-row-click>
        <div class="ii-actions" style="justify-content:flex-end;">
            <button type="button" class="ii-action-btn ii-action-btn--primary"
                    onclick="isrLeverReserve({{ $inscription->id }}, '{{ addslashes($fullName) }}')"
                    title="Lever la réserve">
                <i class="fas fa-check"></i>
            </button>
            <button type="button" class="ii-action-btn ii-action-btn--danger"
                    onclick="isrAnnuler({{ $inscription->id }}, '{{ addslashes($fullName) }}')"
                    title="Annuler l'inscription">
                <i class="fas fa-ban"></i>
            </button>
        </div>
    </td>
</tr>
