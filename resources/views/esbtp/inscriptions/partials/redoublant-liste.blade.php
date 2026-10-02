{{-- Statut redoublant dans la liste : badge de ligne et action groupée. --}}
<style>
.ii-redoublant {
    display: inline-flex; align-items: center; gap: 4px;
    font-size: .66rem; font-weight: 700; color: #0453cb;
    background: rgba(4,83,203,.08); border: 1px solid rgba(4,83,203,.22);
    padding: 1px 7px; border-radius: 999px; white-space: nowrap; margin-left: 4px;
}
.ii-redoublant--a-confirmer { background: transparent; border-style: dashed; }
</style>
<script src="{{ asset('js/inscriptions/redoublant.js') }}?v={{ @filemtime(public_path('js/inscriptions/redoublant.js')) ?: '1' }}" defer></script>
