@php
    $score = null;
    if (!empty($performanceScore)) {
        $score = is_array($performanceScore)
            ? ($performanceScore['total_score'] ?? null)
            : ($performanceScore->total_score ?? null);
    }
    $initiales = collect(preg_split('/\s+/', trim((string) $model->name)))
        ->filter()
        ->take(2)
        ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))
        ->implode('');
    $phone = $model->phone ?? $model->telephone ?? null;
    $editRoute = route($routeBase.'.edit', $model);
    $toggleRoute = route($routeBase.'.toggle-status', $model);
    $destroyRoute = route($routeBase.'.destroy', $model);
    $resetRoute = route($routeBase.'.reset-password', $model);
    $backRoute = auth()->user()->can('personnel.manage')
        ? route('esbtp.personnel.unified.index')
        : route('dashboard');
@endphp

<div class="cs-page">
    <div class="cs-hero">
        <div class="cs-hero-inner">
            <div class="cs-hero-avatar">
                <div class="cs-hero-avatar-circle">
                    @if(!empty($model->photo))
                        <img src="{{ asset('storage/'.$model->photo) }}" alt="">
                    @else
                        {{ $initiales ?: '—' }}
                    @endif
                </div>
                <span class="cs-hero-status {{ $model->is_active ? 'active' : 'inactive' }}"></span>
            </div>
            <div class="cs-hero-text">
                <h1 class="cs-hero-name">{{ $model->name }}</h1>
                <p class="cs-hero-sub">{{ $model->specialite ?: $roleLabel }}</p>
                <div class="cs-hero-pills">
                    <span class="cs-hero-pill"><i class="fas {{ $icon }}"></i> {{ $roleLabel }}</span>
                    <span class="cs-hero-pill {{ $model->is_active ? 'green' : '' }}">
                        {{ $model->is_active ? 'Actif' : 'Inactif' }}
                    </span>
                    <span class="cs-hero-pill muted"><i class="fas fa-at"></i> {{ $model->username }}</span>
                </div>
            </div>
            <div class="cs-hero-actions">
                <div class="cs-hero-btns">
                    <a href="{{ $editRoute }}" class="cs-hero-btn primary"><i class="fas fa-edit"></i> Modifier</a>
                    <a href="{{ $backRoute }}" class="cs-hero-btn ghost"><i class="fas fa-arrow-left"></i> Retour</a>
                </div>
            </div>
        </div>
        <div class="cs-kpi-strip">
            <div class="cs-kpi">
                <i class="fas fa-calendar-check cs-kpi-icon"></i>
                <div>
                    <div class="cs-kpi-val">{{ $model->created_at?->diffInDays(now()) ?? '—' }}</div>
                    <div class="cs-kpi-lbl">Jours d'ancienneté</div>
                </div>
            </div>
            <div class="cs-kpi">
                <i class="fas fa-sign-in-alt cs-kpi-icon"></i>
                <div>
                    <div class="cs-kpi-val">{{ $model->last_login_at ? $model->last_login_at->format('d/m/Y') : '—' }}</div>
                    <div class="cs-kpi-lbl">Dernière connexion</div>
                </div>
            </div>
            <div class="cs-kpi">
                <i class="fas fa-user cs-kpi-icon"></i>
                <div>
                    <div class="cs-kpi-val">{{ $model->username }}</div>
                    <div class="cs-kpi-lbl">Identifiant</div>
                </div>
            </div>
            <div class="cs-kpi">
                <i class="fas fa-chart-line cs-kpi-icon"></i>
                <div>
                    <div class="cs-kpi-val">{{ $score !== null ? $score : '—' }}</div>
                    <div class="cs-kpi-lbl">Score</div>
                </div>
            </div>
        </div>
    </div>

    <div class="cs-tabs-wrap">
        <div class="cs-tabs">
            <button class="cs-tab active" data-tab="info" type="button"><i class="fas fa-user"></i> Informations</button>
            <button class="cs-tab" data-tab="account" type="button"><i class="fas fa-user-cog"></i> Compte</button>
            <button class="cs-tab" data-tab="actions" type="button"><i class="fas fa-cog"></i> Actions</button>
        </div>
    </div>

    <div class="cs-content">
        <div class="cs-panel active" id="cs-tab-info">
            <div class="cs-grid-2">
                <div class="cs-card">
                    <div class="cs-card-header"><div class="cs-card-title"><div class="cs-card-title-icon"><i class="fas fa-id-card"></i></div>Informations personnelles</div></div>
                    <div class="cs-info-row"><span class="cs-info-label"><i class="fas fa-user"></i> Nom complet</span><span class="cs-info-value">{{ $model->name }}</span></div>
                    <div class="cs-info-row"><span class="cs-info-label"><i class="fas fa-at"></i> Identifiant</span><span class="cs-info-value">{{ $model->username }}</span></div>
                    <div class="cs-info-row"><span class="cs-info-label"><i class="fas fa-envelope"></i> Email</span><span class="cs-info-value">{{ $model->email ?: 'Non renseigné' }}</span></div>
                    <div class="cs-info-row"><span class="cs-info-label"><i class="fas fa-phone"></i> Téléphone</span><span class="cs-info-value">{{ $phone ?: 'Non renseigné' }}</span></div>
                    @if($model->specialite)
                    <div class="cs-info-row"><span class="cs-info-label"><i class="fas fa-briefcase"></i> Spécialité</span><span class="cs-info-value">{{ $model->specialite }}</span></div>
                    @endif
                </div>
                <div class="cs-card">
                    <div class="cs-card-header"><div class="cs-card-title"><div class="cs-card-title-icon"><i class="fas fa-briefcase"></i></div>Compte</div></div>
                    <div class="cs-info-row"><span class="cs-info-label"><i class="fas {{ $icon }}"></i> Rôle</span><span class="cs-info-value">{{ $roleLabel }}</span></div>
                    <div class="cs-info-row"><span class="cs-info-label"><i class="fas fa-toggle-on"></i> Statut</span>
                        <span class="cs-info-value"><span class="cs-badge {{ $model->is_active ? 'success' : 'danger' }}">{{ $model->is_active ? 'Actif' : 'Inactif' }}</span></span>
                    </div>
                    <div class="cs-info-row"><span class="cs-info-label"><i class="fas fa-clock"></i> Dernière connexion</span><span class="cs-info-value">{{ $model->last_login_at ? $model->last_login_at->format('d/m/Y H:i') : 'Jamais' }}</span></div>
                    <div class="cs-info-row"><span class="cs-info-label"><i class="fas fa-calendar"></i> Compte créé</span><span class="cs-info-value">{{ $model->created_at?->format('d/m/Y') }}</span></div>
                </div>
            </div>
        </div>

        <div class="cs-panel" id="cs-tab-account">
            <div class="cs-grid-2">
                <div class="cs-card">
                    <div class="cs-card-header"><div class="cs-card-title"><div class="cs-card-title-icon"><i class="fas fa-key"></i></div>Mot de passe</div></div>
                    <p style="color:#64748b;font-size:.88rem;margin:0 0 1rem;">Réinitialise le mot de passe à <strong>Bonjour@2025</strong>. La personne devra le changer à la prochaine connexion.</p>
                    <button type="button" class="cs-action-btn" onclick="showResetPasswordModal()"><i class="fas fa-key"></i><span>Réinitialiser le mot de passe</span></button>
                </div>
                <div class="cs-card">
                    <div class="cs-card-header"><div class="cs-card-title"><div class="cs-card-title-icon"><i class="fas fa-shield-alt"></i></div>Sécurité</div></div>
                    <div class="cs-info-row"><span class="cs-info-label">Identifiant</span><span class="cs-info-value">{{ $model->username }}</span></div>
                    <div class="cs-info-row"><span class="cs-info-label">Dernière connexion</span><span class="cs-info-value">{{ $model->last_login_at ? $model->last_login_at->format('d/m/Y H:i') : 'Jamais' }}</span></div>
                    <div class="cs-info-row"><span class="cs-info-label">Doit changer le mot de passe</span><span class="cs-info-value">{{ $model->must_change_password ? 'Oui' : 'Non' }}</span></div>
                </div>
            </div>
        </div>

        <div class="cs-panel" id="cs-tab-actions">
            <div class="cs-grid-2">
                <div class="cs-card">
                    <div class="cs-card-header"><div class="cs-card-title"><div class="cs-card-title-icon"><i class="fas fa-tools"></i></div>Actions rapides</div></div>
                    <div style="display:flex;flex-direction:column;gap:10px;">
                        <a href="{{ $editRoute }}" class="cs-action-btn"><i class="fas fa-edit"></i><span>Modifier le profil</span></a>
                        <form action="{{ $toggleRoute }}" method="POST" onsubmit="return confirm('Changer le statut de ce compte ?')">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="cs-action-btn {{ $model->is_active ? 'warning-action' : 'success-action' }}">
                                <i class="fas {{ $model->is_active ? 'fa-pause-circle' : 'fa-play-circle' }}"></i>
                                <span>{{ $model->is_active ? 'Désactiver le compte' : 'Activer le compte' }}</span>
                            </button>
                        </form>
                        <button type="button" class="cs-action-btn" onclick="showResetPasswordModal()"><i class="fas fa-key"></i><span>Réinitialiser le mot de passe</span></button>
                    </div>
                </div>
                <div class="cs-card">
                    <div class="cs-card-header"><div class="cs-card-title"><div class="cs-card-title-icon" style="background:linear-gradient(135deg,#b91c1c,#dc2626);"><i class="fas fa-exclamation-triangle"></i></div>Zone de danger</div></div>
                    <p style="font-size:.85rem;color:#64748b;margin:0 0 16px;">Cette action désactive le compte. Elle n'est pas anodine.</p>
                    <form action="{{ $destroyRoute }}" method="POST" onsubmit="return confirm('Supprimer / désactiver ce compte ?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="cs-action-btn danger-action"><i class="fas fa-trash-alt"></i><span>Supprimer</span></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px;border:none;overflow:hidden;">
            <div class="modal-header" style="background:linear-gradient(135deg,#0453cb,#5e91de);color:#fff;border:none;">
                <h5 class="modal-title">Réinitialiser le mot de passe</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="resetPasswordForm" method="POST" action="{{ $resetRoute }}">
                @csrf
                <div class="modal-body">
                    <p style="color:#475569;">Le mot de passe de <strong>{{ $model->name }}</strong> passera à <strong>Bonjour@2025</strong>. Changement obligatoire à la prochaine connexion.</p>
                    <div id="newPasswordDisplay" style="display:none;" class="mb-2">
                        <div id="newPasswordValue" style="background:#d1fae5;border:2px solid #10b981;border-radius:8px;padding:1rem;font-family:monospace;font-size:1.2rem;font-weight:700;text-align:center;color:#047857;"></div>
                    </div>
                </div>
                <div class="modal-footer" style="border:none;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn" id="resetPasswordBtn" style="background:#0453cb;color:#fff;">Réinitialiser à Bonjour@2025</button>
                    <button type="button" class="btn" id="copyPasswordBtn" style="display:none;background:#10b981;color:#fff;" onclick="copyPassword()">Copier</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('styles')
<style>
.cs-page { background:#f4f7fb; min-height:100vh; --cs-blue:#0453cb; --cs-blue-2:#5e91de; --cs-surface:#f4f7fb; --cs-card:#fff; --cs-border:#e2e8f0; --cs-text:#1e293b; --cs-muted:#64748b; --cs-danger:#dc2626; }
.cs-hero { background:linear-gradient(135deg,#0453cb,#5e91de); }
.cs-hero-inner { max-width:1280px; margin:0 auto; padding:30px 32px 20px; display:grid; grid-template-columns:auto minmax(0,1fr) auto; align-items:center; gap:18px; }
.cs-hero-avatar { position:relative; }
.cs-hero-avatar-circle { width:82px; height:82px; border-radius:18px; border:1px solid rgba(255,255,255,.6); background:rgba(255,255,255,.15); color:#fff; font-size:1.6rem; font-weight:700; display:flex; align-items:center; justify-content:center; overflow:hidden; }
.cs-hero-avatar-circle img { width:100%; height:100%; object-fit:cover; }
.cs-hero-status { position:absolute; bottom:-3px; right:-3px; width:16px; height:16px; border-radius:50%; border:3px solid #fff; }
.cs-hero-status.active { background:#10b981; } .cs-hero-status.inactive { background:#94a3b8; }
.cs-hero-text { color:#fff; } .cs-hero-name { font-size:1.55rem; font-weight:800; margin:0 0 4px; } .cs-hero-sub { margin:0 0 12px; opacity:.84; }
.cs-hero-pills { display:flex; gap:7px; flex-wrap:wrap; }
.cs-hero-pill { display:inline-flex; align-items:center; gap:5px; background:rgba(255,255,255,.18); border:1px solid rgba(255,255,255,.28); color:#fff; font-size:.76rem; font-weight:600; padding:3px 11px; border-radius:20px; }
.cs-hero-pill.green { background:rgba(16,185,129,.25); } .cs-hero-pill.muted { background:rgba(255,255,255,.1); }
.cs-hero-btns { display:flex; gap:8px; flex-wrap:wrap; }
.cs-hero-btn { display:inline-flex; align-items:center; gap:6px; padding:9px 18px; min-height:40px; border-radius:10px; font-size:.82rem; font-weight:600; text-decoration:none; }
.cs-hero-btn.primary { background:#fff; color:#0453cb; } .cs-hero-btn.ghost { background:rgba(255,255,255,.15); color:#fff; border:1px solid rgba(255,255,255,.35); }
.cs-kpi-strip { max-width:1280px; margin:0 auto; display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:10px; padding:0 32px 26px; }
.cs-kpi { min-height:70px; border:1px solid rgba(255,255,255,.16); border-radius:14px; background:rgba(255,255,255,.11); color:#fff; display:flex; align-items:center; gap:12px; padding:14px 16px; }
.cs-kpi-val { font-weight:700; font-size:1.05rem; } .cs-kpi-lbl { font-size:.7rem; opacity:.76; text-transform:uppercase; }
.cs-tabs-wrap { background:#fff; box-shadow:0 1px 0 #e2e8f0; position:sticky; top:0; z-index:50; }
.cs-tabs { max-width:1280px; margin:0 auto; display:flex; padding:0 24px; }
.cs-tab { background:none; border:none; height:52px; padding:0 20px; font-weight:600; color:#64748b; position:relative; }
.cs-tab.active { color:#0453cb; } .cs-tab.active::after { content:''; position:absolute; left:12px; right:12px; bottom:0; height:3px; background:#0453cb; border-radius:3px 3px 0 0; }
.cs-content { max-width:1280px; margin:0 auto; padding:28px 24px 60px; }
.cs-panel { display:none; } .cs-panel.active { display:block; }
.cs-card { background:#fff; border:1px solid #e2e8f0; border-radius:16px; padding:24px; margin-bottom:20px; }
.cs-card-header { margin-bottom:16px; } .cs-card-title { display:flex; align-items:center; gap:10px; font-weight:700; }
.cs-card-title-icon { width:32px; height:32px; border-radius:8px; background:linear-gradient(135deg,#0453cb,#5e91de); color:#fff; display:flex; align-items:center; justify-content:center; }
.cs-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
.cs-info-row { display:flex; justify-content:space-between; gap:12px; padding:12px 0; border-bottom:1px solid #e2e8f0; }
.cs-info-label { color:#64748b; font-size:.85rem; } .cs-info-value { font-weight:600; color:#1e293b; }
.cs-badge { display:inline-flex; padding:4px 12px; border-radius:20px; font-size:.76rem; font-weight:600; }
.cs-badge.success { background:rgba(16,185,129,.1); color:#059669; } .cs-badge.danger { background:rgba(220,38,38,.1); color:#dc2626; }
.cs-action-btn { display:flex; align-items:center; gap:10px; width:100%; padding:10px 14px; border-radius:10px; border:1px solid #e2e8f0; background:#fff; color:#1e293b; font-weight:600; text-decoration:none; }
.cs-action-btn.danger-action { color:#dc2626; } .cs-action-btn.warning-action { color:#d97706; } .cs-action-btn.success-action { color:#059669; }
@media (max-width:900px) { .cs-hero-inner, .cs-kpi-strip, .cs-grid-2 { grid-template-columns:1fr; } }
</style>
@endpush

@push('scripts')
<script>
document.querySelectorAll('.cs-tab').forEach(function(tab) {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.cs-tab').forEach(function(t) { t.classList.remove('active'); });
        document.querySelectorAll('.cs-panel').forEach(function(p) { p.classList.remove('active'); });
        this.classList.add('active');
        var panel = document.getElementById('cs-tab-' + this.getAttribute('data-tab'));
        if (panel) panel.classList.add('active');
    });
});
function showResetPasswordModal() {
    new bootstrap.Modal(document.getElementById('resetPasswordModal')).show();
}
document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var form = this;
    var btn = document.getElementById('resetPasswordBtn');
    btn.disabled = true;
    fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                document.getElementById('newPasswordValue').textContent = data.password;
                document.getElementById('newPasswordDisplay').style.display = 'block';
                btn.style.display = 'none';
                document.getElementById('copyPasswordBtn').style.display = 'inline-block';
            } else {
                btn.disabled = false;
                alert(data.message || 'Erreur');
            }
        })
        .catch(function() { btn.disabled = false; alert('Erreur de connexion'); });
});
function copyPassword() {
    navigator.clipboard.writeText(document.getElementById('newPasswordValue').textContent);
}
document.getElementById('resetPasswordModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('newPasswordDisplay').style.display = 'none';
    document.getElementById('resetPasswordBtn').style.display = 'inline-block';
    document.getElementById('resetPasswordBtn').disabled = false;
    document.getElementById('copyPasswordBtn').style.display = 'none';
});
</script>
@endpush
