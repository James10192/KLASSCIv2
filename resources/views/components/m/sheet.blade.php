{{--
    Feuille montante (bottom sheet) du shell mobile — modale centrée dès 768px.
    Alpine : Alpine.data('mSheet') enregistré par public/js/mobile-shell.js.
    Balise : x-m.sheet — ex. id="actions-paiement" title="Actions" sub="Reçu N° 2026-09-00417"
      slot : un bloc .m-menu, des .m-field, un bouton .m-btn (x-on:click="hide()" pour fermer).
    Ouvrir depuis n'importe où :
      window.dispatchEvent(new CustomEvent('m-sheet:open', { detail: { id: 'actions-paiement' } }))
      ou window.__mShell.openSheet('actions-paiement')
    Dans le slot, `hide()` / `show()` / `open` sont disponibles (portée Alpine).
    Fermeture : glisser vers le bas (80px), Escape, clic sur le voile, bouton fermer.
    Props :
      id      string   identifiant unique sur la page (obligatoire)
      title   string   titre (h4) ; null = pas d'en-tête
      sub     string   sous-titre
      close   bool     affiche un bouton « Fermer » discret en haut à droite (défaut : true)
--}}
@props([
    'id',
    'title' => null,
    'sub' => null,
    'close' => true,
])
@php
    $sheetId = 'm-sheet-' . $id;
    $titleId = $sheetId . '-title';
@endphp
<div x-data="mSheet(@js($id))" data-m-sheet="{{ $id }}" {{ $attributes->except(['class']) }}>
    <template x-teleport="body">
        <div class="m-sheet-root" id="{{ $sheetId }}" x-show="open" x-cloak>
            <div class="m-scrim" x-on:click="hide()" aria-hidden="true"></div>
            <section class="m-sheet {{ $attributes->get('class') }}"
                     role="dialog"
                     aria-modal="true"
                     @if($title) aria-labelledby="{{ $titleId }}" @else aria-label="Fenêtre" @endif
                     tabindex="-1"
                     x-ref="panel"
                     x-bind:class="dragging ? 'is-dragging' : ''"
                     x-bind:style="dragStyle"
                     x-on:keydown="onKeydown($event)"
                     x-on:pointerdown="onPointerDown($event)"
                     x-on:pointermove="onPointerMove($event)"
                     x-on:pointerup="onPointerUp($event)"
                     x-on:pointercancel="onPointerUp($event)">
                <div class="m-sheet-drag">
                    <div class="handle" aria-hidden="true"></div>
                    @if($title || $close)
                        <div class="m-sheet-head">
                            @if($title)
                                <h4 id="{{ $titleId }}">{{ $title }}</h4>
                            @endif
                            @if($close)
                                <button type="button" class="m-ib ghost m-sheet-close" x-on:click="hide()" aria-label="Fermer">
                                    <x-m.icon name="x" />
                                </button>
                            @endif
                        </div>
                    @endif
                    @if($sub)
                        <p class="sub">{{ $sub }}</p>
                    @endif
                </div>
                {{ $slot }}
            </section>
        </div>
    </template>
</div>
