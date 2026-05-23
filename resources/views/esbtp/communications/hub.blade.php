@extends('layouts.app')

@section('title', 'Hub Communications - KLASSCI')

@push('styles')
<style>
/* Namespace ch-* — Phase 13 Plan v4 Hub Communications */
.ch-hero {
    background: linear-gradient(135deg, #0a3d8f 0%, #0453cb 40%, #3b7ddb 100%);
    border-radius: 18px;
    padding: 2rem 2.5rem 1.5rem;
    color: #fff;
    margin-bottom: 1.25rem;
    box-shadow: 0 8px 30px rgba(4, 83, 203, .18);
}
.ch-hero-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
}
.ch-hero-left {
    display: flex;
    align-items: center;
    gap: 1rem;
}
.ch-hero-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: rgba(255, 255, 255, .12);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, .15);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
    flex-shrink: 0;
    color: #fff;
}
.ch-hero h1 {
    font-size: 1.45rem;
    font-weight: 700;
    color: #fff;
    margin: 0;
}
.ch-hero p {
    color: rgba(255, 255, 255, .7);
    font-size: .88rem;
    margin: 0;
}
.ch-kpis {
    display: flex;
    gap: .75rem;
    margin-top: 1.5rem;
    flex-wrap: wrap;
}
.ch-kpi {
    flex: 1;
    min-width: 140px;
    background: rgba(255, 255, 255, .1);
    border: 1px solid rgba(255, 255, 255, .15);
    border-radius: 12px;
    padding: .9rem 1rem;
    display: flex;
    align-items: center;
    gap: .75rem;
}
.ch-kpi-icon {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    background: rgba(255, 255, 255, .15);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 1rem;
    flex-shrink: 0;
}
.ch-kpi-value {
    font-size: 1.35rem;
    font-weight: 700;
    color: #fff;
    line-height: 1;
}
.ch-kpi-label {
    font-size: .72rem;
    color: rgba(255, 255, 255, .65);
    margin-top: .25rem;
}

/* Tabs nav */
.ch-tabs {
    display: flex;
    gap: .5rem;
    border-bottom: 2px solid #e2e8f0;
    margin-bottom: 1.25rem;
    overflow-x: auto;
    padding-bottom: 0;
}
.ch-tab {
    padding: .75rem 1.25rem;
    border: none;
    background: transparent;
    color: #64748b;
    font-weight: 600;
    font-size: .9rem;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    transition: all .2s ease;
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    white-space: nowrap;
}
.ch-tab:hover:not(.ch-tab--active) {
    background: rgba(4, 83, 203, .04);
    color: #0453cb;
}
.ch-tab--active {
    color: #0453cb;
    border-bottom-color: #0453cb;
}
.ch-tab-badge {
    background: rgba(4, 83, 203, .1);
    color: #0453cb;
    border-radius: 99px;
    padding: .1rem .55rem;
    font-size: .72rem;
    font-weight: 700;
}
.ch-tab--active .ch-tab-badge {
    background: #0453cb;
    color: #fff;
}

/* Tab panels */
.ch-panel { display: none; }
.ch-panel--active { display: block; }
.ch-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(15, 23, 42, .04), 0 1px 2px rgba(15, 23, 42, .06);
}
.ch-empty {
    text-align: center;
    padding: 3rem 1rem;
    color: #94a3b8;
}
.ch-empty i {
    font-size: 2.5rem;
    margin-bottom: 1rem;
    display: block;
    opacity: .5;
}
.ch-empty-link {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    margin-top: 1rem;
    color: #0453cb;
    font-weight: 600;
    text-decoration: none;
    padding: .55rem 1.25rem;
    border: 1px solid #0453cb;
    border-radius: 8px;
    transition: all .15s;
}
.ch-empty-link:hover {
    background: #0453cb;
    color: #fff;
}
</style>
@endpush

@section('content')
<div class="container-fluid" x-data="communicationsHub()" x-init="init()">
    {{-- Hero --}}
    <div class="ch-hero">
        <div class="ch-hero-top">
            <div class="ch-hero-left">
                <div class="ch-hero-icon"><i class="fas fa-comments"></i></div>
                <div>
                    <h1>Hub Communications</h1>
                    <p>Vue unifiée des annonces, messages app, WhatsApp inbox et chatbot IA</p>
                </div>
            </div>
        </div>
        <div class="ch-kpis">
            <div class="ch-kpi">
                <div class="ch-kpi-icon"><i class="fas fa-bullhorn"></i></div>
                <div>
                    <div class="ch-kpi-value">{{ $kpis['annonces_recent'] }}</div>
                    <div class="ch-kpi-label">Annonces (7j)</div>
                </div>
            </div>
            <div class="ch-kpi">
                <div class="ch-kpi-icon"><i class="fas fa-bell"></i></div>
                <div>
                    <div class="ch-kpi-value">{{ $kpis['messages_unread'] }}</div>
                    <div class="ch-kpi-label">Messages non lus</div>
                </div>
            </div>
            <div class="ch-kpi">
                <div class="ch-kpi-icon"><i class="fab fa-whatsapp"></i></div>
                <div>
                    <div class="ch-kpi-value">{{ $kpis['whatsapp_unread'] }}</div>
                    <div class="ch-kpi-label">WhatsApp non lus</div>
                </div>
            </div>
            <div class="ch-kpi">
                <div class="ch-kpi-icon"><i class="fas fa-robot"></i></div>
                <div>
                    <div class="ch-kpi-value">{{ $kpis['chatbot_pending'] }}</div>
                    <div class="ch-kpi-label">Chatbot à valider</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabs nav --}}
    <div class="ch-tabs" role="tablist">
        @if ($perms['annonces'])
            <button class="ch-tab" :class="tab === 'annonces' ? 'ch-tab--active' : ''" @click="tab = 'annonces'" type="button">
                <i class="fas fa-bullhorn"></i> Annonces
                @if ($kpis['annonces_recent'] > 0)
                    <span class="ch-tab-badge">{{ $kpis['annonces_recent'] }}</span>
                @endif
            </button>
        @endif
        @if ($perms['messages'])
            <button class="ch-tab" :class="tab === 'messages' ? 'ch-tab--active' : ''" @click="tab = 'messages'" type="button">
                <i class="fas fa-bell"></i> Messages
                @if ($kpis['messages_unread'] > 0)
                    <span class="ch-tab-badge">{{ $kpis['messages_unread'] }}</span>
                @endif
            </button>
        @endif
        @if ($perms['whatsapp_inbox'])
            <button class="ch-tab" :class="tab === 'whatsapp' ? 'ch-tab--active' : ''" @click="tab = 'whatsapp'" type="button">
                <i class="fab fa-whatsapp"></i> WhatsApp
                @if ($kpis['whatsapp_unread'] > 0)
                    <span class="ch-tab-badge">{{ $kpis['whatsapp_unread'] }}</span>
                @endif
            </button>
        @endif
        @if ($perms['chatbot_review'])
            <button class="ch-tab" :class="tab === 'chatbot' ? 'ch-tab--active' : ''" @click="tab = 'chatbot'" type="button">
                <i class="fas fa-robot"></i> Chatbot
                @if ($kpis['chatbot_pending'] > 0)
                    <span class="ch-tab-badge">{{ $kpis['chatbot_pending'] }}</span>
                @endif
            </button>
        @endif
    </div>

    {{-- Tab panels --}}
    @if ($perms['annonces'])
        <div class="ch-panel" :class="tab === 'annonces' ? 'ch-panel--active' : ''">
            <div class="ch-card">
                <div class="ch-empty">
                    <i class="fas fa-bullhorn"></i>
                    <div>Module Annonces — accédez à la liste complète</div>
                    <a href="{{ route('esbtp.annonces.index') }}" class="ch-empty-link">
                        <i class="fas fa-arrow-right"></i>Voir toutes les annonces
                    </a>
                </div>
            </div>
        </div>
    @endif

    @if ($perms['messages'])
        <div class="ch-panel" :class="tab === 'messages' ? 'ch-panel--active' : ''">
            <div class="ch-card">
                <div class="ch-empty">
                    <i class="fas fa-bell"></i>
                    <div>Messages internes et notifications app</div>
                    <a href="{{ route('notifications.index') }}" class="ch-empty-link">
                        <i class="fas fa-arrow-right"></i>Voir toutes les notifications
                    </a>
                </div>
            </div>
        </div>
    @endif

    @if ($perms['whatsapp_inbox'])
        <div class="ch-panel" :class="tab === 'whatsapp' ? 'ch-panel--active' : ''">
            <div class="ch-card">
                <div class="ch-empty">
                    <i class="fab fa-whatsapp"></i>
                    <div>Inbox WhatsApp — chat 2-way avec les parents</div>
                    <p style="font-size:.85rem; color:#64748b; margin-top:.5rem;">
                        L'inbox WhatsApp sera disponible après activation Meta KYC du tenant.
                        Statut credentials WhatsApp : à vérifier dans adminKlassci.
                    </p>
                </div>
            </div>
        </div>
    @endif

    @if ($perms['chatbot_review'])
        <div class="ch-panel" :class="tab === 'chatbot' ? 'ch-panel--active' : ''">
            <div class="ch-card">
                <div class="ch-empty">
                    <i class="fas fa-robot"></i>
                    <div>Queue de modération IA (Gemini)</div>
                    <p style="font-size:.85rem; color:#64748b; margin-top:.5rem;">
                        Les réponses IA en attente de validation humaine s'afficheront ici.
                        Activable via setting tenant `whatsapp.chatbot.enabled`.
                    </p>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
function communicationsHub() {
    return {
        tab: @json($perms['whatsapp_inbox'] ? 'whatsapp' : ($perms['annonces'] ? 'annonces' : 'messages')),
        init() {
            // Persistence tab via URL hash (réutilisable au refresh)
            const hash = window.location.hash.replace('#', '');
            if (['annonces', 'messages', 'whatsapp', 'chatbot'].includes(hash)) {
                this.tab = hash;
            }
            this.$watch('tab', (newVal) => {
                window.history.replaceState(null, '', '#' + newVal);
            });
        }
    };
}
</script>
@endsection
