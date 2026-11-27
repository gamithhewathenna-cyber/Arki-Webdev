/* ARKI CMS – small enhancements (no inline scripts, CSP friendly) */
(function () {
    'use strict';

    var MAX_BYTES = 10 * 1024 * 1024;

    // Confirm destructive actions
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!window.confirm(form.getAttribute('data-confirm'))) e.preventDefault();
        });
    });

    /* =================================================================
     *  Mandatory image cropper
     *  Every <input type=file data-crop='{"presets":[{w,h,label}],"fit":bool}'>
     *  opens the cropper as soon as a file is chosen. Only the cropped
     *  result is placed back in the input, so only it is uploaded.
     * ================================================================= */
    var Cropper = (function () {
        var el = {}, st = null, built = false;

        function h(tag, cls, parent, text) {
            var n = document.createElement(tag);
            if (cls) n.className = cls;
            if (text) n.textContent = text;
            if (parent) parent.appendChild(n);
            return n;
        }

        function build() {
            if (built) return;
            built = true;
            el.modal = h('div', 'crop-modal', document.body);
            el.modal.hidden = true;
            el.modal.setAttribute('role', 'dialog');
            el.modal.setAttribute('aria-modal', 'true');
            el.modal.setAttribute('aria-labelledby', 'crop-title');
            var box = h('div', 'crop-box', el.modal);

            var head = h('div', 'crop-head', box);
            el.title = h('h2', 'crop-title', head);
            el.title.id = 'crop-title';
            el.size = h('p', 'crop-size', head);

            el.presets = h('div', 'crop-presets', box);

            el.stage = h('div', 'crop-stage', box);
            el.stage.tabIndex = 0;
            el.stage.setAttribute('aria-label', 'Crop area. Drag or use the arrow keys to move the image, + and - to zoom.');
            el.img = h('img', 'crop-img', el.stage);
            el.img.alt = '';
            el.img.draggable = false;
            el.frame = h('div', 'crop-frame', el.stage);

            var tools = h('div', 'crop-tools', box);
            h('span', 'crop-zoom-label', tools, 'Zoom');
            el.zoom = h('input', 'crop-zoom', tools);
            el.zoom.type = 'range';
            el.zoom.min = '0';
            el.zoom.max = '1000';
            el.zoom.setAttribute('aria-label', 'Zoom');
            el.reset = h('button', 'adm-btn adm-btn--sm adm-btn--ghost', tools, 'Reset');
            el.reset.type = 'button';

            el.note = h('p', 'crop-note', box);

            var actions = h('div', 'crop-actions', box);
            el.cancel = h('button', 'adm-btn adm-btn--ghost', actions, 'Cancel');
            el.cancel.type = 'button';
            el.ok = h('button', 'adm-btn', actions, 'Crop & use image');
            el.ok.type = 'button';

            el.cancel.addEventListener('click', cancel);
            el.ok.addEventListener('click', confirm);
            el.reset.addEventListener('click', function () { layout(true); });
            el.zoom.addEventListener('input', function () {
                zoomTo(sliderToScale(+el.zoom.value), st.fx + st.fw / 2, st.fy + st.fh / 2);
            });
            el.modal.addEventListener('keydown', onKey);
            el.stage.addEventListener('wheel', onWheel, { passive: false });
            el.stage.addEventListener('pointerdown', onDown);
            el.stage.addEventListener('pointermove', onMove);
            el.stage.addEventListener('pointerup', onUp);
            el.stage.addEventListener('pointercancel', onUp);
            window.addEventListener('resize', function () { if (st) layout(false); });
        }

        /* ---- open / close ------------------------------------------ */
        function open(input, file, spec) {
            build();
            if (st && st.url) URL.revokeObjectURL(st.url);
            st = {
                input: input, file: file, spec: spec,
                preset: 0, url: URL.createObjectURL(file),
                natW: 0, natH: 0, s: 1, minS: 1, maxS: 1, x: 0, y: 0,
                fx: 0, fy: 0, fw: 0, fh: 0, drag: null
            };
            el.title.textContent = 'Crop image – ' + (spec.label || 'Image');
            el.presets.innerHTML = '';
            el.presets.hidden = spec.presets.length < 2;
            if (spec.presets.length > 1) {
                h('span', 'crop-presets__label', el.presets, 'Shape:');
                spec.presets.forEach(function (p, i) {
                    var b = h('button', 'crop-preset', el.presets, (p.label || 'Option ' + (i + 1)) + ' · ' + p.w + '×' + p.h);
                    b.type = 'button';
                    b.addEventListener('click', function () { st.preset = i; layout(true); });
                });
            }
            el.img.onload = function () {
                st.natW = el.img.naturalWidth;
                st.natH = el.img.naturalHeight;
                el.modal.hidden = false;
                document.body.classList.add('crop-open');
                layout(true);
                el.ok.focus();
            };
            el.img.onerror = function () {
                alert('This file could not be read as an image. Please choose a JPG, PNG, WebP or GIF.');
                clearInput(input);
            };
            el.img.src = st.url;
        }

        function close() {
            el.modal.hidden = true;
            document.body.classList.remove('crop-open');
            if (st && st.input) st.input.focus();
        }

        function cancel() {
            // No crop = no upload: clear the selection and restore the old preview.
            clearInput(st.input);
            close();
        }

        /* ---- geometry ---------------------------------------------- */
        function preset() { return st.spec.presets[st.preset]; }

        function layout(reset) {
            var p = preset();
            var sw = el.stage.clientWidth, sh = el.stage.clientHeight, pad = 28;
            var ratio = p.w / p.h;
            var fw = sw - pad * 2, fh = fw / ratio;
            if (fh > sh - pad * 2) { fh = sh - pad * 2; fw = fh * ratio; }
            var cx = reset ? null : (st.fx + st.fw / 2 - st.x) / st.s;   // keep focus point on resize
            var cy = reset ? null : (st.fy + st.fh / 2 - st.y) / st.s;
            st.fw = fw; st.fh = fh; st.fx = (sw - fw) / 2; st.fy = (sh - fh) / 2;

            var cover = Math.max(fw / st.natW, fh / st.natH);
            var contain = Math.min(fw / st.natW, fh / st.natH);
            st.minS = st.spec.fit ? contain : cover;
            st.maxS = Math.max(cover * 8, st.minS * 8);
            if (reset || cx === null) {
                st.s = st.spec.fit ? contain : cover;
                st.x = st.fx + (fw - st.natW * st.s) / 2;
                st.y = st.fy + (fh - st.natH * st.s) / 2;
            } else {
                st.s = Math.min(st.maxS, Math.max(st.minS, st.s));
                st.x = st.fx + fw / 2 - cx * st.s;
                st.y = st.fy + fh / 2 - cy * st.s;
            }
            el.frame.style.left = st.fx + 'px';
            el.frame.style.top = st.fy + 'px';
            el.frame.style.width = fw + 'px';
            el.frame.style.height = fh + 'px';
            el.frame.classList.toggle('is-fit', !!st.spec.fit);

            el.size.textContent = 'Final size: ' + p.w + ' × ' + p.h + ' px' + (st.spec.fit ? ' · transparent PNG' : '');
            Array.prototype.forEach.call(el.presets.querySelectorAll('.crop-preset'), function (b, i) {
                b.classList.toggle('is-active', i === st.preset);
                b.setAttribute('aria-pressed', i === st.preset ? 'true' : 'false');
            });
            clamp();
            render();
        }

        // Keep the frame covered (or, for logos, keep the image inside the frame on short axes).
        function clamp() {
            var iw = st.natW * st.s, ih = st.natH * st.s;
            st.x = iw >= st.fw ? Math.min(st.fx, Math.max(st.fx + st.fw - iw, st.x))
                               : Math.max(st.fx, Math.min(st.fx + st.fw - iw, st.x));
            st.y = ih >= st.fh ? Math.min(st.fy, Math.max(st.fy + st.fh - ih, st.y))
                               : Math.max(st.fy, Math.min(st.fy + st.fh - ih, st.y));
        }

        function render() {
            el.img.style.width = st.natW * st.s + 'px';
            el.img.style.height = st.natH * st.s + 'px';
            el.img.style.transform = 'translate(' + st.x + 'px,' + st.y + 'px)';
            el.zoom.value = String(Math.round(scaleToSlider(st.s)));

            // Low-resolution warning
            var p = preset(), srcW = st.fw / st.s;
            if (!st.spec.fit && srcW < p.w * 0.6) {
                el.note.textContent = 'This image is smaller than recommended (' + p.w + ' × ' + p.h + ' px) at this zoom, so it may look blurry. Zoom out or use a larger photo.';
                el.note.className = 'crop-note is-warn';
            } else {
                el.note.textContent = 'Drag to position · scroll or use the slider to zoom. The highlighted area is what will be saved.';
                el.note.className = 'crop-note';
            }
        }

        function sliderToScale(v) { return st.minS * Math.pow(st.maxS / st.minS, v / 1000); }
        function scaleToSlider(s) { return st.maxS === st.minS ? 0 : 1000 * Math.log(s / st.minS) / Math.log(st.maxS / st.minS); }

        function zoomTo(s, ax, ay) {
            s = Math.min(st.maxS, Math.max(st.minS, s));
            st.x = ax - (ax - st.x) * (s / st.s);
            st.y = ay - (ay - st.y) * (s / st.s);
            st.s = s;
            clamp();
            render();
        }

        /* ---- input handlers ---------------------------------------- */
        function onWheel(e) {
            e.preventDefault();
            var r = el.stage.getBoundingClientRect();
            zoomTo(st.s * Math.exp(-e.deltaY * 0.0015), e.clientX - r.left, e.clientY - r.top);
        }
        function onDown(e) {
            if (e.button !== 0) return;
            el.stage.setPointerCapture(e.pointerId);
            st.drag = { px: e.clientX, py: e.clientY, x: st.x, y: st.y };
            el.stage.classList.add('is-dragging');
        }
        function onMove(e) {
            if (!st || !st.drag) return;
            st.x = st.drag.x + e.clientX - st.drag.px;
            st.y = st.drag.y + e.clientY - st.drag.py;
            clamp();
            render();
        }
        function onUp() {
            if (st) st.drag = null;
            el.stage.classList.remove('is-dragging');
        }
        function onKey(e) {
            if (e.key === 'Escape') { e.preventDefault(); cancel(); return; }
            if (e.key === 'Tab') { trapFocus(e); return; }
            if (document.activeElement !== el.stage) return;
            var step = e.shiftKey ? 40 : 10, cx = st.fx + st.fw / 2, cy = st.fy + st.fh / 2;
            if (e.key === 'ArrowLeft') st.x += step;
            else if (e.key === 'ArrowRight') st.x -= step;
            else if (e.key === 'ArrowUp') st.y += step;
            else if (e.key === 'ArrowDown') st.y -= step;
            else if (e.key === '+' || e.key === '=') { zoomTo(st.s * 1.1, cx, cy); e.preventDefault(); return; }
            else if (e.key === '-') { zoomTo(st.s / 1.1, cx, cy); e.preventDefault(); return; }
            else return;
            e.preventDefault();
            clamp();
            render();
        }
        function trapFocus(e) {
            var f = Array.prototype.filter.call(
                el.modal.querySelectorAll('button, input, [tabindex="0"]'),
                function (n) { return !n.disabled && n.offsetParent !== null; });
            if (!f.length) return;
            var first = f[0], last = f[f.length - 1];
            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        }

        /* ---- produce the cropped file ------------------------------ */
        function confirm() {
            var p = preset();
            var srcW = st.fw / st.s;                         // source pixels covered by the frame
            var outW = Math.max(1, Math.round(Math.min(p.w, srcW))); // never upscale
            var outH = Math.max(1, Math.round(outW * p.h / p.w));
            var k = outW / st.fw;                            // frame px → output px

            var c = document.createElement('canvas');
            c.width = outW;
            c.height = outH;
            var ctx = c.getContext('2d');
            var png = !!st.spec.fit;
            if (!png) { ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, outW, outH); }
            ctx.imageSmoothingEnabled = true;
            ctx.imageSmoothingQuality = 'high';
            ctx.drawImage(el.img, (st.x - st.fx) * k, (st.y - st.fy) * k, st.natW * st.s * k, st.natH * st.s * k);

            el.ok.disabled = true;
            c.toBlob(function (blob) {
                el.ok.disabled = false;
                if (!blob) { alert('The image could not be cropped. Please try another file.'); return; }
                var name = (st.file.name || 'image').replace(/\.[^.]+$/, '') + (png ? '.png' : '.jpg');
                var out;
                try {
                    out = new File([blob], name, { type: blob.type });
                    var dt = new DataTransfer();
                    dt.items.add(out);
                    st.input.files = dt.files;      // does not fire "change"
                } catch (err) {
                    alert('Your browser cannot upload cropped images. Please use an up-to-date Chrome, Edge, Firefox or Safari.');
                    clearInput(st.input);
                    close();
                    return;
                }
                if (out.size > MAX_BYTES) {
                    alert('The cropped image is larger than 10 MB. Please zoom in or use a smaller image.');
                    clearInput(st.input);
                    close();
                    return;
                }
                st.input.dataset.cropped = '1';
                showPreview(st.input, URL.createObjectURL(blob), outW + ' × ' + outH + ' px · cropped');
                st.input.dispatchEvent(new Event('input', { bubbles: true })); // mark form as changed
                close();
            }, png ? 'image/png' : 'image/jpeg', 0.9);
        }

        return { open: open };
    })();

    /* ---- wiring for each image input -------------------------------- */
    function preview(input) { return document.querySelector('img[data-preview="' + input.id + '"]'); }

    function showPreview(input, src, label) {
        var img = preview(input);
        if (!img) return;
        if (!('orig' in img.dataset)) img.dataset.orig = img.getAttribute('src') || '';
        img.src = src;
        img.hidden = false;
        img.parentElement.classList.remove('is-empty');
        var tag = img.parentElement.querySelector('.crop-done');
        if (!tag) { tag = document.createElement('span'); tag.className = 'crop-done'; img.insertAdjacentElement('afterend', tag); }
        tag.textContent = '✓ ' + label;
        var again = img.parentElement.querySelector('.crop-again');
        if (!again) {
            again = document.createElement('button');
            again.type = 'button';
            again.className = 'adm-link crop-again';
            again.textContent = 'Crop again';
            again.addEventListener('click', function () { openFor(input, input._source); });
            tag.insertAdjacentElement('afterend', again);
        }
    }

    function clearInput(input) {
        input.value = '';
        input.dataset.cropped = '0';
        input._source = null;
        var img = preview(input);
        if (img && 'orig' in img.dataset) {
            if (img.dataset.orig) { img.src = img.dataset.orig; }
            else { img.removeAttribute('src'); img.hidden = true; img.parentElement.classList.add('is-empty'); }
        }
        if (img) {
            ['.crop-done', '.crop-again'].forEach(function (s) {
                var n = img.parentElement.querySelector(s);
                if (n) n.remove();
            });
        }
    }

    function specOf(input) {
        try { return JSON.parse(input.getAttribute('data-crop')); } catch (e) { return null; }
    }

    function openFor(input, file) {
        var spec = specOf(input);
        if (!spec || !spec.presets || !spec.presets.length || !file) return;
        Cropper.open(input, file, spec);
    }

    // Listen on the whole page so image fields added later (gallery, variations …) are covered too.
    document.addEventListener('change', function (e) {
        var input = e.target;
        if (!input.matches || !input.matches('input[type=file][data-crop]')) return;
        var file = input.files && input.files[0];
        input.dataset.cropped = '0';
        if (!file) { clearInput(input); return; }
        if (!/^image\/(jpeg|png|webp)$/.test(file.type)) {
            alert('Please choose a JPG, PNG or WebP image.');
            clearInput(input);
            return;
        }
        if (file.size > MAX_BYTES) {
            alert('This image is larger than 10 MB. Please choose a smaller file.');
            clearInput(input);
            return;
        }
        input._source = file;   // keep the original so "Crop again" starts from full quality
        openFor(input, file);
    });

    // A form can't be submitted while a chosen image is still uncropped.
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            var pending = Array.prototype.find.call(
                form.querySelectorAll('input[type=file][data-crop]'),
                function (i) { return i.files && i.files.length && i.dataset.cropped !== '1'; });
            if (pending) {
                e.preventDefault();
                alert('Please crop the selected image before saving.');
                openFor(pending, pending._source || pending.files[0]);
            }
        });
    });

    /* =================================================================
     *  Products module helpers
     * ================================================================= */

    // Confirm on individual buttons (e.g. Delete inside the bulk form)
    document.addEventListener('click', function (e) {
        var b = e.target.closest && e.target.closest('[data-confirm-click]');
        if (b && !window.confirm(b.getAttribute('data-confirm-click'))) e.preventDefault();
        var apply = e.target.closest && e.target.closest('[data-bulk-apply]');
        if (apply) {
            var sel = document.getElementById('bulk-action');
            var n = document.querySelectorAll('[data-bulk-item]:checked').length;
            if (!sel.value || !n) { e.preventDefault(); alert('Choose an action and tick at least one product.'); return; }
            if (sel.value === 'delete' && !window.confirm('Delete ' + n + ' product(s)? This cannot be undone.')) e.preventDefault();
        }
    });

    // Dropdown menus (<details data-dropdown>): close on outside click or Escape
    document.addEventListener('click', function (e) {
        document.querySelectorAll('details[data-dropdown][open]').forEach(function (d) {
            if (!d.contains(e.target)) d.removeAttribute('open');
        });
    });
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        document.querySelectorAll('details[data-dropdown][open]').forEach(function (d) {
            d.removeAttribute('open');
            var s = d.querySelector('summary');
            if (s) s.focus();
        });
    });

    // Product → storefront ticked: show that storefront's category tree
    document.querySelectorAll('[data-store-toggle]').forEach(function (cb) {
        var tree = document.querySelector('[data-store-cats="' + cb.getAttribute('data-store-toggle') + '"]');
        if (!tree) return;
        var sync = function () { tree.hidden = !cb.checked; };
        cb.addEventListener('change', sync);
        sync();
    });

    // Select all rows
    document.querySelectorAll('[data-check-all]').forEach(function (all) {
        all.addEventListener('change', function () {
            document.querySelectorAll('[data-bulk-item]').forEach(function (c) { c.checked = all.checked; });
        });
    });

    // ---- Drag-and-drop ordering (mouse) + keyboard (arrow keys on the ⋮⋮ handle) ----
    var dragItem = null;
    var markDirty = function (el) {
        var form = el.closest('form');
        if (form) form.dispatchEvent(new Event('input', { bubbles: true }));
    };
    var sorted = function (list) {
        markDirty(list);
        if (!list.dataset.sortableSave) return;
        var body = new FormData();
        body.append('_csrf', list.dataset.csrf || '');
        body.append('reorder', list.dataset.sortableTable || '');
        list.querySelectorAll('[data-sortable-item]').forEach(function (it) { body.append('ids[]', it.dataset.id); });
        fetch(list.dataset.sortableSave, { method: 'POST', body: body, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (j) { toast(j && j.ok ? 'Order saved' : 'Order could not be saved', !(j && j.ok)); })
            .catch(function () { toast('Order could not be saved', true); });
    };
    var toast = function (msg, bad) {
        var t = document.createElement('div');
        t.className = 'adm-toast' + (bad ? ' is-bad' : '');
        t.textContent = msg;
        t.setAttribute('role', 'status');
        document.body.appendChild(t);
        setTimeout(function () { t.remove(); }, 2200);
    };
    document.addEventListener('pointerdown', function (e) {
        var h = e.target.closest && e.target.closest('[data-drag-handle]');
        if (h) h.closest('[data-sortable-item]').draggable = true;
    });
    document.addEventListener('dragstart', function (e) {
        var item = e.target.closest && e.target.closest('[data-sortable-item]');
        if (!item || !item.draggable) return;
        dragItem = item;
        item.classList.add('is-dragging');
        e.dataTransfer.effectAllowed = 'move';
        try { e.dataTransfer.setData('text/plain', ''); } catch (err) { /* old browsers */ }
    });
    document.addEventListener('dragover', function (e) {
        if (!dragItem) return;
        var list = dragItem.parentElement;
        var over = e.target.closest && e.target.closest('[data-sortable-item]');
        if (!list.contains(e.target)) return;
        e.preventDefault();
        if (!over || over === dragItem || over.parentElement !== list) return;
        var r = over.getBoundingClientRect();
        var grid = list.getAttribute('data-sortable') === 'grid';
        var after = grid ? e.clientX > r.left + r.width / 2 : e.clientY > r.top + r.height / 2;
        list.insertBefore(dragItem, after ? over.nextSibling : over);
    });
    document.addEventListener('drop', function (e) { if (dragItem) e.preventDefault(); });
    document.addEventListener('dragend', function () {
        if (!dragItem) return;
        var list = dragItem.parentElement;
        dragItem.classList.remove('is-dragging');
        dragItem.draggable = false;
        dragItem = null;
        sorted(list);
    });
    document.addEventListener('keydown', function (e) {
        var h = e.target.closest && e.target.closest('[data-drag-handle]');
        if (!h) return;
        var item = h.closest('[data-sortable-item]'), list = item.parentElement, moved = false;
        if ((e.key === 'ArrowUp' || e.key === 'ArrowLeft') && item.previousElementSibling) { list.insertBefore(item, item.previousElementSibling); moved = true; }
        if ((e.key === 'ArrowDown' || e.key === 'ArrowRight') && item.nextElementSibling) { list.insertBefore(item.nextElementSibling, item); moved = true; }
        if (moved) { e.preventDefault(); h.focus(); sorted(list); }
    });

    // ---- Repeating rows: specifications, variations, downloads, new images ----
    var keySeq = 0;
    document.addEventListener('click', function (e) {
        var add = e.target.closest && e.target.closest('[data-repeater-add]');
        if (add) {
            var box = add.closest('[data-repeater]');
            var tpl = box.querySelector('template[data-repeater-template]');
            var list = box.querySelector('[data-repeater-list]');
            var key = 'n' + Date.now().toString(36) + (keySeq++);
            var holder = document.createElement('div');
            holder.innerHTML = tpl.innerHTML.replace(/__KEY__/g, key);
            var row = holder.firstElementChild;
            list.appendChild(row);
            markDirty(list);
            var first = row.querySelector('input[type=file][data-crop]');
            if (add.hasAttribute('data-repeater-pick') && first) { first.click(); }
            else { var f = row.querySelector('input:not([type=hidden]), select, textarea'); if (f) f.focus(); }
            return;
        }
        var rm = e.target.closest && e.target.closest('[data-repeater-remove]');
        if (rm) {
            var r = rm.closest('[data-repeater-row]');
            var flag = r.querySelector('[data-delete-flag]');
            if (r.hasAttribute('data-existing') && flag) {
                flag.value = '1';                 // saved row: mark for deletion on save
                r.hidden = true;
            } else {
                r.remove();
            }
            var any = document.querySelector('form.adm-form');
            if (any) markDirty(any);
        }
    });

    // ---- Category → only matching subcategories ----
    var catSel = document.querySelector('select[name=category_id]');
    var subSel = document.querySelector('select[name=subcategory_id]');
    if (catSel && subSel) {
        var filterSubs = function () {
            Array.prototype.forEach.call(subSel.options, function (o) {
                if (!o.dataset.parent) return;
                var show = !catSel.value || o.dataset.parent === catSel.value;
                o.hidden = !show;
                o.disabled = !show;
                if (!show && o.selected) subSel.value = '';
            });
        };
        catSel.addEventListener('change', filterSubs);
        filterSubs();
    }

    // ---- Related products picker ----
    var relAdd = document.querySelector('[data-related-add]');
    var relList = document.querySelector('[data-related-list]');
    if (relAdd && relList) {
        var syncOptions = function () {
            var chosen = Array.prototype.map.call(relList.querySelectorAll('input[name="related[]"]'), function (i) { return i.value; });
            Array.prototype.forEach.call(relAdd.options, function (o) { if (o.value) o.disabled = chosen.indexOf(o.value) !== -1; });
        };
        relAdd.addEventListener('change', function () {
            var o = relAdd.options[relAdd.selectedIndex];
            if (!o || !o.value) return;
            var li = document.createElement('li');
            li.className = 'rep__row';
            li.setAttribute('data-sortable-item', '');
            li.setAttribute('data-repeater-row', '');
            li.innerHTML = '<button type="button" class="adm-handle" data-drag-handle title="Drag to reorder" aria-label="Move">⋮⋮</button>'
                + '<input type="hidden" name="related[]"><span class="rep__label"></span>'
                + '<button type="button" class="adm-icon adm-icon--danger" data-repeater-remove aria-label="Remove">✕</button>';
            li.querySelector('input').value = o.value;
            li.querySelector('.rep__label').textContent = o.textContent;
            relList.appendChild(li);
            relAdd.value = '';
            syncOptions();
            markDirty(relList);
            var manual = document.querySelector('input[name=related_mode][value=manual]');
            if (manual) manual.checked = true;
        });
        relList.addEventListener('click', function () { setTimeout(syncOptions, 0); });
        syncOptions();
    }

    // ---- HEX colour picker ↔ text field; swatches in lists ----
    document.querySelectorAll('[data-hex-picker]').forEach(function (pick) {
        var text = pick.parentElement.querySelector('input[name=hex]');
        pick.addEventListener('input', function () { text.value = pick.value; text.dispatchEvent(new Event('input', { bubbles: true })); });
        text.addEventListener('input', function () { if (/^#[0-9a-f]{6}$/i.test(text.value)) pick.value = text.value; });
    });
    document.querySelectorAll('.adm-swatch[data-hex]').forEach(function (s) {
        if (/^#[0-9a-f]{3,6}$/i.test(s.dataset.hex)) s.style.backgroundColor = s.dataset.hex;
    });

    // ---- Simple rich-text editor (bold, italic, headings, lists, links) ----
    document.querySelectorAll('textarea[data-richtext]').forEach(function (ta) {
        var wrap = document.createElement('div');
        wrap.className = 'rte';
        var bar = document.createElement('div');
        bar.className = 'rte__bar';
        bar.setAttribute('role', 'toolbar');
        bar.setAttribute('aria-label', 'Text formatting');
        var area = document.createElement('div');
        area.className = 'rte__area';
        area.contentEditable = 'true';
        area.setAttribute('role', 'textbox');
        area.setAttribute('aria-multiline', 'true');
        area.setAttribute('aria-label', ta.labels && ta.labels[0] ? ta.labels[0].textContent : 'Description');
        area.innerHTML = ta.value;
        [
            ['bold', 'B', 'Bold'], ['italic', 'I', 'Italic'], ['underline', 'U', 'Underline'],
            ['h3', 'H', 'Heading'], ['p', '¶', 'Paragraph'],
            ['insertUnorderedList', '• List', 'Bullet list'], ['insertOrderedList', '1. List', 'Numbered list'],
            ['link', 'Link', 'Add link'], ['unlink', 'Unlink', 'Remove link'], ['removeFormat', 'Clear', 'Clear formatting']
        ].forEach(function (c) {
            var b = document.createElement('button');
            b.type = 'button';
            b.textContent = c[1];
            b.title = c[2];
            b.setAttribute('aria-label', c[2]);
            b.addEventListener('mousedown', function (e) { e.preventDefault(); });
            b.addEventListener('click', function () {
                area.focus();
                if (c[0] === 'h3' || c[0] === 'p') {
                    document.execCommand('formatBlock', false, c[0]);
                } else if (c[0] === 'link') {
                    var url = window.prompt('Link address (https://…, /page/, mailto:…)');
                    if (url) document.execCommand('createLink', false, url);
                } else {
                    document.execCommand(c[0], false, null);
                }
                sync();
            });
            bar.appendChild(b);
        });
        var sync = function () {
            ta.value = area.innerHTML;
            ta.dispatchEvent(new Event('input', { bubbles: true }));
        };
        area.addEventListener('input', sync);
        area.addEventListener('paste', function (e) {
            // Paste as plain text to keep the description clean.
            e.preventDefault();
            var text = (e.clipboardData || window.clipboardData).getData('text/plain');
            document.execCommand('insertText', false, text);
        });
        try { document.execCommand('defaultParagraphSeparator', false, 'p'); } catch (err) { /* ignore */ }
        wrap.appendChild(bar);
        wrap.appendChild(area);
        ta.hidden = true;
        ta.parentNode.insertBefore(wrap, ta);
        if (ta.form) ta.form.addEventListener('submit', function () { ta.value = area.innerHTML; });
    });

    // Warn before leaving a form with unsaved changes
    // (registered after the crop check so a blocked submit stays "unsaved").
    document.querySelectorAll('form.adm-form').forEach(function (form) {
        var dirty = false;
        form.addEventListener('input', function () { dirty = true; });
        form.addEventListener('change', function () { dirty = true; });
        form.addEventListener('submit', function (e) { if (!e.defaultPrevented) dirty = false; });
        window.addEventListener('beforeunload', function (e) {
            if (dirty) { e.preventDefault(); e.returnValue = ''; }
        });
    });
})();
