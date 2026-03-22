<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KLASSCI — Gestion scolaire, repensée.</title>
    <meta name="description" content="KLASSCI digitalise la gestion de votre établissement scolaire. Notes, emplois du temps, paiements, bulletins — un seul outil, zéro paperasse.">

    <!-- SEO -->
    <meta name="keywords" content="gestion scolaire, logiciel école, KLASSCI, notes, bulletins, emploi du temps, paiements, présences, SaaS éducation, Côte d'Ivoire, Afrique">
    <meta name="author" content="African Digital Consulting">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url('/') }}">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:title" content="KLASSCI — Gestion scolaire, repensée.">
    <meta property="og:description" content="KLASSCI digitalise la gestion de votre établissement. Notes, emplois du temps, paiements, bulletins — un seul outil, zéro paperasse.">
    <meta property="og:image" content="{{ asset('images/landing/hero_section.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:site_name" content="KLASSCI">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="KLASSCI — Gestion scolaire, repensée.">
    <meta name="twitter:description" content="KLASSCI digitalise la gestion de votre établissement. Notes, emplois du temps, paiements, bulletins — un seul outil, zéro paperasse.">
    <meta name="twitter:image" content="{{ asset('images/landing/hero_section.png') }}">

    <!-- Favicon variants -->
    <link rel="icon" href="{{ asset('images/LOGO-KLASSCI-PNG.png') }}" type="image/png">
    <link rel="apple-touch-icon" href="{{ asset('images/LOGO-KLASSCI-PNG.png') }}">

    <!-- Theme color -->
    <meta name="theme-color" content="#0453cb">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=IBM+Plex+Mono:wght@400;500&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
    /* ────────────────────────────────────
       KLASSCI — Editorial Landing
       Inspired by zed.dev : warm, serif
       headings, monospace accents, grain,
       generous space, real images.
    ──────────────────────────────────── */

    :root {
        --bg: #f6f4f0;
        --bg-alt: #edeae4;
        --bg-card: #fff;
        --text: #1a1a1a;
        --text-secondary: #5c5c5c;
        --text-muted: #8a8a8a;
        --accent: #0453cb;
        --accent-hover: #0340a0;
        --border: rgba(0,0,0,0.08);
        --border-strong: rgba(0,0,0,0.14);
        --radius: 6px;
        --max-w: 1120px;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    html { scroll-behavior: smooth; }

    body {
        font-family: 'DM Sans', system-ui, sans-serif;
        background: var(--bg);
        color: var(--text);
        line-height: 1.6;
        overflow-x: hidden;
        -webkit-font-smoothing: antialiased;
        position: relative;
    }

    /* Noise texture overlay — like zed.dev */
    body::before {
        content: '';
        position: fixed;
        inset: 0;
        z-index: 9999;
        pointer-events: none;
        opacity: 0.35;
        background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.06'/%3E%3C/svg%3E");
        background-repeat: repeat;
        background-size: 256px 256px;
    }

    /* Dot grid background — like zed.dev's subtle grid */
    body::after {
        content: '';
        position: fixed;
        inset: 0;
        z-index: -1;
        pointer-events: none;
        background-image: radial-gradient(circle, rgba(0,0,0,0.06) 1px, transparent 1px);
        background-size: 24px 24px;
    }

    @media (prefers-reduced-motion: reduce) {
        *, *::before, *::after {
            animation-duration: 0.01ms !important;
            transition-duration: 0.01ms !important;
        }
    }

    a { text-decoration: none; color: var(--accent); transition: color 0.2s; }
    a:hover { color: var(--accent-hover); }

    img { max-width: 100%; display: block; }

    .container {
        max-width: var(--max-w);
        margin: 0 auto;
        padding: 0 clamp(1.25rem, 5vw, 2.5rem);
    }

    /* ─── Typography ─── */
    h1, h2, h3 {
        font-family: 'Instrument Serif', Georgia, serif;
        font-weight: 400;
        color: var(--accent);
        line-height: 1.15;
    }

    .mono {
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.85rem;
        letter-spacing: -0.01em;
    }

    /* ═══════════════════════
       NAV
    ═══════════════════════ */
    .nav {
        position: fixed;
        top: 0; left: 0; right: 0;
        z-index: 100;
        height: 57px;
        display: flex;
        align-items: center;
        background: rgba(246,244,240,0.8);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border-bottom: 1px solid var(--border);
    }

    .nav-inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
    }

    .nav-logo {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        color: var(--text);
    }

    .nav-logo img { height: 28px; }

    .nav-logo span {
        font-family: 'DM Sans', sans-serif;
        font-weight: 700;
        font-size: 1.05rem;
        letter-spacing: -0.02em;
    }

    .nav-links {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        list-style: none;
    }

    .nav-links a {
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.8rem;
        font-weight: 500;
        color: var(--text-secondary);
        padding: 0.4rem 0.75rem;
        border-radius: var(--radius);
        transition: all 0.15s;
    }

    .nav-links a:hover {
        color: var(--text);
        background: rgba(0,0,0,0.04);
    }

    .nav-cta {
        background: var(--accent) !important;
        color: #fff !important;
        padding: 0.4rem 1rem !important;
        border-radius: var(--radius) !important;
    }

    .nav-cta:hover {
        background: var(--accent-hover) !important;
        color: #fff !important;
    }

    .nav-hamburger {
        display: none;
        background: none; border: none;
        cursor: pointer; padding: 0.5rem;
    }

    .nav-hamburger span {
        display: block; width: 20px; height: 2px;
        background: var(--text); margin: 4px 0;
        border-radius: 1px;
        transition: all 0.2s;
    }

    .mobile-nav {
        display: none;
        position: fixed; inset: 0;
        background: var(--bg);
        z-index: 99;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 2rem;
    }

    .mobile-nav.open { display: flex; }
    .mobile-nav a {
        font-family: 'Instrument Serif', serif;
        font-size: 1.75rem;
        color: var(--text);
    }

    @media (max-width: 768px) {
        .nav-links { display: none; }
        .nav-hamburger { display: block; }
    }

    /* ═══════════════════════
       HERO
    ═══════════════════════ */
    .hero {
        padding: 10rem 0 5rem;
        text-align: center;
    }

    .hero h1 {
        font-size: clamp(2.75rem, 7vw, 4.5rem);
        margin-bottom: 1.25rem;
        font-style: italic;
    }

    .hero-sub {
        font-family: 'IBM Plex Mono', monospace;
        font-size: clamp(0.9rem, 1.5vw, 1rem);
        color: var(--text-secondary);
        max-width: 520px;
        margin: 0 auto 2.5rem;
        line-height: 1.7;
    }

    .hero-actions {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        margin-bottom: 1rem;
    }

    .btn-primary {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: var(--accent);
        color: #fff;
        padding: 0.6rem 1.4rem;
        border-radius: var(--radius);
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.85rem;
        font-weight: 500;
        border: 1px solid var(--accent);
        cursor: pointer;
        transition: all 0.15s;
    }

    .btn-primary:hover {
        background: var(--accent-hover);
        border-color: var(--accent-hover);
        color: #fff;
    }

    .btn-outline {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: transparent;
        color: var(--text-secondary);
        padding: 0.6rem 1.4rem;
        border-radius: var(--radius);
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.85rem;
        font-weight: 500;
        border: 1px solid var(--border-strong);
        cursor: pointer;
        transition: all 0.15s;
    }

    .btn-outline:hover {
        border-color: var(--text-secondary);
        color: var(--text);
    }

    .hero-note {
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.75rem;
        color: var(--text-muted);
    }

    /* ─── Product shot ─── */
    .hero-image {
        margin: 4rem auto 0;
        max-width: 900px;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid var(--border-strong);
        box-shadow:
            0 2px 4px rgba(0,0,0,0.04),
            0 12px 40px rgba(0,0,0,0.08);
    }

    .hero-image img {
        width: 100%;
        display: block;
    }

    /* ═══════════════════════
       3 PILLARS (Fast / Intelligent / Collaborative style)
    ═══════════════════════ */
    .pillars {
        padding: 4rem 0 2rem;
        border-top: 1px solid var(--border);
    }

    .pillars-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0;
    }

    .pillar {
        padding: 2rem 2.5rem;
        border-right: 1px solid var(--border);
    }

    .pillar:last-child { border-right: none; }

    .pillar h3 {
        font-size: 1.5rem;
        margin-bottom: 0.75rem;
        font-style: normal;
    }

    .pillar p {
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.82rem;
        color: var(--text-secondary);
        line-height: 1.7;
    }

    @media (max-width: 768px) {
        .pillars-grid { grid-template-columns: 1fr; }
        .pillar { border-right: none; border-bottom: 1px solid var(--border); padding: 1.5rem 0; }
        .pillar:last-child { border-bottom: none; }
    }

    /* ═══════════════════════
       SOCIAL PROOF — logos + testimonials
    ═══════════════════════ */
    .proof {
        padding: 5rem 0;
        border-top: 1px solid var(--border);
    }

    .proof h2 {
        text-align: center;
        font-size: clamp(1.75rem, 4vw, 2.5rem);
        font-style: italic;
        margin-bottom: 3rem;
    }

    .proof-logos {
        display: flex;
        align-items: stretch;
        justify-content: center;
        gap: 1.5rem;
        margin-bottom: 4rem;
        padding-bottom: 3rem;
        border-bottom: 1px solid var(--border);
    }

    .proof-logo-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1rem;
        padding: 2rem 3rem;
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .proof-logo-card:hover {
        border-color: var(--border-strong);
        box-shadow: 0 2px 12px rgba(0,0,0,0.04);
    }

    .proof-logo-card img {
        height: 56px;
        width: auto;
    }

    .proof-logo-card .proof-logo-name {
        font-family: 'DM Sans', sans-serif;
        font-weight: 600;
        font-size: 0.9rem;
        color: var(--text);
    }

    .proof-logo-card .proof-logo-detail {
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.72rem;
        color: var(--text-muted);
    }

    @media (max-width: 480px) {
        .proof-logos { flex-direction: column; align-items: center; }
        .proof-logo-card { width: 100%; max-width: 280px; }
    }

    /* Testimonials — organic masonry like zed */
    .testimonials-grid {
        display: grid;
        grid-template-columns: 1fr 1.2fr 1fr;
        gap: 1.5rem;
        align-items: start;
    }

    .testimonial {
        padding: 1.75rem;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        background: var(--bg-card);
    }

    .testimonial-featured {
        border-color: var(--border-strong);
        box-shadow: 0 2px 12px rgba(0,0,0,0.05);
    }

    .testimonial-text {
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.82rem;
        line-height: 1.75;
        color: var(--text-secondary);
        margin-bottom: 1.5rem;
    }

    .testimonial-text mark {
        background: rgba(4,83,203,0.08);
        color: var(--accent);
        padding: 1px 3px;
        border-radius: 2px;
    }

    .testimonial-author {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .testimonial-avatar {
        width: 36px; height: 36px;
        border-radius: 50%;
        object-fit: cover;
    }

    .testimonial-name {
        font-weight: 600;
        font-size: 0.85rem;
        color: var(--text);
    }

    .testimonial-role {
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.7rem;
        color: var(--text-muted);
    }

    .testimonial-company {
        margin-left: auto;
        font-family: 'DM Sans', sans-serif;
        font-weight: 700;
        font-size: 0.75rem;
        color: var(--text-muted);
        letter-spacing: 0.02em;
    }

    @media (max-width: 768px) {
        .testimonials-grid { grid-template-columns: 1fr; }
    }

    /* ═══════════════════════
       FEATURES — grid like zed's feature tiles
    ═══════════════════════ */
    .features {
        padding: 5rem 0;
        border-top: 1px solid var(--border);
    }

    .features h2 {
        font-size: clamp(1.75rem, 4vw, 2.5rem);
        font-style: italic;
        margin-bottom: 1rem;
    }

    .features-intro {
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.88rem;
        color: var(--text-secondary);
        max-width: 600px;
        margin-bottom: 3rem;
        line-height: 1.7;
    }

    /* Big feature — asymmetric text + image */
    .feature-big {
        display: grid;
        grid-template-columns: 1fr 1.3fr;
        gap: 3rem;
        align-items: center;
        padding: 3rem 0;
        border-bottom: 1px solid var(--border);
    }

    .feature-big:last-of-type { border-bottom: none; }

    .feature-big-text h3 {
        font-size: 1.75rem;
        margin-bottom: 0.75rem;
        font-style: normal;
    }

    .feature-big-text p {
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.82rem;
        color: var(--text-secondary);
        line-height: 1.75;
        margin-bottom: 1.25rem;
    }

    .feature-big-text a {
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.82rem;
        font-weight: 500;
    }

    .feature-big-image {
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid var(--border);
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
    }

    .feature-big-image img {
        width: 100%;
        display: block;
    }

    @media (max-width: 768px) {
        .feature-big { grid-template-columns: 1fr; }
        .feature-big-image { order: -1; }
    }

    /* Small features — clean 3-col grid like zed */
    .features-small {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0;
        border-top: 1px solid var(--border);
        margin-top: 2rem;
    }

    .feature-tile {
        padding: 2rem 2.5rem;
        border-right: 1px solid var(--border);
        border-bottom: 1px solid var(--border);
    }

    .feature-tile:nth-child(3n) { border-right: none; }
    .feature-tile:nth-last-child(-n+3) { border-bottom: none; }

    .feature-tile h4 {
        font-family: 'DM Sans', sans-serif;
        font-weight: 600;
        font-size: 0.95rem;
        margin-bottom: 0.4rem;
        color: var(--text);
    }

    .feature-tile p {
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.78rem;
        color: var(--text-secondary);
        line-height: 1.65;
    }

    @media (max-width: 768px) {
        .features-small { grid-template-columns: 1fr; }
        .feature-tile { border-right: none !important; padding: 1.5rem 0; }
    }

    /* ═══════════════════════
       PRICING
    ═══════════════════════ */
    .pricing {
        padding: 5rem 0;
        border-top: 1px solid var(--border);
    }

    .pricing h2 {
        text-align: center;
        font-size: clamp(1.75rem, 4vw, 2.5rem);
        font-style: italic;
        margin-bottom: 0.75rem;
    }

    .pricing-sub {
        text-align: center;
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.85rem;
        color: var(--text-secondary);
        margin-bottom: 3rem;
    }

    .pricing-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        overflow: hidden;
        background: var(--bg-card);
    }

    .price-col {
        padding: 2.5rem 2rem;
        border-right: 1px solid var(--border);
        position: relative;
        display: flex;
        flex-direction: column;
    }

    .price-col:last-child { border-right: none; }

    .price-col.featured {
        background: rgba(4,83,203,0.03);
    }

    .price-badge {
        position: absolute;
        top: 1rem; right: 1rem;
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.65rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        background: var(--accent);
        color: #fff;
        padding: 3px 8px;
        border-radius: 3px;
    }

    .price-name {
        font-family: 'DM Sans', sans-serif;
        font-weight: 700;
        font-size: 1rem;
        margin-bottom: 0.25rem;
    }

    .price-desc {
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.72rem;
        color: var(--text-muted);
        margin-bottom: 1.5rem;
    }

    .price-amount {
        font-family: 'Instrument Serif', serif;
        font-size: 2.75rem;
        color: var(--text);
        line-height: 1;
        margin-bottom: 0.25rem;
    }

    .price-amount span {
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.8rem;
        color: var(--text-muted);
    }

    .price-period {
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.72rem;
        color: var(--text-muted);
        margin-bottom: 2rem;
    }

    .price-features {
        list-style: none;
        display: flex;
        flex-direction: column;
        gap: 0.6rem;
        margin-bottom: 2rem;
        flex: 1;
    }

    .price-features li {
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.78rem;
        color: var(--text-secondary);
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
    }

    .price-features li::before {
        content: '✓';
        color: var(--accent);
        font-weight: 700;
        flex-shrink: 0;
    }

    .price-features li.no::before {
        content: '—';
        color: var(--text-muted);
    }

    .price-features li.no { color: var(--text-muted); }

    .price-btn {
        display: block;
        width: 100%;
        text-align: center;
        padding: 0.6rem;
        border-radius: var(--radius);
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.8rem;
        font-weight: 500;
        border: 1px solid var(--border-strong);
        background: transparent;
        color: var(--text-secondary);
        cursor: pointer;
        transition: all 0.15s;
    }

    .price-btn:hover {
        border-color: var(--text-secondary);
        color: var(--text);
    }

    .price-btn-fill {
        background: var(--accent);
        border-color: var(--accent);
        color: #fff;
    }

    .price-btn-fill:hover {
        background: var(--accent-hover);
        border-color: var(--accent-hover);
        color: #fff;
    }

    @media (max-width: 1024px) {
        .pricing-grid { grid-template-columns: repeat(2, 1fr); }
        .price-col:nth-child(2n) { border-right: none; }
        .price-col { border-bottom: 1px solid var(--border); }
    }
    @media (max-width: 580px) {
        .pricing-grid { grid-template-columns: 1fr; }
        .price-col { border-right: none; border-bottom: 1px solid var(--border); }
        .price-col:last-child { border-bottom: none; }
    }

    .pricing-footer {
        text-align: center;
        margin-top: 1.5rem;
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.72rem;
        color: var(--text-muted);
    }

    /* ═══════════════════════
       LETTER / CTA — like zed's "A letter from the team"
    ═══════════════════════ */
    .letter {
        padding: 5rem 0;
        border-top: 1px solid var(--border);
    }

    .letter-card {
        max-width: 680px;
        margin: 0 auto;
        padding: 3.5rem;
        background: var(--bg-alt);
        border: 1px solid var(--border);
        border-radius: 8px;
        text-align: center;
    }

    .letter-label {
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: var(--text-muted);
        margin-bottom: 1.5rem;
    }

    .letter h2 {
        font-size: clamp(1.5rem, 3.5vw, 2.25rem);
        font-style: italic;
        margin-bottom: 1.25rem;
    }

    .letter p {
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.85rem;
        color: var(--text-secondary);
        line-height: 1.75;
        margin-bottom: 2rem;
        max-width: 480px;
        margin-left: auto;
        margin-right: auto;
    }

    /* ═══════════════════════
       FOOTER
    ═══════════════════════ */
    .footer {
        padding: 3rem 0 2rem;
        border-top: 1px solid var(--border);
    }

    .footer-grid {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr;
        gap: 3rem;
        margin-bottom: 3rem;
    }

    .footer-brand p {
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.78rem;
        color: var(--text-muted);
        line-height: 1.7;
        margin-top: 1rem;
        max-width: 260px;
    }

    .footer-col h4 {
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--text-muted);
        margin-bottom: 1rem;
    }

    .footer-col ul {
        list-style: none;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .footer-col a {
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.8rem;
        color: var(--text-secondary);
    }

    .footer-col a:hover { color: var(--text); }

    .footer-bottom {
        border-top: 1px solid var(--border);
        padding-top: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .footer-bottom p {
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.72rem;
        color: var(--text-muted);
    }

    .footer-social {
        display: flex;
        gap: 1rem;
    }

    .footer-social a {
        color: var(--text-muted);
        font-size: 0.9rem;
    }

    .footer-social a:hover { color: var(--text); }

    @media (max-width: 768px) {
        .footer-grid { grid-template-columns: 1fr 1fr; gap: 2rem; }
    }
    @media (max-width: 480px) {
        .footer-grid { grid-template-columns: 1fr; }
    }

    /* ─── Scroll reveal ─── */
    .reveal {
        opacity: 0;
        transform: translateY(20px);
        transition: opacity 0.6s cubic-bezier(0.22,1,0.36,1), transform 0.6s cubic-bezier(0.22,1,0.36,1);
    }
    .reveal.visible {
        opacity: 1;
        transform: none;
    }
    .reveal-d1 { transition-delay: 60ms; }
    .reveal-d2 { transition-delay: 120ms; }
    .reveal-d3 { transition-delay: 180ms; }

    /* ═══════════════════════
       FAQ
    ═══════════════════════ */
    .faq {
        padding: 5rem 0;
        border-top: 1px solid var(--border);
    }

    .faq h2 {
        text-align: center;
        font-size: clamp(1.75rem, 4vw, 2.5rem);
        font-style: italic;
        margin-bottom: 3rem;
    }

    .faq-list {
        max-width: 700px;
        margin: 0 auto;
    }

    .faq-item {
        border-bottom: 1px solid var(--border);
    }

    .faq-q {
        padding: 1.25rem 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        cursor: pointer;
        user-select: none;
    }

    .faq-q span {
        font-family: 'DM Sans', sans-serif;
        font-weight: 600;
        font-size: 0.95rem;
        color: var(--text);
    }

    .faq-q i {
        color: var(--text-muted);
        font-size: 0.7rem;
        transition: transform 0.25s ease;
        flex-shrink: 0;
    }

    .faq-item.open .faq-q i { transform: rotate(180deg); }

    .faq-a {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.35s ease;
    }

    .faq-item.open .faq-a { max-height: 250px; }

    .faq-a-inner {
        padding: 0 0 1.25rem;
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.82rem;
        color: var(--text-secondary);
        line-height: 1.75;
    }

    /* ═══════════════════════
       CONTACT FORM CTA
    ═══════════════════════ */
    .contact {
        padding: 5rem 0;
        border-top: 1px solid var(--border);
    }

    .contact-inner {
        display: grid;
        grid-template-columns: 1fr 1.2fr;
        gap: 4rem;
        align-items: start;
    }

    .contact-text h2 {
        font-size: clamp(1.5rem, 3.5vw, 2.25rem);
        font-style: italic;
        margin-bottom: 1rem;
    }

    .contact-text p {
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.85rem;
        color: var(--text-secondary);
        line-height: 1.75;
        margin-bottom: 2rem;
    }

    .contact-info-item {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        margin-bottom: 1rem;
    }

    .contact-info-item i {
        color: var(--accent);
        margin-top: 0.2rem;
        font-size: 0.85rem;
        width: 16px;
        text-align: center;
    }

    .contact-info-item span {
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.82rem;
        color: var(--text-secondary);
    }

    .contact-form {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 2rem;
    }

    .contact-form .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .contact-form .form-group {
        margin-bottom: 1rem;
    }

    .contact-form label {
        display: block;
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--text-muted);
        margin-bottom: 0.4rem;
    }

    .contact-form input,
    .contact-form select,
    .contact-form textarea {
        width: 100%;
        padding: 0.6rem 0.75rem;
        border: 1px solid var(--border-strong);
        border-radius: var(--radius);
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.85rem;
        color: var(--text);
        background: var(--bg);
        transition: border-color 0.15s;
        outline: none;
    }

    .contact-form input:focus,
    .contact-form select:focus,
    .contact-form textarea:focus {
        border-color: var(--accent);
    }

    .contact-form textarea { resize: vertical; min-height: 100px; }

    .contact-form .form-submit {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-top: 0.5rem;
    }

    .contact-form .form-note {
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.7rem;
        color: var(--text-muted);
    }

    .contact-success {
        display: none;
        text-align: center;
        padding: 2rem;
    }

    .contact-success.show { display: block; }
    .contact-success.show + form { display: none; }

    .contact-success i {
        font-size: 2rem;
        color: #10b981;
        margin-bottom: 1rem;
    }

    .contact-success h3 {
        font-family: 'DM Sans', sans-serif;
        font-weight: 600;
        color: var(--text);
        margin-bottom: 0.5rem;
    }

    .contact-success p {
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.82rem;
        color: var(--text-secondary);
    }

    @media (max-width: 768px) {
        .contact-inner { grid-template-columns: 1fr; gap: 2rem; }
        .contact-form .form-row { grid-template-columns: 1fr; }
    }

    </style>
</head>
<body>

<!-- NAV -->
<nav class="nav">
    <div class="container nav-inner">
        <a href="/" class="nav-logo">
            <img src="{{ asset('images/LOGO-KLASSCI-PNG.png') }}" alt="KLASSCI">
            <span>KLASSCI</span>
        </a>
        <ul class="nav-links">
            <li><a href="#fonctionnalites">Fonctionnalités</a></li>
            <li><a href="#tarifs">Tarifs</a></li>
            <li><a href="#faq">FAQ</a></li>
            <li><a href="#contact">Contact</a></li>
            <li><a href="{{ route('login') }}" class="nav-cta">Se connecter</a></li>
        </ul>
        <button class="nav-hamburger" id="hamburger" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
    </div>
</nav>

<div class="mobile-nav" id="mobileNav">
    <a href="#fonctionnalites" onclick="closeMobile()">Fonctionnalités</a>
    <a href="#tarifs" onclick="closeMobile()">Tarifs</a>
    <a href="#faq" onclick="closeMobile()">FAQ</a>
    <a href="#contact" onclick="closeMobile()">Contact</a>
    <a href="{{ route('login') }}">Se connecter</a>
</div>

<!-- HERO -->
<section class="hero">
    <div class="container">
        <h1 class="reveal">Gestion scolaire, repensée.</h1>
        <p class="hero-sub reveal reveal-d1">
            KLASSCI est l'outil qui remplace vos fichiers Excel,
            vos cahiers de notes et vos tableaux d'affichage.
            Un seul endroit pour tout gérer.
        </p>
        <div class="hero-actions reveal reveal-d2">
            <a href="#contact" class="btn-primary">
                Essayer gratuitement <span style="opacity:0.6; font-size:0.7rem">— c'est gratuit</span>
            </a>
            <a href="#fonctionnalites" class="btn-outline">
                Voir comment ça marche &gt;
            </a>
        </div>
        <p class="hero-note reveal reveal-d3">Disponible sur navigateur. Aucune installation requise.</p>

        <div class="hero-image reveal">
            <img src="{{ asset('images/landing/hero_section.png') }}" alt="KLASSCI — Tableau de bord Super Admin" loading="eager" width="900" height="auto">
        </div>
    </div>
</section>

<!-- 3 PILLARS -->
<section class="pillars">
    <div class="container">
        <div class="pillars-grid">
            <div class="pillar reveal">
                <h3>Simple</h3>
                <p>Pensé pour les secrétaires, enseignants et directeurs qui n'ont pas le temps d'apprendre un logiciel complexe. Compte test en 2-3 jours, déploiement complet en 2 semaines.</p>
            </div>
            <div class="pillar reveal reveal-d1">
                <h3>Complet</h3>
                <p>Notes, bulletins, emplois du temps, paiements, présences, inscriptions — tout est intégré. Zéro outil supplémentaire.</p>
            </div>
            <div class="pillar reveal reveal-d2">
                <h3>Sécurisé</h3>
                <p>Chaque établissement a sa propre base de données isolée. Vos données ne sont jamais partagées avec d'autres écoles.</p>
            </div>
        </div>
    </div>
</section>

<!-- SOCIAL PROOF -->
<section class="proof">
    <div class="container">
        <h2 class="reveal">Adopté par des établissements<br>qui forment des milliers d'étudiants</h2>

        <div class="proof-logos reveal">
            <div class="proof-logo-card">
                <img src="{{ asset('images/landing/esbtp_logo.png') }}" alt="ESBTP Abidjan">
                <div class="proof-logo-name">ESBTP Abidjan</div>
                <div class="proof-logo-detail">2 600+ étudiants · Plan Élite</div>
            </div>
            <div class="proof-logo-card">
                <img src="{{ asset('images/landing/esbtp_logo.png') }}" alt="ESBTP Yamoussoukro">
                <div class="proof-logo-name">ESBTP Yamoussoukro</div>
                <div class="proof-logo-detail">2 000+ étudiants · Plan Élite</div>
            </div>
        </div>

        <div class="testimonials-grid">
            <div class="testimonial reveal">
                <p class="testimonial-text">"Avant KLASSCI, la saisie des notes prenait 3 semaines. Aujourd'hui, <mark>les bulletins sont prêts en 48 heures</mark>. Les enseignants saisissent directement en ligne."</p>
                <div class="testimonial-author">
                    <img src="{{ asset('images/Images landingPage/Sans titre - 2-11.png') }}" alt="" class="testimonial-avatar">
                    <div>
                        <div class="testimonial-name">Dr. Soro Kouadio</div>
                        <div class="testimonial-role">Directeur</div>
                    </div>
                    <span class="testimonial-company">ESBTP Abidjan</span>
                </div>
            </div>

            <div class="testimonial testimonial-featured reveal reveal-d1">
                <p class="testimonial-text">"<mark>Le suivi financier a complètement changé.</mark> On voit en temps réel qui a payé, qui doit relancer, et les rapports sont toujours à jour. Plus besoin d'attendre la fin du mois pour avoir une vision claire. C'est devenu indispensable pour nous."</p>
                <div class="testimonial-author">
                    <img src="{{ asset('images/testimonial-2.png') }}" alt="" class="testimonial-avatar">
                    <div>
                        <div class="testimonial-name">Ama Bamba</div>
                        <div class="testimonial-role">Comptable</div>
                    </div>
                    <span class="testimonial-company">ESBTP Yakro</span>
                </div>
            </div>

            <div class="testimonial reveal reveal-d2">
                <p class="testimonial-text">"L'émargement numérique a <mark>réduit l'absentéisme de 30%</mark>. Les coordinateurs voient en temps réel qui est présent, sans attendre les feuilles de présence."</p>
                <div class="testimonial-author">
                    <img src="{{ asset('images/testimonial-3.png') }}" alt="" class="testimonial-avatar">
                    <div>
                        <div class="testimonial-name">Traoré Nanga</div>
                        <div class="testimonial-role">Coordinateur</div>
                    </div>
                    <span class="testimonial-company">ESBTP Yakro</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FEATURES -->
<section class="features" id="fonctionnalites">
    <div class="container">
        <h2 class="reveal">Ce que KLASSCI fait pour vous</h2>
        <p class="features-intro reveal reveal-d1">Chaque fonctionnalité a été construite en écoutant les besoins réels des établissements que nous accompagnons depuis 2023.</p>

        <!-- Big feature 1 -->
        <div class="feature-big reveal">
            <div class="feature-big-text">
                <h3>Saisie des notes et bulletins</h3>
                <p>Les enseignants saisissent leurs notes directement depuis leur téléphone. Les moyennes, rangs et appréciations se calculent automatiquement. Les bulletins PDF sont générés en un clic, personnalisés aux couleurs de votre école.</p>
                <a href="#">En savoir plus &gt;</a>
            </div>
            <div class="feature-big-image">
                <img src="{{ asset('images/landing/Saisie_des_notes_et_bulletins.png') }}" alt="Saisie des notes et bulletins — fiche étudiant KLASSCI" loading="lazy">
            </div>
        </div>

        <!-- Big feature 2 -->
        <div class="feature-big reveal">
            <div class="feature-big-text">
                <h3>Suivi financier en temps réel</h3>
                <p>Visualisez instantanément l'état des paiements de chaque étudiant. Envoyez des relances automatiques, générez des reçus, et exportez vos rapports financiers. Compatible avec le système de frais par catégorie.</p>
                <a href="#">En savoir plus &gt;</a>
            </div>
            <div class="feature-big-image">
                <img src="{{ asset('images/landing/Suivi_financier_en_temps_réel.png') }}" alt="Dashboard Comptabilité — suivi financier en temps réel" loading="lazy">
            </div>
        </div>

        <!-- Small features grid -->
        <div class="features-small">
            <div class="feature-tile reveal">
                <h4>Emploi du temps</h4>
                <p>Génération automatique des plannings avec gestion des conflits de salles et d'enseignants.</p>
            </div>
            <div class="feature-tile reveal reveal-d1">
                <h4>Émargement numérique</h4>
                <p>Prise de présence par séance, historique complet, taux de présence en temps réel.</p>
            </div>
            <div class="feature-tile reveal reveal-d2">
                <h4>Système LMD</h4>
                <p>Gestion des UE, ECUE, crédits et semestres conforme aux standards UEMOA.</p>
            </div>
            <div class="feature-tile reveal">
                <h4>Inscriptions en ligne</h4>
                <p>Workflow complet : de la demande à la validation, avec suivi des documents et pièces requises.</p>
            </div>
            <div class="feature-tile reveal reveal-d1">
                <h4>Tableau de bord par rôle</h4>
                <p>Directeur, secrétaire, enseignant, comptable, coordinateur — chacun voit ce qui le concerne.</p>
            </div>
            <div class="feature-tile reveal reveal-d2">
                <h4>API et intégrations</h4>
                <p>Connectez KLASSCI à votre LMS ou système existant via notre API REST documentée.</p>
            </div>
        </div>
    </div>
</section>

<!-- PRICING -->
<section class="pricing" id="tarifs">
    <div class="container">
        <h2 class="reveal">Tarification simple</h2>
        <p class="pricing-sub reveal reveal-d1">Commencez gratuitement. Évoluez quand vous êtes prêt.</p>

        <div class="pricing-grid reveal">
            <div class="price-col">
                <div class="price-name">Free</div>
                <div class="price-desc">Pour découvrir</div>
                <div class="price-amount">0 <span>FCFA</span></div>
                <div class="price-period">3 mois d'essai</div>
                <ul class="price-features">
                    <li>50 étudiants max</li>
                    <li>Gestion des notes</li>
                    <li>Emploi du temps basique</li>
                    <li>1 administrateur</li>
                    <li class="no">Comptabilité</li>
                    <li class="no">Support prioritaire</li>
                </ul>
                <a href="#contact" class="price-btn">Commencer</a>
            </div>

            <div class="price-col featured">
                <span class="price-badge">populaire</span>
                <div class="price-name">Essentiel</div>
                <div class="price-desc">Établissements en croissance</div>
                <div class="price-amount">150k <span>FCFA</span></div>
                <div class="price-period">/ mois</div>
                <ul class="price-features">
                    <li>1 000 étudiants</li>
                    <li>Notes + Bulletins PDF</li>
                    <li>Emploi du temps avancé</li>
                    <li>Comptabilité complète</li>
                    <li>5 utilisateurs</li>
                    <li>Support email</li>
                </ul>
                <a href="#contact" class="price-btn price-btn-fill">Choisir Essentiel</a>
            </div>

            <div class="price-col">
                <div class="price-name">Pro</div>
                <div class="price-desc">Grands établissements</div>
                <div class="price-amount">350k <span>FCFA</span></div>
                <div class="price-period">/ mois</div>
                <ul class="price-features">
                    <li>Étudiants illimités</li>
                    <li>Toutes les fonctionnalités</li>
                    <li>Système LMD complet</li>
                    <li>API pour intégrations</li>
                    <li>Utilisateurs illimités</li>
                    <li>Support prioritaire 24/7</li>
                </ul>
                <a href="#contact" class="price-btn">Choisir Pro</a>
            </div>

            <div class="price-col" style="background:var(--bg-alt);">
                <div class="price-name">Élite</div>
                <div class="price-desc">Sur mesure pour votre établissement</div>
                <div class="price-amount" style="font-size:1.75rem;">Sur devis</div>
                <div class="price-period">personnalisation complète</div>
                <ul class="price-features">
                    <li>Tout le plan Pro inclus</li>
                    <li>Fonctionnalités sur mesure</li>
                    <li>PDF et exports aux couleurs de l'école</li>
                    <li>Déploiement dédié (~2 semaines)</li>
                    <li>Formation de vos équipes</li>
                    <li>Interlocuteur dédié</li>
                </ul>
                <a href="#contact" class="price-btn price-btn-fill">Nous contacter</a>
            </div>
        </div>

        <p class="pricing-footer reveal">Essai gratuit de 3 mois · Aucune carte bancaire requise · Support inclus</p>
    </div>
</section>

<!-- FAQ -->
<section class="faq" id="faq">
    <div class="container">
        <h2 class="reveal">Questions fréquentes</h2>
        <div class="faq-list">
            <div class="faq-item reveal">
                <div class="faq-q" onclick="toggleFaq(this)">
                    <span>Mes données sont-elles en sécurité ?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-a"><div class="faq-a-inner">Chaque établissement dispose de sa propre base de données isolée. Les données sont chiffrées en transit et au repos. Aucun établissement ne peut accéder aux données d'un autre.</div></div>
            </div>
            <div class="faq-item reveal reveal-d1">
                <div class="faq-q" onclick="toggleFaq(this)">
                    <span>Puis-je migrer mes données depuis Excel ?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-a"><div class="faq-a-inner">Oui. KLASSCI dispose d'outils d'importation pour les listes d'étudiants, classes et matières au format Excel. Notre équipe peut également vous accompagner lors de la migration initiale.</div></div>
            </div>
            <div class="faq-item reveal reveal-d1">
                <div class="faq-q" onclick="toggleFaq(this)">
                    <span>KLASSCI fonctionne-t-il avec le système LMD ?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-a"><div class="faq-a-inner">Oui. KLASSCI supporte le système BTS classique et le système LMD (Licence-Master-Doctorat) avec gestion des UE, ECUE, crédits et semestres conforme aux standards UEMOA.</div></div>
            </div>
            <div class="faq-item reveal reveal-d2">
                <div class="faq-q" onclick="toggleFaq(this)">
                    <span>Combien de temps pour être opérationnel ?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-a"><div class="faq-a-inner">Pour le plan gratuit (essai de 3 mois), votre compte est créé en 2 à 3 jours. Si vous passez à une formule payante, comptez environ 2 semaines pour la personnalisation complète et le déploiement de votre établissement.</div></div>
            </div>
            <div class="faq-item reveal reveal-d2">
                <div class="faq-q" onclick="toggleFaq(this)">
                    <span>KLASSCI fonctionne-t-il sur téléphone ?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-a"><div class="faq-a-inner">Oui. L'interface est entièrement responsive et fonctionne sur smartphone, tablette et ordinateur. Les enseignants peuvent saisir les présences et les notes directement depuis leur téléphone.</div></div>
            </div>
            <div class="faq-item reveal reveal-d3">
                <div class="faq-q" onclick="toggleFaq(this)">
                    <span>Puis-je essayer gratuitement ?</span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="faq-a"><div class="faq-a-inner">Le plan Free vous permet de tester KLASSCI pendant 3 mois avec jusqu'à 50 étudiants. Votre compte est créé en 2 à 3 jours. Si vous passez à une formule payante, comptez environ 2 semaines pour la personnalisation et le déploiement complet de votre établissement.</div></div>
            </div>
        </div>
    </div>
</section>

<!-- CONTACT FORM -->
<section class="contact" id="contact">
    <div class="container">
        <div class="contact-inner reveal">
            <div class="contact-text">
                <h2>Demandez une démonstration</h2>
                <p>Vous avez des questions ou souhaitez voir KLASSCI en action ? Remplissez le formulaire et notre équipe vous recontactera sous 24h.</p>
                <div class="contact-info-item">
                    <i class="fas fa-envelope"></i>
                    <span>contact@klassci.com</span>
                </div>
                <div class="contact-info-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>Abidjan, Côte d'Ivoire</span>
                </div>
            </div>
            <div class="contact-form">
                <div class="contact-success" id="contactSuccess">
                    <i class="fas fa-check-circle"></i>
                    <h3>Message envoyé</h3>
                    <p>Nous vous recontacterons sous 24h.</p>
                </div>
                <form id="contactForm" method="POST" action="/contact-demo">
                    @csrf
                    <div class="form-row">
                        <div>
                            <label for="ct-nom">Nom complet</label>
                            <input type="text" id="ct-nom" name="nom" required placeholder="Votre nom">
                        </div>
                        <div>
                            <label for="ct-email">Email</label>
                            <input type="email" id="ct-email" name="email" required placeholder="vous@exemple.com">
                        </div>
                    </div>
                    <div class="form-row">
                        <div>
                            <label for="ct-etablissement">Établissement</label>
                            <input type="text" id="ct-etablissement" name="etablissement" required placeholder="Nom de votre établissement">
                        </div>
                        <div>
                            <label for="ct-telephone">Téléphone</label>
                            <input type="tel" id="ct-telephone" name="telephone" placeholder="+225 07 00 00 00 00">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="ct-type">Type d'établissement</label>
                        <select id="ct-type" name="type_etablissement" required>
                            <option value="">Sélectionnez</option>
                            <option value="ecole_superieure">École supérieure</option>
                            <option value="universite">Université</option>
                            <option value="lycee">Lycée</option>
                            <option value="college">Collège</option>
                            <option value="centre_formation">Centre de formation</option>
                            <option value="autre">Autre</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="ct-message">Message <span style="font-weight:400;color:var(--text-muted)">(optionnel)</span></label>
                        <textarea id="ct-message" name="message" placeholder="Dites-nous en quoi KLASSCI peut vous aider..."></textarea>
                    </div>
                    <div class="form-submit">
                        <span class="form-note">Réponse sous 24h</span>
                        <button type="submit" class="btn-primary">Envoyer la demande <i class="fas fa-arrow-right" style="font-size:0.7rem"></i></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- LETTER / CTA -->
<section class="letter">
    <div class="container">
        <div class="letter-card reveal">
            <div class="letter-label">Un mot de l'équipe</div>
            <h2>Prêt à simplifier la gestion de votre établissement ?</h2>
            <p>Nous avons construit KLASSCI parce que nous croyons que les écoles africaines méritent des outils modernes, simples et fiables. Essayez, c'est gratuit.</p>
            <a href="#contact" class="btn-primary" style="font-size:0.9rem">
                Commencer gratuitement &gt;
            </a>
        </div>
    </div>
</section>

<!-- FOOTER -->
<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <a href="/" class="nav-logo">
                    <img src="{{ asset('images/LOGO-KLASSCI-PNG.png') }}" alt="KLASSCI" style="height:24px">
                    <span style="font-weight:700;font-size:0.95rem;color:var(--text)">KLASSCI</span>
                </a>
                <p>Plateforme de gestion scolaire conçue pour les établissements d'enseignement supérieur en Afrique.</p>
            </div>
            <div class="footer-col">
                <h4>Produit</h4>
                <ul>
                    <li><a href="#fonctionnalites">Fonctionnalités</a></li>
                    <li><a href="#tarifs">Tarifs</a></li>
                    <li><a href="{{ route('login') }}">Connexion</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Ressources</h4>
                <ul>
                    <li><a href="#">Documentation</a></li>
                    <li><a href="#">API Reference</a></li>
                    <li><a href="#">Changelog</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Contact</h4>
                <ul>
                    <li><a href="mailto:contact@klassci.com">contact@klassci.com</a></li>
                    <li><a href="#">Abidjan, Côte d'Ivoire</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; {{ date('Y') }} KLASSCI — African Digital Consulting</p>
            <div class="footer-social">
                <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                <a href="#" aria-label="X"><i class="fab fa-x-twitter"></i></a>
            </div>
        </div>
    </div>
</footer>

<script>
(function() {
    // Mobile nav
    var btn = document.getElementById('hamburger');
    var mob = document.getElementById('mobileNav');
    btn.addEventListener('click', function() { mob.classList.toggle('open'); });
    window.closeMobile = function() { mob.classList.remove('open'); };

    // Scroll reveal
    var els = document.querySelectorAll('.reveal');
    var obs = new IntersectionObserver(function(entries) {
        entries.forEach(function(e) {
            if (e.isIntersecting) { e.target.classList.add('visible'); obs.unobserve(e.target); }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
    els.forEach(function(el) { obs.observe(el); });

    // FAQ toggle
    window.toggleFaq = function(el) {
        var item = el.closest('.faq-item');
        var wasOpen = item.classList.contains('open');
        document.querySelectorAll('.faq-item.open').forEach(function(i) { i.classList.remove('open'); });
        if (!wasOpen) item.classList.add('open');
    };

    // Contact form
    var form = document.getElementById('contactForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var btn = form.querySelector('button[type="submit"]');
            btn.textContent = 'Envoi...';
            btn.disabled = true;

            var data = new FormData(form);
            fetch(form.action, { method: 'POST', body: data, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function() {
                    document.getElementById('contactSuccess').classList.add('show');
                })
                .catch(function() {
                    document.getElementById('contactSuccess').classList.add('show');
                });
        });
    }

    // Smooth anchor
    document.querySelectorAll('a[href^="#"]').forEach(function(a) {
        a.addEventListener('click', function(e) {
            var h = this.getAttribute('href');
            if (h === '#') return;
            var t = document.querySelector(h);
            if (t) { e.preventDefault(); window.scrollTo({ top: t.offsetTop - 70, behavior: 'smooth' }); }
        });
    });
})();
</script>
</body>
</html>
