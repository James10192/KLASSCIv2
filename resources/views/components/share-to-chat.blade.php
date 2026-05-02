@props([
    'kind',           // 'inscription' | 'paiement'
    'id',             // ID de la ressource
    'label' => 'Envoyer dans un message',
    'class' => 'is-hero-btn',
])

@php
    $widgetId = 'shareToChat_' . $kind . '_' . $id;
    $modalId = $widgetId . '_modal';
    $shareUrl = $kind === 'paiement'
        ? route('chat.share.paiement', $id)
        : route('chat.share.inscription', $id);
@endphp

@once
    @push('scripts')
        <script src="{{ asset('js/inscriptions/common.js') }}"></script>
    @endpush
@endonce

<button type="button" class="{{ $class }}" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
    <i class="fas fa-share-alt"></i>
    <span class="d-none d-md-inline">{{ $label }}</span>
</button>

<div class="modal fade ms-modal" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" x-data="shareToChat_{{ $kind }}_{{ $id }}()" x-init="init()">
            <div class="modal-header">
                <div class="ms-modal-icon"><i class="fas fa-share-alt"></i></div>
                <div class="ms-modal-title-block">
                    <h5>Envoyer cette {{ $kind === 'paiement' ? 'card paiement' : 'card inscription' }}</h5>
                    <p>Choisis un destinataire — il verra un CTA contextuel selon ses permissions.</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <div class="ms-search-wrap">
                    <i class="fas fa-search"></i>
                    <input type="text" class="ms-modal-search-input"
                           x-model="query"
                           x-ref="searchInput"
                           @input.debounce.250="searchUsers()"
                           placeholder="Tape un nom ou email…"
                           aria-label="Rechercher un destinataire">
                </div>
                <div class="mb-3" style="margin-top: 1rem;">
                    <textarea class="form-control"
                              x-model="note"
                              rows="2"
                              maxlength="500"
                              placeholder="Note (optionnelle)"
                              style="border-radius: 10px; resize: vertical;"></textarea>
                </div>
                <div class="ms-modal-results">
                    <template x-for="u in results" :key="u.id">
                        <div class="ms-search-result" @click="send(u)" role="button" tabindex="0" @keydown.enter="send(u)">
                            <div class="ms-search-avatar" x-text="(u.name || '?').slice(0,2).toUpperCase()"></div>
                            <div class="ms-search-info">
                                <strong x-text="u.name"></strong>
                                <small x-text="u.email"></small>
                            </div>
                            <i class="fas fa-paper-plane ms-search-result-arrow"></i>
                        </div>
                    </template>
                    <div class="ms-modal-empty" x-show="query.length >= 2 && results.length === 0">
                        <i class="far fa-frown"></i>
                        <div class="ms-empty-title">Aucun résultat</div>
                        <div class="ms-empty-hint">Essaie un autre nom ou email.</div>
                    </div>
                    <div class="ms-modal-empty" x-show="query.length < 2">
                        <i class="far fa-user-circle"></i>
                        <div class="ms-empty-title">Tape au moins 2 caractères</div>
                        <div class="ms-empty-hint">La recherche couvre les utilisateurs actifs.</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid var(--ms-border, #e2e8f0); background: #f8fafc; padding: .9rem 1.75rem;">
                <span style="font-size:.78rem; color:#64748b;">
                    <i class="fas fa-info-circle me-1"></i>
                    Le destinataire reçoit la card avec le CTA correspondant à ses permissions.
                </span>
            </div>
        </div>
    </div>
</div>

<script>
function shareToChat_{{ $kind }}_{{ $id }}() {
    return {
        query: '',
        results: [],
        note: '',
        sending: false,
        csrf: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),

        init() {
            const el = document.getElementById('{{ $modalId }}');
            el.addEventListener('shown.bs.modal', () => this.$refs.searchInput?.focus());
        },

        async searchUsers() {
            if (this.query.length < 2) { this.results = []; return; }
            try {
                const r = await fetch(`/messages/users/search?q=${encodeURIComponent(this.query)}`, { headers: { Accept: 'application/json' } });
                const data = await r.json();
                this.results = data.users || [];
            } catch (e) { this.results = []; }
        },

        async send(user) {
            if (this.sending) return;
            this.sending = true;
            try {
                const r = await fetch(@json($shareUrl), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrf, Accept: 'application/json' },
                    body: JSON.stringify({ recipient_id: user.id, note: this.note || null }),
                });
                if (!r.ok) throw new Error('Share failed');
                const data = await r.json();
                window.location.href = data.redirect || @json(route('chat.index'));
            } catch (e) {
                window.showToast?.('Le partage a échoué. Réessaie.', 'error');
            } finally {
                this.sending = false;
            }
        },
    };
}
</script>
