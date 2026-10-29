<!-- Add this section to the sidebar menu in app.blade.php -->
<div class="menu-category">ESBTP-YAKRO</div>
<li class="nav-item">
    <a href="{{ route('esbtp.cycles.index') }}" class="nav-link {{ request()->routeIs('esbtp.cycles.*') ? 'active' : '' }}">
        <i class="fas fa-sync-alt nav-icon"></i>
        <span>Cycles de Formation</span>
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('esbtp.specialties.index') }}" class="nav-link {{ request()->routeIs('esbtp.specialties.*') ? 'active' : '' }}">
        <i class="fas fa-book nav-icon"></i>
        <span>Spécialités</span>
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('esbtp.partnerships.index') }}" class="nav-link {{ request()->routeIs('esbtp.partnerships.*') ? 'active' : '' }}">
        <i class="fas fa-handshake nav-icon"></i>
        <span>Partenariats</span>
    </a>
</li>
<li class="nav-item">
    <a href="{{ route('esbtp.continuing-education.index') }}" class="nav-link {{ request()->routeIs('esbtp.continuing-education.*') ? 'active' : '' }}">
        <i class="fas fa-user-tie nav-icon"></i>
        <span>Formation Continue</span>
    </a>
</li>
<!-- End of section to add --> 