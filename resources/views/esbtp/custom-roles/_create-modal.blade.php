{{--
    Lot 8 — Modal création de rôle custom (chargé en AJAX).
    Variables attendues :
    - $grantablePermissions : Collection groupée des permissions accordables
--}}
<div class="modal-dialog modal-lg cr-modal-dialog">
    <div class="modal-content cr-modal">
        <form id="cr-create-form" action="{{ route('esbtp.custom-roles.store') }}" method="POST" novalidate>
            @csrf

            <div class="modal-header cr-modal-header">
                <div class="cr-modal-header-left">
                    <div class="cr-modal-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div>
                        <h2 class="cr-modal-title">Créer un rôle personnalisé</h2>
                        <p class="cr-modal-subtitle">Définissez un rôle métier sur mesure (ex: Agent Inscriptions, Surveillant)</p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <div class="modal-body cr-modal-body">

                {{-- Section 1 : Infos générales --}}
                <section class="cr-section">
                    <header class="cr-section-header">
                        <div class="cr-section-icon"><i class="fas fa-id-card"></i></div>
                        <div>
                            <h3 class="cr-section-title">Informations générales</h3>
                            <p class="cr-section-desc">Identité du rôle visible par les utilisateurs</p>
                        </div>
                    </header>

                    <div class="cr-form-grid">
                        <div class="cr-form-group">
                            <label class="cr-form-label" for="cr-create-label">
                                Label affiché <span class="cr-req">*</span>
                            </label>
                            <input type="text" id="cr-create-label" name="label_fr" class="cr-form-input"
                                   placeholder="Ex: Agent Inscriptions" required maxlength="255"
                                   data-cr-label-input>
                            <small class="cr-form-hint">Le nom que verra l'utilisateur dans l'application.</small>
                        </div>

                        <div class="cr-form-group">
                            <label class="cr-form-label" for="cr-create-name">
                                Nom interne (slug) <span class="cr-req">*</span>
                            </label>
                            <input type="text" id="cr-create-name" name="name" class="cr-form-input cr-form-input-mono"
                                   placeholder="agent_inscriptions" required maxlength="64"
                                   pattern="^[a-z][a-z0-9_]*$"
                                   data-cr-name-input>
                            <small class="cr-form-hint">snake_case, lettres/chiffres/underscore. Auto-généré depuis le label.</small>
                        </div>
                    </div>

                    <div class="cr-form-grid">
                        <div class="cr-form-group">
                            <label class="cr-form-label" for="cr-create-icon">
                                Icône (Font Awesome)
                            </label>
                            <div class="cr-icon-picker">
                                <span class="cr-icon-preview"><i class="fas fa-user-tag" data-cr-icon-preview></i></span>
                                <input type="text" id="cr-create-icon" name="icon" class="cr-form-input cr-form-input-mono"
                                       value="fa-user-tag" maxlength="64" placeholder="fa-user-tag" data-cr-icon-input>
                            </div>
                            @include('esbtp.custom-roles._icon-suggestions')
                        </div>

                        <div class="cr-form-group">
                            <label class="cr-form-label" for="cr-create-desc">Description (optionnel)</label>
                            <textarea id="cr-create-desc" name="description" class="cr-form-input" rows="2"
                                      maxlength="1000"
                                      placeholder="Ce rôle peut faire les inscriptions et consulter les fiches étudiants..."></textarea>
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
                        'assignedPermissions' => [],
                    ])
                </section>

                <div class="cr-error-zone" data-cr-error style="display:none;"></div>

            </div>

            <div class="modal-footer cr-modal-footer">
                <button type="button" class="cr-btn cr-btn-ghost" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <button type="submit" class="cr-btn cr-btn-primary" data-cr-submit>
                    <i class="fas fa-check"></i> Créer le rôle
                </button>
            </div>
        </form>
    </div>
</div>
