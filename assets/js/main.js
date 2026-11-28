/* ARKI Environments – front-end behaviour (no dependencies) */
(function () {
    'use strict';

    document.documentElement.classList.add('js');

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

    /* =================================================================
     *  Product listing: filters
     * ================================================================= */
    var filterForm = document.querySelector('[data-shop-filters]');
    if (filterForm) {
        var submitFilters = function () {
            // Leave untouched price sliders out of the URL.
            filterForm.querySelectorAll('[data-price-range]').forEach(function (r) {
                var lo = r.querySelector('[data-price-min]'), hi = r.querySelector('[data-price-max]');
                if (+lo.value <= +r.dataset.min) lo.disabled = true;
                if (+hi.value >= +r.dataset.max) hi.disabled = true;
            });
            filterForm.querySelectorAll('input[type=search]').forEach(function (i) { if (!i.value.trim()) i.disabled = true; });
            filterForm.querySelectorAll('input[name=section][value=all]:checked').forEach(function (i) { i.disabled = true; }); // "All Products" = no section
            filterForm.querySelectorAll('select').forEach(function (s) { if (!s.value || (s.name === 'sort' && s.value === 'featured')) s.disabled = true; });
            document.querySelectorAll('select[form="' + filterForm.id + '"]').forEach(function (s) { if (s.value === 'featured') s.disabled = true; });
            filterForm.submit();
        };
        filterForm.addEventListener('submit', function (e) { e.preventDefault(); submitFilters(); });
        var autoSubmit = window.matchMedia('(min-width: 1101px)');
        document.addEventListener('change', function (e) {
            var t = e.target;
            if (!t.matches('[data-autosubmit]')) return;
            // On phones/tablets the panel has an Apply button; sort always applies at once.
            if (autoSubmit.matches || t.name === 'sort') submitFilters();
        });

        // Expand / collapse subcategories
        filterForm.querySelectorAll('[data-filter-toggle]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var item = btn.closest('.filter-item');
                var open = item.classList.toggle('is-open');
                btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        });

        // Dual price slider
        filterForm.querySelectorAll('[data-price-range]').forEach(function (r) {
            var lo = r.querySelector('[data-price-min]'), hi = r.querySelector('[data-price-max]');
            var out = r.querySelector('[data-price-output]'), cur = r.dataset.currency || '$', max = +r.dataset.max;
            var fmt = function (n) { return cur + ' ' + Number(n).toLocaleString('en-AU'); };
            var sync = function (e) {
                if (+lo.value > +hi.value) { if (e && e.target === lo) hi.value = lo.value; else lo.value = hi.value; }
                out.textContent = fmt(lo.value) + ' - ' + fmt(hi.value) + (+hi.value >= max ? '+' : '');
            };
            lo.addEventListener('input', sync);
            hi.addEventListener('input', sync);
            [lo, hi].forEach(function (i) { i.addEventListener('change', function () { if (autoSubmit.matches) submitFilters(); }); });
        });

        // Slide-in filter panel on tablets / phones
        var openBtn = document.querySelector('[data-filters-open]');
        var panel = document.getElementById('shop-filters');
        var setOpen = function (open) {
            document.body.classList.toggle('filters-open', open);
            if (open) { var f = panel.querySelector('input, select, button'); if (f) f.focus(); } else if (openBtn) { openBtn.focus(); }
        };
        if (openBtn) openBtn.addEventListener('click', function () { setOpen(true); });
        panel.querySelectorAll('[data-filters-close]').forEach(function (b) { b.addEventListener('click', function () { setOpen(false); }); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && document.body.classList.contains('filters-open')) setOpen(false); });
        document.addEventListener('click', function (e) {
            if (document.body.classList.contains('filters-open') && !panel.contains(e.target) && e.target !== openBtn && !openBtn.contains(e.target)) setOpen(false);
        });
    }

    /* =================================================================
     *  Product page
     * ================================================================= */
    var mainBox = document.querySelector('[data-gallery-main]');
    var setMain = function (src, webp, alt) {
        if (!mainBox || !src) return;
        var img = mainBox.querySelector('img');
        if (!img) return;
        var source = mainBox.querySelector('source');
        if (source) { if (webp) source.srcset = webp; else source.remove(); }
        img.removeAttribute('width');
        img.removeAttribute('height');
        img.src = src;
        if (alt !== undefined) img.alt = alt;
    };
    var thumbs = document.querySelectorAll('.product-gallery__thumb');
    thumbs.forEach(function (t) {
        t.addEventListener('click', function () {
            thumbs.forEach(function (x) { x.classList.remove('is-active'); x.setAttribute('aria-pressed', 'false'); });
            t.classList.add('is-active');
            t.setAttribute('aria-pressed', 'true');
            setMain(t.dataset.src, t.dataset.webp, t.dataset.alt);
        });
    });

    // Colour swatches (inline styles are blocked by the security policy, so set them here)
    document.querySelectorAll('.swatch__chip[data-hex]').forEach(function (c) {
        if (c.dataset.hex && /^#[0-9a-f]{3,6}$/i.test(c.dataset.hex)) c.style.backgroundColor = c.dataset.hex;
    });

    // Options → matching variant updates SKU, price, stock and image
    var variantData = document.getElementById('product-variants');
    var optionsBox = document.querySelector('[data-product-options]');
    if (variantData && optionsBox) {
        var variants = [];
        try { variants = JSON.parse(variantData.textContent) || []; } catch (e) { variants = []; }
        var skuEl = document.querySelector('[data-variant-sku]');
        var priceEl = document.querySelector('[data-variant-price]');
        var stockEl = document.querySelector('[data-variant-stock]');
        var base = {
            sku: skuEl ? skuEl.textContent : '',
            price: priceEl ? priceEl.innerHTML : '',
            stockKey: stockEl ? stockEl.dataset.key : '',
            stockLabel: stockEl ? stockEl.querySelector('.stock__label').textContent : ''
        };
        var firstThumb = document.querySelector('.product-gallery__thumb');
        var baseImg = mainBox && mainBox.querySelector('img') ? mainBox.querySelector('img').getAttribute('src') : null;
        var selected = function () {
            var get = function (sel) { var el = optionsBox.querySelector(sel); return el && el.value ? +el.value : null; };
            return {
                size: get('input[name=opt_size]:checked'),
                colour: get('input[name=opt_colour]:checked'),
                material: get('select[name=opt_material]')
            };
        };
        var apply = function () {
            var s = selected(), best = null, bestScore = 0;
            variants.forEach(function (v) {
                var score = 0, ok = true;
                ['size', 'colour', 'material'].forEach(function (k) {
                    if (v[k] === null) return;
                    if (s[k] === v[k]) score++; else ok = false;
                });
                if (ok && score > bestScore) { best = v; bestScore = score; }
            });
            if (skuEl) skuEl.textContent = (best && best.sku) || base.sku;
            if (priceEl) priceEl.innerHTML = (best && best.price) || base.price;
            if (stockEl) {
                var a = best && best.avail;
                stockEl.dataset.key = a ? a.key : base.stockKey;
                stockEl.querySelector('.stock__label').textContent = a ? a.label : base.stockLabel;
            }
            if (best && best.image) {
                setMain(best.image, best.webp);
            } else if (baseImg) {
                setMain(firstThumb ? firstThumb.dataset.src : baseImg, firstThumb ? firstThumb.dataset.webp : '');
            }
        };
        optionsBox.addEventListener('change', apply);
    }

    // Quantity stepper
    document.querySelectorAll('[data-qty]').forEach(function (q) {
        var input = q.querySelector('input');
        q.querySelectorAll('[data-qty-step]').forEach(function (b) {
            b.addEventListener('click', function () {
                var v = Math.min(999, Math.max(1, (parseInt(input.value, 10) || 1) + (+b.dataset.qtyStep)));
                input.value = v;
            });
        });
    });

    // Information tabs (without JavaScript all panels simply show)
    document.querySelectorAll('[data-tabs]').forEach(function (box) {
        var tabs = Array.prototype.slice.call(box.querySelectorAll('[role=tab]'));
        var show = function (tab, focus) {
            tabs.forEach(function (t) {
                var on = t === tab;
                t.setAttribute('aria-selected', on ? 'true' : 'false');
                t.tabIndex = on ? 0 : -1;
                var p = document.getElementById(t.getAttribute('aria-controls'));
                if (p) p.hidden = !on;
            });
            if (focus) tab.focus();
        };
        tabs.forEach(function (t, i) {
            t.addEventListener('click', function () { show(t); });
            t.addEventListener('keydown', function (e) {
                var n = e.key === 'ArrowRight' ? i + 1 : e.key === 'ArrowLeft' ? i - 1 : null;
                if (n === null) return;
                e.preventDefault();
                show(tabs[(n + tabs.length) % tabs.length], true);
            });
        });
        if (tabs.length) show(tabs.filter(function (t) { return t.getAttribute('aria-selected') === 'true'; })[0] || tabs[0]);
    });

    // Product video: load only when played
    document.querySelectorAll('[data-video]').forEach(function (box) {
        var btn = box.querySelector('.product-video__play');
        if (!btn) return;
        btn.addEventListener('click', function () {
            var el;
            if (box.dataset.videoSrc) {
                el = document.createElement('video');
                el.src = box.dataset.videoSrc;
                el.controls = true;
                el.autoplay = true;
                el.playsInline = true;
            } else if (box.dataset.videoEmbed) {
                el = document.createElement('iframe');
                el.src = box.dataset.videoEmbed;
                el.allow = 'autoplay; fullscreen; picture-in-picture';
                el.allowFullscreen = true;
                el.title = 'Product video';
            }
            if (el) { box.appendChild(el); btn.remove(); }
        });
    });

    /* Password fields: Show / Hide */
    document.querySelectorAll('[data-reveal]').forEach(function (btn) {
        var input = document.getElementById(btn.getAttribute('data-reveal'));
        if (!input) return;
        btn.addEventListener('click', function () {
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.textContent = show ? 'Hide' : 'Show';
            btn.setAttribute('aria-pressed', show ? 'true' : 'false');
            btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        });
        btn.setAttribute('aria-label', 'Show password');
    });

    /* Cart: typing a new quantity updates the cart straight away */
    document.querySelectorAll('[data-cart-qty]').forEach(function (input) {
        input.addEventListener('change', function () { if (input.form) input.form.submit(); });
    });

    /* Contact form: after sending, move focus to the success / error message */
    var notice = document.querySelector('[data-focus]');
    if (notice) notice.focus({ preventScroll: true });

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
