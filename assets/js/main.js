/* ARKI Environments – front-end behaviour (no dependencies) */
(function () {
    'use strict';

    var header = document.querySelector('.site-header');

    /* Mobile menu ------------------------------------------------------ */
    var toggle = document.querySelector('.nav-toggle');
    if (toggle && header) {
        toggle.addEventListener('click', function () {
            var open = header.classList.toggle('nav-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && header.classList.contains('nav-open')) {
                header.classList.remove('nav-open');
                toggle.setAttribute('aria-expanded', 'false');
                toggle.focus();
            }
        });
    }

    /* Dropdown sub-menus ------------------------------------------------ */
    document.querySelectorAll('.main-nav__subtoggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var item = btn.closest('.has-sub');
            var open = item.classList.toggle('is-open');
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    });
    document.addEventListener('click', function (e) {
        document.querySelectorAll('.has-sub.is-open').forEach(function (item) {
            if (!item.contains(e.target) && window.innerWidth > 900) {
                item.classList.remove('is-open');
                item.querySelector('.main-nav__subtoggle').setAttribute('aria-expanded', 'false');
            }
        });
    });

    /* Brand logo slider ------------------------------------------------- */
    document.querySelectorAll('[data-slider]').forEach(function (slider) {
        var track = slider.querySelector('[data-slider-track]');
        var prev = slider.querySelector('[data-slider-prev]');
        var next = slider.querySelector('[data-slider-next]');
        if (!track) return;

        function update() {
            var max = track.scrollWidth - track.clientWidth - 2;
            var scrollable = max > 0;
            [prev, next].forEach(function (b) { if (b) b.hidden = !scrollable; });
            if (prev) prev.disabled = track.scrollLeft <= 2;
            if (next) next.disabled = track.scrollLeft >= max;
        }
        function go(dir) { track.scrollBy({ left: dir * track.clientWidth, behavior: 'smooth' }); }

        if (prev) prev.addEventListener('click', function () { go(-1); });
        if (next) next.addEventListener('click', function () { go(1); });
        track.addEventListener('scroll', update, { passive: true });
        window.addEventListener('resize', update);
        update();
    });

    /* Favourite (heart) buttons on product cards – remembered in this browser */
    var FAV_KEY = 'arki-favourites';
    var favs = {};
    try { favs = JSON.parse(localStorage.getItem(FAV_KEY) || '{}') || {}; } catch (e) { favs = {}; }
    document.querySelectorAll('[data-fav]').forEach(function (btn) {
        var id = btn.getAttribute('data-fav');
        if (favs[id]) btn.setAttribute('aria-pressed', 'true');
        btn.addEventListener('click', function () {
            var on = btn.getAttribute('aria-pressed') !== 'true';
            btn.setAttribute('aria-pressed', on ? 'true' : 'false');
            if (on) { favs[id] = 1; } else { delete favs[id]; }
            try { localStorage.setItem(FAV_KEY, JSON.stringify(favs)); } catch (e) { /* storage disabled */ }
        });
    });

    /* Mouse drag-to-scroll for horizontal rows (touch scrolls natively) */
    document.querySelectorAll('[data-drag-scroll]').forEach(function (el) {
        var down = false, moved = false, startX = 0, startLeft = 0;

        el.addEventListener('pointerdown', function (e) {
            if (e.pointerType !== 'mouse' || e.button !== 0) return;
            down = true; moved = false; startX = e.clientX; startLeft = el.scrollLeft;
        });
        window.addEventListener('pointermove', function (e) {
            if (!down) return;
            var dx = e.clientX - startX;
            if (!moved && Math.abs(dx) > 5) { moved = true; el.classList.add('is-dragging'); }
            if (moved) { el.scrollLeft = startLeft - dx; e.preventDefault(); }
        });
        window.addEventListener('pointerup', function () {
            if (!down) return;
            down = false;
            setTimeout(function () { el.classList.remove('is-dragging'); }, 0);
        });
        el.addEventListener('dragstart', function (e) { e.preventDefault(); });
        el.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowRight') { el.scrollBy({ left: 300, behavior: 'smooth' }); }
            if (e.key === 'ArrowLeft') { el.scrollBy({ left: -300, behavior: 'smooth' }); }
        });
    });
})();
