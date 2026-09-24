{{--
    Navigation basse du shell mobile (et rail latéral sur tablette 768–1023px).
    Rendue depuis `$mobileProfile` et `$mobileShellEnabled` (partagés par App\View\Composers\MobileShellComposer,
    profils canoniques : App\Services\Mobile\MobileProfileResolver::PROFILS). Sans profil connu, rien n'est rendu.

    Chaque entrée est gardée par (a) Route::has() et (b) la permission EXISTANTE du registre
    (config/permissions.php) quand une permission évidente existe — jamais de 403 visible.
    L'onglet « Plus » ouvre la feuille m-sheet `m-plus` (profil, raccourcis, déconnexion).
    Pour le superAdmin, si la route `mobile.profil` existe (outil de démonstration), la feuille
    propose un sélecteur des quatre profils (form POST).
--}}
@php
    $mUser = auth()->user();
    $mProfil = $mobileProfile ?? null;
    if (!\App\Services\Mobile\MobileProfileResolver::estUnProfil(is_string($mProfil) ? $mProfil : null)) {
        $mProfil = null;
    }
    if (isset($mobileShellEnabled) && !$mobileShellEnabled) {
        $mProfil = null;
    }

    $mItems = [];
    if ($mUser && $mProfil) {
        $r = fn (string $name) => Route::has($name);
        $on = fn ($patterns) => request()->routeIs($patterns);

        switch ($mProfil) {
            case 'caissier':
                $mItems = [
                    ['label' => 'Accueil', 'icon' => 'home', 'href' => $r('dashboard') ? route('dashboard') : null, 'on' => $on('dashboard'), 'show' => true],
                    ['label' => 'Encaisser', 'icon' => 'cash', 'href' => $r('esbtp.paiements.create') ? route('esbtp.paiements.create') : null, 'on' => $on('esbtp.paiements.create'), 'show' => $mUser->can('paiements.create')],
                    ['label' => 'Encaissements', 'icon' => 'list', 'href' => $r('esbtp.paiements.index') ? route('esbtp.paiements.index') : null, 'on' => $on(['esbtp.paiements.index', 'esbtp.paiements.show']), 'show' => $mUser->canAny(['paiements.view', 'paiements.view_own'])],
                    ['label' => 'Ma caisse', 'icon' => 'wallet', 'href' => $r('esbtp.caisse.ma-caisse') ? route('esbtp.caisse.ma-caisse') : null, 'on' => $on('esbtp.caisse.*'), 'show' => $mUser->canAny(['cash_session.manage', 'module.caisse.access'])],
                    ['label' => 'Plus', 'icon' => 'menu', 'sheet' => 'm-plus', 'on' => false, 'show' => true],
                ];
                break;
            case 'comptable':
                $mItems = [
                    ['label' => 'Tableau', 'icon' => 'grid', 'href' => $r('esbtp.comptabilite.dashboard') ? route('esbtp.comptabilite.dashboard') : null, 'on' => $on('esbtp.comptabilite.dashboard'), 'show' => $mUser->canAny(['comptabilite.access', 'comptabilite.dashboard.view'])],
                    ['label' => 'Paiements', 'icon' => 'cash', 'href' => $r('esbtp.paiements.index') ? route('esbtp.paiements.index') : null, 'on' => $on(['esbtp.paiements.index', 'esbtp.paiements.show']), 'show' => $mUser->canAny(['paiements.view', 'paiements.view_own'])],
                    ['label' => 'Recouvrer', 'icon' => 'phone', 'href' => $r('esbtp.comptabilite.recouvrement.index') ? route('esbtp.comptabilite.recouvrement.index') : null, 'on' => $on('esbtp.comptabilite.recouvrement.*'), 'show' => $mUser->can('comptabilite.recouvrement.access')],
                    ['label' => 'Journal', 'icon' => 'book', 'href' => $r('esbtp.comptabilite.journal-caisse.index') ? route('esbtp.comptabilite.journal-caisse.index') : null, 'on' => $on('esbtp.comptabilite.journal-caisse.*'), 'show' => $mUser->can('comptabilite.journal.view')],
                    ['label' => 'Plus', 'icon' => 'menu', 'sheet' => 'm-plus', 'on' => false, 'show' => true],
                ];
                break;
            case 'etudiant':
                $mItems = [
                    ['label' => 'Accueil', 'icon' => 'home', 'href' => $r('dashboard') ? route('dashboard') : null, 'on' => $on('dashboard'), 'show' => true],
                    ['label' => 'Notes', 'icon' => 'list', 'href' => $r('esbtp.mes-notes.index') ? route('esbtp.mes-notes.index') : null, 'on' => $on('esbtp.mes-notes.*'), 'show' => $mUser->canAny(['notes.view_own', 'notes.view'])],
                    ['label' => 'EDT', 'icon' => 'cal', 'href' => $r('esbtp.mon-emploi-temps.index') ? route('esbtp.mon-emploi-temps.index') : null, 'on' => $on('esbtp.mon-emploi-temps.*'), 'show' => $mUser->canAny(['timetables.view_own', 'timetables.view'])],
                    ['label' => 'Absences', 'icon' => 'clock', 'href' => $r('esbtp.mes-absences.index') ? route('esbtp.mes-absences.index') : null, 'on' => $on('esbtp.mes-absences.*'), 'show' => $mUser->canAny(['attendances.view_own', 'attendances.view'])],
                    ['label' => 'Plus', 'icon' => 'menu', 'sheet' => 'm-plus', 'on' => false, 'show' => true],
                ];
                break;
            case 'enseignant':
                $mItems = [
                    ['label' => 'Accueil', 'icon' => 'home', 'href' => $r('teacher.dashboard') ? route('teacher.dashboard') : null, 'on' => $on('teacher.dashboard'), 'show' => true],
                    ['label' => 'EDT', 'icon' => 'cal', 'href' => $r('teacher.timetable') ? route('teacher.timetable') : null, 'on' => $on('teacher.timetable'), 'show' => true],
                    ['label' => 'Appel', 'icon' => 'hand', 'href' => $r('teacher.dashboard') ? route('teacher.dashboard') . '#pending-roll-calls' : null, 'on' => false, 'show' => true],
                    ['label' => 'Notes', 'icon' => 'pen', 'href' => $r('teacher.grades') ? route('teacher.grades') : null, 'on' => $on('teacher.grades*'), 'show' => true],
                    ['label' => 'Profil', 'icon' => 'user', 'href' => $r('teacher.profile') ? route('teacher.profile') : null, 'on' => $on('teacher.profile*'), 'show' => true],
                ];
                break;
            case 'scolarite':
                // Qui ne voit pas les paiements a les classes en quatrieme onglet.
                $mScoPaiements = $mUser->canAny(['paiements.view', 'paiements.view_own']);
                $mItems = [
                    ['label' => 'Accueil', 'icon' => 'home', 'href' => $r('dashboard') ? route('dashboard') : null, 'on' => $on(['dashboard', 'dashboard.*']), 'show' => true],
                    ['label' => 'Inscriptions', 'icon' => 'file', 'href' => $r('esbtp.inscriptions.index') ? route('esbtp.inscriptions.index') : null, 'on' => $on('esbtp.inscriptions.*'), 'show' => $mUser->can('inscriptions.view')],
                    ['label' => 'Étudiants', 'icon' => 'users', 'href' => $r('esbtp.etudiants.index') ? route('esbtp.etudiants.index') : null, 'on' => $on('esbtp.etudiants.*'), 'show' => $mUser->can('students.view')],
                    ['label' => 'Paiements', 'icon' => 'cash', 'href' => $r('esbtp.paiements.index') ? route('esbtp.paiements.index') : null, 'on' => $on('esbtp.paiements.*'), 'show' => $mScoPaiements],
                    ['label' => 'Classes', 'icon' => 'grid', 'href' => $r('esbtp.classes.index') ? route('esbtp.classes.index') : null, 'on' => $on('esbtp.classes.*'), 'show' => !$mScoPaiements && $mUser->can('classes.view')],
                    ['label' => 'Plus', 'icon' => 'menu', 'sheet' => 'm-plus', 'on' => false, 'show' => true],
                ];
                break;
        }

        $mItems = array_values(array_filter($mItems, fn ($it) => $it['show'] && (isset($it['sheet']) || !empty($it['href']))));
    }

    $mHasPlus = collect($mItems)->contains(fn ($it) => isset($it['sheet']));
    // Pastille de l'onglet actif (une seule) : index posé en CSS pour que la première
    // image de la page soit déjà juste, et que la transition de vue la morphe.
    $mOnIndex = collect($mItems)->search(fn ($it) => (bool) $it['on']);
    $mOnIndex = $mOnIndex === false ? -1 : (int) $mOnIndex;
    $mCanSwitch = $mUser && $mUser->hasRole('superAdmin') && Route::has('mobile.profil');
    // Libellés courts pour l'affichage (la liste canonique vient du resolver).
    $mProfilLabels = ['caissier' => 'Caisse', 'comptable' => 'Comptabilité', 'etudiant' => 'Étudiant', 'enseignant' => 'Enseignant', 'scolarite' => 'Scolarité'];
    $mProfilLabels = array_intersect_key($mProfilLabels, array_flip(\App\Services\Mobile\MobileProfileResolver::PROFILS));
    $mRoleLabel = $mProfil ? ($mProfilLabels[$mProfil] ?? '') : '';
@endphp

@if($mUser && $mProfil && count($mItems))
    {{-- Onglets bas (mobile) --}}
    <nav class="m-bottomnav" aria-label="Navigation principale">
        <span class="m-bottomnav-pill {{ $mOnIndex < 0 ? 'is-hidden' : '' }}" aria-hidden="true" style="--m-pill-i: {{ max($mOnIndex, 0) }}; --m-pill-n: {{ count($mItems) }};"></span>
        @foreach($mItems as $it)
            @if(isset($it['sheet']))
                <button type="button"
                        aria-label="{{ $it['label'] }}"
                        aria-haspopup="dialog"
                        aria-controls="m-sheet-{{ $it['sheet'] }}"
                        onclick="window.dispatchEvent(new CustomEvent('m-sheet:open', { detail: { id: '{{ $it['sheet'] }}' } }))">
                    <x-m.icon :name="$it['icon']" />
                    {{ $it['label'] }}
                </button>
            @else
                <a href="{{ $it['href'] }}"
                   class="{{ $it['on'] ? 'on' : '' }}"
                   aria-label="{{ $it['label'] }}"
                   @if($it['on']) aria-current="page" @endif>
                    <x-m.icon :name="$it['icon']" />
                    {{ $it['label'] }}
                </a>
            @endif
        @endforeach
    </nav>

    {{-- Rail latéral (tablette 768–1023px) --}}
    <aside class="m-rail" aria-label="Navigation principale">
        {{-- La marque de l'application, pas l'initiale de l'école : sur tablette la barre
             latérale est un tiroir fermé, ce rail est le seul endroit où KLASSCI se lit. --}}
        <div class="logo" aria-hidden="true"><img src="{{ asset('images/LOGO-KLASSCI-PNG.png') }}" alt=""></div>
        <nav>
            @foreach($mItems as $it)
                @if(isset($it['sheet']))
                    <button type="button"
                            aria-label="{{ $it['label'] }}"
                            aria-haspopup="dialog"
                            aria-controls="m-sheet-{{ $it['sheet'] }}"
                            onclick="window.dispatchEvent(new CustomEvent('m-sheet:open', { detail: { id: '{{ $it['sheet'] }}' } }))">
                        <x-m.icon :name="$it['icon']" />
                        {{ $it['label'] }}
                    </button>
                @else
                    <a href="{{ $it['href'] }}" class="{{ $it['on'] ? 'on' : '' }}" @if($it['on']) aria-current="page" @endif>
                        <x-m.icon :name="$it['icon']" />
                        {{ $it['label'] }}
                    </a>
                @endif
            @endforeach
        </nav>
    </aside>

    {{-- Feuille « Plus » --}}
    @if($mHasPlus)
        <x-m.sheet id="m-plus" title="Plus" :sub="trim($mUser->name . ($mRoleLabel ? ' · ' . $mRoleLabel : ''))">
            <div class="m-menu">
                @if($mProfil === 'comptable')
                    @if(Route::has('esbtp.comptabilite.relances.index'))
                        @canany(['comptabilite.access', 'comptabilite.relances.send'])
                            <a href="{{ route('esbtp.comptabilite.relances.index') }}" class="{{ request()->routeIs('esbtp.comptabilite.relances.*') ? 'on' : '' }}">
                                <x-m.icon name="bell" />Relances<span class="ch"><x-m.icon name="chr" /></span>
                            </a>
                        @endcanany
                    @endif
                    @if(Route::has('esbtp.comptabilite.reconciliation.index'))
                        @can('comptabilite.reconciliation.view')
                            <a href="{{ route('esbtp.comptabilite.reconciliation.index') }}" class="{{ request()->routeIs('esbtp.comptabilite.reconciliation.*') ? 'on' : '' }}">
                                <x-m.icon name="scale" />Réconciliation<span class="ch"><x-m.icon name="chr" /></span>
                            </a>
                        @endcan
                    @endif
                    @if(Route::has('esbtp.comptabilite.analytics.index'))
                        @can('comptabilite.analytics.view')
                            <a href="{{ route('esbtp.comptabilite.analytics.index') }}" class="{{ request()->routeIs('esbtp.comptabilite.analytics.*') ? 'on' : '' }}">
                                <x-m.icon name="chart" />Analyses<span class="ch"><x-m.icon name="chr" /></span>
                            </a>
                        @endcan
                    @endif
                    @if(Route::has('esbtp.comptabilite.salaires.index'))
                        @can('comptabilite.salaires.view')
                            <a href="{{ route('esbtp.comptabilite.salaires.index') }}" class="{{ request()->routeIs('esbtp.comptabilite.salaires.*') ? 'on' : '' }}">
                                <x-m.icon name="users" />Paie<span class="ch"><x-m.icon name="chr" /></span>
                            </a>
                        @endcan
                    @endif
                    @if(Route::has('esbtp.frais.index'))
                        @can('frais.view')
                            <a href="{{ route('esbtp.frais.index') }}" class="{{ request()->routeIs('esbtp.frais.*') ? 'on' : '' }}">
                                <x-m.icon name="file" />Frais<span class="ch"><x-m.icon name="chr" /></span>
                            </a>
                        @endcan
                    @endif
                @elseif($mProfil === 'etudiant')
                    @if(Route::has('esbtp.mon-bulletin.index'))
                        @canany(['bulletins.view_own', 'bulletins.view'])
                            <a href="{{ route('esbtp.mon-bulletin.index') }}" class="{{ request()->routeIs('esbtp.mon-bulletin.*') ? 'on' : '' }}">
                                <x-m.icon name="file" />Mon bulletin<span class="ch"><x-m.icon name="chr" /></span>
                            </a>
                        @endcanany
                    @endif
                    @if(Route::has('esbtp.mes-paiements.index'))
                        @canany(['profile.view_own', 'notes.view_own'])
                            <a href="{{ route('esbtp.mes-paiements.index') }}" class="{{ request()->routeIs('esbtp.mes-paiements.*') ? 'on' : '' }}">
                                <x-m.icon name="wallet" />Mes paiements<span class="ch"><x-m.icon name="chr" /></span>
                            </a>
                        @endcanany
                    @endif
                    @if(Route::has('esbtp.mes-annonces.index'))
                        @can('annonces.view')
                            <a href="{{ route('esbtp.mes-annonces.index') }}" class="{{ request()->routeIs('esbtp.mes-annonces.*') ? 'on' : '' }}">
                                <x-m.icon name="msg" />Annonces<span class="ch"><x-m.icon name="chr" /></span>
                            </a>
                        @endcan
                    @endif
                    @if(Route::has('esbtp.mon-profil.index'))
                        @canany(['profile.view_own', 'students.view'])
                            <a href="{{ route('esbtp.mon-profil.index') }}" class="{{ request()->routeIs('esbtp.mon-profil.*') ? 'on' : '' }}">
                                <x-m.icon name="user" />Mon profil<span class="ch"><x-m.icon name="chr" /></span>
                            </a>
                        @endcanany
                    @endif
                    @if(Route::has('esbtp.preferences.index'))
                        <a href="{{ route('esbtp.preferences.index') }}" class="{{ request()->routeIs('esbtp.preferences.*') ? 'on' : '' }}">
                            <x-m.icon name="settings" />Préférences<span class="ch"><x-m.icon name="chr" /></span>
                        </a>
                    @endif
                @elseif($mProfil === 'scolarite')
                    @php
                        $mScoLiens = [
                            ['route' => 'esbtp.inscriptions.create', 'on' => 'esbtp.inscriptions.create', 'perm' => 'inscriptions.create', 'icon' => 'plus', 'label' => 'Nouvelle inscription'],
                            ['route' => 'esbtp.classes.index', 'on' => 'esbtp.classes.*', 'perm' => 'classes.view', 'icon' => 'grid', 'label' => 'Classes', 'sauf_onglet' => true],
                            ['route' => 'esbtp.filieres.index', 'on' => 'esbtp.filieres.*', 'perm' => 'filieres.view', 'icon' => 'book', 'label' => 'Filières'],
                            ['route' => 'esbtp.emploi-temps.index', 'on' => 'esbtp.emploi-temps.*', 'perm' => 'timetables.view', 'icon' => 'cal', 'label' => 'Emplois du temps'],
                            ['route' => 'esbtp.evaluations.index', 'on' => 'esbtp.evaluations.*', 'perm' => 'evaluations.view', 'icon' => 'pen', 'label' => 'Évaluations'],
                            ['route' => 'esbtp.notes.index', 'on' => 'esbtp.notes.*', 'perm' => 'notes.view', 'icon' => 'list', 'label' => 'Notes'],
                            ['route' => 'esbtp.bulletins.index', 'on' => 'esbtp.bulletins.*', 'perm' => 'bulletins.view', 'icon' => 'file', 'label' => 'Bulletins'],
                            ['route' => 'esbtp.annonces.index', 'on' => 'esbtp.annonces.*', 'perm' => 'annonces.view', 'icon' => 'msg', 'label' => 'Annonces'],
                        ];
                        $mScoOngletClasses = collect($mItems)->contains(fn ($it) => ($it['label'] ?? '') === 'Classes');
                    @endphp
                    @foreach($mScoLiens as $lien)
                        @continue(!Route::has($lien['route']) || !$mUser->can($lien['perm']) || (($lien['sauf_onglet'] ?? false) && $mScoOngletClasses))
                        <a href="{{ route($lien['route']) }}" class="{{ request()->routeIs($lien['on']) ? 'on' : '' }}">
                            <x-m.icon :name="$lien['icon']" />{{ $lien['label'] }}<span class="ch"><x-m.icon name="chr" /></span>
                        </a>
                    @endforeach
                    <button type="button" x-on:click="hide(); document.getElementById('sidebar-toggle')?.click();">
                        <x-m.icon name="menu" />Tout le menu<span class="ch"><x-m.icon name="chr" /></span>
                    </button>
                @endif

                @if(Route::has('logout'))
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit">
                            <x-m.icon name="logout" />Se déconnecter<span class="ch"><x-m.icon name="chr" /></span>
                        </button>
                    </form>
                @endif
            </div>

            @if($mCanSwitch)
                {{-- Outil de démonstration (superAdmin) : basculer le profil mobile --}}
                <form method="POST" action="{{ route('mobile.profil') }}" class="m-field">
                    @csrf
                    <label for="m-profil-switch">Voir l’application comme</label>
                    <div class="m-seg" id="m-profil-switch" role="group" aria-label="Profil mobile">
                        @foreach($mProfilLabels as $key => $label)
                            <button type="submit" name="profil" value="{{ $key }}" class="{{ $mProfil === $key ? 'on' : '' }}" @if($mProfil === $key) aria-pressed="true" @endif>{{ $label }}</button>
                        @endforeach
                    </div>
                </form>
            @endif
        </x-m.sheet>
    @endif
@endif
