/**
 * Danh sach yeu thich (port module Angular "favourite" + partials create/list-farourite). Toan bo
 * du lieu luu o localStorage (giong Angular, khong can login) - DUNG DUNG TEN KHOA THAT (xac nhan
 * qua favourite.component.ts/create-farourite.component.ts, PPlusAngularResi):
 *   ListFavourite            = [{ id, FavouriteName, DateCreate }]                 (bo suu tap)
 *   listApartmentFavourite   = [{ IdFavourite, apartmentId, title, photoList, ... }] (can ho trong tung bo)
 *
 * SUA 22/9 (cung lan sua page-favourite.php - xem docblock file do): khoa cu "listOfficeFavourite"
 * la tan du copy tu theme Van phong, KHONG khop du lieu that cua Resi (component that dung
 * "listApartmentFavourite" + field "apartmentId", khong phai "projectId"). Nut ".item-like" gio
 * doc tu "data-apartment" (parts/apartment-card.php, moi them) thay vi "data-office" cu (van giu
 * fallback doc "data-office" phong khi office-card.php con noi nao dang dung).
 *
 * Nut ".item-like" (Yeu thich) tren card mo modal (parts/favourite-modals.php). Trang
 * /danh-sach-yeu-thich (page-favourite.php) CHI doc localStorage (khong goi API/REST nao - giong
 * y het LoadList() that, xem doc trong page-favourite.php) de render luoi bo suu tap.
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
    var LS_OFF = 'listApartmentFavourite';

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
    // "apartmentId" (Resi that) hoac "projectId" (Office, fallback) - dung o nhieu cho de tranh
    // lap dieu kien "a.apartmentId || a.projectId" moi lan can so sanh/dedup 1 item yeu thich.
    function favId(o) { return o && (o.apartmentId != null ? o.apartmentId : o.projectId); }

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

    /* ---------------- Doc can ho/VP tu card ---------------- */
    function officeFromEl(el) {
        var card = el.closest('[data-apartment]') || el.closest('[data-office]');
        if (!card) return null;
        var raw = card.getAttribute('data-apartment') || card.getAttribute('data-office');
        try { return JSON.parse(raw); } catch (e) { return null; }
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
            var dup = offices.some(function (x) { return x.IdFavourite === collId && favId(x) === favId(o); });
            if (!dup) offices.push(o);
        });
        writeLS(LS_OFF, offices);
    }

    /* ================= TAO/SUA BO SUU TAP ================= */
    var createMode = 'plain'; // 'plain' = tao roi thoi; 'add' = tao xong them pendingOffice vao
    var editCollId = null; // != null => dang SUA ten bo suu tap co san (khong tao moi) - port
    // EditFavourite() (create-farourite.component.ts dong 104-114): "Favourite" != undefined thi
    // nut doi thanh "Sua", chi doi FavouriteName, KHONG tao id moi/KHONG dong toi listApartmentFavourite.

    // Mo modal tao/sua o TRANG THAI "plain" (tao moi) hoac "edit" (sua ten, "collId" != null).
    // KHONG dong toi "createMode"/"pendingOffice" o day - do la trang thai cua NGUOI GOI ham nay
    // (vd nut "Bộ sưu tập mới" trong modal them-vao can giu "createMode='add'" + "pendingOffice"
    // nguyen ven qua ham nay) - tung co bug thuc te: ham nay tu RESET ca 2 bien ve 'plain'/null
    // ngay sau khi nguoi goi vua gan 'add', khien bam "Bộ sưu tập mới" tao bo suu tap THANH CONG
    // nhung KHONG gan can ho dang bam "thích" vao bo suu tap moi do (im lang, khong loi).
    function openCreateModal(collId) {
        editCollId = collId || null;
        var input = document.getElementById('pplus-create-name');
        var btn = document.getElementById('pplus-create-add');
        var title = document.querySelector('#pplus-modal-create .modal-title');
        if (editCollId) {
            var coll = readLS(LS_COLL).filter(function (c) { return c.id === editCollId; })[0];
            if (input) input.value = coll ? coll.FavouriteName : '';
            if (btn) btn.textContent = T.editBtn || 'Sửa';
            if (title) title.textContent = T.editTitle || 'Đổi tên bộ sưu tập';
        } else {
            if (input) input.value = '';
            if (btn) btn.textContent = T.addBtn || 'Thêm vào';
            if (title) title.textContent = T.createTitle || 'Tạo bộ sưu tập mới';
        }
        openModal('pplus-modal-create');
    }

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
            closeModal(document.getElementById('pplus-modal-addfa'));
            openCreateModal(null);
        });

        // Nut "+" (tao bo suu tap moi) tren trang /danh-sach-yeu-thich (page-favourite.php
        // "#btnAddCollectionGrid") - port openCreateFarourite(null) (favourite.component.html/.ts
        // goc, khong co pendingOffice).
        var faCreate = document.getElementById('btnAddCollectionGrid');
        if (faCreate) faCreate.addEventListener('click', function () {
            createMode = 'plain';
            openCreateModal(null);
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

        // Nut "Them vao"/"Sua" trong modal tao/sua (tuy "editCollId", xem openCreateModal()).
        var createAdd = document.getElementById('pplus-create-add');
        if (createAdd) createAdd.addEventListener('click', function () {
            var input = document.getElementById('pplus-create-name');
            var name = input ? input.value.trim() : '';
            if (!name) { toast('error', T.needName || 'Bạn cần nhập tên bộ sưu tập'); return; }

            if (editCollId) {
                var colls = readLS(LS_COLL);
                colls.forEach(function (c) { if (c.id === editCollId) c.FavouriteName = name; });
                writeLS(LS_COLL, colls);
                toast('success', T.renamed || 'Cập nhật bộ sưu tập thành công');
                editCollId = null;
                closeModal(document.getElementById('pplus-modal-create'));
                if (window.__pplusFaPage) window.__pplusFaPage.reloadList();
                return;
            }

            var colls2 = readLS(LS_COLL);
            var coll = { id: uuid(), FavouriteName: name, DateCreate: new Date().toISOString() };
            colls2.push(coll);
            writeLS(LS_COLL, colls2);
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
        document.querySelectorAll('[data-apartment],[data-office]').forEach(function (card) {
            try {
                var raw = card.getAttribute('data-apartment') || card.getAttribute('data-office');
                var o = JSON.parse(raw);
                if (o && String(favId(o)) === String(favId(office))) card.classList.add('pplus-liked');
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
            }).then(function (r) {
                if ((r.headers.get('Content-Type') || '').indexOf('application/pdf') === -1) throw new Error();
                return r.blob();
            })
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

    /* ================= TRANG /danh-sach-yeu-thich (page-favourite.php) =================
       Port DUNG tu favourite.component.ts LoadList() that: KHONG goi API/REST nao ca, chi doc 3
       khoa localStorage (ListFavourite/listApartmentFavourite/listProjectFavourite - ve sau
       "Project" luon rong vi WP nay chua co tinh nang "them Project vao yeu thich" o dau ca) roi
       ghep photoList/Count cho tung bo suu tap. KHAC HAN khoi CU (da xoa, xem git history) tung
       goi REST "offices-by-ids"/Google Map - do la logic cua favourite-DETAIL (1 trang RIENG,
       CHUA xay - xem docblock day du trong page-favourite.php), khong phai trang nay. */
    function initFavouritePage() {
        var grid = document.getElementById('pplus-fa-grid');
        if (!grid) return;

        var typeView = 1; // 1 = luoi the (mac dinh, that), 2 = bang
        var filterText = '';

        function collections() { return readLS(LS_COLL); }
        function apartments() { return readLS(LS_OFF); }

        // Ghep photoList/Count cho tung bo suu tap - port DUNG doan LoadList() (favourite.
        // component.ts dong 81-116): gop photoList cua MOI can ho thuoc bo suu tap do, lay 4 anh
        // dau lam thumbnail, "Count" = so luong can ho (KHONG PHAI so anh).
        function buildList() {
            var offs = apartments();
            var colls = collections();
            if (filterText) {
                var f = filterText.toLowerCase();
                colls = colls.filter(function (c) { return (c.FavouriteName || '').toLowerCase().indexOf(f) !== -1; });
            }
            return colls.map(function (c) {
                var inColl = offs.filter(function (o) { return o.IdFavourite === c.id; });
                var photos = [];
                inColl.forEach(function (o) { photos = photos.concat(o.photoList || []); });
                return {
                    id: c.id,
                    FavouriteName: c.FavouriteName,
                    DateCreate: c.DateCreate,
                    Count: inColl.length,
                    photo1: photos[0] || '',
                    photo2: photos[1] || '',
                    photo3: photos[2] || '',
                    photo4: photos[3] || '',
                    photoCount: photos.length
                };
            });
        }

        function detailUrl(id) {
            return (CFG.detailUrl || '') + '?fshare_link=' + encodeURIComponent(id);
        }
        var placeholder = CFG.placeholderImg || '';

        function gridItemHtml(c) {
            var url = detailUrl(c.id);
            function photoImg(cls, src) {
                return '<a class="photo ' + cls + '" href="' + esc(url) + '"><img src="' + esc(src || placeholder) + '" onerror="this.src=\'' + esc(placeholder) + '\'" /></a>';
            }
            return '<div class="collection-gridbox">' +
                '<div class="collection-photos">' +
                photoImg('photo1', c.photo1) +
                photoImg('photo2', c.photo2) +
                photoImg('photo3', c.photo3) +
                '<a class="photo photo4" href="' + esc(url) + '"><img src="' + esc(c.photo4 || placeholder) + '" onerror="this.src=\'' + esc(placeholder) + '\'" />' +
                (c.photoCount > 4 ? '<div class="bg-count"></div><div class="count-plus">+' + (c.photoCount - 4) + '</div>' : '') +
                '</a>' +
                '</div>' +
                '<div class="collection-des">' +
                '<a class="collec-infos" href="' + esc(url) + '">' +
                '<div class="collection-created">' + esc(T.savedOn || 'Lưu ngày') + ' ' + fmtDate(c.DateCreate) + '</div>' +
                '<div class="collection-header"><div class="collection-title">' + esc(c.FavouriteName) + '</div>' +
                '<div class="collection-item-count"><span>' + c.Count + '</span> ' + esc(T.apartmentUnit || 'căn hộ') + '</div></div>' +
                '</a>' +
                toolboxHtml(c.id) +
                '</div></div>';
        }

        function tableRowHtml(c) {
            var url = detailUrl(c.id);
            return '<tr>' +
                '<td><a class="collection-title" href="' + esc(url) + '">' + esc(c.FavouriteName) + '</a></td>' +
                '<td class="collection-created">' + fmtDate(c.DateCreate) + '</td>' +
                '<td class="collection-item-count">' + c.Count + '</td>' +
                '<td>' + toolboxHtml(c.id) + '</td>' +
                '</tr>';
        }

        function toolboxHtml(id) {
            return '<div ngbDropdown class="toolbox" data-fa-toolbox="' + esc(id) + '">' +
                '<button type="button" class="dropdown-toggle" data-fa-menu="' + esc(id) + '"><div></div><div></div><div></div></button>' +
                '<ul class="dropdown-menu dropdown-menu-favourite" data-fa-dd="' + esc(id) + '">' +
                '<li class="collection-menu-item"><a data-fa-rename="' + esc(id) + '">' + esc(T.rename || 'Đổi tên') + '</a></li>' +
                '<li class="collection-menu-item"><a data-fa-del="' + esc(id) + '">' + esc(T.delColl || 'Xóa') + '</a></li>' +
                '<li class="collection-menu-item"><a data-fa-pdf="' + esc(id) + '">' + esc(T.exportPdf || 'Tải PDF') + '</a></li>' +
                '<li class="collection-menu-item"><a data-fa-share="' + esc(id) + '">' + esc(T.share || 'Copy link') + '</a></li>' +
                '</ul></div>';
        }

        function render() {
            var list = buildList();
            document.getElementById('pplus-fa-count').textContent = collections().length;
            if (typeView === 1) {
                grid.style.display = '';
                document.getElementById('collection-area-data-table').style.display = 'none';
                grid.innerHTML = list.map(gridItemHtml).join('');
            } else {
                grid.style.display = 'none';
                document.getElementById('collection-area-data-table').style.display = '';
                document.getElementById('collection-area-data-body').innerHTML = list.map(tableRowHtml).join('');
            }
        }

        /* ----- Doi luoi/bang ----- */
        document.querySelectorAll('.action-find .sort-list').forEach(function (el) {
            el.addEventListener('click', function () {
                typeView = parseInt(el.getAttribute('data-fa-view'), 10) || 1;
                document.querySelectorAll('.action-find .sort-list').forEach(function (x) { x.classList.remove('active'); });
                el.classList.add('active');
                render();
            });
        });

        /* ----- Tim theo ten (loc client-side, khong co trong Angular goc nhung input da co san
           trong markup that - #FavouriteName - de trong tay khong lam gi la vo ly) ----- */
        var searchInput = document.getElementById('FavouriteName');
        if (searchInput) searchInput.addEventListener('input', function () {
            filterText = searchInput.value.trim();
            render();
        });

        /* ----- Share (URL that: /favourite/favourite-detail?ids=...&IdProject=..., xem
           copyLinkShare() favourite.component.ts dong 169-198 - trang dich CHUA xay, van copy
           DUNG URL de link hoat dong ngay khi favourite-detail duoc xay sau nay) ----- */
        function shareLink(collId) {
            var ids = apartments().filter(function (o) { return o.IdFavourite === collId; }).map(function (o) { return o.apartmentId; });
            var url = (CFG.detailUrl || '') + '?ids=' + ids.join(',');
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(function () { toast('success', T.copied || 'Copy link yêu thích thành công!'); });
            } else { window.prompt('Copy link', url); }
        }

        /* ----- PDF ----- */
        function exportPdf(name, collId) {
            var ids = apartments().filter(function (o) { return o.IdFavourite === collId; }).map(function (o) { return o.apartmentId; });
            if (!ids.length) { toast('error', T.pdfNoOffice || 'Không thấy căn hộ nào'); return; }
            if (!window.confirm(T.pdfConfirm2 || 'Bạn xác nhận muốn tải file PDF bộ sưu tập này?')) return;
            // API tao PDF co the mat vai chuc giay - bao truoc de nguoi dung khong tuong nut khong an (Office abe1965).
            toast('info', T.pdfWait || 'Đang tạo file PDF, vui lòng chờ...');
            fetch(CFG.restUrl + 'favourite-pdf', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ name: name || 'Căn hộ yêu thích', ids: ids.join(','), lang: CFG.lang || 'vi' })
            }).then(function (r) {
                // Chi coi la PDF khi server tra dung "application/pdf"; nguoc lai doc thong bao loi JSON (inc/favourite-rest.php).
                if ((r.headers.get('Content-Type') || '').indexOf('application/pdf') === -1) {
                    return r.json().catch(function () { return null; }).then(function (data) {
                        throw new Error((data && data.message) || '');
                    });
                }
                return r.blob();
            })
                .then(function (blob) {
                    var url = URL.createObjectURL(blob);
                    var a = document.createElement('a');
                    a.href = url; a.download = (name || 'YeuThich') + '-' + fmtDate() + '-PDFYeuThich.pdf';
                    document.body.appendChild(a); a.click(); a.remove(); URL.revokeObjectURL(url);
                    toast('success', T.pdfOk || 'Tải file PDF thành công!');
                }).catch(function (err) { toast('error', (err && err.message) || T.pdfErr || 'Không tải được file PDF!'); });
        }

        /* ----- Delegated clicks (toolbox menu tren tung the/dong) ----- */
        document.addEventListener('click', function (e) {
            var menuBtn = e.target.closest('[data-fa-menu]');
            if (menuBtn) {
                e.preventDefault(); e.stopPropagation();
                var id = menuBtn.getAttribute('data-fa-menu');
                document.querySelectorAll('.toolbox.open').forEach(function (t) { if (t.getAttribute('data-fa-toolbox') !== id) t.classList.remove('open'); });
                var tb = e.target.closest('.toolbox');
                if (tb) tb.classList.toggle('open');
                return;
            }

            var rename = e.target.closest('[data-fa-rename]');
            if (rename) { e.preventDefault(); openCreateModal(rename.getAttribute('data-fa-rename')); return; }

            var del = e.target.closest('[data-fa-del]');
            if (del) {
                e.preventDefault();
                var delId = del.getAttribute('data-fa-del');
                if (window.confirm(T.confirmDelColl || 'Bạn muốn bỏ bộ sưu tập yêu thích này?')) {
                    writeLS(LS_COLL, collections().filter(function (c) { return c.id !== delId; }));
                    writeLS(LS_OFF, apartments().filter(function (o) { return o.IdFavourite !== delId; }));
                    toast('success', T.deletedCollection || 'Xóa bộ sưu tập thành công');
                    render();
                }
                return;
            }

            var pdf = e.target.closest('[data-fa-pdf]');
            if (pdf) {
                e.preventDefault();
                var pdfId = pdf.getAttribute('data-fa-pdf');
                var pc = collections().filter(function (c) { return c.id === pdfId; })[0];
                exportPdf(pc ? pc.FavouriteName : '', pdfId);
                return;
            }

            var sh = e.target.closest('[data-fa-share]');
            if (sh) { e.preventDefault(); shareLink(sh.getAttribute('data-fa-share')); return; }

            if (!e.target.closest('.toolbox')) {
                document.querySelectorAll('.toolbox.open').forEach(function (t) { t.classList.remove('open'); });
            }
        });

        window.__pplusFaPage = { reloadList: render };
        render();
    }

    /* ---------------- Khoi tao ---------------- */
    document.addEventListener('DOMContentLoaded', function () {
        initModalButtons();
        initFavouritePage();
    });
})();
