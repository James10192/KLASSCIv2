@extends('layouts.app')
@section('title', $examen->titre)

@push('styles')
<style>
[x-cloak] { display:none !important; }
.exp-show-hero { background:linear-gradient(135deg,#0a3d8f,#0453cb,#3b7ddb);border-radius:18px;
    padding:2rem 2.5rem;color:#fff;margin-bottom:1.25rem;}
.exp-show-hero h1 { margin:0;font-size:1.5rem; }
.exp-show-hero p { margin:.3rem 0 0;color:rgba(255,255,255,.75);font-size:.88rem; }
.exp-show-meta { display:flex;gap:1.5rem;flex-wrap:wrap;margin-top:1.25rem; }
.exp-show-meta-item { background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);
    border-radius:10px;padding:.6rem 1rem;}
.exp-show-meta-label { font-size:.65rem;color:rgba(255,255,255,.6);text-transform:uppercase;letter-spacing:.5px;}
.exp-show-meta-value { font-size:.95rem;font-weight:700;color:#fff;margin-top:.2rem;}

.exp-show-grid { display:grid;grid-template-columns:2fr 1fr;gap:1.25rem; }
.exp-show-card { background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:1.25rem; }
.exp-show-card h2 { margin:0 0 1rem;font-size:1rem;color:#1e293b;display:flex;align-items:center;gap:.5rem; }
.exp-show-card h2 i { color:#0453cb; }
.kv-row { display:flex;justify-content:space-between;padding:.5rem 0;border-bottom:1px solid #f1f5f9;font-size:.85rem;}
.kv-row:last-child { border-bottom:none; }
.kv-row > span:first-child { color:#64748b; }
.kv-row > span:last-child { color:#1e293b;font-weight:600;}

.exp-actions { display:flex;gap:.5rem;flex-wrap:wrap;margin-top:1.5rem;}
.btn { padding:.5rem .9rem;border-radius:9px;font-size:.82rem;font-weight:600;border:1px solid;
    cursor:pointer;display:inline-flex;align-items:center;gap:.4rem;text-decoration:none;}
.btn-primary { background:#0453cb;color:#fff;border-color:#0453cb;}
.btn-secondary { background:#f1f5f9;color:#475569;border-color:#e2e8f0;}
.btn-warning { background:rgba(245,158,11,.10);color:#b45309;border-color:rgba(245,158,11,.25);}
.btn-danger { background:rgba(220,38,38,.08);color:#b91c1c;border-color:rgba(220,38,38,.2);}

.surv-row { display:flex;align-items:center;justify-content:space-between;padding:.55rem .75rem;
    background:#f8fafc;border-radius:8px;margin-bottom:.4rem;font-size:.82rem; }
.surv-role { padding:.15rem .5rem;border-radius:5px;font-size:.65rem;text-transform:uppercase;
    background:rgba(4,83,203,.10);color:#0453cb;font-weight:700;}

@media(max-width:992px){.exp-show-grid{grid-template-columns:1fr;}}
</style>
@endpush

@section('content')
<div x-data="examenShow()">

<div class="exp-show-hero">
    <div style="display:flex;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
        <div>
            <h1>{{ $examen->titre }}</h1>
            <p>
                {{ $examen->classe->name ?? '—' }} ·
                {{ $examen->matiere->name ?? '—' }} ·
                <span style="font-family:'Courier New',monospace;font-weight:600;">{{ $examen->numero_convocation ?? '—' }}</span>
            </p>
        </div>
        <div style="display:flex;gap:.4rem;">
            @can('lmd.examens.manage')
            @if(! $examen->notes_locked)
            <a href="{{ route('esbtp.examens.edit', $examen) }}" class="btn" style="background:rgba(255,255,255,.15);color:#fff;border-color:rgba(255,255,255,.2);">
                <i class="fas fa-pen"></i> Modifier
            </a>
            @endif
            @endcan
        </div>
    </div>

    <div class="exp-show-meta">
        <div class="exp-show-meta-item">
            <div class="exp-show-meta-label">Type</div>
            <div class="exp-show-meta-value">{{ $examen->type_examen }}</div>
        </div>
        <div class="exp-show-meta-item">
            <div class="exp-show-meta-label">Date</div>
            <div class="exp-show-meta-value">{{ optional($examen->date_debut)->format('d/m/Y H:i') }}</div>
        </div>
        <div class="exp-show-meta-item">
            <div class="exp-show-meta-label">Durée</div>
            <div class="exp-show-meta-value">{{ $examen->duree_minutes ?? '—' }} min</div>
        </div>
        <div class="exp-show-meta-item">
            <div class="exp-show-meta-label">Salle</div>
            <div class="exp-show-meta-value">{{ $examen->salle ?? '—' }}</div>
        </div>
        <div class="exp-show-meta-item">
            <div class="exp-show-meta-label">Statut</div>
            <div class="exp-show-meta-value">
                @if($examen->notes_locked) <i class="fas fa-lock"></i> @endif
                {{ ucfirst(str_replace('_',' ', $examen->status)) }}
            </div>
        </div>
    </div>
</div>

<div class="exp-show-grid">
    <div>
        <div class="exp-show-card">
            <h2><i class="fas fa-info-circle"></i> Détails</h2>
            <div class="kv-row"><span>Semestre</span><span>{{ $examen->semestre ? 'S'.$examen->semestre : '—' }}</span></div>
            <div class="kv-row"><span>Parcours</span><span>{{ $examen->parcours->nom ?? '—' }}</span></div>
            <div class="kv-row"><span>Coefficient</span><span>{{ rtrim(rtrim(number_format($examen->coefficient, 2, '.', ''), '0'), '.') }}</span></div>
            <div class="kv-row"><span>Barème</span><span>{{ (int) $examen->bareme }}</span></div>
            <div class="kv-row"><span>Anonymisé</span><span>{{ $examen->is_anonymous ? 'Oui' : 'Non' }}</span></div>
            <div class="kv-row"><span>Créé le</span><span>{{ $examen->created_at?->format('d/m/Y H:i') }}</span></div>
            <div class="kv-row"><span>Créé par</span><span>{{ $examen->createdBy->name ?? '—' }}</span></div>
            @if($examen->notes_locked)
            <div class="kv-row"><span>Notes verrouillées</span><span><i class="fas fa-lock" style="color:#b91c1c;"></i> {{ $examen->notes_locked_at?->format('d/m/Y H:i') }}</span></div>
            @endif
            @if($examen->description)
            <div style="margin-top:1rem;padding:.75rem;background:#f8fafc;border-radius:8px;color:#475569;font-size:.85rem;">
                {{ $examen->description }}
            </div>
            @endif

            <div class="exp-actions">
                @can('lmd.examens.notes_lock')
                @if(! $examen->notes_locked)
                <button type="button" class="btn btn-warning" @click="lockNotes()" :disabled="locking">
                    <i class="fas fa-lock"></i> <span x-text="locking ? 'Verrouillage…' : 'Verrouiller les notes'"></span>
                </button>
                @endif
                @endcan
                <a href="{{ route('esbtp.examens.convocations.preview', ['classe_id' => $examen->classe_id, 'annee_universitaire_id' => $examen->annee_universitaire_id]) }}" target="_blank" class="btn btn-secondary">
                    <i class="fas fa-file-pdf"></i> Convocations PDF
                </a>
                @can('lmd.examens.manage')
                @if(! $examen->notes_locked)
                <form method="POST" action="{{ route('esbtp.examens.destroy', $examen) }}" style="display:inline;"
                    onsubmit="return confirm('Supprimer cet examen ?');">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Supprimer</button>
                </form>
                @endif
                @endcan
            </div>
        </div>
    </div>

    <div>
        <div class="exp-show-card">
            <h2><i class="fas fa-user-shield"></i> Surveillants <span style="margin-left:auto;font-size:.75rem;color:#64748b;font-weight:500;" x-text="`(${surveillants.length})`">({{ $examen->surveillants->count() }})</span></h2>

            <template x-for="s in surveillants" :key="s.id">
                <div class="surv-row">
                    <div>
                        <div style="font-weight:600;" x-text="s.user_name"></div>
                        <div style="color:#64748b;font-size:.7rem;" x-text="s.confirmed ? 'Confirmé' : 'En attente'"></div>
                    </div>
                    <span class="surv-role" x-text="s.role"></span>
                </div>
            </template>

            <template x-if="surveillants.length === 0">
                <div style="color:#94a3b8;font-size:.82rem;padding:1rem;text-align:center;">Aucun surveillant.</div>
            </template>

            @can('lmd.examens.manage')
            <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid #f1f5f9;">
                <select x-model="newUserId" style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:.5rem;font-size:.85rem;margin-bottom:.5rem;">
                    <option value="">— Choisir un surveillant —</option>
                    @foreach($surveillantsDispo as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
                <select x-model="newRole" style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:.5rem;font-size:.85rem;margin-bottom:.5rem;">
                    <option value="surveillant">Surveillant</option>
                    <option value="surveillant_principal">Surveillant principal</option>
                    <option value="secretaire">Secrétaire</option>
                    <option value="responsable_salle">Responsable salle</option>
                </select>
                <button type="button" class="btn btn-primary" @click="assignSurveillant()" :disabled="!newUserId || assigning" style="width:100%;justify-content:center;">
                    <i class="fas fa-plus"></i> <span x-text="assigning ? 'Ajout…' : 'Ajouter'"></span>
                </button>
            </div>
            @endcan
        </div>
    </div>
</div>

</div>

@php
    $survData = $examen->surveillants->map(function ($s) {
        return [
            'id' => $s->id,
            'user_id' => $s->user_id,
            'user_name' => $s->user?->name,
            'role' => $s->role,
            'confirmed' => (bool) $s->confirmed,
        ];
    })->values();
@endphp

@push('scripts')
<script>
function examenShow() {
    return {
        surveillants: @json($survData),
        newUserId: '',
        newRole: 'surveillant',
        locking: false,
        assigning: false,
        async assignSurveillant() {
            if (!this.newUserId) return;
            this.assigning = true;
            try {
                const res = await fetch('{{ route('esbtp.examens.surveillants.assign', $examen) }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, Accept: 'application/json' },
                    body: JSON.stringify({ user_ids: [parseInt(this.newUserId)], role: this.newRole })
                });
                if (!res.ok) throw new Error('Erreur ' + res.status);
                const data = await res.json();
                this.surveillants = data.surveillants;
                this.newUserId = '';
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: 'Surveillant ajouté.' } }));
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: e.message } }));
            } finally { this.assigning = false; }
        },
        async lockNotes() {
            if (!confirm('Verrouiller les notes ? Cette action est irréversible (anti-tampering).')) return;
            this.locking = true;
            try {
                const res = await fetch('{{ route('esbtp.examens.lock-notes', $examen) }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, Accept: 'application/json' },
                });
                if (!res.ok) throw new Error('Erreur ' + res.status);
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: 'Notes verrouillées.' } }));
                setTimeout(() => window.location.reload(), 800);
            } catch (e) {
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: e.message } }));
            } finally { this.locking = false; }
        }
    };
}
</script>
@endpush
@endsection
