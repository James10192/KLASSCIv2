{{--
    Éditeur de texte riche (Summernote, version « lite » : sans Bootstrap 4).

    Usage : @include('partials.editeur-riche') dans la page, puis
    data-editeur-riche sur chaque <textarea> concernée. Le contenu est nettoyé
    côté serveur par App\Casts\TexteRicheCast : l'éditeur ne propose que ce que
    le nettoyeur garde (titres, gras, italique, souligné, listes, liens,
    citations), pour que rien ne disparaisse à l'enregistrement sans prévenir.

    Pré-remplir avec App\Support\TexteRiche::pourEditeur() : un ancien texte
    brut devient des paragraphes au lieu d'une seule ligne.
--}}
@once
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/summernote@0.9.1/dist/summernote-lite.min.css">
<style>
    .note-editor.note-frame {
        border: 1px solid #e2e8f0 !important;
        border-radius: 10px !important;
        overflow: hidden;
        box-shadow: none;
        background: #fff;
    }
    .note-editor.note-frame:focus-within { border-color: #0453cb !important; box-shadow: 0 0 0 3px rgba(4, 83, 203, .12); }
    .note-editor .note-toolbar {
        background: #f8fafc !important;
        border-bottom: 1px solid #e2e8f0 !important;
        padding: 6px 8px !important;
    }
    .note-editor .note-toolbar .note-btn {
        border: 1px solid transparent !important;
        border-radius: 7px !important;
        background: transparent !important;
        color: #475569 !important;
        padding: 4px 8px !important;
        box-shadow: none !important;
    }
    .note-editor .note-toolbar .note-btn:hover,
    .note-editor .note-toolbar .note-btn.active { background: rgba(4, 83, 203, .08) !important; color: #0453cb !important; }
    .note-editor .note-editing-area .note-editable {
        padding: 12px 14px !important;
        color: #1e293b;
        font-size: 14px;
        line-height: 1.6;
    }
    .note-editor .note-editable h3 { font-size: 1.15rem; font-weight: 700; margin: .75rem 0 .35rem; }
    .note-editor .note-editable h4 { font-size: 1rem; font-weight: 700; margin: .65rem 0 .3rem; }
    .note-editor .note-editable blockquote { border-left: 3px solid #0453cb; padding-left: .75rem; color: #475569; }
    .note-editor .note-statusbar { background: #f8fafc !important; border-top: 1px solid #eef2f7 !important; }
    .note-editor .note-placeholder { color: #94a3b8; padding: 12px 14px !important; }
    .note-modal .note-modal-content { border-radius: 14px; border: 1px solid #e2e8f0; }
    .is-invalid + .note-editor.note-frame { border-color: #dc2626 !important; }
</style>
@endpush
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/summernote@0.9.1/dist/summernote-lite.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.9.1/dist/lang/summernote-fr-FR.min.js"></script>
<script>
    (function () {
        function demarrer() {
            if (!window.jQuery || !jQuery.fn.summernote) {
                // CDN injoignable : le champ reste une zone de texte, qui s'enregistre normalement.
                console.warn('Éditeur riche indisponible : la description reste en texte simple.');
                return;
            }
            jQuery('textarea[data-editeur-riche]').each(function () {
                var zone = this;
                jQuery(zone).summernote({
                    lang: 'fr-FR',
                    height: 220,
                    placeholder: zone.getAttribute('placeholder') || '',
                    disableDragAndDrop: true,
                    styleTags: ['p', 'h3', 'h4', 'blockquote'],
                    toolbar: [
                        ['style', ['style']],
                        ['font', ['bold', 'italic', 'underline', 'clear']],
                        ['para', ['ul', 'ol']],
                        ['insert', ['link']],
                        ['view', ['fullscreen']]
                    ],
                    callbacks: {
                        // La zone de texte reste ce que le formulaire envoie.
                        onChange: function (contenu) { zone.value = contenu; },
                        // Un collage depuis Word ou une page web garde son texte, pas ses styles.
                        onPaste: function (ev) {
                            var presse = (ev.originalEvent || ev).clipboardData;
                            if (!presse) return;
                            ev.preventDefault();
                            document.execCommand('insertText', false, presse.getData('text/plain'));
                        }
                    }
                });
            });
        }
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', demarrer);
        else demarrer();
    })();
</script>
@endpush
@endonce
