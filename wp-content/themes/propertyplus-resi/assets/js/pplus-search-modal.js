// #searchModal - logic tim kiem (parts/search-modal.php). Port tu search-base.component.ts:
// DataCoreroupToBack() (dong 574-727), ChangeApartmentType() (dong 937-948), clickSubmitSearch()
// (dong 265-271, nhanh "isMapshow===false" - trang WP nay luon full-page-reload, khong co nhanh
// AJAX "isMapshow===true" cua ban Angular).
//
// 11/11/2026 (theo yeu cau nguoi dung): popup KHONG con mo ra "trang" tren trang danh sach. Vung (quan/loai/khu vuc...) va bo loc cua trang dang
// xem (data-context, xem pplus_search_context() trong inc/render-helpers.php) duoc dien san:
//   - o "Vùng" hien san vung dang xem (kem nut x de bo -> "Tất cả"), popup mo thang o "Chi tiết" (loc gia/phong ngu...) thay vi luoi goi y;
//   - bo loc dang ap (gia, phong ngu...) dien lai, loc them se CONG DON, co nut "Xóa bộ lọc";
//   - bam "Tìm kiếm" giu nguyen vung + bo loc moi (truoc day mat vung, ve /ha-noi).
// Trang chu / trang khong co vung: hanh vi cu (o trong, bam vao o Vùng moi hien luoi goi y).
(function ($) {
  'use strict';

  $(function () {
    // Goc cua thanh tim kiem: popup "#searchModal" (moi trang co hang goi y) hoac - o TRANG CHU, noi footer.php khong in popup - chinh thanh
    // tim kiem trong hero "#pplus-home-search" (front-page.php). Cung markup luoi goi y/panel loc (parts/search-suggest-grid.php,
    // parts/search-filter-panel.php), cung id, nen toan bo logic ben duoi dung chung.
    var $modal = $('#searchModal');
    var isModal = $modal.length > 0;
    if (!isModal) {
      $modal = $('#pplus-home-search');
    }
    if (!$modal.length) {
      return;
    }

    var $suggestList = $('#pplus-search-suggest-list');
    var $locationInput = $('#search-modal-input1');
    var $detailInput = $('#search-modal-input2');
    var $locationField = $locationInput.closest('.search-input-group');
    var selectedAlias = '';
    // Lua chon tu goi y khi go (du an / dia diem Google - pplus-search-suggest.js); null = khong chon gi.
    var selection = null;
    // "Loại nhà/phòng" - 2 checkbox CUNG ten nhung KHONG loai tru nhau (khong phai radio, dung y het ban goc) - ChangeApartmentType() that:
    // checked -> ghi de gia tri, unchecked -> xoa ve rong BAT KE checkbox con lai the nao (gia tri CUOI CUNG thang doi, khong phai "gop" ca 2).
    var apartmentType = '';

    var ctx = {};
    try {
      ctx = JSON.parse($modal.attr('data-context') || '{}') || {};
    } catch (e) {
      ctx = {};
    }

    // Goi y -> o "Chi tiết": cac truong loc ma goi y mang theo (data-filter, xem pplus_suggestion_option_filter()) duoc dien san (so phong
    // ngu/tam to mau nut tuong ung, dien tich, gia, tang). mapped = cac truong DO GOI Y dat - chon goi y khac thi tra ve mac dinh dung cac
    // truong do, khong dong vao o nguoi dung tu nhap. (Cung co che voi pplus-search-mobile.js.)
    var mapped = [];
    var CHIP_FIELDS = { bedroom_qty: 'pplus-bedroom-qty', bathroom_qty: 'pplus-bathroom-qty' };
    var INPUT_FIELDS = {
      price_from: '#pplus-price-from', price_to: '#pplus-price-to',
      area_from: '#pplus-area-from', area_to: '#pplus-area-to',
      floor_from: '#pplus-floor-from', floor_to: '#pplus-floor-to'
    };
    function setChip(inputId, value) {
      var $group = $('.list-value-select[data-input="' + inputId + '"]');
      var $items = $group.find('.list-value-item');
      var $match = value === '' ? $items.filter('.value-item-all') : $items.filter(function () { return String($(this).attr('data-value')) === String(value); });
      if (!$match.length) {
        $match = $items.filter('.value-item-all');
        value = '';
      }
      $items.removeClass('action');
      $match.addClass('action');
      $('#' + inputId).val(value);
    }
    function applySuggestionFilter(filter) {
      $.each(mapped, function (_, key) {
        if (CHIP_FIELDS[key]) {
          setChip(CHIP_FIELDS[key], '');
        } else if (INPUT_FIELDS[key]) {
          $(INPUT_FIELDS[key]).val('');
        }
      });
      mapped = [];
      if (!filter || typeof filter !== 'object') {
        syncSummary();
        return;
      }
      $.each(filter, function (key, value) {
        if (CHIP_FIELDS[key]) {
          setChip(CHIP_FIELDS[key], value);
          mapped.push(key);
        } else if (INPUT_FIELDS[key]) {
          $(INPUT_FIELDS[key]).val(value);
          mapped.push(key);
        }
      });
      syncSummary();
    }

    function setApartmentType(value) {
      apartmentType = value ? String(value) : '';
      $('.pplus-apartment-type-check').each(function () {
        this.checked = apartmentType !== '' && this.value === apartmentType;
      });
    }

    // "Xóa bộ lọc": ve het ve mac dinh ("Bất kỳ"/rong/bo tich) - KE CA bo loc ma vung dang chon tu mang theo (nguoi dung muon thay tat ca).
    function resetFilters() {
      mapped = [];
      $.each(CHIP_FIELDS, function (_, id) { setChip(id, ''); });
      $.each(INPUT_FIELDS, function (_, sel) { $(sel).val(''); });
      setApartmentType('');
      $('#pplus-has-bathtub').prop('checked', false);
      syncSummary();
    }
    function hasFilters() {
      var any = false;
      $.each(CHIP_FIELDS, function (_, id) { if ($('#' + id).val() !== '') { any = true; } });
      $.each(INPUT_FIELDS, function (_, sel) { if ($(sel).val() !== '') { any = true; } });
      return any || apartmentType !== '' || $('#pplus-has-bathtub').is(':checked');
    }

    // Tom tat bo loc dang chon hien NGAY TRONG o "Chi tiết" (o thu 2, doc-chi): khong can mo panel moi biet dang loc gi. Cung cach ghep voi chip tren
    // thanh goi y (pplus_search_filter_summary() trong inc/render-helpers.php): loai nha, phong ngu, phong tam, gia, dien tich, tang, bon tam; toi da
    // 3 phan roi "+N". Nhan lay tu chinh panel/PPLUS_SUGGEST_CONFIG.i18n nen dung ngon ngu hien tai.
    function filterSummary() {
      var i18n = (window.PPLUS_SUGGEST_CONFIG && window.PPLUS_SUGGEST_CONFIG.i18n) || {};
      var parts = [];
      function range(from, to, prefix, suffix) {
        var a = parseInt($(from).val(), 10) || 0;
        var b = parseInt($(to).val(), 10) || 0;
        if (a > 0 && b > 0) { return prefix + a + '–' + b + suffix; }
        if (a > 0) { return '≥ ' + prefix + a + suffix; }
        return b > 0 ? '≤ ' + prefix + b + suffix : '';
      }
      if (apartmentType) {
        parts.push($.trim($('.pplus-apartment-type-check[value="' + apartmentType + '"]').closest('label').text()));
      }
      if ($('#pplus-bedroom-qty').val()) { parts.push($('#pplus-bedroom-qty').val() + ' ' + (i18n.bedShort || 'BR')); }
      if ($('#pplus-bathroom-qty').val()) { parts.push($('#pplus-bathroom-qty').val() + ' ' + (i18n.bathShort || 'Bath')); }
      $.each([
        range('#pplus-price-from', '#pplus-price-to', '$', ''),
        range('#pplus-area-from', '#pplus-area-to', '', ' m²'),
        range('#pplus-floor-from', '#pplus-floor-to', (i18n.floorShort || 'Floor') + ' ', '')
      ], function (_, p) { if (p) { parts.push(p); } });
      if ($('#pplus-has-bathtub').is(':checked')) {
        parts.push($.trim($('#pplus-has-bathtub').closest('label').text()));
      }
      return parts.length > 3 ? parts.slice(0, 3).join(' · ') + ' +' + (parts.length - 3) : parts.join(' · ');
    }
    function syncSummary() {
      var text = filterSummary();
      $detailInput.val(text).attr('title', text);
      $detailInput.closest('.search-input-group').toggleClass('has-summary', text !== '');
    }

    // TRANG CHU: thanh tim kiem nam giua hero, popup (luoi goi y ~560px / panel loc ~490px) mo XUONG DUOI thanh nen tren man hinh thap bi cat o day. Bam vao
    // 1 o -> cuon mem de CA thanh tim kiem + popup nam giua khung hinh (popup cao hon khung hinh thi dua thanh tim kiem len gan dinh de thay tu tren xuong).
    // Do chieu cao NOI DUNG popup (khong do khung ".focus-popup": o trang thai an no nam o bottom:5360px va truot xuong trong 0.3s nen vi tri dang
    // do dang trong qua trinh chuyen dong), vi tri cuoi cung luon la ngay duoi thanh (top:100% + margin 5px).
    var centerTimer = null;
    function centerSearchBar() {
      if (isModal || window.innerWidth < 1025) {
        return;
      }
      var bar = $modal.find('.box-search')[0];
      if (!bar) {
        return;
      }
      var popupH = 0;
      try {
        $modal.find('.search-option:focus-within .focus-popup').each(function () {
          var h = 0;
          $(this).children().each(function () { h += this.offsetHeight || 0; });
          popupH = Math.max(popupH, h);
        });
      } catch (err) {
        popupH = 520; // trinh duyet khong co ":focus-within" (rat cu): lay chieu cao thuong gap
      }
      var barRect = bar.getBoundingClientRect();
      var viewH = window.innerHeight;
      var regionTop = barRect.top + window.pageYOffset;
      var regionH = barRect.height + (popupH ? popupH + 10 : 0);
      var target = regionH > viewH - 48 ? regionTop - 100 : regionTop + regionH / 2 - viewH / 2;
      target = Math.max(0, Math.min(target, document.documentElement.scrollHeight - viewH));
      if (Math.abs(target - window.pageYOffset) < 12) {
        return;
      }
      var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      window.scrollTo({ top: target, behavior: reduce ? 'auto' : 'smooth' });
    }
    function scheduleCenter(delay) {
      if (isModal) {
        return;
      }
      window.clearTimeout(centerTimer);
      centerTimer = window.setTimeout(centerSearchBar, delay || 40);
    }

    // Vung dang chon (alias tu goi y / du an / dia diem Google): to dam o "Vùng" + hien nut x; ".has-value" = o co chu (nut x).
    function syncScopeUi() {
      $locationField.toggleClass('has-scope', !!(selectedAlias || selection));
      $locationField.toggleClass('has-value', $.trim($locationInput.val()) !== '');
    }

    // ---- Dien san theo trang dang xem ----
    selectedAlias = ctx.alias || '';
    if (selectedAlias && ctx.label) {
      $locationInput.val(ctx.label);
    }
    if (ctx.selection && ctx.selection.label) {
      selection = ctx.selection;
      $locationInput.val(ctx.selection.label);
    }
    // Bo loc CHINH vung do mang theo (vd Studio = 1 phong ngu, <=49m2) di theo vung (mapped): doi vung thi tu bo. Bo loc nguoi dung da ap
    // (query string) dien sau, khong bi doi vung dong vao.
    applySuggestionFilter(ctx.scopeFilters && !$.isEmptyObject(ctx.scopeFilters) ? ctx.scopeFilters : null);
    $.each(ctx.filters || {}, function (key, value) {
      if (CHIP_FIELDS[key]) {
        setChip(CHIP_FIELDS[key], value);
      } else if (INPUT_FIELDS[key]) {
        $(INPUT_FIELDS[key]).val(value);
      } else if (key === 'apartment_type') {
        setApartmentType(value);
      } else if (key === 'has_bathtub') {
        $('#pplus-has-bathtub').prop('checked', true);
      }
    });
    syncScopeUi();
    syncSummary();

    // Da co vung/bo loc thi mo popup la sang thang o "Chi tiết" (focus o do tu mo panel qua ":focus-within" CSS, dong luoi goi y cua o Vùng);
    // chua co gi (trang chu...) thi giu hanh vi cu: khong focus o nao, bam vao o Vùng moi hien luoi goi y.
    if (isModal) {
      $modal[0].addEventListener('shown.bs.modal', function () {
        if (selectedAlias || selection || hasFilters()) {
          $detailInput.trigger('focus');
        }
      });
    }

    // Bam 1 goi y: dien ten vao o Location, chuyen focus sang o "Chi tiết" - viec doi focus nay TU
    // KICH HOAT ":focus-within" CSS co san (header.less): dong luoi goi y cua Location (khong con
    // ":focus-within" tren ".search-option" cua no), mo panel "Chi tiết" (".long-term-s3") cua o
    // ben canh - dung y het DataCoreroupToBack() that (dong 574-727), khong can JS rieng nao khac
    // cho phan hien/an popup.
    //
    // SUA 22/9 (nguoi dung phat hien: thi thoang luoi goi y KHONG dong khi bam chon, 2 popup chong
    // nhau): chi ":focus-within" thoi CHUA DU - CSS con co ".search-option .focus-popup:hover" (giu
    // popup mo khi RE CHUOT ben trong, vd de cuon danh sach), va chuot THUONG VAN DANG NAM tren luoi
    // ngay luc bam chon (chua kip di chuyen ra ngoai) nen ":hover" tiep tuc giu no mo de ca sau khi
    // focus da chuyen di. Them class "pplus-force-hide" (CSS o header.less, "!important" de thang
    // ca 2 rule) ngay khi bam chon, bat ke chuot dang o dau; go class nay khi nguoi dung bam lai vao
    // chinh o Location (coi nhu "mo lai tu dau"), de lan mo sau van hoat dong binh thuong qua native
    // ":focus-within" va ":hover".
    $suggestList.on('click keydown', '.item-suggest', function (e) {
      if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') {
        return;
      }
      e.preventDefault();
      var $item = $(this);
      selection = null;
      selectedAlias = $item.data('alias') || '';
      $locationInput.val($item.data('label') || '');
      applySuggestionFilter($item.data('filter') || null);
      syncScopeUi();
      $suggestList.addClass('pplus-force-hide');
      $detailInput.trigger('focus');
    });

    $locationInput.on('focus', function () {
      $suggestList.removeClass('pplus-force-hide');
      // Dang co vung: boi den san de go la thay the (va hien lai luoi goi y), khong phai xoa tung chu.
      if ($locationField.hasClass('has-scope')) {
        var el = this;
        window.setTimeout(function () {
          try { el.select(); } catch (err) { /* bo qua */ }
        }, 0);
      }
    });
    $locationInput.on('input', syncScopeUi);
    // Trang chu: bam/focus vao 1 o (kể ca bam lai o dang focus) -> dua thanh tim kiem + popup ra giua khung hinh.
    $modal.on('focusin click', '.search-input', function () {
      scheduleCenter(40);
    });

    // Nut x trong o "Vùng": bo vung dang chon (ve "Tất cả Hà Nội"), de trong o va mo luoi goi y de chon vung khac.
    $modal.on('click', '.pplus-scope-clear', function (e) {
      e.preventDefault();
      selection = null;
      selectedAlias = '';
      applySuggestionFilter(null);
      $locationInput.val('').trigger('input').trigger('focus');
      syncScopeUi();
    });

    $modal.on('click', '.pplus-reset-filters', function (e) {
      e.preventDefault();
      resetFilters();
    });

    // Goi y khi go (du an / dia diem Google) - xem pplus-search-suggest.js. Chon xong: dong popup, sang o
    // "Chi tiết" (giong chon 1 muc trong luoi goi y o tren).
    if (window.PplusSearchSuggest) {
      window.PplusSearchSuggest.attach({
        input: $locationInput,
        box: $suggestList,
        grid: $suggestList.find('.pplus-show-district'),
        variant: 'desktop',
        // Ket qua goi y khi go (du an/Google) lam popup cao/thap di -> can lai giua khung hinh (trang chu).
        onToggle: function (visible) {
          if (visible) {
            scheduleCenter(120);
          }
        },
        onType: function () {
          selection = null;
          selectedAlias = '';
          applySuggestionFilter(null);
          syncScopeUi();
        },
        onPick: function (sel) {
          selection = sel;
          selectedAlias = '';
          applySuggestionFilter(null);
          syncScopeUi();
          $suggestList.addClass('pplus-force-hide');
          $detailInput.trigger('focus');
        }
      });
      var q = window.PplusSearchSuggest.currentQuery();
      if (q && !$.trim($locationInput.val())) {
        $locationInput.val(q);
        syncScopeUi();
      }
    }

    $modal.on('change', '.pplus-apartment-type-check', function () {
      apartmentType = this.checked ? this.value : '';
    });
    // Moi thay doi trong panel loc (bam o so phong, go gia/dien tich/tang, tich o) -> cap nhat tom tat o "Chi tiết". setTimeout 0 de chay SAU cac handler
    // tren (ghi gia tri vao input an, loc ky tu khong phai so, cap nhat apartmentType).
    $modal.on('input change click', '.focus-popup', function () {
      window.setTimeout(syncSummary, 0);
    });

    // Pill chon so phong ngu/tam ("Phòng ngủ"/"Phòng tắm", listValue=[1..8] - search-base.component.ts
    // dong 63) - click 1 pill thi cac pill CON LAI trong CUNG nhom mat ".action", ghi gia tri vao
    // input an tuong ung (".list-value-select" tu "data-input" tro toi).
    $modal.on('click', '.list-value-select .list-value-item', function () {
      var $group = $(this).closest('.list-value-select');
      $group.find('.list-value-item').removeClass('action');
      $(this).addClass('action');
      $('#' + $group.data('input')).val($(this).data('value'));
    });

    // Chi cho go so trong o gia (khop "onInput()" that - chi giu ky tu so).
    $modal.on('input', '.ranger-input', function () {
      this.value = this.value.replace(/[^0-9]/g, '');
    });

    // Nut tim kiem tron xanh (".btn-search") - port "clickSubmitSearch()" nhanh don gian (trang
    // nay luon full-page-reload, khong co nhanh AJAX "isMapshow" cua ban Angular): gop TOAN BO
    // truong loc da dien thanh query string that, dieu huong toi URL /cho-thue-can-ho/ha-noi/
    // {alias da chon}/ (hoac trang goc "/ha-noi/" neu chua chon vung nao) - page-reference.php doc
    // lai cac tham so nay va gop vao $filter truoc khi goi Apartment/_GetApartments (xac nhan qua
    // goi API That: ca 10 truong duoi day DA CO SAN trong "filter" tra ve tu Reference/GetIndex,
    // khong phai bia them). Vung mac dinh = vung cua trang dang xem (selectedAlias khoi tao tu data-context).
    // Trang chu: Enter trong 1 o / nut kinh lup (type=submit) cung di qua day thay vi gui form GET cu (search_key/detail khong loc duoc gi).
    $modal.on('submit', 'form', function (e) {
      e.preventDefault();
      $modal.find('.btn-search').first().trigger('click');
    });

    $modal.on('click', '.btn-search', function (e) {
      e.preventDefault();
      var filters = {
        price_from: $('#pplus-price-from').val(),
        price_to: $('#pplus-price-to').val(),
        bedroom_qty: $('#pplus-bedroom-qty').val(),
        bathroom_qty: $('#pplus-bathroom-qty').val(),
        area_from: $('#pplus-area-from').val(),
        area_to: $('#pplus-area-to').val(),
        floor_from: $('#pplus-floor-from').val(),
        floor_to: $('#pplus-floor-to').val(),
        has_bathtub: $('#pplus-has-bathtub').is(':checked') ? '1' : '',
        apartment_type: apartmentType
      };

      // Chon du an/dia diem Google: ban goc chuyen toi trang danh sach tong (clickSubmitSearch, isMapshow=false).
      if (selection && window.PplusSearchSuggest) {
        window.location.href = window.PplusSearchSuggest.listUrl(selection, filters);
        return;
      }

      var params = [];
      $.each(filters, function (name, value) {
        if (value !== '' && value !== null && typeof value !== 'undefined') {
          params.push(encodeURIComponent(name) + '=' + encodeURIComponent(value));
        }
      });

      // 24/10/2026: URL chuan khong co \"/\" cuoi (khop web chinh, xem inc/url-canonical.php).
      var baseUrl = String($modal.data('base-url')).replace(/\/+$/, '') + (selectedAlias ? '/' + selectedAlias : '');
      window.location.href = baseUrl + (params.length ? '?' + params.join('&') : '');
    });
  });
})(jQuery);
