{{--
    Lot 17c — Modal édition d'un rôle STANDARD (système éditable).
    Variables attendues :
    - $role : \Spatie\Permission\Models\Role (système, is_custom=false)
    - $configMeta : array — metadata par défaut depuis config/permissions.php
    - $grantablePermissions : Collection groupée des permissions accordables
    - $assignedPermissions : array<string> permissions actuellement attribuées

    Différences vs _edit-modal.blade.php (rôle custom) :
    - name READONLY (visible mais non éditable, sinon casse @can() partout)
    - Bandeau warning rappelant que c'est un rôle système
    - label_fr / icon / description peuvent être OVERRIDE par rapport au config
    - Le « Restaurer défauts » garde le pointeur vers le config registry
--}}
<div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content cr-modal cr-modal--standard">
        <form id="cr-edit-standard-form" action="{{ route('esbtp.custom-roles.standard.update', $role->name) }}" method="POST" novalidate>
            @csrf
            @method('PUT')

            <div class="modal-header cr-modal-header">
                <div class="cr-modal-header-left">
                    <div class="cr-modal-icon">
                        <i class="fas {{ $role->icon ?? ($configMeta['icon'] ?? 'fa-user-tag') }}"></i>
                    </div>
                    <div>
                        <h2 class="cr-modal-title">Modifier le rôle système</h2>
                        <p class="cr-modal-subtitle">
                            {{ $role->label_fr ?? ($configMeta['label'] ?? $role->name) }}
                            <span class="cr-modal-name-tag">{{ $role->name }}</span>
                        </p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <div class="modal-body cr-modal-body">

                {{-- Bandeau warning rôle système --}}
                <div class="cr-info-note cr-info-note--warning" style="margin-bottom: 1rem;">
                    <i class="fas fa-shield-alt"></i>
                    <div>
                        <strong>Rôle système — modifications préservées</strong>
                        <p>Vous modifiez un rôle standard de KLASSCI. Le <strong>nom interne</strong> (<code>{{ $role->name }}</code>) est figé. Le label, l'icône, la description et les permissions sont customisables. Pour des modifications avancées (création/suppression de rôle système, rôles superAdmin/serviceTechnique), contactez le Service Technique.</p>
                    </div>
                </div>

                {{-- Section 1 : Infos générales (name READONLY) --}}
                <section class="cr-section">
                    <header class="cr-section-header">
                        <div class="cr-section-icon"><i class="fas fa-id-card"></i></div>
                        <div>
                            <h3 class="cr-section-title">Informations générales</h3>
                            <p class="cr-section-desc">Override l'affichage du rôle (nom interne immuable)</p>
                        </div>
                    </header>

                    <div class="cr-form-grid">
                        <div class="cr-form-group">
                            <label class="cr-form-label" for="cr-edit-std-label">
                                Label affiché <span class="cr-req">*</span>
                            </label>
                            <input type="text" id="cr-edit-std-label" name="label_fr" class="cr-form-input"
                                   value="{{ old('label_fr', $role->label_fr ?? ($configMeta['label'] ?? '')) }}"
                                   placeholder="{{ $configMeta['label'] ?? '' }}"
                                   required maxlength="255">
                            @if(!empty($configMeta['label']))
                                <small class="cr-form-hint">Défaut registry : <em>{{ $configMeta['label'] }}</em></small>
                            @endif
                        </div>

                        <div class="cr-form-group">
                            <label class="cr-form-label" for="cr-edit-std-name-readonly">
                                Nom interne <span class="cr-form-tag-immutable">immuable</span>
                            </label>
                            <input type="text" id="cr-edit-std-name-readonly" class="cr-form-input cr-form-input-mono"
                                   value="{{ $role->name }}" readonly disabled>
                        </div>
                    </div>

                    <div class="cr-form-grid">
                        <div class="cr-form-group">
                            <label class="cr-form-label" for="cr-edit-std-icon">Icône (Font Awesome)</label>
                            <div class="cr-icon-picker">
                                <span class="cr-icon-preview"><i class="fas {{ $role->icon ?? ($configMeta['icon'] ?? 'fa-user-tag') }}" data-cr-icon-preview></i></span>
                                <input type="text" id="cr-edit-std-icon" name="icon" class="cr-form-input cr-form-input-mono"
                                       value="{{ old('icon', $role->icon ?? ($configMeta['icon'] ?? 'fa-user-tag')) }}"
                                       maxlength="64" data-cr-icon-input>
                            </div>
                            <div class="cr-icon-suggestions">
                                @foreach(['fa-user-tag', 'fa-user-shield', 'fa-user-tie', 'fa-user-cog', 'fa-cash-register', 'fa-calculator', 'fa-chalkboard-teacher', 'fa-user-graduate', 'fa-clipboard-list', 'fa-pen-fancy'] as $iconClass)
                                    <button type="button" class="cr-icon-chip" data-cr-icon-suggest="{{ $iconClass }}" title="{{ $iconClass }}">
                                        <i class="fas {{ $iconClass }}"></i>
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div class="cr-form-group">
                            <label class="cr-form-label" for="cr-edit-std-desc">Description (optionnel)</label>
                            <textarea id="cr-edit-std-desc" name="description" class="cr-form-input" rows="2"
                                      placeholder="{{ $configMeta['description'] ?? '' }}"
                                      maxlength="1000">{{ old('description', $role->description) }}</textarea>
                        </div>
                    </div>
                </section>

                {{-- Section 2 : Permissions --}}
                <section class="cr-section">
                    <header class="cr-section-header">
                        <div class="cr-section-icon"><i class="fas fa-key"></i></div>
                        <div>
                            <h3 class="cr-section-title">Permissions accordées</h3>
                            <p class="cr-section-desc">Vous ne pouvez accorder que les permissions que vous-même possédez</p>
                        </div>
                    </header>

                    @include('esbtp.custom-roles._permissions-picker', [
                        'grantablePermissions' => $grantablePermissions,
                        'assignedPermissions' => $assignedPermissions,
                    ])
                </section>

                <div class="cr-error-zone" data-cr-error style="display:none;"></div>

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
