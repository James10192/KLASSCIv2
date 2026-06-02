@extends('layouts.app')

@section('title', 'Édition des professeurs — KLASSCI')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/dashboard-moderne.css') }}">
<link rel="stylesheet" href="{{ asset('css/student-results.css') }}">
<style>
.subject-card {
    border: 1.5px solid #e5e7eb;
    border-radius: 14px;
    padding: 1.25rem;
    transition: all 0.2s ease;
    background: white;
}
.subject-card:hover {
    border-color: #0453cb;
    box-shadow: 0 4px 12px rgba(4,83,203,0.1);
}
.form-select-modern {
    border: 1.5px solid #e5e7eb;
    border-radius: 8px;
    padding: 0.5rem 0.75rem;
    font-size: 0.85rem;
    transition: border-color 0.2s;
    width: 100%;
}
.form-select-modern:focus { border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,0.1); outline: none; }
.form-control-modern {
    border: 1.5px solid #e5e7eb;
    border-radius: 8px;
    padding: 0.6rem 0.85rem;
    font-size: 0.9rem;
    font-weight: 600;
    transition: all 0.2s;
    width: 100%;
}
.form-control-modern:focus { border-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,0.1); outline: none; }
.form-control-modern.value-changed { animation: valFlash 0.8s ease; }
@keyframes valFlash {
    0% { background: #d1fae5; transform: scale(1.01); }
    100% { background: white; transform: scale(1); }
}
</style>
@endsection

@section('content')
<div class="dashboard-acasi">
    <div class="main-content">

        {{-- Hero --}}
        <div class="sr-hero sr-animate">
            <div class="sr-hero-content">
                <div class="sr-hero-left">
                    <div class="sr-hero-avatar"><i class="fas fa-chalkboard-teacher"></i></div>
                    <div class="sr-hero-info">
                        <h1>Édition des professeurs</h1>
                        <p>{{ $etudiant->nom }} {{ $etudiant->prenoms }} · {{ $classe->name ?? '' }} · {{ $periode }}</p>
                        <div class="sr-breadcrumb">
                            <a href="{{ route('esbtp.resultats.etudiant', ['etudiant' => $etudiant->id, 'classe_id' => $classe->id, 'periode' => $periode, 'annee_universitaire_id' => $anneeUniversitaire->id]) }}">Résultats</a>
                            <i class="fas fa-chevron-right"></i>
                            <span>Professeurs</span>
                        </div>
                    </div>
                </div>
                <div class="sr-hero-actions">
                    @php $otherPeriode = $periode === 'semestre1' ? 'semestre2' : 'semestre1'; @endphp
                    @if(in_array($periode, ['semestre1', 'semestre2'], true))
                    <button type="button"
                            id="copyProfsBtn"
                            class="sr-hero-btn"
                            style="background:rgba(255,255,255,.16); color:#fff; border:1px solid rgba(255,255,255,.32);"
                            title="Copier les professeurs déjà saisis sur l'autre semestre (souvent le même professeur enseigne aux 2 semestres)">
                        <i class="fas fa-copy"></i>Copier depuis {{ $otherPeriode === 'semestre1' ? 'Semestre 1' : 'Semestre 2' }}
                    </button>
                    @endif
                    <a href="{{ route('esbtp.resultats.etudiant', ['etudiant' => $etudiant->id, 'classe_id' => $classe->id, 'periode' => $periode, 'annee_universitaire_id' => $anneeUniversitaire->id]) }}" class="sr-hero-btn">
                        <i class="fas fa-arrow-left"></i>Retour
                    </a>
                </div>
            </div>
        </div>

        {{-- KPIs --}}
        <div class="sr-stats sr-animate sr-animate-delay-1" style="margin-bottom: 1.5rem;">
            <div class="sr-stat sr-stat--primary">
                <div class="sr-stat-icon"><i class="fas fa-school"></i></div>
                <div class="sr-stat-value" style="font-size: 1rem;">{{ $classe->name ?? 'N/A' }}</div>
                <div class="sr-stat-label">Classe</div>
            </div>
            <div class="sr-stat sr-stat--info">
                <div class="sr-stat-icon"><i class="fas fa-graduation-cap"></i></div>
                <div class="sr-stat-value">{{ $resultatsGeneraux->count() ?? 0 }}</div>
                <div class="sr-stat-label">Générales</div>
            </div>
            <div class="sr-stat sr-stat--success">
                <div class="sr-stat-icon"><i class="fas fa-tools"></i></div>
                <div class="sr-stat-value">{{ $resultatsTechniques->count() ?? 0 }}</div>
                <div class="sr-stat-label">Techniques</div>
            </div>
            <div class="sr-stat sr-stat--warning">
                <div class="sr-stat-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                <div class="sr-stat-value">{{ collect($professeurs)->filter()->count() }}</div>
                <div class="sr-stat-label">Assignés</div>
            </div>
        </div>

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle me-2"></i>
                @foreach($errors->all() as $error) {{ $error }} @endforeach
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        <form id="professeursForm" action="{{ route('esbtp.bulletins.save-professeurs') }}" method="POST">
            @csrf
            <input type="hidden" name="etudiant_id" value="{{ $etudiant->id }}">
            <input type="hidden" name="classe_id" value="{{ $classe->id }}">
            <input type="hidden" name="periode" value="{{ $periode }}">
            <input type="hidden" name="annee_universitaire_id" value="{{ $anneeUniversitaire->id }}">

            {{-- Enseignement général --}}
            @if(isset($resultatsGeneraux) && $resultatsGeneraux->count() > 0)
            <div class="sr-table-card sr-animate sr-animate-delay-2" style="margin-bottom: 1.5rem;">
                <div class="sr-table-header">
                    <div class="sr-table-header-left">
                        <i class="fas fa-graduation-cap"></i>
                        <h3>Enseignement général</h3>
                    </div>
                    <span class="sr-table-count">{{ $resultatsGeneraux->count() }} matières</span>
                </div>
                <div style="padding: 1.25rem; display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 1rem;">
                    @foreach($resultatsGeneraux as $resultat)
                        @php $enseignantsMatiere = $enseignantsParMatiere[$resultat->matiere_id] ?? collect(); @endphp
                        <div class="subject-card">
                            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid #f3f4f6;">
                                <div style="width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #dbeafe, #bfdbfe); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <i class="fas fa-book" style="color: #0453cb; font-size: 0.9rem;"></i>
                                </div>
                                <div>
                                    <div style="font-weight: 700; color: #1e293b; font-size: 0.95rem;">{{ $resultat->matiere->name ?? 'Matière #'.$resultat->matiere_id }}</div>
                                    <span style="font-size: 0.7rem; color: #6b7280;">{{ $resultat->matiere->code ?? '' }}</span>
                                </div>
                            </div>
                            @if($enseignantsMatiere->count() > 0)
                                <div style="margin-bottom: 0.75rem;">
                                    <label style="font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; margin-bottom: 0.3rem; display: block;">Sélection rapide</label>
                                    <select class="form-select-modern">
                                        <option value="">— Choisir un enseignant —</option>
                                        @foreach($enseignantsMatiere as $enseignant)
                                            <option value="{{ $enseignant->name }}">{{ $enseignant->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div>
                                <label style="font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #10b981; margin-bottom: 0.3rem; display: block;">
                                    <i class="fas fa-user-edit me-1"></i>Nom sur le bulletin
                                </label>
                                <input type="text" class="form-control-modern"
                                       id="professeur_{{ $resultat->matiere_id }}"
                                       name="professeurs[{{ $resultat->matiere_id }}]"
                                       value="{{ $professeurs[$resultat->matiere_id] ?? '' }}"
                                       placeholder="Nom qui apparaîtra sur le bulletin">
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Enseignement technique --}}
            @if(isset($resultatsTechniques) && $resultatsTechniques->count() > 0)
            <div class="sr-table-card sr-animate sr-animate-delay-3" style="margin-bottom: 1.5rem;">
                <div class="sr-table-header">
                    <div class="sr-table-header-left">
                        <i class="fas fa-tools"></i>
                        <h3>Enseignement technique</h3>
                    </div>
                    <span class="sr-table-count">{{ $resultatsTechniques->count() }} matières</span>
                </div>
                <div style="padding: 1.25rem; display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 1rem;">
                    @foreach($resultatsTechniques as $resultat)
                        @php $enseignantsMatiere = $enseignantsParMatiere[$resultat->matiere_id] ?? collect(); @endphp
                        <div class="subject-card">
                            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid #f3f4f6;">
                                <div style="width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #d1fae5, #a7f3d0); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <i class="fas fa-cog" style="color: #065f46; font-size: 0.9rem;"></i>
                                </div>
                                <div>
                                    <div style="font-weight: 700; color: #1e293b; font-size: 0.95rem;">{{ $resultat->matiere->name ?? 'Matière #'.$resultat->matiere_id }}</div>
                                    <span style="font-size: 0.7rem; color: #6b7280;">{{ $resultat->matiere->code ?? '' }}</span>
                                </div>
                            </div>
                            @if($enseignantsMatiere->count() > 0)
                                <div style="margin-bottom: 0.75rem;">
                                    <label style="font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; margin-bottom: 0.3rem; display: block;">Sélection rapide</label>
                                    <select class="form-select-modern">
                                        <option value="">— Choisir un enseignant —</option>
                                        @foreach($enseignantsMatiere as $enseignant)
                                            <option value="{{ $enseignant->name }}">{{ $enseignant->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div>
                                <label style="font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #10b981; margin-bottom: 0.3rem; display: block;">
                                    <i class="fas fa-user-edit me-1"></i>Nom sur le bulletin
                                </label>
                                <input type="text" class="form-control-modern"
                                       id="professeur_{{ $resultat->matiere_id }}"
                                       name="professeurs[{{ $resultat->matiere_id }}]"
                                       value="{{ $professeurs[$resultat->matiere_id] ?? '' }}"
                                       placeholder="Nom qui apparaîtra sur le bulletin">
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Aucune matière --}}
            @if((!isset($resultatsGeneraux) || $resultatsGeneraux->isEmpty()) && (!isset($resultatsTechniques) || $resultatsTechniques->isEmpty()))
            <div class="sr-empty" style="margin-bottom: 1.5rem;">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Aucune matière configurée</h3>
                <p>Veuillez d'abord configurer les matières pour cet étudiant.</p>
                <a href="{{ route('esbtp.bulletins.config-matieres') }}?classe_id={{ $classe->id }}&periode={{ $periode }}&annee_universitaire_id={{ $anneeUniversitaire->id }}&bulletin={{ $etudiant->id }}" class="sr-filter-btn" style="margin-top: 0.5rem;">
                    <i class="fas fa-cogs"></i>Configurer les matières
                </a>
            </div>
            @endif

            {{-- Toggle propagation --}}
            <div style="background: white; border: 1.5px solid #e5e7eb; border-radius: 14px; padding: 1.25rem; margin-bottom: 1.5rem;">
                <label class="sr-filter-toggle" style="margin: 0; cursor: pointer;">
                    <input type="checkbox" name="appliquer_a_classe" id="appliquerAClasse" value="1">
                    <span class="sr-toggle-track"></span>
                    <span style="font-weight: 700; color: #1e293b;">
                        Appliquer à <strong style="color: #0453cb;">tous les étudiants de {{ $classe->name }}</strong>
                    </span>
                </label>
                <div style="margin-left: 2.85rem; margin-top: 0.35rem; font-size: 0.8rem; color: #6b7280;">
                    <i class="fas fa-info-circle me-1"></i>
                    Copie les noms d'enseignants pour tous les bulletins de cette classe ({{ $periode }}).
                    Les bulletins manquants seront créés automatiquement.
                </div>
            </div>

            {{-- Actions --}}
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                <a href="{{ route('esbtp.resultats.etudiant', ['etudiant' => $etudiant->id, 'classe_id' => $classe->id, 'periode' => $periode, 'annee_universitaire_id' => $anneeUniversitaire->id]) }}"
                   class="sr-hero-btn" style="background: var(--sr-bg, #f8fafc); color: var(--sr-muted, #6b7280); border-color: var(--sr-border, #e5e7eb);">
                    <i class="fas fa-arrow-left"></i>Retour
                </a>
                <div style="display: flex; gap: 0.75rem;">
                    <button type="submit" name="action" value="save_and_return" class="sr-hero-btn" style="background: linear-gradient(135deg, #10b981, #059669); border-color: #10b981; color: white;">
                        <i class="fas fa-save"></i>Enregistrer
                    </button>
                    <button type="submit" name="action" value="generate" class="sr-hero-btn sr-hero-btn--solid" style="background: var(--sr-primary-gradient, linear-gradient(135deg, #0453cb, #5e91de)); color: white; border-color: #0453cb;">
                        <i class="fas fa-file-pdf"></i>Enregistrer + bulletin
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function animateValueChange(el) {
    el.classList.add('value-changed');
    setTimeout(function() { el.classList.remove('value-changed'); }, 800);
}

document.addEventListener('DOMContentLoaded', function() {
    // Quick-select → populate text input
    document.querySelectorAll('.form-select-modern').forEach(function(select) {
        select.addEventListener('change', function() {
            if (!this.value) return;
            var card = this.closest('.subject-card');
            if (!card) return;
            var input = card.querySelector('.form-control-modern');
            if (input) {
                input.value = this.value;
                input.setAttribute('value', this.value);
                input.dispatchEvent(new Event('input', { bubbles: true }));
                animateValueChange(input);
                this.value = '';
            }
        });
    });

    // ═══ Copier les professeurs depuis l'autre semestre ═══
    var copyBtn = document.getElementById('copyProfsBtn');
    if (copyBtn) {
        copyBtn.addEventListener('click', async function (ev) {
            ev.preventDefault();
            if (copyBtn.disabled) return;
            const original = copyBtn.innerHTML;
            copyBtn.disabled = true;
            copyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>Chargement…';
            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const res = await fetch('{{ route('esbtp.bulletins.copy-professeurs-from-other-semestre') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        etudiant_id: {{ (int) $etudiant->id }},
                        classe_id: {{ (int) $classe->id }},
                        periode: @json($periode),
                        annee_universitaire_id: {{ (int) $anneeUniversitaire->id }},
                    }),
                });
                const data = await res.json();
                if (!res.ok || !data.success) {
                    if (typeof window.klassciToast === 'function') {
                        window.klassciToast('error', data?.message || 'Erreur lors de la copie.');
                    } else {
                        alert(data?.message || 'Erreur lors de la copie.');
                    }
                    return;
                }
                // Hydrate les inputs avec les profs source. Confirm avant écrasement si valeurs existantes.
                let filledCount = 0, overwrittenCount = 0;
                Object.entries(data.professeurs || {}).forEach(([matiereId, profNom]) => {
                    const input = document.getElementById('professeur_' + matiereId);
                    if (!input) return;
                    const had = (input.value || '').trim() !== '';
                    if (had) overwrittenCount++;
                    input.value = profNom;
                    input.setAttribute('value', profNom);
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    if (typeof animateValueChange === 'function') animateValueChange(input);
                    filledCount++;
                });
                const summary = `<strong>${filledCount}</strong> professeur(s) copié(s) depuis ${data.source_periode_label}` + (overwrittenCount > 0 ? `<br><em>${overwrittenCount} valeur(s) précédente(s) écrasée(s)</em>` : '') + '<br>Cliquez sur <strong>Enregistrer</strong> pour confirmer.';
                if (typeof window.klassciToast === 'function') {
                    window.klassciToast('success', summary, 7000);
                } else {
                    alert(summary.replace(/<[^>]+>/g, ' '));
                }
            } catch (err) {
                console.error(err);
                if (typeof window.klassciToast === 'function') {
                    window.klassciToast('error', err.message || 'Erreur réseau.');
                } else {
                    alert('Erreur : ' + (err.message || 'réseau'));
                }
            } finally {
                copyBtn.disabled = false;
                copyBtn.innerHTML = original;
            }
        });
    }
});
</script>
@include('partials._klassci_toast')
@endsection
