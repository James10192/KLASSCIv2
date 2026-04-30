{{--
    Lot 8 — Modal assignation d'utilisateurs à un rôle custom (chargé en AJAX).
    Variables :
    - $role : \Spatie\Permission\Models\Role
    - $assignableUsers : Collection<User>
    - $currentUserIds : array<int> users actuellement attribués
--}}
<div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content cr-modal">
        <form id="cr-assign-form" action="{{ route('esbtp.custom-roles.assign-users', $role->name) }}" method="POST" novalidate>
            @csrf

            <div class="modal-header cr-modal-header">
                <div class="cr-modal-header-left">
                    <div class="cr-modal-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <h2 class="cr-modal-title">Assigner des utilisateurs</h2>
                        <p class="cr-modal-subtitle">Rôle : <strong>{{ $role->label_fr ?? $role->name }}</strong></p>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>

            <div class="modal-body cr-modal-body">

                <div class="cr-assign-info">
                    <i class="fas fa-info-circle"></i>
                    Seuls les utilisateurs de votre périmètre de gestion sont listés.
                    Les utilisateurs déjà assignés sont pré-cochés.
                </div>

                <div class="cr-picker-toolbar">
                    <div class="cr-picker-search">
                        <i class="fas fa-search"></i>
                        <input type="text" class="cr-picker-search-input" placeholder="Rechercher par nom ou email..." data-cr-user-search>
                    </div>
                    <div class="cr-picker-counter">
                        <span class="cr-picker-counter-value" data-cr-user-counter>{{ count($currentUserIds) }}</span>
                        <span class="cr-picker-counter-sep">/</span>
                        <span class="cr-picker-counter-total">{{ $assignableUsers->count() }}</span>
                        <span class="cr-picker-counter-label">utilisateurs sélectionnés</span>
                    </div>
                </div>

                @if($assignableUsers->isEmpty())
                    <div class="cr-picker-empty">
                        <div class="cr-picker-empty-icon"><i class="fas fa-user-slash"></i></div>
                        <h4>Aucun utilisateur dans votre périmètre</h4>
                        <p>Vous n'avez accès à aucun utilisateur que vous pouvez gérer pour ce rôle.</p>
                    </div>
                @else
                    <div class="cr-users-list">
                        @foreach($assignableUsers as $user)
                            @php
                                $primaryRole = $user->roles->first();
                                $isAssigned = in_array($user->id, $currentUserIds, true);
                            @endphp
                            <label class="cr-user-row" data-cr-user-label="{{ Str::lower($user->name . ' ' . $user->email) }}">
                                <input type="checkbox" name="user_ids[]" value="{{ $user->id }}"
                                       class="cr-user-check" data-cr-user-check
                                       @checked($isAssigned)>
                                <span class="cr-user-box"><i class="fas fa-check"></i></span>
                                <span class="cr-user-avatar">{{ strtoupper(mb_substr($user->name, 0, 2)) }}</span>
                                <span class="cr-user-info">
                                    <span class="cr-user-name">{{ $user->name }}</span>
                                    <span class="cr-user-email">{{ $user->email }}</span>
                                </span>
                                @if($primaryRole)
                                    <span class="cr-user-role-tag">
                                        <i class="fas fa-shield"></i>
                                        {{ $primaryRole->label_fr ?? $primaryRole->name }}
                                    </span>
                                @endif
                            </label>
                        @endforeach
                    </div>
                @endif

                <div class="cr-error-zone" data-cr-error style="display:none;"></div>

            </div>

            <div class="modal-footer cr-modal-footer">
                <button type="button" class="cr-btn cr-btn-ghost" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <button type="submit" class="cr-btn cr-btn-primary" data-cr-submit>
                    <i class="fas fa-check"></i> Mettre à jour les affectations
                </button>
            </div>
        </form>
    </div>
</div>
