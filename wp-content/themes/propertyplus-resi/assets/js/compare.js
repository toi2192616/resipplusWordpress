// Tinh nang "So sanh" (nut "So sanh" tren the van phong, parts/office-card.php) - port tu:
//   src/app/partials/list-compare/list-compare.component.ts    (buoc 1: danh sach mini)
//   src/app/partials/product-compare/product-compare.component.ts (buoc 2: bang so sanh day du)
//
// Khac Angular goc (GlobalConstants.ListProducCompare - bien tinh trong bo nho, chia se qua
// Angular Router vi la SPA, MAT khi F5/doi trang): WP la site nhieu trang (moi lan chuyen trang la
// 1 lan tai lai toan bo JS) nen phai doi sang localStorage de danh sach "So sanh" duoc GIU LAI khi
// nguoi dung di tu trang nay sang trang khac roi quay lai bam "So sanh" tiep - day la thay doi CO
// CHU DICH so voi hanh vi goc, khong phai loi.
//
// Du lieu tung item lay tu attribute "data-compare" (JSON, xem parts/office-card.php) - nhung SAN
// tren the ngay luc PHP render (khong goi lai API luc mo modal, vi item co the da duoc chon tu 1
// trang KHAC ma trang hien tai khong co du lieu).
(function ($) {
  'use strict';

  var STORAGE_KEY = 'pplus_compare_list';
  var MAX_ITEMS = 4;

  var ICON_TRASH = '<svg width="20" height="22" viewBox="0 8 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">' +
    '<path d="M9.42773 12.8572L11.142 30H24.8563L26.5706 12.8572" stroke-linecap="round" stroke-linejoin="round" />' +
    '<path d="M20.25 23.25V15.75" stroke-linecap="round" stroke-linejoin="round" />' +
    '<path d="M15.75 23.25V15.75" stroke-linecap="round" stroke-linejoin="round" />' +
    '<path d="M6.85742 9.42857H13.7146M13.7146 9.42857L14.2877 7.1362C14.4546 6.46845 15.0546 6 15.7429 6H20.2577C20.946 6 21.546 6.46845 21.7129 7.1362L22.286 9.42857M13.7146 9.42857H22.286M22.286 9.42857H29.1431" stroke-linecap="round" stroke-linejoin="round" />' +
    '</svg>';

  function i18n() {
    return typeof PPLUS_COMPARE_I18N !== 'undefined' ? PPLUS_COMPARE_I18N : {};
  }

  function escapeHtml(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function getList() {
    try {
      var raw = window.localStorage.getItem(STORAGE_KEY);
      var list = raw ? JSON.parse(raw) : [];
      return Array.isArray(list) ? list : [];
    } catch (e) {
      return [];
    }
  }

  function saveList(list) {
    try {
      window.localStorage.setItem(STORAGE_KEY, JSON.stringify(list));
    } catch (e) {
      // localStorage khong kha dung (private mode het dung luong...) - bo qua, danh sach chi
      // song trong bo nho JS cho lan render hien tai, giong nhu Angular goc khi khong the luu.
    }
  }

  function unitFor(item) {
    return String(item.subType) === '1' ? i18n().unitPerson : i18n().unitArea;
  }

  function openModal(id) {
    $('.modal-container-ppus').removeClass('out').removeClass('two');
    $('body').removeClass('modal-active');
    $('#' + id).removeClass('out').removeClass('two').addClass('two');
    $('body').addClass('modal-active');
  }

  // ===== Modal 1: danh sach mini (list-compare.component.html) =====
  function renderRatingBadge(rating) {
    var cls = '';
    var text = i18n().updating || 'Updating';
    if (rating === 'A' || rating === 'B' || rating === 'C') {
      cls = ' class' + rating;
      text = (i18n().gradeLabel || 'Grade %s').replace('%s', rating);
    }
    return '<div class="RateTingContent' + cls + '">' + escapeHtml(text) + '</div>';
  }

  function renderListModal(list) {
    var $container = $('#pplus-compare-list-items');
    if (!$container.length) {
      return;
    }
    if (!list.length) {
      $container.empty();
      return;
    }
    var html = list.map(function (item) {
      var photo = item.photo ? escapeHtml(item.photo) : '';
      return '' +
        '<div class="item-compare" data-project-id="' + escapeHtml(item.projectId) + '">' +
        (photo ? '<img src="' + photo + '" alt="' + escapeHtml(item.projectName) + '" />' : '') +
        '<div class="item-dislike">' + ICON_TRASH + '</div>' +
        '<div class="item-content-m"><div class="item-content">' +
        '<div class="RateTing">' + renderRatingBadge(item.ratingLevel) + '</div>' +
        '<div class="item-name">' + escapeHtml(item.projectName) + '</div>' +
        '<div class="item-address">' + escapeHtml(item.address) + '</div>' +
        '<div class="item-price">' + escapeHtml(item.itemPrice) + '</div>' +
        '</div></div>' +
        '</div>';
    }).join('');
    $container.html(html);
  }

  // ===== Modal 2: bang so sanh day du (product-compare.component.html) =====
  // THU TU 14 dong nay PHAI khop dung thu tu $pplus_compare_criteria trong parts/compare-modal.php
  // (cot nhan/icon ben do render tinh boi PHP, o day chi render GIA TRI tuong ung tung cot item).
  var CRITERIA_ROWS = [
    { price: true, render: function (item) { return item.itemPrice; } },
    { price: true, render: function (item) { return item.managerFee !== '' ? ('$' + item.managerFee + '/' + unitFor(item)) : ''; } },
    { price: true, render: function (item) { return (item.fromTotalAmt !== '' || item.toTotalAmt !== '') ? ('$' + item.fromTotalAmt + '-' + item.toTotalAmt + '/' + unitFor(item)) : ''; } },
    { address: true, render: function (item) { return item.address; } },
    { render: function (item) { return item.ratingLevel; } },
    { render: function (item) { return item.pmCompany; } },
    { render: function (item) { return item.releaseDate; } },
    { render: function (item) { return item.floorAreas; } },
    { render: function (item) { return item.parkingAvailability; } },
    { render: function (item) { return item.powerGeneratorInfo; } },
    { render: function (item) { return item.elevatorInfo; } },
    { render: function (item) { return item.airConditionInfo; } },
    { render: function (item) { return item.carParkInfo; } },
    { render: function (item) { return item.motorParkInfo; } }
  ];

  // Cot nhan (PHP, parts/compare-modal.php) va tung cot san pham (JS o day) CUNG la con truc tiep
  // cua 1 luoi CSS Grid duy nhat (".container-compare-data", "display:contents" tren cac wrapper -
  // xem compare-component.css) - moi o o day PHAI mang dung "grid-row" khop voi cot nhan (1=header,
  // 2..15=14 tieu chi, xem PPLUS_COMPARE_CRITERIA_ORDER) va "grid-column" rieng cua chinh no
  // (2=item dau tien, 3=item thu 2...) de trinh duyet TU canh chieu cao tung hang khop nhau giua
  // nhan va MOI cot (hang nao noi dung dai thi ca hang cao ra, hang ngan thi hep lai - theo dung
  // yeu cau nguoi dung, KHONG con ep 1 chieu cao co dinh chung cho ca bang nhu truoc).
  function renderTableModal(list) {
    var $container = $('#pplus-compare-table-items');
    var $grid = $('#modal-compare-table .container-compare-data');
    if (!$container.length) {
      return;
    }
    if (!list.length) {
      $container.empty();
      if ($grid.length) {
        $grid.css('grid-template-columns', '220px');
      }
      return;
    }
    if ($grid.length) {
      $grid.css('grid-template-columns', '220px repeat(' + list.length + ', minmax(260px, 1fr))');
    }
    var html = list.map(function (item, idx) {
      var col = idx + 2;
      var photo = item.photo ? escapeHtml(item.photo) : '';
      var rows = CRITERIA_ROWS.map(function (row, rowIdx) {
        var value = row.render(item) || '';
        var cls = 'inforComapre' + (row.address ? ' inforComapre-address' : '');
        var labelCls = row.price ? ' class="lable-Price"' : '';
        var style = 'grid-row:' + (rowIdx + 2) + ';grid-column:' + col + ';';
        return '<div class="' + cls + '" style="' + style + '"><label' + labelCls + '>' + escapeHtml(value) + '</label></div>';
      }).join('');
      return '' +
        '<div class="inforCompare-data" data-project-id="' + escapeHtml(item.projectId) + '">' +
        '<div class="header-data-1" style="grid-row:1;grid-column:' + col + ';"><div class="photo-data">' +
        (photo ? '<img src="' + photo + '" alt="' + escapeHtml(item.projectName) + '" />' : '') +
        '<div class="item-dislike">' + ICON_TRASH + '</div>' +
        '<div class="name-office">' + escapeHtml(item.projectName) + '</div>' +
        '</div></div>' +
        rows +
        '</div>';
    }).join('');
    $container.html(html);
  }

  // Mo bang so sanh day du NGAY LAP TUC voi trang thai "Dang tai" (chi la "chua co du lieu",
  // KHONG dung "rong" - ".container-compare-data" rong that se ep grid ve "220px" nhu
  // renderTableModal() lam), thay vi doi ensureCorrectLang()/ensureParkFees() (2 REST call, co the
  // mat vai giay tuy toc do backend .NET) xong moi mo - truoc day nut "So sanh" tren thanh cong cu
  // nhu "khong phan hoi gi" trong luc cho, du click DA duoc nhan (bug thuc te nguoi dung bao cao:
  // "bấm nút so sánh khá lâu mới mở ra dữ liệu"). Chi cai thien CAM GIAC cho (modal mo ngay), KHONG
  // rut ngan thoi gian goi API that (backend .NET o xa, ngoai tam kiem soat cua theme).
  function showTableLoading() {
    var $container = $('#pplus-compare-table-items');
    if ($container.length) {
      $container.html('<div class="pplus-compare-loading">' + escapeHtml(i18n().loadingCompare || 'Đang tải dữ liệu so sánh...') + '</div>');
    }
    openModal('modal-compare-table');
  }

  function removeItem(projectId) {
    var list = getList().filter(function (o) { return String(o.projectId) !== String(projectId); });
    saveList(list);
    renderListModal(list);
    renderTableModal(list);
    refreshActiveButtons();
  }

  // Nut "So sanh" tren the (parts/office-card.php .viewdetail) doi sang nen xanh/chu trang khi
  // toa nha do DA co trong danh sach so sanh - port [ngClass]="{'active': item.isCompare==1}"
  // (office-list.component.html dong 68). CSS ".viewdetail.active" da co san tu truoc (xem
  // assets/officebase/style/layout.css dong 1836) nhung chua tung duoc gan vi truoc day nut
  // "So sanh" la UI tinh, chua noi JS - o day chi can toggle class dung luc.
  function refreshActiveButtons() {
    var ids = getList().map(function (o) { return String(o.projectId); });
    $('.viewdetail[data-compare]').each(function () {
      var $btn = $(this);
      $btn.toggleClass('active', ids.indexOf(String($btn.attr('data-id'))) !== -1);
    });
  }

  // Lay them "Phi gui o to"/"Phi gui xe may" tu Office/_GetDetail (LUON rong tren endpoint danh
  // sach dung de dung the - xem inc/compare-pdf-route.php). Goi 1 LAN DUY NHAT ngay TRUOC khi mo
  // bang so sanh (khong phai moi luc them 1 item), va CHI cho nhung item con thieu du lieu.
  //
  // BUG THUC TE DA GAP (ban truoc goi rieng le luc them tung item): them nhieu tòa lien tiep khien
  // nhieu request bay gan nhu cung luc; moi callback tu doc TOAN BO danh sach tu localStorage, gan
  // ket qua cua CHINH NO, roi ghi lai CA danh sach - callback ve SAU doc phai ban CHUA co cap nhat
  // cua callback ve TRUOC (con dang xu ly bat dong bo), nen khi no ghi lai se XOA MAT cap nhat truoc
  // do (ghi de toan bo, khong phai chi 1 truong). Ket qua: co luc thay du lieu (them 1 tòa mot),
  // co luc mat trang (them nhieu tòa lien tiep) - dung ngay hien tuong nguoi dung bao cao.
  // Fix: dung 1 bien "list" DUY NHAT, moi request chi SUA truc tiep object cua no trong CHINH bien
  // do (khong doc lai localStorage giua chung), roi CHI saveList() 1 LAN DUY NHAT sau khi TAT CA
  // request da xong (dem "remaining" ve 0) - khong con 2 lan doc/ghi nao chen ngang nhau duoc nua.
  function ensureParkFees(list, done) {
    var pending = list.filter(function (o) {
      return o.urlHerf && (typeof o.carParkInfo === 'undefined' || typeof o.motorParkInfo === 'undefined');
    });
    if (!pending.length || typeof PPLUS_COMPARE_PARK_FEES_URL === 'undefined') {
      done(list);
      return;
    }
    var remaining = pending.length;
    pending.forEach(function (item) {
      $.get(PPLUS_COMPARE_PARK_FEES_URL, { href: item.urlHerf, lang: pplusCompareLang() })
        .done(function (res) {
          if (res && res.success) {
            item.carParkInfo = res.carParkInfo || '';
            item.motorParkInfo = res.motorParkInfo || '';
          }
        })
        .always(function () {
          remaining--;
          if (remaining === 0) {
            saveList(list);
            done(list);
          }
        });
    });
  }

  function pplusCompareLang() {
    return typeof PPLUS_COMPARE_LANG !== 'undefined' ? PPLUS_COMPARE_LANG : 'vi';
  }

  // Danh sach "So sánh" song trong localStorage (xem ghi chu dau file) - 1 item duoc THEM luc dang
  // xem 1 ngon ngu se giu NGUYEN VAN du lieu cua ngon ngu do MAI MAI trong may nguoi dung, ke ca sau
  // khi doi site sang ngon ngu khac (bug thuc te nguoi dung bao cao: "Màn so sánh khi ở tiếng anh...
  // cần lấy nội dung tiếng anh"). Goi truoc luc mo bang so sanh day du: item nao co "item.lang" KHAC
  // ngon ngu hien tai (hoac CHUA CO "lang" - item them tu truoc luc chua co co che nay, coi nhu cu/
  // khong chac chan, an toan hon la lam moi lai) se duoc goi lai TOAN BO du lieu qua REST route
  // "pplus/v1/compare-item" (xem inc/compare-pdf-route.php). Xoa han "carParkInfo"/"motorParkInfo"
  // CU sau khi lam moi - 2 truong nay KHONG nam trong du lieu tra ve (cung 1 ly do voi luc them moi,
  // xem parts/office-card.php), de ensureParkFees() nhan ra can goi lay lai o dung ngon ngu moi
  // (neu khong se giu nguyen gia tri phi gui xe cu, co the sai ngon ngu).
  function ensureCorrectLang(list, done) {
    var currentLang = pplusCompareLang();
    var pending = list.filter(function (o) {
      return o.projectId && o.lang !== currentLang;
    });
    if (!pending.length || typeof PPLUS_COMPARE_ITEM_URL === 'undefined') {
      done(list);
      return;
    }
    var remaining = pending.length;
    pending.forEach(function (item) {
      $.get(PPLUS_COMPARE_ITEM_URL, { project_id: item.projectId, lang: currentLang })
        .done(function (res) {
          if (res && res.success && res.item) {
            $.extend(item, res.item);
            item.lang = currentLang;
            delete item.carParkInfo;
            delete item.motorParkInfo;
          }
        })
        .always(function () {
          remaining--;
          if (remaining === 0) {
            saveList(list);
            done(list);
          }
        });
    });
  }

  $(function () {
    // Ap dung trang thai "active" ngay luc tai trang (danh sach co the da co san tu 1 trang khac,
    // xem ghi chu localStorage o dau file).
    refreshActiveButtons();

    // Bam "So sanh" tren 1 the van phong (parts/office-card.php .viewdetail[data-compare]) - them
    // vao danh sach (co kiem tra trung/toi da 4, giong het thu tu logic cua
    // list-compare.component.ts openListCompare()) roi LUON mo modal danh sach mini, du them
    // thanh cong hay khong (giong hanh vi goc).
    $(document).on('click', '.viewdetail[data-compare]', function (e) {
      e.preventDefault();
      e.stopPropagation();

      var item;
      try {
        item = JSON.parse($(this).attr('data-compare'));
      } catch (err) {
        return;
      }
      if (!item || !item.projectId) {
        return;
      }
      // "item.lang" - ngon ngu cua CHINH data-compare vua doc (luon la ngon ngu trang hien tai,
      // dung PHP render ra), de ensureCorrectLang() sau nay biet item nay CO CAN lam moi lai hay
      // khong khi nguoi dung mo bang so sanh o 1 ngon ngu khac.
      item.lang = pplusCompareLang();

      var list = getList();
      if (list.length >= MAX_ITEMS) {
        if (typeof toastr !== 'undefined') {
          toastr.error(i18n().maxReached, i18n().warningTitle);
        }
      } else {
        var exists = list.some(function (o) { return String(o.projectId) === String(item.projectId); });
        if (exists) {
          if (typeof toastr !== 'undefined') {
            toastr.error(i18n().alreadyAdded, i18n().warningTitle);
          }
        } else {
          list.push(item);
          saveList(list);
        }
      }

      renderListModal(getList());
      refreshActiveButtons();
      openModal('modal-compare-list');
    });

    // Nut "So sanh" tren thanh cong cu canh "Tim kiem"/"Ban do" (page-office-list.php +
    // page-reference.php, class ".js-open-compare-list") - xac nhan truc tiep tren propertyplus.vn
    // (khac voi doan (kha nghi ban dau): nut nay mo THANG bang so sanh DAY DU (modal-compare-table,
    // giong het actionPageCompare() cua Angular), KHONG qua modal danh sach mini - khong co logic
    // them gi ca, chi hien thi lai nhung gi dang co (co the rong).
    $(document).on('click', '.js-open-compare-list', function (e) {
      e.preventDefault();
      showTableLoading();
      ensureCorrectLang(getList(), function (list) {
        ensureParkFees(list, function (list) {
          renderTableModal(list);
        });
      });
    });

    // Xoa 1 item khoi danh sach - dung chung 1 handler cho ca 2 modal (luon lam tuoi lai CA HAI
    // container de tranh du lieu cu khi nguoi dung mo lai modal con lai).
    $(document).on('click', '#pplus-compare-list-items .item-dislike, #pplus-compare-table-items .item-dislike', function () {
      var id = $(this).closest('[data-project-id]').attr('data-project-id');
      removeItem(id);
    });

    // Nut "So sanh" trong modal danh sach mini -> mo bang so sanh day du (actionPageCompare()).
    $('#pplus-compare-open-table').on('click', function () {
      showTableLoading();
      ensureCorrectLang(getList(), function (list) {
        ensureParkFees(list, function (list) {
          renderTableModal(list);
        });
      });
    });

    // Nut "Xuat PDF" trong modal bang so sanh - proxy qua REST route WordPress (xem
    // inc/compare-pdf-route.php), thay the officeService.DowloadPdfCompare() ben Angular.
    $('#pplus-compare-export-pdf').on('click', function () {
      var list = getList();
      var $btn = $(this);

      if (!list.length) {
        if (typeof toastr !== 'undefined') {
          toastr.error(i18n().pdfEmptyError, i18n().warningTitle);
        }
        return;
      }
      if (!window.confirm(i18n().pdfConfirm)) {
        return;
      }
      if (typeof PPLUS_COMPARE_PDF_URL === 'undefined') {
        return;
      }

      var ids = list.map(function (item) { return item.projectId; }).join(',');
      $btn.prop('disabled', true);

      $.ajax({
        url: PPLUS_COMPARE_PDF_URL,
        method: 'POST',
        contentType: 'application/json; charset=utf-8',
        data: JSON.stringify({ ids: ids, lang: pplusCompareLang() }),
        xhrFields: { responseType: 'blob' }
      }).done(function (blob, status, xhr) {
        var contentType = xhr.getResponseHeader('Content-Type') || '';
        if (contentType.indexOf('application/pdf') === -1) {
          // Backend tra loi dang JSON (khong phai PDF that) nhung xhrFields da ep responseType
          // thanh blob nen phai doc lai noi dung qua FileReader thay vi doc thang xhr.responseText.
          var reader = new FileReader();
          reader.onload = function () {
            var message = i18n().pdfError;
            try {
              var parsed = JSON.parse(reader.result);
              message = (parsed && parsed.message) || message;
            } catch (e) { /* giu message mac dinh */ }
            if (typeof toastr !== 'undefined') {
              toastr.error(message, i18n().warningTitle);
            }
          };
          reader.readAsText(blob);
          return;
        }

        var url = window.URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = 'SoSanhVanPhong.pdf';
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(url);
        if (typeof toastr !== 'undefined') {
          toastr.success(i18n().pdfSuccess);
        }
      }).fail(function () {
        if (typeof toastr !== 'undefined') {
          toastr.error(i18n().pdfError, i18n().warningTitle);
        }
      }).always(function () {
        $btn.prop('disabled', false);
      });
    });
  });
})(jQuery);
