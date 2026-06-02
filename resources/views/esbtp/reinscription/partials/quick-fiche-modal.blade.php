{{-- ============================================================
     Modal "Mettre à jour la fiche étudiant ?" (RQF = Réinscription Quick Fiche)
     Affiché avant validation finale de la réinscription si l'utilisateur
     a la permission students.edit.
     Namespace CSS : rqf-*
============================================================ --}}
@php
    $rqfParents = $etudiant->relationLoaded('parents') ? $etudiant->parents : $etudiant->parents()->get();
    $rqfParent0 = $rqfParents[0] ?? null;
    $rqfParent1 = $rqfParents[1] ?? null;

    $rqfMaritalOptions = [
        'celibataire' => 'Célibataire',
        'marie'       => 'Marié(e)',
        'divorce'     => 'Divorcé(e)',
        'veuf'        => 'Veuf(ve)',
        'union_libre' => 'Union libre',
    ];
    $rqfBloodOptions = [
        'A+' => 'A+', 'A-' => 'A-',
        'B+' => 'B+', 'B-' => 'B-',
        'AB+' => 'AB+', 'AB-' => 'AB-',
        'O+' => 'O+', 'O-' => 'O-',
    ];
    $rqfRelationOptions = [
        'Père'   => 'Père',
        'Mère'   => 'Mère',
        'Tuteur' => 'Tuteur légal',
        'Frère'  => 'Frère',
        'Sœur'   => 'Sœur',
        'Oncle'  => 'Oncle',
        'Tante'  => 'Tante',
        'Conjoint' => 'Conjoint(e)',
        'Ami'    => 'Ami(e)',
        'Autre'  => 'Autre',
    ];
    $rqfPieceOptions = [
        'CNI'              => 'CNI',
        'Passeport'        => 'Passeport',
        'Permis'           => 'Permis de conduire',
        'Carte_consulaire' => 'Carte consulaire',
        'Autre'            => 'Autre',
    ];
    $rqfSexeOptions = ['M' => 'Masculin', 'F' => 'Féminin'];
@endphp

<div class="modal fade rqf-modal" id="rqfModal" tabindex="-1" aria-labelledby="rqfModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content rqf-content">
            {{-- Header gradient KLASSCI --}}
            <div class="rqf-header">
                <div class="rqf-header-left">
                    <div class="rqf-header-icon"><i class="fas fa-id-card"></i></div>
                    <div>
                        <h5 class="rqf-title" id="rqfModalLabel">Mettre à jour la fiche étudiant ?</h5>
                        <p class="rqf-subtitle">Vérifiez et complétez les informations de
                            <strong>{{ $etudiant->nom_complet }}</strong>
                            avant de valider la réinscription. Vous pouvez tout laisser tel quel et continuer.
                        </p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            {{-- Body : 3 sections accordéons --}}
            <div class="rqf-body">
                <div id="rqfErrors" class="rqf-errors" style="display:none;"></div>

                {{-- ── SECTION A : Coordonnées étudiant ───────────────────── --}}
                <details class="rqf-section" open>
                    <summary class="rqf-section-summary">
                        <span class="rqf-section-icon"><i class="fas fa-user-circle"></i></span>
                        <span class="rqf-section-title">Coordonnées et infos personnelles</span>
                        <i class="fas fa-chevron-down rqf-chevron"></i>
                    </summary>
                    <div class="rqf-section-body">
                        <div class="rqf-grid">
                            <div class="rqf-field">
                                <label class="rqf-label">Téléphone <span class="rqf-required">*</span></label>
                                <input type="tel" name="telephone" class="rqf-input" required
                                       value="{{ $etudiant->telephone }}" placeholder="+225 XX XX XX XX XX">
                            </div>
                            <div class="rqf-field">
                                <label class="rqf-label">Email personnel</label>
                                <input type="email" name="email_personnel" class="rqf-input"
                                       value="{{ $etudiant->email_personnel }}" placeholder="exemple@email.com">
                            </div>
                            <div class="rqf-field">
                                <label class="rqf-label">Ville de résidence <span class="rqf-required">*</span></label>
                                <input type="text" name="ville" class="rqf-input" required
                                       value="{{ $etudiant->ville }}" placeholder="Ex : Abidjan">
                            </div>
                            <div class="rqf-field">
                                <label class="rqf-label">Commune <span class="rqf-required">*</span></label>
                                <input type="text" name="commune" class="rqf-input" required
                                       value="{{ $etudiant->commune }}" placeholder="Ex : Cocody">
                            </div>
                            <div class="rqf-field rqf-field--wide">
                                <label class="rqf-label">Adresse complète</label>
                                <input type="text" name="adresse" class="rqf-input"
                                       value="{{ $etudiant->adresse }}" placeholder="Quartier, rue, repère…">
                            </div>
                            <div class="rqf-field">
                                <label class="rqf-label">Situation matrimoniale</label>
                                <x-au-select name="situation_matrimoniale"
                                             :value="$etudiant->situation_matrimoniale"
                                             icon="fa-heart"
                                             placeholder="— Non renseignée —"
                                             :options="$rqfMaritalOptions" />
                            </div>
                            <div class="rqf-field">
                                <label class="rqf-label">Nombre d'enfants</label>
                                <input type="number" name="nombre_enfants" class="rqf-input" min="0" max="50"
                                       value="{{ $etudiant->nombre_enfants ?? 0 }}">
                            </div>
                            <div class="rqf-field">
                                <label class="rqf-label">Groupe sanguin</label>
                                <x-au-select name="groupe_sanguin"
                                             :value="$etudiant->groupe_sanguin"
                                             icon="fa-tint"
                                             placeholder="— Non renseigné —"
                                             :options="$rqfBloodOptions" />
                            </div>
                        </div>

                        <div class="rqf-subsection-title">
                            <i class="fas fa-phone-volume"></i>Contact d'urgence
                        </div>
                        <div class="rqf-grid">
                            <div class="rqf-field">
                                <label class="rqf-label">Nom de la personne</label>
                                <input type="text" name="urgence_contact_nom" class="rqf-input"
                                       value="{{ $etudiant->urgence_contact_nom }}" placeholder="Prénom Nom">
                            </div>
                            <div class="rqf-field">
                                <label class="rqf-label">Téléphone</label>
                                <input type="tel" name="urgence_contact_telephone" class="rqf-input"
                                       value="{{ $etudiant->urgence_contact_telephone }}" placeholder="+225 XX XX XX XX XX">
                            </div>
                            <div class="rqf-field">
                                <label class="rqf-label">Relation avec l'étudiant</label>
                                <x-au-select name="urgence_contact_relation"
                                             :value="$etudiant->urgence_contact_relation"
                                             icon="fa-link"
                                             placeholder="— Non renseignée —"
                                             :options="$rqfRelationOptions" />
                            </div>
                        </div>
                    </div>
                </details>

                {{-- ── SECTIONS B et C : Parents / Tuteurs ─────────────────── --}}
                @foreach([0 => $rqfParent0, 1 => $rqfParent1] as $idx => $parent)
                    <details class="rqf-section" {{ $parent ? 'open' : '' }}>
                        <summary class="rqf-section-summary">
                            <span class="rqf-section-icon"><i class="fas fa-user-friends"></i></span>
                            <span class="rqf-section-title">
                                Parent / Tuteur #{{ $idx + 1 }}
                                <span class="rqf-section-hint">{{ $parent ? 'modifier les infos' : 'optionnel — laisser vide pour ignorer' }}</span>
                            </span>
                            <i class="fas fa-chevron-down rqf-chevron"></i>
                        </summary>
                        <div class="rqf-section-body">
                            <input type="hidden" name="parents[{{ $idx }}][parent_id]" value="{{ $parent?->id }}">

                            <div class="rqf-grid">
                                <div class="rqf-field">
                                    <label class="rqf-label">Nom <span class="rqf-conditional" data-rqf-required-when="parents[{{ $idx }}][prenoms],parents[{{ $idx }}][telephone]">*</span></label>
                                    <input type="text" name="parents[{{ $idx }}][nom]" class="rqf-input"
                                           value="{{ $parent?->nom }}" placeholder="Nom">
                                </div>
                                <div class="rqf-field">
                                    <label class="rqf-label">Prénoms <span class="rqf-conditional" data-rqf-required-when="parents[{{ $idx }}][nom]">*</span></label>
                                    <input type="text" name="parents[{{ $idx }}][prenoms]" class="rqf-input"
                                           value="{{ $parent?->prenoms }}" placeholder="Prénom(s)">
                                </div>
                                <div class="rqf-field">
                                    <label class="rqf-label">Sexe</label>
                                    <x-au-select :name="'parents[' . $idx . '][sexe]'"
                                                 :value="$parent?->sexe"
                                                 icon="fa-venus-mars"
                                                 placeholder="— Non renseigné —"
                                                 :options="$rqfSexeOptions" />
                                </div>
                                <div class="rqf-field">
                                    <label class="rqf-label">Téléphone <span class="rqf-conditional" data-rqf-required-when="parents[{{ $idx }}][nom]">*</span></label>
                                    <input type="tel" name="parents[{{ $idx }}][telephone]" class="rqf-input"
                                           value="{{ $parent?->telephone }}" placeholder="+225 XX XX XX XX XX">
                                </div>
                                <div class="rqf-field">
                                    <label class="rqf-label">Téléphone secondaire</label>
                                    <input type="tel" name="parents[{{ $idx }}][telephone_secondaire]" class="rqf-input"
                                           value="{{ $parent?->telephone_secondaire }}" placeholder="+225 XX XX XX XX XX">
                                </div>
                                <div class="rqf-field">
                                    <label class="rqf-label">Email</label>
                                    <input type="email" name="parents[{{ $idx }}][email]" class="rqf-input"
                                           value="{{ $parent?->email }}" placeholder="exemple@email.com">
                                </div>
                                <div class="rqf-field">
                                    <label class="rqf-label">Profession</label>
                                    <input type="text" name="parents[{{ $idx }}][profession]" class="rqf-input"
                                           value="{{ $parent?->profession }}" placeholder="Ex : Ingénieur">
                                </div>
                                <div class="rqf-field">
                                    <label class="rqf-label">Relation avec l'étudiant <span class="rqf-conditional" data-rqf-required-when="parents[{{ $idx }}][nom]">*</span></label>
                                    <x-au-select :name="'parents[' . $idx . '][relation]'"
                                                 :value="$parent?->pivot?->relation"
                                                 icon="fa-link"
                                                 placeholder="— Choisir —"
                                                 :options="$rqfRelationOptions" />
                                </div>
                                <div class="rqf-field rqf-field--wide">
                                    <label class="rqf-label">Adresse</label>
                                    <input type="text" name="parents[{{ $idx }}][adresse]" class="rqf-input"
                                           value="{{ $parent?->adresse }}" placeholder="Quartier, rue, repère…">
                                </div>
                                <div class="rqf-field">
                                    <label class="rqf-label">Type de pièce d'identité</label>
                                    <x-au-select :name="'parents[' . $idx . '][type_piece_identite]'"
                                                 :value="$parent?->type_piece_identite"
                                                 icon="fa-id-badge"
                                                 placeholder="— Non renseigné —"
                                                 :options="$rqfPieceOptions" />
                                </div>
                                <div class="rqf-field">
                                    <label class="rqf-label">Numéro de pièce</label>
                                    <input type="text" name="parents[{{ $idx }}][numero_piece_identite]" class="rqf-input"
                                           value="{{ $parent?->numero_piece_identite }}" placeholder="Numéro">
                                </div>
                                <div class="rqf-field rqf-field--checkbox">
                                    <label class="rqf-checkbox">
                                        <input type="checkbox" name="parents[{{ $idx }}][is_tuteur]" value="1"
                                               {{ $parent?->pivot?->is_tuteur ? 'checked' : '' }}>
                                        <span>Tuteur principal</span>
                                    </label>
                                </div>
                            </div>

                            <button type="button" class="rqf-btn rqf-btn--ghost" data-rqf-clear="parents[{{ $idx }}]">
                                <i class="fas fa-eraser"></i>Vider cette section (ne pas lier ce parent)
                            </button>
                        </div>
                    </details>
                @endforeach

                {{-- ── SECTION D : Profil d'accessibilité (optionnel) ──────── --}}
                @can('students.accessibility.edit')
                <div class="rqf-accessibility-wrapper">
                    @include('esbtp.inscriptions.partials.accessibility-section', [
                        'accessibilityProfile' => $etudiant->accessibilityProfile ?? null,
                    ])
                </div>
                @endcan
            </div>

            {{-- Footer --}}
            <div class="rqf-footer">
                <button type="button" class="rqf-btn rqf-btn--secondary" id="rqfBtnSkip">
                    <i class="fas fa-forward"></i>Continuer sans modifier
                </button>
                <button type="button" class="rqf-btn rqf-btn--primary" id="rqfBtnUpdate">
                    <i class="fas fa-save"></i><span>Mettre à jour et continuer</span>
                </button>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
.rqf-modal .modal-content { border: 0; border-radius: 18px; overflow: hidden; box-shadow: 0 24px 60px rgba(15,23,42,.18); }
.rqf-content { background: #f8fafc; }

.rqf-header {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    color: #fff; padding: 1.4rem 1.75rem;
    display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem;
}
.rqf-header-left { display: flex; align-items: flex-start; gap: 1rem; flex: 1; }
.rqf-header-icon {
    width: 52px; height: 52px; border-radius: 14px;
    background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.22);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem; flex-shrink: 0;
}
.rqf-title { font-size: 1.18rem; font-weight: 700; color: #fff; margin: 0 0 .25rem; }
.rqf-subtitle { font-size: .82rem; color: rgba(255,255,255,.85); margin: 0; line-height: 1.45; }
.rqf-subtitle strong { color: #fff; font-weight: 700; }

.rqf-body { padding: 1.5rem 1.75rem; max-height: 70vh; overflow-y: auto; }
.rqf-errors {
    background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b;
    padding: .8rem 1rem; border-radius: 10px; font-size: .85rem; margin-bottom: 1rem;
}
.rqf-errors ul { margin: 0; padding-left: 1.25rem; }
.rqf-errors--warning { background: #fef3c7; border-color: #fde68a; color: #78350f; }

.rqf-section {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 12px;
    margin-bottom: 1rem;
    box-shadow: 0 1px 3px rgba(15,23,42,.04);
}
.rqf-section-summary {
    display: flex; align-items: center; gap: .75rem;
    padding: 1rem 1.25rem; cursor: pointer; user-select: none;
    list-style: none;
}
.rqf-section-summary::-webkit-details-marker { display: none; }
.rqf-section-icon {
    width: 36px; height: 36px; border-radius: 10px;
    background: linear-gradient(135deg,#0453cb,#3b7ddb); color: #fff;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: .9rem; flex-shrink: 0;
}
.rqf-section-title { flex: 1; font-weight: 700; font-size: .95rem; color: #0f172a; }
.rqf-section-hint {
    display: block; font-weight: 500; font-size: .72rem; color: #64748b;
    margin-top: .15rem;
}
.rqf-chevron { color: #64748b; font-size: .8rem; transition: transform .2s ease; }
.rqf-section[open] .rqf-chevron { transform: rotate(180deg); }
.rqf-section-body { padding: 0 1.25rem 1.25rem; border-top: 1px solid #f1f5f9; }

.rqf-grid {
    display: grid; gap: 1rem;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    margin-top: 1rem;
}
@media (max-width: 992px) { .rqf-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 600px) { .rqf-grid { grid-template-columns: 1fr; } }
.rqf-field { display: flex; flex-direction: column; min-width: 0; }
.rqf-field--wide { grid-column: span 2; }
.rqf-field--checkbox { justify-content: center; }

.rqf-label {
    font-size: .72rem; font-weight: 600; color: #64748b;
    text-transform: uppercase; letter-spacing: .04em;
    margin-bottom: .35rem;
}
.rqf-required { color: #dc2626; }
.rqf-conditional { color: #f59e0b; }

.rqf-input {
    background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
    padding: .55rem .85rem; font-size: .88rem; color: #1e293b;
    transition: border-color .15s, box-shadow .15s;
    height: 38px;
}
.rqf-input:focus { border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.12); outline: 0; }
.rqf-input:invalid:not(:placeholder-shown) { border-color: #dc2626; }

.rqf-checkbox {
    display: inline-flex; align-items: center; gap: .5rem;
    background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;
    padding: .55rem .9rem; font-size: .85rem; color: #1e293b;
    cursor: pointer; user-select: none;
}
.rqf-checkbox input { margin: 0; accent-color: #0453cb; }

.rqf-subsection-title {
    display: flex; align-items: center; gap: .5rem;
    font-size: .78rem; font-weight: 700; color: #0f172a;
    text-transform: uppercase; letter-spacing: .04em;
    margin-top: 1.5rem;
    padding-top: 1rem; border-top: 1px dashed #e2e8f0;
}
.rqf-subsection-title i { color: #0453cb; }

.rqf-footer {
    background: #fff; border-top: 1px solid #e2e8f0;
    padding: 1rem 1.75rem;
    display: flex; justify-content: flex-end; gap: .65rem; flex-wrap: wrap;
}
.rqf-btn {
    border: 0; border-radius: 10px; padding: .6rem 1.1rem;
    font-size: .85rem; font-weight: 600; cursor: pointer;
    display: inline-flex; align-items: center; gap: .45rem;
    transition: all .2s ease;
}
.rqf-btn--secondary { background: #fff; color: #475569; border: 1px solid #cbd5e1; }
.rqf-btn--secondary:hover { background: #f1f5f9; color: #0f172a; border-color: #94a3b8; }
.rqf-btn--primary { background: linear-gradient(135deg,#0453cb,#3b7ddb); color: #fff; }
.rqf-btn--primary:hover { box-shadow: 0 8px 24px rgba(4,83,203,.28); transform: translateY(-1px); }
.rqf-btn--primary:disabled { opacity: .65; cursor: wait; transform: none; }
.rqf-btn--ghost {
    background: transparent; color: #94a3b8; border: 1px dashed #cbd5e1;
    margin-top: 1rem; font-size: .78rem;
}
.rqf-btn--ghost:hover { color: #dc2626; border-color: #dc2626; }

.rqf-accessibility-wrapper { margin-bottom: 1rem; }
.rqf-accessibility-wrapper .ia-section { margin-bottom: 0; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const trigger = document.querySelector('[data-rqf-trigger="1"]');
    const modalEl = document.getElementById('rqfModal');
    if (!trigger || !modalEl) return;

    const modal = new bootstrap.Modal(modalEl);
    const mainForm = trigger.closest('form');
    const btnSkip = document.getElementById('rqfBtnSkip');
    const btnUpdate = document.getElementById('rqfBtnUpdate');
    const errorsBox = document.getElementById('rqfErrors');

    let allowSubmit = false;

    // Intercept submit click → open modal
    trigger.addEventListener('click', function (e) {
        if (allowSubmit) return; // already validated, let it submit
        e.preventDefault();
        errorsBox.style.display = 'none';
        modal.show();
    });

    // Skip → submit parent form as-is
    btnSkip.addEventListener('click', function () {
        modal.hide();
        allowSubmit = true;
        mainForm.submit();
    });

    // Vider une section parent
    modalEl.querySelectorAll('[data-rqf-clear]').forEach(btn => {
        btn.addEventListener('click', function () {
            const prefix = this.dataset.rqfClear;
            modalEl.querySelectorAll('[name^="' + prefix + '"]').forEach(input => {
                if (input.type === 'checkbox' || input.type === 'radio') {
                    input.checked = false;
                } else if (input.tagName === 'SELECT') {
                    input.value = '';
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                } else {
                    input.value = '';
                }
            });
        });
    });

    // Update → AJAX PATCH then submit parent form
    btnUpdate.addEventListener('click', async function () {
        errorsBox.style.display = 'none';
        const originalLabel = btnUpdate.querySelector('span').textContent;
        btnUpdate.disabled = true;
        btnUpdate.querySelector('span').textContent = 'Mise à jour…';

        try {
            const fd = new FormData();
            modalEl.querySelectorAll('input[name], select[name], textarea[name]').forEach(input => {
                if (input.type === 'checkbox' && !input.checked) return;
                if (input.disabled) return;
                fd.append(input.name, input.value ?? '');
            });
            fd.append('_method', 'PATCH');

            const url = "{{ route('esbtp.reinscription.quick-update-fiche', $etudiant->id) }}";
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: fd,
            });

            if (res.ok) {
                const json = await res.json().catch(() => ({}));
                allowSubmit = true;
                // Si l'accessibilité a échoué silencieusement, afficher brièvement le warning avant de continuer.
                if (json.has_warning && json.message) {
                    errorsBox.classList.add('rqf-errors--warning');
                    errorsBox.innerHTML = '<i class="fas fa-exclamation-circle me-1"></i>' + json.message;
                    errorsBox.style.display = 'block';
                    setTimeout(() => { modal.hide(); mainForm.submit(); }, 1800);
                } else {
                    modal.hide();
                    mainForm.submit();
                }
                return;
            }

            if (res.status === 422) {
                const json = await res.json();
                const errs = json.errors || {};
                const items = Object.values(errs).flat().map(m => '<li>' + m + '</li>').join('');
                errorsBox.innerHTML = '<strong><i class="fas fa-exclamation-triangle me-1"></i>Corrigez les erreurs suivantes :</strong><ul>' + items + '</ul>';
                errorsBox.style.display = 'block';
                modalEl.querySelector('.rqf-body').scrollTop = 0;
            } else {
                errorsBox.innerHTML = '<i class="fas fa-times-circle me-1"></i>Erreur serveur. Réessayez ou cliquez sur « Continuer sans modifier ».';
                errorsBox.style.display = 'block';
            }
        } catch (err) {
            errorsBox.innerHTML = '<i class="fas fa-wifi me-1"></i>Erreur réseau. Vérifiez votre connexion.';
            errorsBox.style.display = 'block';
        } finally {
            btnUpdate.disabled = false;
            btnUpdate.querySelector('span').textContent = originalLabel;
        }
    });
})();
</script>
@endpush
