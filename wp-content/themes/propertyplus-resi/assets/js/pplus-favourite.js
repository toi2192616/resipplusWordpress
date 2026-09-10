/**
 * Danh sach yeu thich (port module Angular "favourite" + partials create/list-farourite). Toan bo
 * du lieu luu o localStorage (giong Angular, khong can login):
 *   ListFavourite        = [{ id, FavouriteName, DateCreate }]                (bo suu tap)
 *   listOfficeFavourite  = [{ IdFavourite, projectId, projectName, ... }]     (VP trong tung bo)
 *
 * Nut ".item-like" (Yeu thich) tren card (parts/office-card.php) mo modal (parts/favourite-modals.php).
 * Trang /office-favourite (page-favourite.php) doc localStorage roi goi REST proxy
 * (inc/favourite-rest.php) de render luoi VP + Google Map.
 *
 * File nay BAN GOC (Dev A) co ca phan "So sanh" (dung chung ".viewdetail" + key localStorage
 * "ListProducCompare") - da BO PHAN DO khi merge 2 nhanh Dev A/Dev B, GIU LAI tinh nang "So sanh"
 * cua Dev B (assets/js/compare.js + parts/compare-modal.php, day du hon: co ca bang so sanh chi
 * tiet + xuat PDF, da test end-to-end) theo quyet dinh cua nguoi dung.
 */
(function () {
    'use strict';
    var CFG = window.PPLUS_FAV_CONFIG || {};
    var T = CFG.i18n || {};
    var LS_COLL = 'ListFavourite';
    var LS_OFF = 'listOfficeFavourite';

    function readLS(key) { try { return JSON.parse(localStorage.getItem(key)) || []; } catch (e) { return []; } }
    function writeLS(key, val) { localStorage.setItem(key, JSON.stringify(val)); }
    function toast(type, msg) {
        if (window.toastr) { toastr[type](msg); }
        else if (type === 'error') { alert(msg); }
    }
    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
    function uuid() {
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
            var r = Math.random() * 16 | 0, v = c === 'x' ? r : (r & 0x3 | 0x8); return v.toString(16);
        });
    }
    function fmtDate(d) {
        var dt = d ? new Date(d) : new Date();
        function p(n) { return (n < 10 ? '0' : '') + n; }
        return p(dt.getDate()) + '/' + p(dt.getMonth() + 1) + '/' + dt.getFullYear();
    }

    /* ---------------- Modal he thong ---------------- */
    function openModal(id) {
        var m = document.getElementById(id);
        if (!m) return;
        m.classList.add('open'); m.setAttribute('aria-hidden', 'false');
        document.body.classList.add('pplus-modal-active');
    }
    function closeModal(m) {
        if (!m) return;
        m.classList.remove('open'); m.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('pplus-modal-active');
    }
    document.addEventListener('click', function (e) {
        if (e.target.closest('[data-pplus-close]')) {
            var m = e.target.closest('.pplus-modal'); closeModal(m);
        }
    });

    /* ---------------- Doc office tu card ---------------- */
    function officeFromEl(el) {
        var card = el.closest('[data-office]');
        if (!card) return null;
        try { return JSON.parse(card.getAttribute('data-office')); } catch (e) { return null; }
    }

    /* ================= THEM VAO BO SUU TAP (nut Yeu thich) ================= */
    var pendingOffice = null; // VP cho vao bo suu tap (khi bam Yeu thich)

    function openAddFa(office) {
        pendingOffice = office;
        renderAddFaList();
        openModal('pplus-modal-addfa');
    }
    function renderAddFaList() {
        var wrap = document.getElementById('pplus-addfa-list');
        if (!wrap) return;
        var colls = readLS(LS_COLL);
        if (!colls.length) {
            wrap.innerHTML = '<div class="pplus-addfa-empty">' + esc(T.noCollection || 'Chưa có bộ sưu tập nào') + '</div>';
            return;
        }
        wrap.innerHTML = colls.map(function (c) {
            return '<label class="item-favourite"><input class="input-check" type="checkbox" value="' + esc(c.id) + '" /> ' + esc(c.FavouriteName) + '</label>';
        }).join('');
    }
    function addPendingToCollections(ids) {
        if (!pendingOffice || !ids.length) return;
        var offices = readLS(LS_OFF);
        ids.forEach(function (collId) {
            var o = JSON.parse(JSON.stringify(pendingOffice));
            o.IdFavourite = collId;
            var dup = offices.some(function (x) { return x.IdFavourite === collId && x.projectId === o.projectId; });
            if (!dup) offices.push(o);
        });
        writeLS(LS_OFF, offices);
    }

    /* ================= TAO BO SUU TAP ================= */
    var createMode = 'plain'; // 'plain' = tao roi thoi; 'add' = tao xong them pendingOffice vao

    function initModalButtons() {
        // Nut "Bo suu tap moi" trong modal them-vao - PHAI dong modal them-vao truoc khi mo modal
        // tao moi: he thong modal cua theme (.pplus-modal, toggle class "open" bang tay) khong tu
        // xep z-index chong lop nhu NgbModal (Angular goc, moi lan modalService.open() la 1 lop
        // moi tu tang z-index) - 2 modal cung mo cung luc se chong len nhau theo THU TU TRONG DOM
        // (modal them-vao dung SAU modal tao moi trong parts/favourite-modals.php nen luon de len
        // tren, khien modal tao moi bi che khuat hoan toan - bug thuc te: bam "Bo suu tap moi"
        // nhin nhu khong co gi xay ra, nguoi dung khong the nhap ten bo suu tap moi).
        var addfaNew = document.getElementById('pplus-addfa-new');
        if (addfaNew) addfaNew.addEventListener('click', function () {
            createMode = 'add';
            var i = document.getElementById('pplus-create-name'); if (i) i.value = '';
            closeModal(document.getElementById('pplus-modal-addfa'));
            openModal('pplus-modal-create');
        });

        // Nut "Tao danh sach moi" o sidebar trang /office-favourite (page-favourite.php
        // "#pplus-fa-create") - port show-modalCreate/openCreateFarourite() (favourite.component.html/
        // .ts ben Angular goc, khong co pendingOffice, "createMode" giu nguyen 'plain').
        var faCreate = document.getElementById('pplus-fa-create');
        if (faCreate) faCreate.addEventListener('click', function () {
            createMode = 'plain';
            pendingOffice = null;
            var i = document.getElementById('pplus-create-name'); if (i) i.value = '';
            openModal('pplus-modal-create');
        });

        // Nut "Them vao" trong modal them-vao
        var addfaAdd = document.getElementById('pplus-addfa-add');
        if (addfaAdd) addfaAdd.addEventListener('click', function () {
            var checked = Array.prototype.slice.call(document.querySelectorAll('#pplus-addfa-list .input-check:checked')).map(function (c) { return c.value; });
            if (!checked.length) { toast('error', T.pickCollection || 'Bạn chưa chọn danh mục yêu thích'); return; }
            addPendingToCollections(checked);
            toast('success', T.added || 'Thêm vào bộ sưu tập thành công');
            closeModal(document.getElementById('pplus-modal-addfa'));
            markLiked(pendingOffice);
        });

        // Nut "Them vao" trong modal tao moi
        var createAdd = document.getElementById('pplus-create-add');
        if (createAdd) createAdd.addEventListener('click', function () {
            var input = document.getElementById('pplus-create-name');
            var name = input ? input.value.trim() : '';
            if (!name) { toast('error', T.needName || 'Bạn cần nhập tên bộ sưu tập'); return; }
            var colls = readLS(LS_COLL);
            var coll = { id: uuid(), FavouriteName: name, DateCreate: new Date().toISOString() };
            colls.push(coll);
            writeLS(LS_COLL, colls);
            if (createMode === 'add' && pendingOffice) {
                addPendingToCollections([coll.id]);
                toast('success', T.added || 'Thêm vào bộ sưu tập thành công');
                markLiked(pendingOffice);
                closeModal(document.getElementById('pplus-modal-addfa'));
            } else {
                toast('success', T.createdCollection || 'Thêm mới bộ sưu tập thành công');
            }
            createMode = 'plain';
            closeModal(document.getElementById('pplus-modal-create'));
            if (window.__pplusFaPage) window.__pplusFaPage.reloadList();
        });
    }

    function markLiked(office) {
        if (!office) return;
        document.querySelectorAll('[data-office]').forEach(function (card) {
            try {
                var o = JSON.parse(card.getAttribute('data-office'));
                if (o && String(o.projectId) === String(office.projectId)) card.classList.add('pplus-liked');
            } catch (e) { }
        });
    }

    /* ================= NUT TREN CARD ================= */
    document.addEventListener('click', function (e) {
        var like = e.target.closest('.item-like');
        if (like) { e.preventDefault(); e.stopPropagation(); var o = officeFromEl(like); if (o) openAddFa(o); return; }
    });

    /* ================= "Chia se"/"Xuat PDF" tren trang chi tiet (.actine-share, page-office-
       detail.php) - doi chieu truc tiep tren propertyplus.vn (Pearl 5 Building, 13/8/2026) truoc
       khi sua: copyLinkShare() KHONG co confirm (copy link ngay + toast), rieng ExportPDF() CO
       confirm truoc (dung window.confirm thay SweetAlert - da bo khoi theme, xem functions.php),
       roi moi goi API tai PDF - giong het facon exportPdf() da co san o duoi (trang /office-favourite),
       dung lai CHUNG 1 route REST "favourite-pdf" (POST {name, ids, lang} -> blob PDF, xem
       inc/favourite-rest.php) chi khac "ids" la 1 projectId duy nhat thay vi ca bo suu tap. */
    document.addEventListener('click', function (e) {
        var share = e.target.closest('.share-link');
        if (share) {
            e.preventDefault();
            var shareUrl = window.location.origin + window.location.pathname;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(shareUrl).then(function () {
                    toast('success', T.copied || 'Copy link yêu thích thành công!');
                }).catch(function () { /* giong production: chi console.error, khong toast loi */ });
            }
            return;
        }

        var pdfBtn = e.target.closest('.export-favorite');
        if (pdfBtn) {
            e.preventDefault();
            var office = officeFromEl(pdfBtn);
            if (!office || !office.projectId) return;
            if (!window.confirm(T.pdfConfirm || 'Bạn xác nhận muốn tải file PDF văn phòng này?')) return;

            pdfBtn.disabled = true;
            fetch(CFG.restUrl + 'favourite-pdf', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name: office.projectName || 'Văn phòng', ids: String(office.projectId), lang: CFG.lang || 'vi' })
            }).then(function (r) { if (!r.ok) throw new Error(); return r.blob(); })
                .then(function (blob) {
                    var url = URL.createObjectURL(blob);
                    var a = document.createElement('a');
                    a.href = url; a.download = (office.projectName || 'VanPhong') + '-' + fmtDate() + '-PDFYeuThich.pdf';
                    document.body.appendChild(a); a.click(); a.remove(); URL.revokeObjectURL(url);
                    toast('success', T.pdfOk || 'Tải file PDF thành công!');
                }).catch(function () { toast('error', T.pdfErr || 'Không tải được file PDF!'); })
                .finally(function () { pdfBtn.disabled = false; });
            return;
        }
    });

    /* ================= TRANG /office-favourite ================= */
    function initFavouritePage() {
        var sidebar = document.getElementById('item-list-fa');
        if (!sidebar) return;

        var map = null, markers = [], infoWin = null;
        var currentColl = null;
        var sharedIds = (function () {
            var m = window.location.search.match(/[?&]ids=([^&]+)/); return m ? decodeURIComponent(m[1]) : null;
        })();

        function offices() { return readLS(LS_OFF); }
        function collections() { return readLS(LS_COLL); }

        function renderSidebar() {
            var colls = collections();
            var offs = offices();
            sidebar.innerHTML = colls.map(function (c) {
                var inColl = offs.filter(function (o) { return o.IdFavourite === c.id; });
                var img = (inColl[0] && (inColl[0].PhotoListBaSE || (inColl[0].photoList && inColl[0].photoList[0]))) || CFG.placeholderImg || '';
                var active = (currentColl === c.id) ? ' active' : '';
                var actions = (currentColl === c.id) ?
                    '<div class="action-item">' +
                    '<div class="btn-action-dropdow-group">' +
                    '<button type="button" class="btn btn-action-dropdow" data-fa-menu="' + esc(c.id) + '"><i class="fa fa-ellipsis-h"></i></button>' +
                    '<div class="fa-dropdown-menu" data-fa-dd="' + esc(c.id) + '">' +
                    '<button data-fa-pdf="' + esc(c.id) + '"><i><img src="' + esc(CFG.iconPdf) + '"></i> ' + esc(T.exportPdf || 'Tải PDF') + '</button>' +
                    '<button data-fa-share="' + esc(c.id) + '"><i><img src="' + esc(CFG.iconShare) + '"></i> ' + esc(T.share || 'Chia sẻ') + '</button>' +
                    '<button data-fa-del="' + esc(c.id) + '"><i><img src="' + esc(CFG.iconTrash) + '"></i> ' + esc(T.delColl || 'Xóa bộ sưu tập') + '</button>' +
                    '</div></div>' +
                    '<button class="btn btn-action-active"><i class="fa fa-chevron-right"></i></button>' +
                    '</div>' : '';
                return '<div class="item-list' + active + '" data-fa-coll="' + esc(c.id) + '">' +
                    '<div class="item-img"><img src="' + esc(img) + '" /></div>' +
                    '<div class="item-content"><label>' + esc(c.FavouriteName) + '</label><span>' + esc(T.savedOn || 'Lưu ngày') + ' ' + fmtDate(c.DateCreate) + '</span></div>' +
                    actions + '</div>';
            }).join('');
        }

        function projectIdsOfColl(collId) {
            return offices().filter(function (o) { return o.IdFavourite === collId; }).map(function (o) { return o.projectId; });
        }

        function selectColl(collId) {
            currentColl = collId;
            renderSidebar();
            var ids = projectIdsOfColl(collId);
            loadOffices(ids, false);
        }

        function loadOffices(ids, isShared) {
            var idStr = (ids || []).join(',');
            if (!idStr && isShared) idStr = sharedIds || '';
            var empty = document.getElementById('pplus-fa-empty');
            var header = document.getElementById('pplus-fa-header');
            var contentF = document.getElementById('content-office-f');
            var listWrap = document.getElementById('pplus-fa-list');
            if (!idStr) {
                if (empty) empty.style.display = '';
                if (header) header.style.display = 'none';
                if (contentF) contentF.style.display = 'none';
                clearMarkers();
                return;
            }
            fetch(CFG.restUrl + 'offices-by-ids?lang=' + encodeURIComponent(CFG.lang || 'vi') + '&ids=' + encodeURIComponent(idStr))
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    var list = (data && data.listOffice) || [];
                    if (!list.length) {
                        if (empty) empty.style.display = '';
                        if (header) header.style.display = 'none';
                        if (contentF) contentF.style.display = 'none';
                        clearMarkers();
                        return;
                    }
                    if (empty) empty.style.display = 'none';
                    if (header) header.style.display = '';
                    if (contentF) contentF.style.display = '';
                    listWrap.innerHTML = list.map(function (o) { return officeItemHtml(o, !isShared); }).join('');
                    loadMarkers(idStr);
                })
                .catch(function () { toast('error', T.loadErr || 'Không tải được danh sách'); });
        }

        function officeItemHtml(o, canRemove) {
            var img = o.PhotoListBaSE || (o.photoList && o.photoList[0]) || CFG.placeholderImg || '';
            var rank = o.ratingLevel || '';
            var rankHtml = (rank === 'A' || rank === 'B' || rank === 'C')
                ? '<div class="RateTingContent class' + rank + '">' + esc((T.grade || 'Hạng') + ' ' + rank) + '</div>'
                : '<div class="RateTingContent">' + esc(T.updating || 'Đang cập nhật') + '</div>';
            var removeHtml = canRemove ? '<div class="item-dislike" data-fa-remove="' + esc(o.projectId) + '" title="' + esc(T.removeOff || 'Bỏ yêu thích') + '"><i>' +
                '<svg width="24" height="26" viewBox="0 8 36 36" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.428 12.857 11.142 30h13.714l1.714-17.143" stroke="#fff" stroke-linecap="round" stroke-linejoin="round"/><path d="M20.25 23.25v-7.5M15.75 23.25v-7.5" stroke="#fff" stroke-linecap="round" stroke-linejoin="round"/><path d="M6.857 9.429h6.857m0 0 .573-2.292A1.5 1.5 0 0 1 15.743 6h4.515a1.5 1.5 0 0 1 1.455 1.137l.573 2.292m-8.572 0h8.572m0 0h6.857" stroke="#fff" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
                '</i></div>' : '';
            return '<a class="item-office hover-office-not" href="' + esc(o.urlHerf || '#') + '" data-id="office-' + esc(o.projectId) + '">' +
                '<img src="' + esc(img) + '" alt="' + esc(o.projectName) + '" />' +
                removeHtml +
                '<div class="item-content-m"><div class="item-content">' +
                '<div class="RateTing">' + rankHtml + '<div class="item-price">' + esc(o.itemPrice || '') + '</div></div>' +
                '<div class="item-name">' + esc(o.projectName) + '</div>' +
                '<div class="item-address">' + esc(o.address) + '</div>' +
                '</div></div></a>';
        }

        /* ----- Map ----- */
        // pplusLoadGoogleMaps (assets/js/pplus-maps.js, dung chung voi A1/A2) chi tu chay khi trang
        // co "#map"/"#map-detail" - trang nay dung "#mapfarourite" rieng nen phai TU GOI de thuc su
        // tai script Google Maps (truoc day chi kiem tra "window.google" co san hay chua ma khong
        // bao gio tu tai, nut "Xem bang Ban do" bam khong co gi xay ra - bug thuc te da bao cao).
        function ensureMap(cb) {
            var el = document.getElementById('mapfarourite');
            if (!el) { if (cb) cb(); return; }
            if (map) { if (cb) cb(); return; }
            function build() {
                if (!map) {
                    // window.pplusMapOptionsBase (assets/js/pplus-maps.js) - style terrain + an bot
                    // nhan POI mac dinh cua Google, GIONG HET ban do A1/A4/A2 - truoc day trang nay
                    // dung option "tho" (chi zoom/center/mapTypeControl/streetViewControl) nen nhin
                    // khac han (bug thuc te: "map... chưa giống bản chính").
                    map = new google.maps.Map(el, Object.assign({ zoom: 13, center: { lat: 21.0278, lng: 105.8342 } }, window.pplusMapOptionsBase || {}));
                    infoWin = new google.maps.InfoWindow();
                }
                if (cb) cb();
            }
            if (window.pplusLoadGoogleMaps) {
                window.pplusLoadGoogleMaps(build);
            } else if (window.google && window.google.maps) {
                build();
            }
        }
        function clearMarkers() { markers.forEach(function (m) { m.setMap(null); }); markers = []; }
        // office-markers-by-ids (inc/favourite-rest.php) la endpoint RIENG chi tra ve id/lat/lng/
        // rating/title (khong co anh/gia/dia chi that - xac nhan qua goi API truc tiep) - phai ghep
        // them voi du lieu DAY DU da co san trong "offices()" (localStorage, dung chung voi luoi
        // the ben duoi) de co du field cho OfficeMarkerOverlay (photo/price/address/url).
        function loadMarkers(idStr) {
            ensureMap(function () {
                if (!map) return;
                fetch(CFG.restUrl + 'office-markers-by-ids?lang=' + encodeURIComponent(CFG.lang || 'vi') + '&ids=' + encodeURIComponent(idStr))
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        clearMarkers();
                        var list = (data && data.markers) || [];
                        if (!list.length || !window.pplusCreateOfficeMarker) return;
                        var full = offices();
                        var bounds = new google.maps.LatLngBounds();
                        list.forEach(function (it) {
                            if (it.lat == null || it.lng == null) return;
                            var matched = full.filter(function (o) { return String(o.projectId) === String(it.id); })[0] || {};
                            var pos = { lat: parseFloat(it.lat), lng: parseFloat(it.lng) };
                            var markerItem = {
                                id: 'office-' + it.id,
                                lat: pos.lat,
                                lng: pos.lng,
                                rating: it.rating || '',
                                title: it.title || matched.projectName || '',
                                url: matched.urlHerf || '',
                                photo: matched.PhotoListBaSE || (matched.photoList && matched.photoList[0]) || '',
                                price: matched.itemPrice || '',
                                address: matched.address || ''
                            };
                            var overlay = window.pplusCreateOfficeMarker(markerItem, map, infoWin);
                            markers.push(overlay); bounds.extend(pos);
                        });
                        if (markers.length) map.fitBounds(bounds);
                    });
            });
        }

        /* ----- Toggle map (fullContent) ----- */
        var detail = document.getElementById('pplus-fa-detail');
        var boxmap = document.getElementById('pplus-fa-boxmap');
        var toggletop = document.getElementById('pplus-fa-toggletop');
        function setMapView(show) {
            if (show) {
                detail.classList.remove('fullContent');
                boxmap.classList.remove('hidden-map');
                if (toggletop) toggletop.style.display = '';
                ensureMap(function () {
                    if (map) google.maps.event.trigger(map, 'resize');
                });
            } else {
                detail.classList.add('fullContent');
                boxmap.classList.add('hidden-map');
                if (toggletop) toggletop.style.display = 'none';
            }
        }
        var btnToggleMap = document.getElementById('pplus-fa-togglemap');
        if (btnToggleMap) btnToggleMap.addEventListener('click', function () { setMapView(true); });
        var btnCollapse = document.getElementById('pplus-fa-collapsemap');
        if (btnCollapse) btnCollapse.addEventListener('click', function () { setMapView(false); });
        if (toggletop) toggletop.addEventListener('click', function () { setMapView(false); });

        /* ----- Share ----- */
        function shareLink(ids) {
            var url = window.location.origin + window.location.pathname + '?ids=' + ids.join(',');
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(function () { toast('success', T.copied || 'Copy link yêu thích thành công!'); });
            } else { window.prompt('Copy link', url); }
        }
        var btnShare = document.getElementById('pplus-fa-share');
        if (btnShare) btnShare.addEventListener('click', function () { shareLink(projectIdsOfColl(currentColl)); });

        /* ----- PDF ----- */
        function exportPdf(name, ids) {
            if (!ids.length) { toast('error', T.pdfNoOffice || 'Không thấy văn phòng nào'); return; }
            fetch(CFG.restUrl + 'favourite-pdf', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name: name || 'Văn phòng yêu thích', ids: ids.join(','), lang: CFG.lang || 'vi' })
            }).then(function (r) { if (!r.ok) throw new Error(); return r.blob(); })
                .then(function (blob) {
                    var url = URL.createObjectURL(blob);
                    var a = document.createElement('a');
                    a.href = url; a.download = (name || 'YeuThich') + '-' + fmtDate() + '-PDFYeuThich.pdf';
                    document.body.appendChild(a); a.click(); a.remove(); URL.revokeObjectURL(url);
                    toast('success', T.pdfOk || 'Tải file PDF thành công!');
                }).catch(function () { toast('error', T.pdfErr || 'Không tải được file PDF!'); });
        }
        var btnPdf = document.getElementById('pplus-fa-pdf');
        if (btnPdf) btnPdf.addEventListener('click', function () {
            var coll = collections().filter(function (c) { return c.id === currentColl; })[0];
            exportPdf(coll ? coll.FavouriteName : 'Văn phòng yêu thích', projectIdsOfColl(currentColl));
        });

        /* ----- Delegated clicks (sidebar + remove office) ----- */
        document.addEventListener('click', function (e) {
            var item = e.target.closest('[data-fa-coll]');
            if (item && !e.target.closest('.action-item')) { selectColl(item.getAttribute('data-fa-coll')); return; }

            var menuBtn = e.target.closest('[data-fa-menu]');
            if (menuBtn) { e.stopPropagation(); var dd = document.querySelector('[data-fa-dd="' + menuBtn.getAttribute('data-fa-menu') + '"]'); if (dd) dd.classList.toggle('open'); return; }

            var del = e.target.closest('[data-fa-del]');
            if (del) {
                var id = del.getAttribute('data-fa-del');
                if (window.confirm(T.confirmDelColl || 'Bạn muốn bỏ bộ sưu tập yêu thích này?')) {
                    writeLS(LS_COLL, collections().filter(function (c) { return c.id !== id; }));
                    writeLS(LS_OFF, offices().filter(function (o) { return o.IdFavourite !== id; }));
                    currentColl = null;
                    reloadList();
                }
                return;
            }
            var pdf = e.target.closest('[data-fa-pdf]');
            if (pdf) { var cid = pdf.getAttribute('data-fa-pdf'); var c = collections().filter(function (x) { return x.id === cid; })[0]; exportPdf(c ? c.FavouriteName : '', projectIdsOfColl(cid)); return; }
            var sh = e.target.closest('[data-fa-share]');
            if (sh) { shareLink(projectIdsOfColl(sh.getAttribute('data-fa-share'))); return; }

            var rm = e.target.closest('[data-fa-remove]');
            if (rm) {
                e.preventDefault(); e.stopPropagation();
                var pid = rm.getAttribute('data-fa-remove');
                if (window.confirm(T.confirmRemove || 'Bạn muốn bỏ yêu thích văn phòng này?')) {
                    writeLS(LS_OFF, offices().filter(function (o) { return !(o.IdFavourite === currentColl && String(o.projectId) === String(pid)); }));
                    selectColl(currentColl);
                    renderSidebar();
                }
                return;
            }
        });

        function reloadList() {
            renderSidebar();
            var colls = collections();
            if (sharedIds) { loadOffices([], true); return; }
            if (colls.length) {
                if (!currentColl || !colls.some(function (c) { return c.id === currentColl; })) currentColl = colls[0].id;
                selectColl(currentColl);
            } else {
                loadOffices([], false);
            }
        }

        window.__pplusFaPage = { reloadList: reloadList };
        reloadList();
    }

    /* ---------------- Khoi tao ---------------- */
    document.addEventListener('DOMContentLoaded', function () {
        initModalButtons();
        initFavouritePage();
    });
})();
