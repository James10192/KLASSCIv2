{{--
    Bottom navigation mobile — espace ETUDIANT (PWA).
    Visible uniquement sur mobile (<768px) via .stu-bottomnav (mobile-student.css).
    Inclus dans layouts.app sous condition @role('etudiant') :
    le superAdmin a toutes les permissions via Gate::before, donc on gate par ROLE
    pour que cet element reste strictement etudiant.
--}}
<nav class="stu-bottomnav" aria-label="Navigation etudiant">
    <a href="{{ route('dashboard') }}"
       class="stu-bottomnav__item {{ request()->routeIs('dashboard') ? 'is-active' : '' }}"
       aria-label="Accueil"
       @if(request()->routeIs('dashboard')) aria-current="page" @endif>
        <i class="fas fa-house" aria-hidden="true"></i>
        <span>Accueil</span>
    </a>

    <a href="{{ route('esbtp.mes-notes.index') }}"
       class="stu-bottomnav__item {{ request()->routeIs('esbtp.mes-notes.*') ? 'is-active' : '' }}"
       aria-label="Mes notes"
       @if(request()->routeIs('esbtp.mes-notes.*')) aria-current="page" @endif>
        <i class="fas fa-list-check" aria-hidden="true"></i>
        <span>Notes</span>
    </a>

    <a href="{{ route('esbtp.mon-emploi-temps.index') }}"
       class="stu-bottomnav__item {{ request()->routeIs('esbtp.mon-emploi-temps.*') ? 'is-active' : '' }}"
       aria-label="Emploi du temps"
       @if(request()->routeIs('esbtp.mon-emploi-temps.*')) aria-current="page" @endif>
        <i class="fas fa-calendar-days" aria-hidden="true"></i>
        <span>EDT</span>
    </a>

    <a href="{{ route('esbtp.mon-bulletin.index') }}"
       class="stu-bottomnav__item {{ request()->routeIs('esbtp.mon-bulletin.*') ? 'is-active' : '' }}"
       aria-label="Mon bulletin"
       @if(request()->routeIs('esbtp.mon-bulletin.*')) aria-current="page" @endif>
        <i class="fas fa-file-lines" aria-hidden="true"></i>
        <span>Bulletin</span>
    </a>

    <a href="{{ route('esbtp.mon-profil.index') }}"
       class="stu-bottomnav__item {{ request()->routeIs('esbtp.mon-profil.*') ? 'is-active' : '' }}"
       aria-label="Mon profil"
       @if(request()->routeIs('esbtp.mon-profil.*')) aria-current="page" @endif>
        <i class="fas fa-user" aria-hidden="true"></i>
        <span>Profil</span>
    </a>
</nav>
