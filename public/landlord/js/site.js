/*
 * NovaxPOS public site behaviour. Plain JS, no dependencies.
 * Every block is opt-in by data attribute, so a page only pays for what it uses.
 */
(function () {
    'use strict';

    // ---- Header: transparent over a dark hero, solid once scrolled -------
    var header = document.querySelector('[data-site-header]');
    var menu = document.getElementById('mobileMenu');
    var burger = document.querySelector('[data-menu-toggle]');

    function syncHeader() {
        if (!header) return;
        var solid = header.hasAttribute('data-always-solid') || window.scrollY > 40 || (menu && !menu.hidden);
        header.classList.toggle('is-solid', solid);
    }
    window.addEventListener('scroll', syncHeader, { passive: true });
    syncHeader();

    if (burger && menu) {
        burger.addEventListener('click', function () {
            menu.hidden = !menu.hidden;
            burger.setAttribute('aria-expanded', String(!menu.hidden));
            syncHeader();
        });
        menu.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                menu.hidden = true;
                burger.setAttribute('aria-expanded', 'false');
                syncHeader();
            });
        });
    }

    // ---- Scroll reveal ---------------------------------------------------
    var revealables = document.querySelectorAll('.reveal');
    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
        revealables.forEach(function (el) { observer.observe(el); });
    } else {
        revealables.forEach(function (el) { el.classList.add('is-visible'); });
    }

    // ---- Hero slider -----------------------------------------------------
    var hero = document.querySelector('[data-hero]');
    if (hero) {
        var slides = hero.querySelectorAll('[data-slide]');
        var tabs = hero.querySelectorAll('[data-slide-tab]');
        var toggle = hero.querySelector('[data-hero-toggle]');
        var interval = parseInt(hero.getAttribute('data-interval'), 10) || 6000;
        var current = 0;
        var timer = null;
        var paused = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        hero.style.setProperty('--slide-ms', interval + 'ms');

        var show = function (index) {
            current = (index + slides.length) % slides.length;
            slides.forEach(function (s, i) {
                s.classList.toggle('is-active', i === current);
                s.setAttribute('aria-hidden', i === current ? 'false' : 'true');
            });
            tabs.forEach(function (t, i) {
                t.classList.toggle('is-active', i === current);
                t.classList.toggle('is-done', i < current);
                t.setAttribute('aria-selected', i === current ? 'true' : 'false');
                // Restart the progress bar on the newly active tab.
                var bar = t.querySelector('.hero__tab-bar');
                bar.style.animation = 'none';
                void bar.offsetWidth;
                bar.style.animation = '';
            });
            var active = tabs[current];
            var list = active && active.parentElement;
            if (list && list.scrollWidth > list.clientWidth) {
                list.scrollTo({ left: active.offsetLeft - 16, behavior: 'smooth' });
            }
        };

        var schedule = function () {
            clearTimeout(timer);
            if (!paused) timer = setTimeout(function () { show(current + 1); schedule(); }, interval);
        };

        var setPaused = function (value) {
            paused = value;
            hero.classList.toggle('is-paused', paused);
            if (toggle) {
                toggle.setAttribute('aria-label', paused ? 'Play slideshow' : 'Pause slideshow');
                toggle.querySelector('i').className = 'fas ' + (paused ? 'fa-play' : 'fa-pause');
            }
            schedule();
        };

        tabs.forEach(function (t, i) {
            t.addEventListener('click', function () { show(i); schedule(); });
        });
        if (toggle) toggle.addEventListener('click', function () { setPaused(!paused); });
        // Don't run through slides in a background tab.
        document.addEventListener('visibilitychange', function () {
            if (document.hidden) clearTimeout(timer); else schedule();
        });

        show(0);
        setPaused(paused);
    }

    // ---- Monthly / yearly pricing toggle --------------------------------
    // A [data-billing] container holds .only-monthly / .only-yearly content;
    // buttons with [data-billing-set] switch it.
    document.querySelectorAll('[data-billing]').forEach(function (scope) {
        var buttons = document.querySelectorAll('[data-billing-set]');
        var set = function (mode) {
            scope.setAttribute('data-billing', mode);
            buttons.forEach(function (b) {
                b.setAttribute('aria-pressed', b.getAttribute('data-billing-set') === mode ? 'true' : 'false');
            });
        };
        buttons.forEach(function (b) {
            b.addEventListener('click', function () { set(b.getAttribute('data-billing-set')); });
        });
    });

    // ---- Tabs (how it works, payment methods) ---------------------------
    document.querySelectorAll('[data-tabs]').forEach(function (group) {
        var btns = group.querySelectorAll('[role="tab"]');
        btns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                btns.forEach(function (b) {
                    var on = b === btn;
                    b.setAttribute('aria-selected', on ? 'true' : 'false');
                    b.tabIndex = on ? 0 : -1;
                    var panel = document.getElementById(b.getAttribute('aria-controls'));
                    if (panel) panel.hidden = !on;
                });
                var panel = document.getElementById(btn.getAttribute('aria-controls'));
                if (panel) panel.querySelectorAll('.reveal').forEach(function (el) { el.classList.add('is-visible'); });
            });
        });
    });
})();
