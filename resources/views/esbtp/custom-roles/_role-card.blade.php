{{--
    Lot 8 — Liste des rôles custom (rendue dans personnel/unified-index.blade.php).
    Variables :
    - $customRoles : Collection<['id','name','label','icon','description','users_count','permissions_count']>
--}}
<div class="cr-roles-list">
    @if($customRoles->isEmpty())
        <div class="cr-empty">
            <div class="cr-empty-icon"><i class="fas fa-id-badge"></i></div>
            <h3>Aucun rôle personnalisé</h3>
            <p>Créez votre premier rôle métier sur mesure (ex: Agent Inscriptions, Surveillant)</p>
            @can('personnel.manage')
                <button type="button" class="cr-empty-btn" data-cr-create-trigger>
                    <i class="fas fa-plus"></i> Créer un rôle personnalisé
                </button>
            @endcan
        </div>
    @else
        @foreach($customRoles as $role)
            <div class="cr-role-card" data-cr-role-card="{{ $role['name'] }}">
                <div class="cr-role-card-icon">
                    <i class="fas {{ $role['icon'] }}"></i>
                </div>
                <div class="cr-role-card-body">
                    <div class="cr-role-card-head">
                        <h4 class="cr-role-card-label">{{ $role['label'] }}</h4>
                        <span class="cr-role-card-name">{{ $role['name'] }}</span>
                    </div>
                    @if(!empty($role['description']))
                        <p class="cr-role-card-desc">{{ $role['description'] }}</p>
                    @endif
                    <div class="cr-role-card-stats">
                        <span class="cr-role-card-stat" title="{{ $role['users_count'] }} utilisateur(s) attribué(s)">
                            <i class="fas fa-users"></i>{{ $role['users_count'] }}
                        </span>
                        <span class="cr-role-card-stat" title="{{ $role['permissions_count'] }} permission(s)">
                            <i class="fas fa-key"></i>{{ $role['permissions_count'] }}
                        </span>
                    </div>
                </div>
                @can('personnel.manage')
                    <div class="cr-role-card-actions">
                        <button type="button" class="cr-role-action" data-cr-edit="{{ $role['name'] }}" title="Modifier">
                            <i class="fas fa-pen"></i>
                        </button>
                        <button type="button" class="cr-role-action cr-role-action-users" data-cr-assign="{{ $role['name'] }}" title="Assigner des utilisateurs">
                            <i class="fas fa-user-plus"></i>
                        </button>
                        <button type="button" class="cr-role-action cr-role-action-danger" data-cr-delete="{{ $role['name'] }}" data-cr-delete-label="{{ $role['label'] }}" data-cr-delete-users="{{ $role['users_count'] }}" title="Supprimer">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                @endcan
            </div>
        @endforeach
    @endif
</div>
