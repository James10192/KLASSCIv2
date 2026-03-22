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
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Serif:wght@300;400;500&family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
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
        --text-secondary: #3a3d43;
        --text-muted: #8a8a8a;
        --accent: #0453cb;
        --accent-hover: #0340a0;
        --accent-light: rgba(4,83,203,0.08);
        --border: #dadde2;
        --border-strong: #c5c9d0;
        --radius: 4px;
        --max-w: 1120px;
        --nav-bg: rgba(246,244,240,0.85);
        --footer-bg: var(--accent);
        --footer-text: #fff;
        --footer-link: rgba(255,255,255,0.7);
        --footer-border: rgba(255,255,255,0.15);
        --dot-color: rgba(0,0,0,0.06);
        --duration-fast: 100ms;
        --duration-normal: 200ms;
        --duration-slow: 300ms;
        --ease-out: cubic-bezier(0.22, 1, 0.36, 1);
    }

    html.dark {
        --bg: #1a1d23;
        --bg-alt: #21252b;
        --bg-card: #282c34;
        --text: #dce0e5;
        --text-secondary: #9da3ae;
        --text-muted: #6b7280;
        --accent: #4b8af0;
        --accent-hover: #6da3f7;
        --accent-light: rgba(75,138,240,0.12);
        --border: #363b44;
        --border-strong: #4b5263;
        --nav-bg: rgba(26,29,35,0.85);
        --footer-bg: #111318;
        --footer-text: #dce0e5;
        --footer-link: rgba(220,224,229,0.6);
        --footer-border: rgba(255,255,255,0.08);
        --dot-color: rgba(255,255,255,0.04);
        color-scheme: dark;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    html { scroll-behavior: smooth; overflow-x: hidden; }

    body {
        font-family: 'IBM Plex Sans', system-ui, sans-serif;
        background: var(--bg);
        color: var(--text);
        line-height: 1.5;
        letter-spacing: -0.025em;
        overflow-x: hidden;
        -webkit-font-smoothing: antialiased;
        position: relative;
        transition: background var(--duration-slow), color var(--duration-slow);
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
        background-image: radial-gradient(circle, var(--dot-color) 1px, transparent 1px);
        background-size: 20px 20px;
    }

    @media (prefers-reduced-motion: reduce) {
        *, *::before, *::after {
            animation-duration: 0.01ms !important;
            transition-duration: 0.01ms !important;
        }
        .blob, .hero h1 {
            animation: none !important;
        }
        .hero h1 {
            -webkit-text-fill-color: var(--accent) !important;
            background: none !important;
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
        font-family: 'IBM Plex Serif', Georgia, serif;
        font-weight: 300;
        color: var(--accent);
        line-height: 1.2;
        letter-spacing: -0.02em;
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
        background: var(--nav-bg);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border-bottom: 1px solid var(--border);
        transition: background var(--duration-slow), border-color var(--duration-slow);
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
        font-family: 'IBM Plex Sans', sans-serif;
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

    .nav-links a, .nav-links button {
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--text-secondary);
        padding: 0.4rem 0.75rem;
        border-radius: var(--radius);
        transition: all var(--duration-normal);
        letter-spacing: -0.02em;
        background: none;
        border: none;
        cursor: pointer;
    }

    .nav-links a:hover, .nav-links button:hover {
        color: var(--text);
        background: var(--accent-light);
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
        font-family: 'IBM Plex Serif', serif;
        font-size: 1.75rem;
        color: var(--text);
    }

    .mobile-nav-cta {
        background: var(--accent) !important;
        color: #fff !important;
        padding: 0.6rem 2rem !important;
        border-radius: var(--radius) !important;
        font-family: 'IBM Plex Sans', sans-serif !important;
        font-size: 1rem !important;
        font-weight: 600 !important;
        margin-top: 0.5rem;
    }

    /* Mobile-only icon toggle in nav bar */
    .nav-mobile-actions {
        display: none;
        align-items: center;
        gap: 0.5rem;
    }

    .theme-toggle-mini {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        background: var(--bg-card);
        cursor: pointer;
        color: var(--text-secondary);
        transition: all var(--duration-normal);
        padding: 0;
    }

    .theme-toggle-mini:hover {
        color: var(--accent);
        border-color: var(--accent);
    }

    .theme-toggle-mini svg { width: 16px; height: 16px; }

    .theme-toggle-mini .sun-icon { display: none; }
    .theme-toggle-mini .moon-icon { display: block; }
    html.dark .theme-toggle-mini .sun-icon { display: block; }
    html.dark .theme-toggle-mini .moon-icon { display: none; }

    @media (max-width: 768px) {
        .nav-links { display: none; }
        .nav-mobile-actions { display: flex; }
        .hero-blobs { display: none; }
        .hero-image { margin: 2rem -1rem 0; max-width: calc(100% + 2rem); }
        .hero-image img { border-radius: 8px; }
    }

    /* ═══════════════════════
       HERO
    ═══════════════════════ */
    .hero {
        padding: 10rem 0 5rem;
        text-align: center;
    }

    .hero h1 {
        font-size: clamp(2.75rem, 7vw, 3.5rem);
        margin-bottom: 1.25rem;
        font-style: normal;
        font-weight: 300;
        letter-spacing: -0.02em;
        line-height: 1.2;
    }

    .hero-sub {
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: clamp(1rem, 1.8vw, 1.15rem);
        color: var(--text-secondary);
        max-width: 540px;
        margin: 0 auto 2.5rem;
        line-height: 1.6;
        letter-spacing: -0.02em;
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
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 0.875rem;
        font-weight: 500;
        border: 1px solid var(--accent);
        cursor: pointer;
        transition: all var(--duration-normal) var(--ease-out);
        letter-spacing: -0.02em;
    }

    .btn-primary:hover {
        background: var(--accent-hover);
        border-color: var(--accent-hover);
        color: #fff;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(4,83,203,0.25);
    }

    .btn-outline {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: rgba(255,255,255,0.5);
        color: var(--text);
        padding: 0.6rem 1.4rem;
        border-radius: var(--radius);
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 0.875rem;
        font-weight: 500;
        border: 1px solid var(--border);
        cursor: pointer;
        transition: all var(--duration-normal) var(--ease-out);
        letter-spacing: -0.02em;
    }

    html.dark .btn-outline { background: rgba(255,255,255,0.05); }

    .btn-outline:hover {
        border-color: var(--border-strong);
        color: var(--text);
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    }

    .hero-note {
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 0.78rem;
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
        font-size: 1.35rem;
        margin-bottom: 0.75rem;
        font-style: normal;
        font-weight: 400;
    }

    .pillar p {
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 1rem;
        color: var(--text-secondary);
        line-height: 1.6;
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
        font-size: clamp(1.75rem, 4vw, 2.25rem);
        font-style: normal;
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
        transition: all var(--duration-normal) var(--ease-out);
    }

    .proof-logo-card:hover {
        border-color: var(--accent);
        box-shadow: 0 4px 16px rgba(4,83,203,0.08);
        transform: translateY(-2px);
    }

    .proof-logo-card img {
        height: 56px;
        width: auto;
    }

    .proof-logo-card .proof-logo-name {
        font-family: 'IBM Plex Sans', sans-serif;
        font-weight: 600;
        font-size: 0.9rem;
        color: var(--text);
    }

    .proof-logo-card .proof-logo-detail {
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 0.75rem;
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
        transition: all var(--duration-normal) var(--ease-out);
    }

    .testimonial:hover {
        border-color: var(--border-strong);
        box-shadow: 0 4px 16px rgba(0,0,0,0.06);
        transform: translateY(-2px);
    }

    .testimonial-featured {
        border-color: var(--border-strong);
        box-shadow: 0 2px 12px rgba(0,0,0,0.05);
    }

    .testimonial-text {
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 0.95rem;
        line-height: 1.65;
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
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 0.72rem;
        color: var(--text-muted);
    }

    .testimonial-company {
        margin-left: auto;
        font-family: 'IBM Plex Sans', sans-serif;
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
        font-size: clamp(1.75rem, 4vw, 2.25rem);
        font-style: normal;
        margin-bottom: 1rem;
    }

    .features-intro {
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 1.05rem;
        color: var(--text-secondary);
        max-width: 620px;
        margin-bottom: 3rem;
        line-height: 1.65;
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
        font-size: 1.5rem;
        margin-bottom: 0.75rem;
        font-style: normal;
        font-weight: 400;
    }

    .feature-big-text p {
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 1rem;
        color: var(--text-secondary);
        line-height: 1.65;
        margin-bottom: 1.25rem;
    }

    .feature-big-text a {
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 1rem;
        font-weight: 500;
        transition: color var(--duration-normal);
    }

    .feature-big-image {
        border-radius: var(--radius);
        overflow: hidden;
        border: 1px solid var(--border);
        box-shadow: 0 4px 20px rgba(0,0,0,0.06);
        transition: all var(--duration-slow) var(--ease-out);
    }

    .feature-big-image:hover {
        box-shadow: 0 8px 30px rgba(0,0,0,0.1);
        transform: translateY(-2px);
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

    .feature-tile {
        transition: background var(--duration-normal);
    }

    .feature-tile:hover {
        background: var(--accent-light);
    }

    .feature-tile h4 {
        font-family: 'IBM Plex Sans', sans-serif;
        font-weight: 600;
        font-size: 0.95rem;
        margin-bottom: 0.4rem;
        color: var(--text);
    }

    .feature-tile p {
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 0.9rem;
        color: var(--text-secondary);
        line-height: 1.6;
    }

    @media (max-width: 768px) {
        .features-small { grid-template-columns: 1fr; }
        .feature-tile { border-right: none !important; padding: 1.5rem 0; }
    }

    /* ═══════════════════════
       PARTNERSHIP BANNER
    ═══════════════════════ */
    .partnership {
        border-top: 1px solid var(--border);
        overflow: hidden;
    }

    .partnership img {
        width: 100%;
        display: block;
    }

    /* ═══════════════════════
       VIDEO TESTIMONIAL
    ═══════════════════════ */
    .video-section {
        padding: 5rem 0;
        border-top: 1px solid var(--border);
    }

    .video-inner {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 4rem;
        align-items: center;
    }

    .video-text h2 {
        font-size: clamp(1.75rem, 4vw, 2.25rem);
        font-style: normal;
        margin-bottom: 1rem;
    }

    .video-text p {
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 1rem;
        color: var(--text-secondary);
        line-height: 1.65;
    }

    .video-container {
        position: relative;
        border-radius: 16px;
        overflow: hidden;
        background: #000;
        aspect-ratio: 9/16;
        max-width: 300px;
        margin: 0 auto;
        box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        cursor: pointer;
    }

    .video-container video {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .video-play-overlay {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(0,0,0,0.3);
        transition: opacity 0.3s;
    }

    .video-play-overlay i {
        width: 60px; height: 60px;
        background: rgba(255,255,255,0.9);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--accent);
        font-size: 1.25rem;
        padding-left: 3px;
    }

    .video-container.playing .video-play-overlay { opacity: 0; pointer-events: none; }

    .video-badge {
        position: absolute;
        top: 1rem; left: 1rem;
        background: #ef4444;
        color: #fff;
        font-family: 'IBM Plex Mono', monospace;
        font-size: 0.65rem;
        font-weight: 700;
        padding: 4px 10px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        gap: 5px;
        z-index: 2;
    }

    .video-badge-dot {
        width: 6px; height: 6px;
        background: #fff;
        border-radius: 50%;
        animation: vPulse 1.5s infinite;
    }

    @keyframes vPulse { 0%,100% { opacity:1; } 50% { opacity:0.4; } }

    @media (max-width: 768px) {
        .video-inner { grid-template-columns: 1fr; }
        .video-container { max-width: 260px; }
    }

    /* ═══════════════════════
       SECURITY + SUPPORT SECTIONS
    ═══════════════════════ */
    .info-section {
        padding: 5rem 0;
        border-top: 1px solid var(--border);
    }

    .info-grid {
        display: grid;
        grid-template-columns: 1fr 1.4fr;
        gap: 2.5rem;
        align-items: center;
    }

    .info-grid.reverse { direction: rtl; }
    .info-grid.reverse > * { direction: ltr; }

    .info-text h2 {
        font-size: clamp(1.75rem, 4vw, 2.25rem);
        font-style: normal;
        margin-bottom: 1.25rem;
    }

    .info-text p {
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 1rem;
        color: var(--text-secondary);
        line-height: 1.65;
        margin-bottom: 0.75rem;
    }

    .info-image {
        border-radius: 12px;
        overflow: hidden;
    }

    .info-image img {
        width: 100%;
        display: block;
    }

    @media (max-width: 768px) {
        .info-grid, .info-grid.reverse { grid-template-columns: 1fr; direction: ltr; }
        .info-image { order: -1; }
    }

    /* Full-width image banner */
    .image-banner {
        border-top: 1px solid var(--border);
        overflow: hidden;
    }

    .image-banner img {
        width: 100%;
        display: block;
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
        font-size: clamp(1.75rem, 4vw, 2.25rem);
        font-style: normal;
        margin-bottom: 0.75rem;
    }

    .pricing-sub {
        text-align: center;
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 0.9rem;
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
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 0.65rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        background: var(--accent);
        color: #fff;
        padding: 3px 8px;
        border-radius: 3px;
    }

    .price-col {
        transition: background var(--duration-normal);
    }

    .price-col:hover {
        background: var(--accent-light);
    }

    .price-name {
        font-family: 'IBM Plex Sans', sans-serif;
        font-weight: 700;
        font-size: 1rem;
        margin-bottom: 0.25rem;
    }

    .price-desc {
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 0.78rem;
        color: var(--text-muted);
        margin-bottom: 1.5rem;
    }

    .price-amount {
        font-family: 'IBM Plex Serif', serif;
        font-size: 2.75rem;
        font-weight: 300;
        color: var(--text);
        line-height: 1;
        margin-bottom: 0.25rem;
    }

    .price-amount span {
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 0.8rem;
        color: var(--text-muted);
    }

    .price-period {
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 0.75rem;
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
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 0.82rem;
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
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 0.85rem;
        font-weight: 500;
        border: 1px solid var(--border-strong);
        background: transparent;
        color: var(--text-secondary);
        cursor: pointer;
        transition: all var(--duration-normal) var(--ease-out);
    }

    .price-btn:hover {
        border-color: var(--accent);
        color: var(--accent);
        transform: translateY(-1px);
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
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 0.75rem;
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
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: var(--text-muted);
        margin-bottom: 1.5rem;
    }

    .letter h2 {
        font-size: clamp(1.75rem, 4vw, 2.25rem);
        font-style: normal;
        margin-bottom: 1.25rem;
    }

    .letter p {
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 1rem;
        color: var(--text-secondary);
        line-height: 1.65;
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
        background: var(--footer-bg);
        color: var(--footer-text);
        border-top: none;
        transition: background var(--duration-slow);
    }

    .footer-grid {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr;
        gap: 3rem;
        margin-bottom: 3rem;
    }

    .footer-brand p {
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 0.82rem;
        color: var(--footer-link);
        line-height: 1.6;
        margin-top: 1rem;
        max-width: 260px;
    }

    .footer .nav-logo span { color: var(--footer-text) !important; }

    .footer-col h4 {
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--footer-text);
        margin-bottom: 1rem;
        font-weight: 600;
    }

    .footer-col ul {
        list-style: none;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .footer-col a {
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 0.82rem;
        color: var(--footer-link);
        transition: color var(--duration-normal);
    }

    .footer-col a:hover { color: var(--footer-text); }

    .footer-bottom {
        border-top: 1px solid var(--footer-border);
        padding-top: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .footer-bottom p {
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 0.75rem;
        color: var(--footer-link);
    }

    .footer-social {
        display: flex;
        gap: 1rem;
    }

    .footer-social a {
        color: var(--footer-link);
        font-size: 0.9rem;
        transition: color var(--duration-normal);
    }

    .footer-social a:hover { color: var(--footer-text); }

    @media (max-width: 768px) {
        .footer-grid { grid-template-columns: 1fr 1fr; gap: 2rem; }
    }
    @media (max-width: 480px) {
        .footer-grid { grid-template-columns: 1fr; }
    }

    /* ─── Scroll reveal ─── */
    .reveal {
        opacity: 0;
        transform: translateY(24px);
        transition: opacity 0.7s var(--ease-out), transform 0.7s var(--ease-out);
    }
    .reveal.visible {
        opacity: 1;
        transform: none;
    }
    .reveal-d1 { transition-delay: 80ms; }
    .reveal-d2 { transition-delay: 160ms; }
    .reveal-d3 { transition-delay: 240ms; }

    /* Hero fade-up animation — like zed.dev */
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: none; }
    }

    .hero .reveal.visible {
        animation: fadeUp 0.8s var(--ease-out) forwards;
    }
    .hero .reveal.visible.reveal-d1 { animation-delay: 100ms; }
    .hero .reveal.visible.reveal-d2 { animation-delay: 200ms; }
    .hero .reveal.visible.reveal-d3 { animation-delay: 300ms; }

    /* ─── PREMIUM ANIMATIONS ─── */

    /* 1. Gradient text shimmer on hero h1 */
    .hero h1 {
        background: linear-gradient(
            90deg,
            var(--accent) 0%,
            #5e91de 25%,
            var(--accent) 50%,
            #5e91de 75%,
            var(--accent) 100%
        );
        background-size: 200% auto;
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        animation: gradientText 4s linear infinite;
    }

    html.dark .hero h1 {
        background: linear-gradient(
            90deg,
            #4b8af0 0%,
            #7ab0ff 25%,
            #4b8af0 50%,
            #7ab0ff 75%,
            #4b8af0 100%
        );
        background-size: 200% auto;
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        animation: gradientText 4s linear infinite;
    }

    @keyframes gradientText {
        to { background-position: 200% center; }
    }

    /* 2. Slide-in from left/right for feature-big sections */
    .reveal-left {
        opacity: 0;
        transform: translateX(-40px);
        transition: opacity 0.8s var(--ease-out), transform 0.8s var(--ease-out);
    }
    .reveal-right {
        opacity: 0;
        transform: translateX(40px);
        transition: opacity 0.8s var(--ease-out), transform 0.8s var(--ease-out);
    }
    .reveal-left.visible, .reveal-right.visible {
        opacity: 1;
        transform: none;
    }

    /* 3. Scale-in for hero image */
    .reveal-scale {
        opacity: 0;
        transform: scale(0.92);
        transition: opacity 0.9s var(--ease-out), transform 0.9s var(--ease-out);
    }
    .reveal-scale.visible {
        opacity: 1;
        transform: none;
    }

    /* 4. Floating blobs / decorative shapes behind hero */
    .hero-blobs {
        position: absolute;
        inset: 0;
        overflow: hidden;
        pointer-events: none;
        z-index: -1;
    }

    .blob {
        position: absolute;
        filter: blur(60px);
        opacity: 0.15;
        animation: blobMorph 10s ease-in-out infinite;
    }

    html.dark .blob { opacity: 0.07; }

    .blob-1 {
        width: 400px; height: 400px;
        background: linear-gradient(135deg, var(--accent), #5e91de);
        top: -10%; right: 10%;
    }
    .blob-2 {
        width: 300px; height: 300px;
        background: linear-gradient(135deg, #5e91de, #87ceeb);
        bottom: -5%; left: 5%;
        animation-delay: -4s;
        animation-duration: 13s;
        animation-direction: reverse;
    }
    .blob-3 {
        width: 200px; height: 200px;
        background: linear-gradient(135deg, var(--accent), #5e91de);
        top: 40%; left: 60%;
        animation-delay: -7s;
        animation-duration: 16s;
    }

    @keyframes blobMorph {
        0%, 100% {
            border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%;
            transform: translate(0, 0) scale(1);
        }
        25% {
            border-radius: 30% 60% 70% 40% / 50% 60% 30% 60%;
            transform: translate(30px, -40px) scale(1.05);
        }
        50% {
            border-radius: 70% 30% 60% 40% / 30% 60% 50% 70%;
            transform: translate(-20px, 20px) scale(0.95);
        }
        75% {
            border-radius: 40% 60% 30% 70% / 70% 40% 60% 30%;
            transform: translate(40px, 30px) scale(1.02);
        }
    }

    /* 11. Clip-path text reveal on hero subtitle */
    @keyframes clipReveal {
        from { clip-path: inset(0 100% 0 0); }
        to { clip-path: inset(0 0 0 0); }
    }

    .hero .reveal.visible.hero-sub {
        animation: clipReveal 0.9s var(--ease-out) 0.15s both;
    }

    /* 5. Shimmer line separator between sections */
    .shimmer-line {
        height: 1px;
        background: linear-gradient(90deg, transparent 0%, var(--accent) 50%, transparent 100%);
        background-size: 200% 100%;
        animation: shimmerSlide 3s ease-in-out infinite;
        opacity: 0.3;
    }

    @keyframes shimmerSlide {
        0%, 100% { background-position: 200% 0; }
        50% { background-position: -200% 0; }
    }

    /* 6. Button pulse on hero CTA */
    .btn-primary {
        position: relative;
    }

    .btn-primary::after {
        content: '';
        position: absolute;
        inset: -3px;
        border-radius: inherit;
        background: var(--accent);
        opacity: 0;
        z-index: -1;
        animation: btnPulse 2.5s var(--ease-out) infinite;
    }

    @keyframes btnPulse {
        0% { opacity: 0.4; transform: scale(1); }
        100% { opacity: 0; transform: scale(1.15); }
    }

    /* 7. Pillar cards stagger slide-up */
    .pillar {
        opacity: 0;
        transform: translateY(20px);
        transition: opacity 0.6s var(--ease-out), transform 0.6s var(--ease-out);
    }
    .pillars.in-view .pillar:nth-child(1) { opacity: 1; transform: none; transition-delay: 0ms; }
    .pillars.in-view .pillar:nth-child(2) { opacity: 1; transform: none; transition-delay: 150ms; }
    .pillars.in-view .pillar:nth-child(3) { opacity: 1; transform: none; transition-delay: 300ms; }

    /* 8. Testimonial cards tilt on hover */
    .testimonial {
        transition: all 0.35s var(--ease-out);
    }
    .testimonial:hover {
        transform: translateY(-4px) rotate(-0.5deg);
    }

    /* 9. Feature tile icon appear */
    .feature-tile {
        position: relative;
        overflow: hidden;
    }
    .feature-tile::after {
        content: '';
        position: absolute;
        top: 0; left: -100%;
        width: 60%;
        height: 100%;
        background: linear-gradient(90deg, transparent, var(--accent-light), transparent);
        transition: left 0.6s var(--ease-out);
    }
    .feature-tile:hover::after {
        left: 100%;
    }

    /* 10. Pricing card lift on hover */
    .price-col {
        transition: all 0.3s var(--ease-out);
    }
    .price-col:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(4,83,203,0.1);
    }

    /* Disable transitions briefly during theme switch */
    .no-transitions,
    .no-transitions *,
    .no-transitions *::before,
    .no-transitions *::after {
        transition-duration: 0s !important;
    }

    /* ═══════════════════════
       FAQ
    ═══════════════════════ */
    .faq {
        padding: 5rem 0;
        border-top: 1px solid var(--border);
    }

    .faq h2 {
        text-align: center;
        font-size: clamp(1.75rem, 4vw, 2.25rem);
        font-style: normal;
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
        font-family: 'IBM Plex Sans', sans-serif;
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
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 0.875rem;
        color: var(--text-secondary);
        line-height: 1.65;
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
        font-size: clamp(1.75rem, 4vw, 2.25rem);
        font-style: normal;
        margin-bottom: 1rem;
    }

    .contact-text p {
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 1rem;
        color: var(--text-secondary);
        line-height: 1.65;
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
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 0.875rem;
        color: var(--text-secondary);
    }

    .contact-form {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 2rem;
        transition: background var(--duration-slow), border-color var(--duration-slow);
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
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 0.72rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--text-muted);
        margin-bottom: 0.4rem;
        font-weight: 600;
    }

    .contact-form input,
    .contact-form select,
    .contact-form textarea {
        width: 100%;
        padding: 0.6rem 0.75rem;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 0.875rem;
        color: var(--text);
        background: var(--bg);
        transition: border-color var(--duration-normal), background var(--duration-slow);
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
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 0.72rem;
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
        font-family: 'IBM Plex Sans', sans-serif;
        font-weight: 600;
        color: var(--text);
        margin-bottom: 0.5rem;
    }

    .contact-success p {
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 0.875rem;
        color: var(--text-secondary);
    }

    @media (max-width: 768px) {
        .contact-inner { grid-template-columns: 1fr; gap: 2rem; }
        .contact-form .form-row { grid-template-columns: 1fr; }
    }

    /* ═══════════════════════
       THEME TOGGLE
    ═══════════════════════ */
    .theme-toggle {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        height: 34px;
        padding: 0 0.75rem;
        border: 1px solid var(--border-strong);
        border-radius: var(--radius);
        background: var(--bg-card);
        cursor: pointer;
        color: var(--text);
        transition: all var(--duration-normal) var(--ease-out);
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 0.78rem;
        font-weight: 500;
        letter-spacing: -0.02em;
    }

    .theme-toggle:hover {
        border-color: var(--accent);
        background: var(--accent-light);
        color: var(--accent);
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(4,83,203,0.12);
    }

    .theme-toggle svg {
        width: 15px;
        height: 15px;
        flex-shrink: 0;
    }

    .theme-toggle .sun-group,
    .theme-toggle .moon-group {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        position: absolute;
        left: 50%;
        transform: translateX(-50%);
        white-space: nowrap;
        transition: opacity 0.6s var(--ease-out), transform 0.6s var(--ease-out);
    }

    .theme-toggle {
        position: relative;
        width: 100px;
        justify-content: center;
    }

    .theme-toggle .sun-group { opacity: 0; transform: translateX(-50%) translateY(8px); pointer-events: none; }
    .theme-toggle .moon-group { opacity: 1; transform: translateX(-50%) translateY(0); }
    html.dark .theme-toggle .sun-group { opacity: 1; transform: translateX(-50%) translateY(0); pointer-events: auto; }
    html.dark .theme-toggle .moon-group { opacity: 0; transform: translateX(-50%) translateY(-8px); pointer-events: none; }

    /* Theme switch flash animation */
    @keyframes themeFlash {
        0% { opacity: 1; }
        40% { opacity: 0.7; }
        100% { opacity: 1; }
    }

    html.theme-switching body {
        animation: themeFlash 0.8s var(--ease-out);
    }

    /* ═══════════════════════
       FEATURE MODAL
    ═══════════════════════ */
    .feat-modal-overlay {
        position: fixed;
        inset: 0;
        z-index: 200;
        background: rgba(0,0,0,0.5);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
        opacity: 0;
        pointer-events: none;
        transition: opacity var(--duration-slow) var(--ease-out);
    }

    .feat-modal-overlay.open {
        opacity: 1;
        pointer-events: auto;
    }

    .feat-modal {
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        max-width: 720px;
        width: 100%;
        max-height: 85vh;
        overflow-y: auto;
        transform: translateY(24px) scale(0.97);
        transition: transform var(--duration-slow) var(--ease-out);
        box-shadow: 0 20px 60px rgba(0,0,0,0.15);
    }

    .feat-modal-overlay.open .feat-modal {
        transform: none;
    }

    .feat-modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1.5rem 2rem 0;
    }

    .feat-modal-header h3 {
        font-family: 'IBM Plex Serif', serif;
        font-weight: 300;
        font-size: 1.5rem;
        color: var(--accent);
        letter-spacing: -0.02em;
    }

    .feat-modal-close {
        width: 32px;
        height: 32px;
        border: 1px solid var(--border);
        border-radius: var(--radius);
        background: none;
        cursor: pointer;
        color: var(--text-muted);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        transition: all var(--duration-normal);
        flex-shrink: 0;
    }

    .feat-modal-close:hover {
        color: var(--text);
        border-color: var(--border-strong);
        background: var(--accent-light);
    }

    .feat-modal-image {
        margin: 1.5rem 2rem;
        border-radius: var(--radius);
        overflow: hidden;
        border: 1px solid var(--border);
    }

    .feat-modal-image img {
        width: 100%;
        display: block;
    }

    .feat-modal-body {
        padding: 0 2rem 2rem;
    }

    .feat-modal-body p {
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 1rem;
        color: var(--text-secondary);
        line-height: 1.65;
        margin-bottom: 1rem;
    }

    .feat-modal-body ul {
        list-style: none;
        padding: 0;
        margin: 1rem 0;
        display: flex;
        flex-direction: column;
        gap: 0.6rem;
    }

    .feat-modal-body ul li {
        font-family: 'IBM Plex Sans', sans-serif;
        font-size: 0.95rem;
        color: var(--text-secondary);
        display: flex;
        align-items: flex-start;
        gap: 0.6rem;
    }

    .feat-modal-body ul li::before {
        content: '✓';
        color: var(--accent);
        font-weight: 700;
        flex-shrink: 0;
    }

    .feat-modal-cta {
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid var(--border);
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    @media (max-width: 640px) {
        .feat-modal-overlay { padding: 1rem; }
        .feat-modal-header, .feat-modal-image, .feat-modal-body { padding-left: 1.25rem; padding-right: 1.25rem; }
        .feat-modal-image { margin-left: 1.25rem; margin-right: 1.25rem; }
    }

    /* ═══════════════════════
       BORDER TRANSITION
    ═══════════════════════ */
    .pillars, .proof, .features, .video-section,
    .info-section, .pricing, .faq, .contact, .letter,
    .partnership, .image-banner {
        transition: border-color var(--duration-slow);
    }

    .pricing-grid, .price-col, .pillar,
    .feature-big, .features-small, .faq-item {
        transition: border-color var(--duration-slow);
        border-color: var(--border);
    }

    </style>
    <script>
        // Prevent flash — set theme before body renders
        (function(){var t=localStorage.getItem('klassci-theme');var h=document.documentElement;if(t==='dark'){h.classList.add('dark');h.classList.remove('light');}else{h.classList.add('light');h.classList.remove('dark');}})();
    </script>
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
            <li>
                <button class="theme-toggle" id="themeToggle" aria-label="Changer le thème">
                    <span class="moon-group">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                        <span>Sombre</span>
                    </span>
                    <span class="sun-group">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                        <span>Clair</span>
                    </span>
                </button>
            </li>
        </ul>
        <div class="nav-mobile-actions">
            <button class="theme-toggle-mini" id="themeToggleMobile" aria-label="Changer le thème">
                <svg class="moon-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                <svg class="sun-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
            </button>
            <button class="nav-hamburger" id="hamburger" aria-label="Menu">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>
</nav>

<div class="mobile-nav" id="mobileNav">
    <a href="#fonctionnalites" onclick="closeMobile()">Fonctionnalités</a>
    <a href="#tarifs" onclick="closeMobile()">Tarifs</a>
    <a href="#faq" onclick="closeMobile()">FAQ</a>
    <a href="#contact" onclick="closeMobile()">Contact</a>
    <a href="{{ route('login') }}" class="mobile-nav-cta">Se connecter</a>
</div>

<!-- HERO -->
<section class="hero" style="position:relative">
    <div class="hero-blobs">
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>
    </div>
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

        <div class="hero-image reveal-scale">
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
        <div class="feature-big">
            <div class="feature-big-text reveal-left">
                <h3>Saisie des notes et bulletins</h3>
                <p>Les enseignants saisissent leurs notes directement depuis leur téléphone. Les moyennes, rangs et appréciations se calculent automatiquement. Les bulletins PDF sont générés en un clic, personnalisés aux couleurs de votre école.</p>
                <a href="#" class="feat-modal-trigger" data-feature="notes">En savoir plus &gt;</a>
            </div>
            <div class="feature-big-image reveal-right">
                <img src="{{ asset('images/landing/Saisie_des_notes_et_bulletins.png') }}" alt="Saisie des notes et bulletins — fiche étudiant KLASSCI" loading="lazy">
            </div>
        </div>

        <!-- Big feature 2 -->
        <div class="feature-big">
            <div class="feature-big-text reveal-right">
                <h3>Suivi financier en temps réel</h3>
                <p>Visualisez instantanément l'état des paiements de chaque étudiant. Envoyez des relances automatiques, générez des reçus, et exportez vos rapports financiers. Compatible avec le système de frais par catégorie.</p>
                <a href="#" class="feat-modal-trigger" data-feature="finance">En savoir plus &gt;</a>
            </div>
            <div class="feature-big-image reveal-left">
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

<!-- PARTNERSHIP BANNER — Devenez Partenaire -->
<section class="partnership reveal">
    <img src="{{ asset('images/Images landingPage/Sans titre - 2-02.png') }}" alt="Devenez partenaire KLASSCI — 0 FCFA" loading="lazy">
</section>

<!-- VIDEO TESTIMONIAL -->
<section class="video-section">
    <div class="container">
        <div class="video-inner reveal">
            <div class="video-text">
                <h2>Ils en parlent mieux que nous</h2>
                <p>Découvrez le témoignage d'un responsable d'établissement qui utilise KLASSCI au quotidien pour gérer ses étudiants, ses enseignants et ses finances.</p>
            </div>
            <div class="video-container" id="videoContainer">
                <div class="video-badge"><span class="video-badge-dot"></span> TÉMOIGNAGE</div>
                <video id="testimonialVideo" muted loop playsinline preload="none"
                    data-src="{{ asset('images/WhatsApp Video 2025-11-02 at 12.10.55 PM.mp4') }}">
                </video>
                <div class="video-play-overlay" id="videoOverlay">
                    <i class="fas fa-play"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SECURITY -->
<section class="info-section">
    <div class="container">
        <div class="info-grid reveal">
            <div class="info-image">
                <img src="{{ asset('images/Images landingPage/Sans titre - 2-04.png') }}" alt="Sécurité et protection des données KLASSCI" loading="lazy">
            </div>
            <div class="info-text">
                <h2>Sécurité et confiance totales</h2>
                <p>Nous garantissons la protection optimale de vos données, dans le respect strict des normes RGPD. Notre équipe dédiée à la cybersécurité travaille en continu pour renforcer nos dispositifs de protection.</p>
                <p>Chaque établissement dispose de sa propre base de données isolée — aucun partage de données entre écoles.</p>
            </div>
        </div>
    </div>
</section>

<!-- SUPPORT CLIENT -->
<section class="info-section">
    <div class="container">
        <div class="info-grid reverse reveal">
            <div class="info-text">
                <h2>Support client disponible 24h/24</h2>
                <p>Accédez à des informations fiables à tout moment grâce à notre chatbot intégré. Échangez directement avec notre service client par email, WhatsApp ou Telegram.</p>
                <p>Nos agents maîtrisent le français et l'anglais. Temps de réponse moyen : moins de 2 minutes.</p>
            </div>
            <div class="info-image">
                <img src="{{ asset('images/Images landingPage/bulles.png') }}" alt="Support client KLASSCI — chat et assistance" loading="lazy" style="max-width: 320px; margin: 0 auto;">
            </div>
        </div>
    </div>
</section>

<!-- IMAGE CTA — Students banner -->
<section class="image-banner reveal">
    <img src="{{ asset('images/Images landingPage/Sans titre - 2-03.png') }}" alt="Étudiants KLASSCI" loading="lazy">
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

<!-- FEATURE MODAL -->
<div class="feat-modal-overlay" id="featModalOverlay">
    <div class="feat-modal">
        <div class="feat-modal-header">
            <h3 id="featModalTitle"></h3>
            <button class="feat-modal-close" id="featModalClose" aria-label="Fermer">&times;</button>
        </div>
        <div class="feat-modal-image">
            <img id="featModalImg" src="" alt="" loading="lazy">
        </div>
        <div class="feat-modal-body" id="featModalBody"></div>
    </div>
</div>

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
    // Theme toggle — persist in localStorage
    var html = document.documentElement;
    var saved = localStorage.getItem('klassci-theme');
    if (saved === 'dark') {
        html.classList.add('dark');
        html.classList.remove('light');
    } else {
        html.classList.add('light');
        html.classList.remove('dark');
    }

    function doToggleTheme() {
        html.classList.toggle('dark');
        html.classList.toggle('light');
        html.classList.add('theme-switching');
        localStorage.setItem('klassci-theme', html.classList.contains('dark') ? 'dark' : 'light');
        setTimeout(function() {
            html.classList.remove('theme-switching');
        }, 850);
    }

    var toggle = document.getElementById('themeToggle');
    var toggleMob = document.getElementById('themeToggleMobile');
    if (toggle) toggle.addEventListener('click', doToggleTheme);
    if (toggleMob) toggleMob.addEventListener('click', doToggleTheme);

    // Mobile nav
    var btn = document.getElementById('hamburger');
    var mob = document.getElementById('mobileNav');
    btn.addEventListener('click', function() { mob.classList.toggle('open'); });
    window.closeMobile = function() { mob.classList.remove('open'); };

    // Scroll reveal — supports .reveal, .reveal-left, .reveal-right, .reveal-scale
    var els = document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-scale');
    var obs = new IntersectionObserver(function(entries) {
        entries.forEach(function(e) {
            if (e.isIntersecting) { e.target.classList.add('visible'); obs.unobserve(e.target); }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });
    els.forEach(function(el) { obs.observe(el); });

    // Pillar stagger animation
    var pillars = document.querySelector('.pillars');
    if (pillars) {
        var pObs = new IntersectionObserver(function(entries) {
            entries.forEach(function(e) {
                if (e.isIntersecting) { e.target.classList.add('in-view'); pObs.unobserve(e.target); }
            });
        }, { threshold: 0.2 });
        pObs.observe(pillars);
    }

    // Video lazy load + play/pause
    var video = document.getElementById('testimonialVideo');
    var vContainer = document.getElementById('videoContainer');
    var vOverlay = document.getElementById('videoOverlay');
    var videoLoaded = false;

    if (video && vContainer) {
        // Lazy load when visible
        var vObs = new IntersectionObserver(function(entries) {
            entries.forEach(function(e) {
                if (e.isIntersecting && !videoLoaded) {
                    var src = video.dataset.src;
                    if (src) {
                        var source = document.createElement('source');
                        source.src = src;
                        source.type = 'video/mp4';
                        video.appendChild(source);
                        video.load();
                        video.addEventListener('canplay', function() {
                            video.play().catch(function() {});
                            vContainer.classList.add('playing');
                            videoLoaded = true;
                        }, { once: true });
                    }
                    vObs.unobserve(video);
                }
            });
        }, { rootMargin: '100px' });
        vObs.observe(video);

        // Click to toggle play/pause
        vContainer.addEventListener('click', function() {
            if (!videoLoaded) return;
            if (video.paused) {
                video.play();
                video.muted = false;
                vContainer.classList.add('playing');
            } else {
                video.pause();
                vContainer.classList.remove('playing');
            }
        });
    }

    // Feature modal
    var featureData = {
        notes: {
            title: 'Saisie des notes et bulletins',
            img: '{{ asset("images/landing/Saisie_des_notes_et_bulletins.png") }}',
            body: '<p>KLASSCI transforme la gestion des notes en un processus simple et rapide. Fini les tableaux Excel partagés et les erreurs de saisie.</p>' +
                '<ul>' +
                '<li>Saisie en ligne depuis téléphone, tablette ou ordinateur</li>' +
                '<li>Calcul automatique des moyennes, rangs et appréciations</li>' +
                '<li>Bulletins PDF personnalisés aux couleurs de votre école</li>' +
                '<li>Gestion des coefficients, crédits et matières par classe</li>' +
                '<li>Historique complet des notes par étudiant et par semestre</li>' +
                '<li>Export en lot pour impression ou archivage</li>' +
                '</ul>' +
                '<p>Les enseignants saisissent directement — les secrétaires n\'ont plus à recopier. Le temps de production des bulletins passe de 3 semaines à 48 heures.</p>' +
                '<div class="feat-modal-cta"><a href="#contact" class="btn-primary" onclick="closeFeatModal()">Demander une démo</a><a href="#tarifs" class="btn-outline" onclick="closeFeatModal()">Voir les tarifs</a></div>'
        },
        finance: {
            title: 'Suivi financier en temps réel',
            img: '{{ asset("images/landing/Suivi_financier_en_temps_réel.png") }}',
            body: '<p>Le module comptabilité de KLASSCI donne une vision claire et instantanée de la santé financière de votre établissement.</p>' +
                '<ul>' +
                '<li>Dashboard avec KPI en temps réel : recettes, créances, taux de recouvrement</li>' +
                '<li>Suivi des paiements par étudiant avec historique complet</li>' +
                '<li>Relances automatiques par email avec modèles personnalisables</li>' +
                '<li>Génération de reçus de paiement et situations financières PDF</li>' +
                '<li>Rapports financiers par période, classe ou catégorie de frais</li>' +
                '<li>Système de frais flexible : inscription, scolarité, examens, etc.</li>' +
                '</ul>' +
                '<p>Les comptables voient en un coup d\'œil qui a payé, qui est en retard, et peuvent agir immédiatement — sans attendre la fin du mois.</p>' +
                '<div class="feat-modal-cta"><a href="#contact" class="btn-primary" onclick="closeFeatModal()">Demander une démo</a><a href="#tarifs" class="btn-outline" onclick="closeFeatModal()">Voir les tarifs</a></div>'
        }
    };

    var featOverlay = document.getElementById('featModalOverlay');
    var featTitle = document.getElementById('featModalTitle');
    var featImg = document.getElementById('featModalImg');
    var featBody = document.getElementById('featModalBody');
    var featClose = document.getElementById('featModalClose');

    document.querySelectorAll('.feat-modal-trigger').forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            var key = this.dataset.feature;
            var data = featureData[key];
            if (!data) return;
            featTitle.textContent = data.title;
            featImg.src = data.img;
            featImg.alt = data.title;
            featBody.innerHTML = data.body;
            featOverlay.classList.add('open');
            document.body.style.overflow = 'hidden';
        });
    });

    window.closeFeatModal = function() {
        featOverlay.classList.remove('open');
        document.body.style.overflow = '';
    };

    if (featClose) {
        featClose.addEventListener('click', closeFeatModal);
    }

    featOverlay.addEventListener('click', function(e) {
        if (e.target === featOverlay) closeFeatModal();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && featOverlay.classList.contains('open')) closeFeatModal();
    });

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
