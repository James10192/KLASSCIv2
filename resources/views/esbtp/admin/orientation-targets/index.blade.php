@extends('layouts.app')

@section('title', 'Sorties BTS Tronc Commun')

@push('styles')
<style>
[x-cloak] { display: none !important; }

.ot-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 1.75rem 2.25rem 1.5rem;
    color: #fff;
    margin-bottom: 1.25rem;
    box-shadow: 0 8px 30px rgba(4,83,203,.18);
}
.ot-hero-top { display:flex; align-items:flex-start; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
.ot-hero-left { display:flex; align-items:flex-start; gap:1rem; flex:1; }
.ot-hero-icon { width:50px; height:50px; border-radius:13px; background:rgba(255,255,255,.15); backdrop-filter:blur(8px);
    border:1px solid rgba(255,255,255,.20); display:flex; align-items:center; justify-content:center; font-size:1.25rem; color:#fff; flex-shrink:0; }
.ot-hero h1 { font-size:1.4rem; font-weight:700; color:#fff; margin:0 0 .3rem; }
.ot-hero p { color:rgba(255,255,255,.80); font-size:.88rem; margin:0; }

.ot-empty {
    background: #fff; border:1px dashed #cbd5e1; border-radius:14px;
    padding: 3rem 2rem; text-align:center; color:#64748b;
}
.ot-empty i { font-size:2.5rem; color:#cbd5e1; display:block; margin-bottom:.85rem; }
.ot-empty h3 { font-size:1.05rem; color:#1e293b; margin:0 0 .35rem; }
.ot-empty p { font-size:.85rem; margin:0; }

.ot-card {
    background:#fff; border:1px solid #e2e8f0; border-radius:14px;
    margin-bottom:1rem;
    box-shadow:0 1px 3px rgba(15,23,42,.04);
    position:relative; z-index:1;
}
.ot-card:focus-within { z-index:10; }
.ot-card-header {
    padding: 1rem 1.25rem .85rem;
    border-bottom:1px solid #f1f5f9;
    display:flex; align-items:center; gap:.65rem;
}
.ot-card-icon {
    width:38px; height:38px; border-radius:10px;
    background:linear-gradient(135deg,#0453cb,#3b7ddb);
    color:#fff; display:flex; align-items:center; justify-content:center; font-size:.95rem;
    box-shadow:0 2px 6px rgba(4,83,203,.22); flex-shrink:0;
}
.ot-card-title { font-size:.95rem; font-weight:700; color:#0f172a; }
.ot-card-subtitle { font-size:.72rem; color:#64748b; font-weight:500; }
.ot-card-meta {
    margin-left:auto; font-size:.7rem; color:#64748b; font-weight:600;
    padding:.15rem .55rem; background:#f1f5f9; border-radius:6px;
}
.ot-card-body { padding: 1rem 1.25rem; }

.ot-target-row {
    display:flex; align-items:center; gap:.7rem;
    padding:.65rem .85rem;
    background:#f8fafc; border:1px solid #e2e8f0;
    border-radius:10px; margin-bottom:.45rem;
    transition: background .15s, border-color .15s;
}
.ot-target-row:hover { background:#eff6ff; border-color:rgba(4,83,203,.30); }
.ot-target-icon {
    width:32px; height:32px; border-radius:9px;
    background:linear-gradient(135deg,#0453cb,#3b7ddb);
    color:#fff; display:inline-flex; align-items:center; justify-content:center;
    font-size:.8rem; flex-shrink:0;
}
.ot-target-name { font-weight:600; color:#0f172a; font-size:.88rem; }
.ot-target-meta { font-size:.72rem; color:#64748b; }
.ot-target-actions { display:flex; gap:.3rem; flex-shrink:0; }
.ot-target-semestre {
    background:rgba(4,83,203,.10); color:#0453cb; border:1px solid rgba(4,83,203,.25);
    padding:.18rem .5rem; border-radius:5px; font-size:.7rem; font-weight:700;
    font-family:'SFMono-Regular',Consolas,monospace;
}
.ot-target-toggle {
    appearance:none; cursor:pointer;
    width:34px; height:18px; border-radius:9px;
    background:#cbd5e1; position:relative; transition: background .15s;
}
.ot-target-toggle::after {
    content:''; position:absolute; top:2px; left:2px;
    width:14px; height:14px; border-radius:50%; background:#fff;
    transition: left .15s;
}
.ot-target-toggle:checked { background:#10b981; }
.ot-target-toggle:checked::after { left:18px; }

.ot-add-form {
    margin-top:.5rem; padding:.85rem 1rem;
    background:linear-gradient(180deg, transparent, rgba(4,83,203,.04));
    border:1px dashed #cbd5e1; border-radius:10px;
    display:flex; gap:.5rem; align-items:end; flex-wrap:wrap;
}
.ot-add-form select, .ot-add-form input {
    border:1px solid #e2e8f0; border-radius:8px;
    padding:.45rem .65rem; font-size:.85rem; background:#fff;
}
.ot-add-form select { flex:1; min-width:200px; }
.ot-add-form input[type="number"] { width:90px; }
.ot-btn {
    padding:.45rem .85rem; border-radius:8px;
    font-size:.78rem; font-weight:600; cursor:pointer;
    display:inline-flex; align-items:center; gap:.35rem;
    border:1px solid; text-decoration:none; transition: all .15s ease;
}
.ot-btn--primary { background:linear-gradient(135deg,#0453cb,#3b7ddb); color:#fff; border-color:transparent; }
.ot-btn--primary:hover { background:linear-gradient(135deg,#033a8e,#0453cb); color:#fff; transform:translateY(-1px); }
.ot-btn--ghost { background:transparent; color:#64748b; border-color:#e2e8f0; }
.ot-btn--ghost:hover { background:#f1f5f9; color:#0453cb; }
.ot-btn--danger { background:transparent; color:#dc2626; border-color:rgba(220,38,38,.3); }
.ot-btn--danger:hover { background:rgba(220,38,38,.08); }
.ot-btn:disabled { opacity:.55; cursor:wait; }

.ot-toasts { position:fixed; bottom:1.5rem; right:1.5rem; z-index:1100;
    display:flex; flex-direction:column; gap:.5rem; max-width:400px; }
.ot-toast { display:flex; gap:.6rem; padding:.7rem 1rem; border-radius:10px;
    background:#fff; border:1px solid #e2e8f0; box-shadow:0 8px 24px rgba(15,23,42,.12); font-size:.85rem; }
.ot-toast--success { border-left:4px solid #10b981; color:#065f46; }
.ot-toast--error { border-left:4px solid #dc2626; color:#991b1b; }
</style>
@endpush

@section('content')
<div x-data="orientationTargets()" x-init="init()">

    {{-- HERO --}}
    <div class="ot-hero">
        <div class="ot-hero-top">
            <div class="ot-hero-left">
                <div class="ot-hero-icon"><i class="fas fa-route"></i></div>
                <div>
                    <h1>Sorties BTS Tronc Commun</h1>
                    <p>Configurez les spécialités possibles pour chaque classe de tronc commun. Sans configuration, le bouton « Orienter » sur fiche étudiant affiche un message d'erreur.</p>
                </div>
            </div>
            <div style="display:flex; gap:.45rem;">
                <a href="{{ route('esbtp.inscriptions.index') }}" class="ot-btn ot-btn--ghost" style="color:#fff; border-color:rgba(255,255,255,.25); background:rgba(255,255,255,.15);">
                    <i class="fas fa-arrow-left"></i> Inscriptions
                </a>
            </div>
        </div>
    </div>

    @if($sourceClasses->isEmpty())
        <div class="ot-empty">
            <i class="fas fa-route"></i>
            <h3>Aucune classe Tronc Commun configurée</h3>
            <p>Marquez d'abord une filière comme « tronc commun » dans <em>Filières & Classes</em>, puis revenez ici configurer ses sorties.</p>
        </div>
    @else
        @foreach($sourceClasses as $sourceClasse)
            @php
                $candidates = $candidatesByClasse[$sourceClasse->id] ?? collect();
            @endphp
            <div class="ot-card">
                <div class="ot-card-header">
                    <div class="ot-card-icon"><i class="fas fa-chalkboard"></i></div>
                    <div style="flex:1; min-width:0;">
                        <div class="ot-card-title">{{ $sourceClasse->name }}</div>
                        <div class="ot-card-subtitle">
                            {{ $sourceClasse->filiere?->name ?? '—' }}
                            @if($sourceClasse->niveauEtude) · {{ $sourceClasse->niveauEtude->name }} @endif
                            @if($sourceClasse->anneeUniversitaire) · {{ $sourceClasse->anneeUniversitaire->name }} @endif
                        </div>
                    </div>
                    <span class="ot-card-meta">
                        {{ $sourceClasse->orientationTargets->count() }} spécialité{{ $sourceClasse->orientationTargets->count() > 1 ? 's' : '' }}
                    </span>
                </div>
                <div class="ot-card-body">
                    @forelse($sourceClasse->orientationTargets->sortBy('sort_order') as $target)
                        <div class="ot-target-row">
                            <span class="ot-target-icon"><i class="fas fa-graduation-cap"></i></span>
                            <div style="flex:1; min-width:0;">
                                <div class="ot-target-name">{{ $target->targetClasse?->name ?? '— classe supprimée —' }}</div>
                                <div class="ot-target-meta">
                                    {{ $target->targetClasse?->filiere?->name ?? '—' }}
                                    @if($target->notes) · <em>{{ $target->notes }}</em> @endif
                                </div>
                            </div>
                            <span class="ot-target-semestre" title="Semestre d'activation de la spécialisation">
                                S{{ $target->semestre_activation }}
                            </span>
                            <input type="checkbox" class="ot-target-toggle"
                                {{ $target->is_active ? 'checked' : '' }}
                                @change="toggleActive({{ $target->id }}, $event.target.checked)"
                                title="Activer/désactiver cette sortie">
                            <button class="ot-btn ot-btn--danger" @click="deleteTarget({{ $target->id }})" title="Supprimer cette sortie">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    @empty
                        <div style="padding:1rem; text-align:center; color:#94a3b8; font-size:.85rem;">
                            <i class="fas fa-arrow-down"></i> Aucune sortie configurée — ajoutez-en une ci-dessous.
                        </div>
                    @endforelse

                    @if($candidates->isNotEmpty())
                        <form @submit.prevent="addTarget($el)" class="ot-add-form">
                            @csrf
                            <input type="hidden" name="source_classe_id" value="{{ $sourceClasse->id }}">
                            <select name="target_classe_id" required>
                                <option value="">+ Ajouter une spécialité possible</option>
                                @foreach($candidates as $cls)
                                    <option value="{{ $cls->id }}">
                                        {{ $cls->name }}@if($cls->filiere) · {{ $cls->filiere->name }} @endif
                                    </option>
                                @endforeach
                            </select>
                            <input type="number" name="semestre_activation" min="1" max="8" value="2" title="Semestre d'activation" placeholder="S2">
                            <input type="text" name="notes" maxlength="500" placeholder="Note (optionnel)" style="flex:1; min-width:120px;">
                            <button type="submit" class="ot-btn ot-btn--primary">
                                <i class="fas fa-plus"></i> Ajouter
                            </button>
                        </form>
                    @else
                        <div style="margin-top:.5rem; padding:.65rem; background:rgba(245,158,11,.08); border:1px solid rgba(245,158,11,.25); border-radius:9px; font-size:.78rem; color:#92400e;">
                            <i class="fas fa-circle-exclamation"></i>
                            Aucune classe de spécialité candidate pour cette classe TC (même niveau + même année). Créez d'abord les classes spé.
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    @endif

    <div class="ot-toasts">
        <template x-for="t in toasts" :key="t.id">
            <div class="ot-toast" :class="'ot-toast--' + t.type">
                <i class="fas" :class="t.type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'"></i>
                <span x-text="t.message"></span>
            </div>
        </template>
    </div>
</div>

@push('scripts')
<script>
function orientationTargets() {
    return {
        toasts: [], toastId: 0,
        init() {},

        async addTarget(formEl) {
            const fd = new FormData(formEl);
            const payload = {};
            fd.forEach((v, k) => { if (k !== '_token' && v !== '') payload[k] = v; });

            try {
                const res = await fetch('{{ route("esbtp.admin.orientation-targets.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        Accept: 'application/json',
                    },
                    body: JSON.stringify(payload),
                });
                if (res.status === 422) {
                    const body = await res.json();
                    const errors = Object.values(body.errors || {}).flat();
                    this.toast('error', errors.join(' · '));
                    return;
                }
                if (!res.ok) throw new Error('Erreur ' + res.status);
                this.toast('success', 'Spécialité ajoutée');
                setTimeout(() => window.location.reload(), 700);
            } catch (e) {
                this.toast('error', e.message);
            }
        },

        async toggleActive(id, isActive) {
            try {
                const res = await fetch(`/esbtp/admin/orientation-targets/${id}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({ is_active: isActive ? 1 : 0 }),
                });
                if (!res.ok) throw new Error('Erreur ' + res.status);
                this.toast('success', isActive ? 'Sortie activée' : 'Sortie désactivée');
            } catch (e) {
                this.toast('error', e.message);
            }
        },

        async deleteTarget(id) {
            if (! confirm('Supprimer définitivement cette sortie de tronc commun ?')) return;
            try {
                const res = await fetch(`/esbtp/admin/orientation-targets/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        Accept: 'application/json',
                    },
                });
                if (!res.ok) throw new Error('Erreur ' + res.status);
                this.toast('success', 'Sortie supprimée');
                setTimeout(() => window.location.reload(), 700);
            } catch (e) {
                this.toast('error', e.message);
            }
        },

        toast(type, message) {
            const id = ++this.toastId;
            this.toasts.push({ id, type, message });
            setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id); }, 4000);
        },
    };
}
</script>
@endpush
@endsection
