@props([
    'name' => 'user_id',
    'value' => null,
    'users' => collect(),
    'placeholder' => '— Tous les utilisateurs —',
    'submitOnChange' => false,
])

@php
    $rolePriority = [
        'superAdmin'       => ['label' => 'Super Administrateur', 'color' => '#7c3aed', 'icon' => 'fa-crown'],
        'serviceTechnique' => ['label' => 'Service Technique',    'color' => '#0ea5e9', 'icon' => 'fa-shield-alt'],
        'secretaire'       => ['label' => 'Secrétaire',           'color' => '#0453cb', 'icon' => 'fa-clipboard-list'],
        'coordinateur'     => ['label' => 'Coordinateur',         'color' => '#1d4ed8', 'icon' => 'fa-user-tie'],
        'comptable'        => ['label' => 'Comptable',            'color' => '#059669', 'icon' => 'fa-calculator'],
        'caissier'         => ['label' => 'Caissier',             'color' => '#10b981', 'icon' => 'fa-cash-register'],
        'enseignant'       => ['label' => 'Enseignant',           'color' => '#f59e0b', 'icon' => 'fa-chalkboard-teacher'],
        'etudiant'         => ['label' => 'Étudiant',             'color' => '#94a3b8', 'icon' => 'fa-user-graduate'],
    ];

    $userToBucket = function ($u) {
        return [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email ?? '',
            'username' => $u->username ?? null,
            'initial' => mb_strtoupper(mb_substr(trim($u->name ?? '?'), 0, 1, 'UTF-8'), 'UTF-8'),
        ];
    };

    $groupedJson = collect();
    $usersCollection = $users instanceof \Illuminate\Support\Collection ? $users : collect($users);

    foreach ($rolePriority as $key => $meta) {
        $bucket = $usersCollection->filter(function ($u) use ($key) {
            $names = method_exists($u, 'roles') && $u->relationLoaded('roles')
                ? $u->roles->pluck('name')->all()
                : (method_exists($u, 'getRoleNames') ? $u->getRoleNames()->all() : []);
            return in_array($key, $names, true);
        })->map($userToBucket)->sortBy('name')->values();

        if ($bucket->isNotEmpty()) {
            $groupedJson->push([
                'key' => $key,
                'label' => $meta['label'],
                'color' => $meta['color'],
                'icon' => $meta['icon'],
                'users' => $bucket->all(),
                'count' => $bucket->count(),
            ]);
        }
    }

    $orphaned = $usersCollection->filter(function ($u) use ($rolePriority) {
        $names = method_exists($u, 'roles') && $u->relationLoaded('roles')
            ? $u->roles->pluck('name')->all()
            : (method_exists($u, 'getRoleNames') ? $u->getRoleNames()->all() : []);
        return count(array_intersect($names, array_keys($rolePriority))) === 0;
    })->map($userToBucket)->sortBy('name')->values();

    if ($orphaned->isNotEmpty()) {
        $groupedJson->push([
            'key' => 'autre',
            'label' => 'Autres',
            'color' => '#64748b',
            'icon' => 'fa-user',
            'users' => $orphaned->all(),
            'count' => $orphaned->count(),
        ]);
    }

    $totalUsers = $usersCollection->count();
@endphp

<div class="au-up"
     x-data="auUserPicker()"
     data-groups='@json($groupedJson)'
     data-submit-on-change="{{ $submitOnChange ? '1' : '0' }}"
     data-current="{{ $value ?? '' }}"
     @click.outside="open = false"
     @keydown.escape="open = false">

    <button type="button"
            class="au-up-trigger"
            :class="{ 'au-up-trigger--open': open, 'au-up-trigger--has-value': currentValue !== '' }"
            @click="toggle()"
            :aria-expanded="open.toString()"
            aria-haspopup="listbox">
        <template x-if="!selectedUser">
            <span class="au-up-trigger-empty">
                <i class="fas fa-users au-up-trigger-icon"></i>
                <span>{{ $placeholder }}</span>
            </span>
        </template>
        <template x-if="selectedUser">
            <span class="au-up-trigger-selected">
                <span class="au-up-avatar" :style="`background: ${selectedUser.color}`" x-text="selectedUser.initial"></span>
                <span class="au-up-trigger-info">
                    <span class="au-up-trigger-name" x-text="selectedUser.name"></span>
                    <span class="au-up-trigger-role" x-text="selectedUser.roleLabel"></span>
                </span>
            </span>
        </template>
        <i class="fas fa-chevron-down au-up-caret" :class="{ 'au-up-caret--open': open }"></i>
    </button>

    <div class="au-up-menu" x-show="open" x-cloak
         x-transition:enter="au-up-menu--entering"
         x-transition:enter-start="au-up-menu--enter-start"
         x-transition:enter-end="au-up-menu--enter-end">

        <div class="au-up-search">
            <i class="fas fa-search"></i>
            <input type="text" x-model="search" x-ref="searchInput" @click.stop
                   @keydown.escape.stop="open = false"
                   placeholder="Rechercher par nom, email ou rôle…">
            <button type="button" x-show="search.length > 0"
                    @click="search = ''; $refs.searchInput.focus()"
                    class="au-up-search-clear" aria-label="Effacer">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="au-up-stats" x-show="!search">
            <span class="au-up-stats-pill">
                <i class="fas fa-users"></i>
                <strong>{{ $totalUsers }}</strong> utilisateur{{ $totalUsers > 1 ? 's' : '' }} au total
            </span>
        </div>

        <div class="au-up-options" role="listbox">
            <button type="button"
                    class="au-up-option au-up-option--all"
                    :class="{ 'au-up-option--active': currentValue === '' }"
                    @click="select(null)">
                <span class="au-up-avatar au-up-avatar--all"><i class="fas fa-globe"></i></span>
                <span class="au-up-option-info">
                    <span class="au-up-option-name">Tous les utilisateurs</span>
                    <span class="au-up-option-meta">Vue d'ensemble — toutes les actions tracées</span>
                </span>
                <i class="fas fa-check au-up-option-check" x-show="currentValue === ''"></i>
            </button>

            <template x-for="group in filteredGroups" :key="group.key">
                <div class="au-up-group">
                    <div class="au-up-group-header">
                        <i class="fas" :class="group.icon" :style="`color: ${group.color}`"></i>
                        <span class="au-up-group-label" x-text="group.label"></span>
                        <span class="au-up-group-count" x-text="group.users.length"></span>
                    </div>
                    <template x-for="u in group.users" :key="u.id">
                        <button type="button" class="au-up-option"
                                :class="{ 'au-up-option--active': String(currentValue) === String(u.id) }"
                                @click="select(u, group)" role="option"
                                :aria-selected="(String(currentValue) === String(u.id)).toString()">
                            <span class="au-up-avatar" :style="`background: ${group.color}`" x-text="u.initial"></span>
                            <span class="au-up-option-info">
                                <span class="au-up-option-name" x-text="u.name"></span>
                                <span class="au-up-option-meta">
                                    <span class="au-up-role-chip" :style="`background: ${group.color}1a; color: ${group.color}; border-color: ${group.color}33`" x-text="group.label"></span>
                                    <span class="au-up-email" x-text="u.email"></span>
                                </span>
                            </span>
                            <i class="fas fa-check au-up-option-check" x-show="String(currentValue) === String(u.id)"></i>
                        </button>
                    </template>
                </div>
            </template>

            <div class="au-up-empty" x-show="filteredGroups.length === 0" x-cloak>
                <i class="fas fa-search"></i>
                <span>Aucun utilisateur ne correspond à <strong x-text="search"></strong></span>
            </div>
        </div>
    </div>

    <input type="hidden" name="{{ $name }}" :value="currentValue" x-ref="native">
</div>

@once
@push('styles')
<style>
.au-up { position: relative; flex: 1; min-width: 0; display: flex; }
.au-up-trigger {
    width: 100%; display: flex; align-items: center; gap: .65rem;
    padding: .5rem .8rem;
    background: #fff; border: 1px solid #e2e8f0; border-radius: 10px;
    cursor: pointer; transition: border-color .15s, box-shadow .15s;
    text-align: left; line-height: 1.2;
}
.au-up-trigger:hover { border-color: #cbd5e1; }
.au-up-trigger--open { border-color: #0453cb; box-shadow: 0 0 0 3px rgba(4,83,203,.1); }
.au-up-trigger-empty { display: flex; align-items: center; gap: .5rem; flex: 1; color: #64748b; font-size: .85rem; min-width: 0; }
.au-up-trigger-empty span { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.au-up-trigger-icon { color: #94a3b8; }
.au-up-trigger-selected { display: flex; align-items: center; gap: .65rem; flex: 1; min-width: 0; }
.au-up-trigger-info { display: flex; flex-direction: column; min-width: 0; }
.au-up-trigger-name { font-size: .85rem; font-weight: 600; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.au-up-trigger-role { font-size: .68rem; color: #64748b; text-transform: uppercase; letter-spacing: .4px; }
.au-up-caret { color: #94a3b8; font-size: .72rem; flex-shrink: 0; transition: transform .2s; margin-left: auto; }
.au-up-caret--open { transform: rotate(180deg); color: #0453cb; }
.au-filter-field > .au-up { width: 100%; }
.au-filter-field > .au-up .au-up-trigger { background: transparent; border: none; padding: 0; }
.au-filter-field > .au-up .au-up-trigger:hover { box-shadow: none; }
.au-up-avatar {
    width: 32px; height: 32px; border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    color: #fff; font-weight: 700; font-size: .82rem;
    background: #94a3b8; flex-shrink: 0;
    box-shadow: 0 1px 3px rgba(15,23,42,.15);
}
.au-up-avatar--all { background: linear-gradient(135deg, #0a3d8f, #3b7ddb) !important; }
.au-up-avatar--all i { color: #fff; font-size: .9rem; }
.au-up-menu {
    position: absolute; top: calc(100% + 6px); left: 0;
    width: max(420px, 100%); z-index: 1050;
    background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
    box-shadow: 0 16px 50px rgba(15,23,42,.14), 0 4px 12px rgba(15,23,42,.06);
    overflow: hidden; max-height: 500px;
    display: flex; flex-direction: column;
    transform-origin: top center;
}
.au-up-menu--enter-start { opacity: 0; transform: translateY(-8px) scale(.98); transition: opacity .14s, transform .14s; }
.au-up-menu--enter-end { opacity: 1; transform: translateY(0) scale(1); }
.au-up-search {
    position: relative; padding: .65rem .8rem;
    border-bottom: 1px solid #f1f5f9;
    display: flex; align-items: center; gap: .55rem;
    background: #f8fafc;
}
.au-up-search > i:first-child { color: #94a3b8; }
.au-up-search input { flex: 1; border: none; background: transparent; outline: none; font-size: .88rem; color: #0f172a; }
.au-up-search input::placeholder { color: #94a3b8; }
.au-up-search-clear {
    background: #e2e8f0; border: none; width: 24px; height: 24px;
    border-radius: 50%; cursor: pointer;
    display: inline-flex; align-items: center; justify-content: center;
    color: #475569; font-size: .7rem;
}
.au-up-search-clear:hover { background: #cbd5e1; color: #0f172a; }
.au-up-stats { padding: .5rem .8rem; background: #fafafa; border-bottom: 1px solid #f1f5f9; }
.au-up-stats-pill {
    display: inline-flex; align-items: center; gap: .4rem;
    background: #fff; border: 1px solid #e2e8f0; border-radius: 999px;
    padding: .2rem .65rem; font-size: .72rem; color: #475569;
}
.au-up-stats-pill strong { color: #0453cb; }
.au-up-options { flex: 1; overflow-y: auto; padding: .35rem 0;
    scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent;
}
.au-up-options::-webkit-scrollbar { width: 6px; }
.au-up-options::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
.au-up-option {
    width: 100%; display: flex; align-items: center; gap: .75rem;
    padding: .6rem .8rem;
    background: transparent; border: none; cursor: pointer;
    text-align: left; transition: background .12s;
    border-left: 3px solid transparent;
}
.au-up-option:hover { background: #f8fafc; border-left-color: #cbd5e1; }
.au-up-option--active { background: #eff6ff !important; border-left-color: #0453cb !important; }
.au-up-option--all {
    background: linear-gradient(90deg, #f0f9ff, transparent) !important;
    margin: 0 .35rem .35rem; border-radius: 8px; border-left-width: 0;
}
.au-up-option--all .au-up-option-name { color: #0453cb; font-weight: 600; }
.au-up-option-info { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: .15rem; }
.au-up-option-name { font-size: .87rem; font-weight: 600; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.au-up-option-meta { display: flex; align-items: center; gap: .45rem; font-size: .72rem; color: #64748b; flex-wrap: wrap; }
.au-up-role-chip {
    padding: .08rem .45rem; border-radius: 999px;
    font-weight: 600; font-size: .68rem; border: 1px solid;
    text-transform: uppercase; letter-spacing: .3px;
}
.au-up-email { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 220px; }
.au-up-option-check { color: #0453cb; font-size: .82rem; flex-shrink: 0; }
.au-up-group { padding: .25rem 0; }
.au-up-group + .au-up-group { border-top: 1px solid #f1f5f9; margin-top: .25rem; padding-top: .35rem; }
.au-up-group-header {
    display: flex; align-items: center; gap: .5rem;
    padding: .35rem .85rem;
    font-size: .68rem; text-transform: uppercase; letter-spacing: .6px;
    color: #475569; font-weight: 700;
    background: #fafafa;
}
.au-up-group-label { flex: 1; }
.au-up-group-count {
    background: #e2e8f0; color: #475569;
    border-radius: 999px; padding: .1rem .55rem; font-size: .65rem;
}
.au-up-empty {
    padding: 2rem 1rem; text-align: center;
    color: #64748b; font-size: .85rem;
    display: flex; flex-direction: column; align-items: center; gap: .5rem;
}
.au-up-empty i { color: #cbd5e1; font-size: 1.6rem; }
@media (max-width: 768px) {
    .au-up-menu { width: calc(100vw - 2rem); max-height: 70vh; }
    .au-up-email { max-width: 140px; }
}
</style>
@endpush

@push('scripts')
<script>
if (typeof window.auUserPicker !== 'function') {
    window.auUserPicker = function () {
        return {
            open: false,
            search: '',
            groups: [],
            currentValue: '',
            submitOnChange: false,
            init() {
                try { this.groups = JSON.parse(this.$el.dataset.groups || '[]'); }
                catch (e) { this.groups = []; }
                this.currentValue = this.$el.dataset.current || '';
                this.submitOnChange = this.$el.dataset.submitOnChange === '1';
                this.$nextTick(() => { if (this.$refs.native) this.$refs.native.value = this.currentValue; });
            },
            toggle() {
                this.open = !this.open;
                if (this.open) this.$nextTick(() => this.$refs.searchInput?.focus());
            },
            get filteredGroups() {
                const s = this.search.trim().toLowerCase();
                if (!s) return this.groups;
                return this.groups.map(g => ({
                    ...g,
                    users: g.users.filter(u =>
                        u.name.toLowerCase().includes(s) ||
                        (u.email || '').toLowerCase().includes(s) ||
                        g.label.toLowerCase().includes(s) ||
                        (u.username || '').toLowerCase().includes(s)
                    ),
                })).filter(g => g.users.length > 0);
            },
            get selectedUser() {
                if (!this.currentValue) return null;
                for (const g of this.groups) {
                    for (const u of g.users) {
                        if (String(u.id) === String(this.currentValue)) {
                            return { ...u, color: g.color, roleLabel: g.label };
                        }
                    }
                }
                return null;
            },
            select(u, group) {
                this.currentValue = u ? String(u.id) : '';
                this.open = false; this.search = '';
                this.$nextTick(() => {
                    if (this.$refs.native) {
                        this.$refs.native.value = this.currentValue;
                        this.$refs.native.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    if (this.submitOnChange) {
                        const form = this.$el.closest('form');
                        if (form) form.submit();
                    }
                });
            },
        };
    };
}
</script>
@endpush
@endonce
