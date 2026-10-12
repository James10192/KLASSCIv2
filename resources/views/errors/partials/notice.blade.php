<style>
    .en-card { max-width: 520px; margin: 1rem auto; border: 1px solid #e2e8f0; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 16px 42px rgba(4,83,203,.09); }
    .en-head { background: linear-gradient(135deg,#0a3d8f,#0453cb,#3b7ddb); text-align: center; padding: 2.25rem 1.5rem; color: #fff; }
    .en-head .en-code { font-size: 3.25rem; line-height: 1; font-weight: 800; letter-spacing: -.05em; margin-bottom: 10px; }
    .en-head h1 { font-size: 1.4rem; font-weight: 700; margin: 0 0 6px; color: #fff; }
    .en-head p { color: #dbeafe; font-size: .91rem; margin: 0; }
    .en-body { padding: 2rem; text-align: center; }
    .en-body p { color: #64748b; font-size: .9rem; line-height: 1.6; margin: 0 0 1.6rem; }
    .en-actions { display: flex; justify-content: center; flex-wrap: wrap; gap: .65rem; }
    .en-btn { border-radius: 8px; border: 1px solid #0453cb; padding: .8rem 1.1rem; background: #0453cb; color: #fff; text-decoration: none; font-weight: 600; font-size: .87rem; }
    .en-btn:hover { background: #0341a0; color: #fff; }
    .en-btn--outline { background: #fff; color: #0453cb; }
    .en-btn--outline:hover { background: #eff6ff; color: #0341a0; }
    .en-foot { padding: .9rem 1rem; border-top: 1px solid #e2e8f0; text-align: center; font-size: .74rem; color: #94a3b8; }
    @media(max-width: 480px) { .en-actions { flex-direction: column; } .en-body { padding: 1.3rem; } }
</style>
<div class="en-card" role="main">
    <header class="en-head">
        <div class="en-code">{{ $code }}</div>
        <h1>{{ $heading }}</h1>
        <p>KLASSCI · Votre espace numérique</p>
    </header>
    <div class="en-body">
        <p>{{ $message }}</p>
        <div class="en-actions">
            <a class="en-btn" href="{{ $primaryUrl }}">{{ $primaryLabel }}</a>
            <a class="en-btn en-btn--outline" href="{{ route('login') }}">Retour à la connexion</a>
        </div>
    </div>
    <footer class="en-foot">Un problème persistant ? Contactez votre établissement ou le support KLASSCI.</footer>
</div>
