{{--
    Lot 8 — Modal édition de rôle custom (chargé en AJAX).
    Variables attendues :
    - $role : \Spatie\Permission\Models\Role (custom)
    - $grantablePermissions : Collection groupée des permissions accordables
    - $assignedPermissions : array<string> permissions actuellement attribuées
--}}
<div class="modal-dialog modal-lg cr-modal-dialog">
    <div class="modal-content cr-modal">
        <form id="cr-edit-form" action="{{ route('esbtp.custom-roles.update', $role->name) }}" method="POST" novalidate>
            @csrf
            @method('PUT')

            <div class="modal-header cr-modal-header">
                <div class="cr-modal-header-left">
                    <div class="cr-modal-icon">
                        <i class="fas {{ $role->icon ?? 'fa-user-tag' }}"></i>
                    </div>
                    <div>
                        <h2 class="cr-modal-title">Modifier le rôle</h2>
                        <p class="cr-modal-subtitle">{{ $role->label_fr ?? $role->name }} <span class="cr-modal-name-tag">{{ $role->name }}</span></p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <div class="modal-body cr-modal-body">
                <div class="cr-modal-scroll">

                {{-- Section 1 : Infos générales (name est figé) --}}
                <section class="cr-section">
                    <header class="cr-section-header">
                        <div class="cr-section-icon"><i class="fas fa-id-card"></i></div>
                        <div>
                            <h3 class="cr-section-title">Informations générales</h3>
                            <p class="cr-section-desc">Le nom interne (slug) est figé — modifiable uniquement par recréation</p>
                        </div>
                    </header>

                    <div class="cr-form-grid">
                        <div class="cr-form-group">
                            <label class="cr-form-label" for="cr-edit-label">
                                Label affiché <span class="cr-req">*</span>
                            </label>
                            <input type="text" id="cr-edit-label" name="label_fr" class="cr-form-input"
                                   value="{{ old('label_fr', $role->label_fr) }}"
                                   required maxlength="255">
                        </div>

                        <div class="cr-form-group">
                            <label class="cr-form-label" for="cr-edit-name-readonly">Nom interne (lecture seule)</label>
                            <input type="text" id="cr-edit-name-readonly" class="cr-form-input cr-form-input-mono"
                                   value="{{ $role->name }}" readonly disabled>
                        </div>
                    </div>

                    <div class="cr-form-grid">
                        <div class="cr-form-group">
                            <label class="cr-form-label" for="cr-edit-icon">Icône (Font Awesome)</label>
                            <div class="cr-icon-picker">
                                <span class="cr-icon-preview"><i class="fas {{ $role->icon ?? 'fa-user-tag' }}" data-cr-icon-preview></i></span>
                                <input type="text" id="cr-edit-icon" name="icon" class="cr-form-input cr-form-input-mono"
                                       value="{{ old('icon', $role->icon ?? 'fa-user-tag') }}" maxlength="64" data-cr-icon-input>
                            </div>
                            @include('esbtp.custom-roles._icon-suggestions')
                        </div>

                        <div class="cr-form-group">
                            <label class="cr-form-label" for="cr-edit-desc">Description (optionnel)</label>
                            <textarea id="cr-edit-desc" name="description" class="cr-form-input" rows="2" maxlength="1000">{{ old('description', $role->description) }}</textarea>
                        </div>
                    </div>
                </section>

                {{-- Section 2 : Permissions --}}
                <section class="cr-section">
                    <header class="cr-section-header">
                        <div class="cr-section-icon"><i class="fas fa-key"></i></div>
                        <div>
                            <h3 class="cr-section-title">Permissions accordées</h3>
                            <p class="cr-section-desc">Cochez les actions que ce rôle pourra effectuer</p>
                        </div>
                    </header>

                    @include('esbtp.custom-roles._permissions-picker', [
                        'grantablePermissions' => $grantablePermissions,
                        'assignedPermissions' => $assignedPermissions,
                    ])
                </section>

                    <div class="cr-error-zone" data-cr-error style="display:none;"></div>

                </div>
            </div>

            <div class="modal-footer cr-modal-footer">
                <button type="button" class="cr-btn cr-btn-ghost" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <button type="submit" class="cr-btn cr-btn-primary" data-cr-submit>
                    <i class="fas fa-check"></i> Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>
