/**
 * Trang chi tiet 1 danh sach yeu thich (page-favourite-detail.php) - port favourite-detail.component.ts (PPlusAngularResi).
 *
 * Du lieu bo suu tap nam o localStorage (giong trang danh sach, khong can dang nhap/backend):
 *   ListFavourite            = [{ id, FavouriteName, DateCreate }]
 *   listApartmentFavourite   = [{ IdFavourite, apartmentId, ...payload cua the }]
 *   listProjectFavourite     = [{ IdFavourite, projectId }]   (Resi hien chua co nut them du an, giu cho khop ban goc)
 * 2 che do (giong ban goc):
 *   ?fshare_link={id}            danh sach tren THIET BI nay (co the bo yeu thich tung can / xoa danh sach)
 *   ?ids=1,2&IdProject=3         link chia se (chi xem; khong co localStorage cua nguoi gui)
 * "Bo yeu thich" o day SUA LOI cua ban goc: ban Angular loc `o.apartmentId != id && o.IdFavourite == idF` nen xoa NHAM toan bo can cua cac
 * danh sach KHAC - o day chi go dung cap (danh sach, can ho).
 */
(function () {
    'use strict';
    var root = document.getElementById('pplus-fd');
    if (!root) return;

    var CFG = window.PPLUS_FD_CONFIG || {};
    var T = CFG.i18n || {};
    var LS_COLL = 'ListFavourite', LS_OFF = 'listApartmentFavourite', LS_PRJ = 'listProjectFavourite';

    function readLS(k) { try { return JSON.parse(localStorage.getItem(k)) || []; } catch (e) { return []; } }
    function writeLS(k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) { } }
    function $(id) { return document.getElementById(id); }
    function toast(type, msg) { if (window.toastr && window.toastr[type]) window.toastr[type](msg); }
    function favId(o) { return o && (o.apartmentId != null ? o.apartmentId : o.projectId); }
    function fmtDate(d) {
        var dt = d ? new Date(d) : null;
        if (!dt || isNaN(dt)) return '';
        function p(n) { return (n < 10 ? '0' : '') + n; }
        return p(dt.getDate()) + '/' + p(dt.getMonth() + 1) + '/' + dt.getFullYear();
    }

    var q = new URLSearchParams(window.location.search);
    var collId = q.get('fshare_link');
    var coll = null;
    var apartmentIds = [], projectIds = [];
    var map = null, markersLayer = [], infoWindow = null;

    function collectIds() {
        if (collId) {
            coll = readLS(LS_COLL).filter(function (c) { return c.id === collId; })[0] || null;
            apartmentIds = readLS(LS_OFF).filter(function (o) { return o.IdFavourite === collId; }).map(favId).filter(Boolean);
            projectIds = readLS(LS_PRJ).filter(function (o) { return o.IdFavourite === collId; }).map(favId).filter(Boolean);
        } else {
            coll = null;
            apartmentIds = (q.get('ids') || q.get('Ids') || '').split(',').map(function (s) { return s.trim(); }).filter(Boolean);
            projectIds = (q.get('IdProject') || '').split(',').map(function (s) { return s.trim(); }).filter(Boolean);
        }
    }

    function setState(msg, isError) {
        var s = $('fd-state');
        if (!s) return;
        s.textContent = msg || '';
        s.hidden = !msg;
        s.classList.toggle('fd-state-error', !!isError);
    }

    function renderHead(total) {
        $('fd-count').textContent = String(total);
        if (collId) {
            $('fd-title').textContent = coll ? coll.FavouriteName : (T.chiTiet || '');
            $('fd-date').textContent = coll ? fmtDate(coll.DateCreate) : '';
            $('fd-delete').hidden = !coll;
        } else {
            $('fd-title').textContent = T.shared || T.chiTiet || '';
            $('fd-date').textContent = '';
            $('fd-delete').hidden = true;
        }
        $('fd-actions').hidden = !(apartmentIds.length || projectIds.length);
    }

    function currentShareUrl() {
        var u = (CFG.detailUrl || (window.location.origin + window.location.pathname)) + '?ids=' + apartmentIds.join(',');
        if (projectIds.length) u += '&IdProject=' + projectIds.join(',');
        return u;
    }

    /* ---------------- ban do ---------------- */
    function drawMap(markers) {
        var box = $('fd-map-box');
        if (!box) return;
        if (!markers || !markers.length || typeof window.pplusLoadGoogleMaps !== 'function') { box.hidden = true; return; }
        box.hidden = false;
        window.pplusLoadGoogleMaps(function () {
            if (!window.google || !google.maps) { box.hidden = true; return; }
            var el = $('mapfarourite');
            if (!map) {
                map = new google.maps.Map(el, Object.assign({ zoom: 12, center: { lat: markers[0].lat, lng: markers[0].lng }, mapTypeControl: false, streetViewControl: false }, window.pplusMapOptionsBase || {}));
                infoWindow = new google.maps.InfoWindow();
            }
            markersLayer.forEach(function (m) { m.setMap(null); });
            markersLayer = [];
            var bounds = new google.maps.LatLngBounds();
            markers.forEach(function (it) {
                var pos = { lat: it.lat, lng: it.lng };
                var mk = new google.maps.Marker({ position: pos, map: map, title: it.title });
                mk.addListener('click', function () {
                    var h = document.createElement('div');
                    h.className = 'fd-iw';
                    var a = document.createElement('a'); a.href = it.url; a.textContent = it.title; a.className = 'fd-iw-title';
                    h.appendChild(a);
                    if (it.price) { var p = document.createElement('div'); p.textContent = it.price + '$'; p.className = 'fd-iw-price'; h.appendChild(p); }
                    infoWindow.setContent(h); infoWindow.open(map, mk);
                });
                markersLayer.push(mk);
                bounds.extend(pos);
            });
            if (markers.length === 1) { map.setCenter(bounds.getCenter()); map.setZoom(15); } else { map.fitBounds(bounds, 40); }
        });
    }

    /* ---------------- tai + ve ---------------- */
    var lastMarkers = [];
    function load() {
        collectIds();
        if (collId && !coll) {
            renderHead(0);
            setState(T.notOnDevice || '', true);
            $('fd-actions').hidden = true;
            return;
        }
        if (!apartmentIds.length && !projectIds.length) {
            renderHead(0);
            setState(T.empty || '', false);
            $('fd-apartments').innerHTML = ''; $('fd-projects').innerHTML = ''; $('fd-projects-title').hidden = true;
            drawMap([]);
            return;
        }
        setState(T.loading || '', false);
        var url = CFG.restUrl + 'favourite-items?lang=' + encodeURIComponent(CFG.lang || 'vi') + '&ids=' + encodeURIComponent(apartmentIds.join(',')) + '&projects=' + encodeURIComponent(projectIds.join(','));
        fetch(url, { headers: { 'Accept': 'application/json' } }).then(function (r) { return r.json(); }).then(function (d) {
            $('fd-apartments').innerHTML = d.apartmentsHtml || '';
            $('fd-projects').innerHTML = d.projectsHtml || '';
            $('fd-projects-title').hidden = !d.projects;
            var total = (d.apartments || 0) + (d.projects || 0);
            renderHead(total);
            setState(total ? '' : (T.empty || ''), false);
            // che do danh sach cua thiet bi: can ho da bi go khoi he thong -> khong hien duoc, nhung van tinh trong localStorage nen cho biet
            lastMarkers = d.markers || [];
            drawMap(lastMarkers);
            if (window.pplusRefreshHearts) window.pplusRefreshHearts();
        }).catch(function () {
            setState(T.error || '', true);
        });
    }

    /* ---------------- bo yeu thich 1 can ---------------- */
    function removeItem(kind, id) {
        if (!collId || !coll) return;
        if (!window.confirm(T.confirmRemove || '')) return;
        if (kind === 'project') {
            writeLS(LS_PRJ, readLS(LS_PRJ).filter(function (o) { return !(o.IdFavourite === collId && String(favId(o)) === String(id)); }));
        } else {
            writeLS(LS_OFF, readLS(LS_OFF).filter(function (o) { return !(o.IdFavourite === collId && String(favId(o)) === String(id)); }));
        }
        window.dispatchEvent(new Event('pplus:favourite-changed'));
        toast('success', T.removed || '');
        var card = root.querySelector('.fd-card[data-kind="' + kind + '"][data-id="' + id + '"]');
        if (card) card.remove();
        collectIds();
        var total = apartmentIds.length + projectIds.length;
        renderHead(total);
        lastMarkers = lastMarkers.filter(function (m) { return !(m.kind === kind && String(m.id) === String(id)); });
        drawMap(lastMarkers);
        $('fd-projects-title').hidden = !projectIds.length;
        setState(total ? '' : (T.empty || ''), false);
    }

    root.addEventListener('click', function (e) {
        var rm = e.target.closest('[data-fd-remove]');
        if (rm) { e.preventDefault(); e.stopPropagation(); removeItem(rm.getAttribute('data-fd-remove'), rm.getAttribute('data-id')); }
    });
    root.addEventListener('keydown', function (e) {
        if (e.key !== 'Enter' && e.key !== ' ') return;
        var rm = e.target.closest && e.target.closest('[data-fd-remove]');
        if (rm) { e.preventDefault(); removeItem(rm.getAttribute('data-fd-remove'), rm.getAttribute('data-id')); }
    });

    /* ---------------- copy link / xoa danh sach ---------------- */
    $('fd-copy').addEventListener('click', function () {
        var url = currentShareUrl();
        function ok() { toast('success', T.copied || ''); }
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url).then(ok).catch(function () { window.prompt('', url); });
        } else { window.prompt('', url); }
    });
    $('fd-delete').addEventListener('click', function () {
        if (!collId || !coll) return;
        if (!window.confirm(T.confirmDelete || '')) return;
        writeLS(LS_COLL, readLS(LS_COLL).filter(function (c) { return c.id !== collId; }));
        writeLS(LS_OFF, readLS(LS_OFF).filter(function (o) { return o.IdFavourite !== collId; }));
        writeLS(LS_PRJ, readLS(LS_PRJ).filter(function (o) { return o.IdFavourite !== collId; }));
        window.dispatchEvent(new Event('pplus:favourite-changed'));
        window.location.href = CFG.listUrl || '/';
    });

    load();
})();
