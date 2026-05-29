@php
    $alert = $inscriptionWorkflowAlert ?? [];
    $redirectTo = $redirectTo ?? 'etudiant';
    $redirectUrl = $redirectUrl ?? request()->fullUrl();
@endphp

@if(($alert['show_banner'] ?? false) === true)
<div style="
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border: 1.5px solid #f59e0b;
    border-left: 5px solid #d97706;
    border-radius: 10px;
    padding: 16px 20px;
    margin-bottom: 24px;
    display: flex;
    align-items: flex-start;
    gap: 14px;
">
    <div style="flex-shrink:0; width:36px; height:36px; background:#d97706; border-radius:50%; display:flex; align-items:center; justify-content:center;">
        <i class="fas fa-exclamation-triangle" style="color:#fff; font-size:.9rem;"></i>
    </div>
    <div style="flex:1;">
        <div style="font-weight:700; color:#92400e; font-size:.95rem; margin-bottom:4px;">
            L'inscription de {{ $alert['year_label'] ?? 'cette année' }} n'est pas validée
        </div>
        <div style="color:#78350f; font-size:.85rem; line-height:1.5; margin-bottom:8px;">
            Cette inscription existe mais le processus de validation n'est pas terminé. L'étudiant n'a pas encore accès à toutes les fonctionnalités.
        </div>
        <div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom:10px;">
            <span style="display:inline-flex; align-items:center; gap:5px; padding:4px 10px; background:rgba(146,64,14,.1); border:1px solid rgba(146,64,14,.2); border-radius:6px; font-size:.78rem; color:#92400e; font-weight:600;">
                <i class="fas fa-tag" style="font-size:.65rem;"></i> Statut : {{ $alert['status_label'] ?? 'Non défini' }}
            </span>
            <span style="display:inline-flex; align-items:center; gap:5px; padding:4px 10px; background:rgba(146,64,14,.1); border:1px solid rgba(146,64,14,.2); border-radius:6px; font-size:.78rem; color:#92400e; font-weight:600;">
                <i class="fas fa-tasks" style="font-size:.65rem;"></i> Étape : {{ $alert['workflow_step_label'] ?? 'Non défini' }}
            </span>
        </div>
        @if(($alert['can_validate'] ?? false) && !empty($alert['validation_url']) && !empty($alert['inscription_id']))
            <form action="{{ $alert['validation_url'] }}" method="POST" style="display:inline-flex;">
                @csrf
                @method('PUT')
                <input type="hidden" name="redirect_to" value="{{ $redirectTo }}">
                <input type="hidden" name="redirect_url" value="{{ $redirectUrl }}">
                <button type="submit"
                        style="display:inline-flex; align-items:center; gap:6px; padding:8px 16px; background:linear-gradient(135deg, #059669, #10b981); color:#fff; border:none; border-radius:8px; font-size:.84rem; font-weight:600; cursor:pointer; box-shadow:0 2px 8px rgba(5,150,105,.3);">
                    <i class="fas fa-check-circle"></i> Valider l'inscription
                </button>
            </form>
        @endif
    </div>
</div>
@endif
