@extends('layouts.app')

@section('title')
    @yield('title', 'Dashboard Financier')
@endsection

@push('styles')
<link href="{{ asset('css/dashboard-moderne.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="dashboard-acasi">
    {{-- Sidebar Gauche --}}
    <aside class="sidebar-left">
        <div class="logo">Comptabilité</div>
        <nav class="navigation">
            <ul class="navigation-menu">
                @yield('sidebar')
            </ul>
        </nav>
    </aside>

    {{-- Contenu Principal --}}
    <main class="main-content">
        {{-- Header --}}
        <header class="dashboard-header">
            @yield('header')
        </header>
        {{-- Contenu principal --}}
        <section class="dashboard-content">
            @yield('content-block')
        </section>
    </main>

    {{-- Sidebar Droite --}}
    <aside class="sidebar-right">
        @yield('sidebarRight')
    </aside>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@yield('scripts')
@endpush
