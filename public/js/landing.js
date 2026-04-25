/* KLASSCI — Editorial Landing (chrome JS)
 * Theme toggle (persisted), mobile nav, scroll
 * reveal observer, smooth anchor scroll. Page-
 * specific JS lives in each Blade via @push.
 */
(function () {
    'use strict';

    var html = document.documentElement;

    /* Theme : init from localStorage (also done inline in <head> to prevent FOUC) */
    var saved = localStorage.getItem('klassci-theme');
    if (saved === 'dark') {
        html.classList.add('dark');
        html.classList.remove('light');
    } else {
        html.classList.add('light');
        html.classList.remove('dark');
    }

    function toggleTheme() {
        html.classList.toggle('dark');
        html.classList.toggle('light');
        html.classList.add('theme-switching');
        localStorage.setItem(
            'klassci-theme',
            html.classList.contains('dark') ? 'dark' : 'light'
        );
        setTimeout(function () {
            html.classList.remove('theme-switching');
        }, 850);
    }

    var themeBtn = document.getElementById('themeToggle');
    var themeBtnMobile = document.getElementById('themeToggleMobile');
    if (themeBtn) themeBtn.addEventListener('click', toggleTheme);
    if (themeBtnMobile) themeBtnMobile.addEventListener('click', toggleTheme);

    /* Mobile nav */
    var hamburger = document.getElementById('hamburger');
    var mobileNav = document.getElementById('mobileNav');
    if (hamburger && mobileNav) {
        hamburger.addEventListener('click', function () {
            mobileNav.classList.toggle('open');
        });
        window.closeMobile = function () {
            mobileNav.classList.remove('open');
        };
    }

    /* Scroll reveal — supports .reveal, .reveal-left, .reveal-right, .reveal-scale */
    var reveals = document.querySelectorAll('.reveal, .reveal-left, .reveal-right, .reveal-scale');
    if ('IntersectionObserver' in window && reveals.length) {
        var obs = new IntersectionObserver(
            function (entries) {
                entries.forEach(function (e) {
                    if (e.isIntersecting) {
                        e.target.classList.add('visible');
                        obs.unobserve(e.target);
                    }
                });
            },
            { threshold: 0.1, rootMargin: '0px 0px -40px 0px' }
        );
        reveals.forEach(function (el) { obs.observe(el); });
    } else {
        /* No IO support → just show everything */
        reveals.forEach(function (el) { el.classList.add('visible'); });
    }

    /* Smooth anchor scroll (offsets for fixed nav) */
    document.querySelectorAll('a[href^="#"]').forEach(function (a) {
        a.addEventListener('click', function (e) {
            var h = this.getAttribute('href');
            if (h === '#' || h.length < 2) return;
            var target = document.querySelector(h);
            if (target) {
                e.preventDefault();
                window.scrollTo({
                    top: target.getBoundingClientRect().top + window.pageYOffset - 70,
                    behavior: 'smooth'
                });
            }
        });
    });

    /* Mark active sidebar entry based on hash + path */
    var path = window.location.pathname.replace(/\/$/, '') || '/';
    document.querySelectorAll('.page-sidebar a, .nav-links a').forEach(function (a) {
        try {
            var url = new URL(a.href, window.location.origin);
            var aPath = url.pathname.replace(/\/$/, '') || '/';
            if (aPath === path && (!url.hash || url.hash === window.location.hash)) {
                a.classList.add('is-active');
            }
        } catch (err) { /* ignore malformed URLs */ }
    });
})();
