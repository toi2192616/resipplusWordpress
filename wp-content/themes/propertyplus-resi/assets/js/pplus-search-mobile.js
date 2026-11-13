// Panel tim kiem MOBILE (#search-tab-mobile, parts/search-mobile.php) + thanh ".searching-tool".
// Port tu search-base.component.ts: showSearchBase() (dong 886-894), prevSteppMobile() (924-930),
// DataCoreroupToBack() nhanh mobile (574-727, chi type 2 = quan/huyen), clickSubmitSearch()
// (265-285, o WP: dieu huong toi URL loc - giong pplus-search-modal.js), ChangeApartmentType()
// (937-948) + teamplate-header.component.ts showSearchBase() (dong 121-127, CHI toggle class).
(function ($) {
  'use strict';

  $(function () {
    var $panel = $('#search-tab-mobile');
    if (!$panel.length) {
      return;
    }

    var STORE_KEY = 'pplusSearchFilter';
    var $toolInput = $('.searching-tool .txt-search');
    var $filter = $('#pplus-m-filter');
    var $btnSearch = $panel.find('.btn-search-notext');
    var $btnDelete = $panel.find('.btn-delete-text');
    var selectedAlias = '';
    var apartmentType = '';
    // Lua chon tu goi y khi go (du an / dia diem Google - pplus-search-suggest.js); null = khong chon gi.
    var selection = null;

    // 11/11/2026: vung + bo loc cua trang dang xem (xem pplus_search_context() trong inc/render-helpers.php) - giong popup desktop
    // (pplus-search-modal.js): mo panel la co san vung dang xem; bam o tim kiem tren thanh di thang sang buoc loc (buoc 2), khong bat chon lai goi y.
    var ctx = {};
    try {
      ctx = JSON.parse($panel.attr('data-context') || '{}') || {};
    } catch (e) {
      ctx = {};
    }

    // Ban Angular giu "searchBase.filter" trong bo nho SPA xuyen suot cac lan chuyen trang; WP la
    // full-page-reload nen luu vao sessionStorage - chi hien lai neu URL hien tai VAN LA trang cua
    // goi y da chon (tranh hien chu cu tren trang khong lien quan).
    function readStore() {
      try {
        return JSON.parse(window.sessionStorage.getItem(STORE_KEY) || 'null');
      } catch (e) {
        return null;
      }
    }
    function writeStore(label, alias, filter) {
      try {
        window.sessionStorage.setItem(STORE_KEY, JSON.stringify({ label: label, alias: alias, filter: filter || null }));
      } catch (e) { /* private mode: bo qua */ }
    }
    // Goi y -> man "tuy chon": cac truong loc ma goi y mang theo (data-filter, xem pplus_suggestion_option_filter()) duoc dien san vao man tuy chon
    // (so phong ngu/tam to mau nut tuong ung, dien tich, gia, tang). mapped = cac truong DO GOI Y dat - chon goi y khac thi tra ve mac dinh dung
    // cac truong do (khong dong vao cac o nguoi dung tu nhap).
    var mapped = [];
    var CHIP_FIELDS = { bedroom_qty: 'pplus-m-bedroom-qty', bathroom_qty: 'pplus-m-bathroom-qty' };
    var INPUT_FIELDS = {
      price_from: '#pplus-m-price-from', price_to: '#pplus-m-price-to',
      area_from: '#pplus-m-area-from', area_to: '#pplus-m-area-to',
      floor_from: '#pplus-m-floor-from', floor_to: '#pplus-m-floor-to'
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
      var scroller = $group.closest('.rs-mobile-list-value')[0];
      if (scroller && $match[0]) {
        window.setTimeout(function () { scroller.scrollLeft = Math.max(0, $match[0].offsetLeft - 24); }, 60);
      }
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
    }

    function setApartmentType(value) {
      apartmentType = value ? String(value) : '';
      $('.pplus-m-apartment-type').each(function () {
        this.checked = apartmentType !== '' && this.value === apartmentType;
      });
    }

    // "Xóa bộ lọc": ve het ve mac dinh ("Bất kỳ"/rong/bo tich) - KE CA bo loc ma vung dang chon tu mang theo.
    function resetFilters() {
      mapped = [];
      $.each(CHIP_FIELDS, function (_, id) { setChip(id, ''); });
      $.each(INPUT_FIELDS, function (_, sel) { $(sel).val(''); });
      setApartmentType('');
      $('#pplus-m-has-bathtub').prop('checked', false);
    }

    // Vung/bo loc cua trang dang xem -> dien san (uu tien hon sessionStorage ben duoi, vi ctx dung ca khi vao trang bang menu/link).
    if (ctx.alias && ctx.label) {
      $toolInput.val(ctx.label);
      $filter.val(ctx.label);
      selectedAlias = ctx.alias;
    }
    if (ctx.selection && ctx.selection.label) {
      selection = ctx.selection;
      $toolInput.val(ctx.selection.label);
      $filter.val(ctx.selection.label);
    }
    // Bo loc CHINH vung do mang theo (vd Studio = 1 phong ngu, <=49m2) di theo vung (mapped): doi vung thi tu bo; bo loc nguoi dung da ap
    // (query string) dien sau, khong bi doi vung dong vao.
    if (ctx.alias || ctx.selection || !$.isEmptyObject(ctx.filters || {})) {
      applySuggestionFilter(ctx.scopeFilters && !$.isEmptyObject(ctx.scopeFilters) ? ctx.scopeFilters : null);
      $.each(ctx.filters || {}, function (key, value) {
        if (CHIP_FIELDS[key]) {
          setChip(CHIP_FIELDS[key], value);
        } else if (INPUT_FIELDS[key]) {
          $(INPUT_FIELDS[key]).val(value);
        } else if (key === 'apartment_type') {
          setApartmentType(value);
        } else if (key === 'has_bathtub') {
          $('#pplus-m-has-bathtub').prop('checked', true);
        }
      });
    }

    var saved = readStore();
    if (!selectedAlias && !selection && saved && saved.alias && window.location.pathname.indexOf('/' + saved.alias) > -1) {
      $toolInput.val(saved.label);
      $filter.val(saved.label);
      selectedAlias = saved.alias;
      applySuggestionFilter(saved.filter);
    }

    function hasScope() {
      return !!(selectedAlias || selection);
    }

    // Dong "Vùng: ... [Đổi]" o dau buoc loc: chi hien khi co vung da chon.
    function syncScopeRow() {
      var $row = $panel.find('.pplus-m-scope');
      var label = $.trim($filter.val());
      $row.toggle(hasScope() && label !== '');
      $row.find('.pplus-m-scope-name').text(label);
    }

    function syncFilterButtons() {
      var hasText = $.trim($filter.val()) !== '';
      $btnSearch.toggleClass('hidden', !hasText);
      $btnDelete.toggleClass('hidden', !hasText);
      syncScopeRow();
    }
    syncFilterButtons();

    function applyStep(n) {
      $panel.attr('step', n);
      $panel.find('.m-step').toggle(n === 1);
      $panel.find('.m-step-content').toggle(n === 2);
      $panel.find('.btnNextStep').toggle(n !== 2);
      syncScopeRow();
    }
    function currentStep() {
      return parseInt($panel.attr('step'), 10) || 1;
    }
    applyStep(1);

    // showSearchBase(steptype=1): toggle class "showSearch" roi dat buoc = steptype.
    function showSearchBase(steptype) {
      $panel.toggleClass('showSearch');
      applyStep(steptype || 1);
    }

    $(document).on('click', '[data-pplus-search-mobile]', function (e) {
      e.preventDefault();
      // Icon luoi goi y chi nap khi mo panel lan dau (xem PplusSearchSuggest.loadIcons).
      if (window.PplusSearchSuggest) {
        window.PplusSearchSuggest.loadIcons($panel[0]);
      }
      var v = $(this).attr('data-pplus-search-mobile');
      // Da co vung (dang o 1 quan/loai/khu vuc...): bam o tim kiem tren thanh = di thang sang buoc loc; doi vung bang nut "Đổi" o dau buoc do.
      // Chi ap dung khi MO panel (dang dong); dang mo thi giu hanh vi cu.
      if (v === '1' && hasScope() && !$panel.hasClass('showSearch')) {
        v = '2';
      }
      if (v === '1' || v === '2') {
        showSearchBase(parseInt(v, 10));
      } else {
        // Nut "Tim kiem" o menu chan trang (loi vao chinh tren trang danh sach - thanh ".searching-tool" tren cung chi hien o che do ban do):
        // header.showSearchBase() CHI toggle class, giu nguyen buoc. 11/11/2026: dang o 1 vung thi mo thang buoc loc (giong o tim kiem tren thanh).
        if (hasScope() && !$panel.hasClass('showSearch')) {
          applyStep(2);
        }
        $panel.toggleClass('showSearch');
      }
    });

    $panel.on('click', '.btnBackStep', function () {
      var s = currentStep();
      if (s > 1) {
        applyStep(s - 1);
      } else {
        showSearchBase();
      }
    });
    $panel.on('click', '.btnNextStep', function () {
      applyStep(currentStep() + 1);
    });

    if (window.PplusSearchSuggest) {
      window.PplusSearchSuggest.attach({
        input: $filter,
        box: $panel.find('.m-step'),
        grid: $panel.find('.pplus-show-district'),
        variant: 'mobile',
        onType: function () {
          selection = null;
          selectedAlias = '';
          applySuggestionFilter(null);
        },
        onPick: function (sel) {
          selection = sel;
          selectedAlias = '';
          applySuggestionFilter(null);
          $toolInput.val(sel.label);
          syncFilterButtons();
          applyStep(currentStep() + 1);
        }
      });
      var q = window.PplusSearchSuggest.currentQuery();
      if (q && !$.trim($filter.val())) {
        $toolInput.val(q);
        $filter.val(q);
      }
    }

    $filter.on('input keyup', syncFilterButtons);
    $btnDelete.on('click', function () {
      $filter.val('').trigger('input').trigger('focus');
      selectedAlias = '';
      selection = null;
      applySuggestionFilter(null);
      syncFilterButtons();
    });

    // Buoc loc: "Đổi" -> quay lai buoc 1 de chon vung khac; "Xóa bộ lọc" -> ve mac dinh.
    $panel.on('click', '.pplus-m-scope-change', function () {
      applyStep(1);
    });
    $panel.on('click', '.pplus-m-reset', function () {
      resetFilters();
    });

    // DataCoreroupToBack(item, 2): dien ten vao o, roi sang buoc tiep theo (buoc 2).
    $panel.on('click keydown', '.item-suggest', function (e) {
      if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') {
        return;
      }
      e.preventDefault();
      var $item = $(this);
      selection = null;
      selectedAlias = $item.data('alias') || '';
      var label = $item.data('label') || '';
      var optionFilter = $item.data('filter') || null;
      $filter.val(label);
      $toolInput.val(label);
      applySuggestionFilter(optionFilter);
      writeStore(label, selectedAlias, optionFilter);
      syncFilterButtons();
      applyStep(currentStep() + 1);
    });

    // ChangeApartmentType(): checked -> ghi de gia tri, unchecked -> rong (2 checkbox khong loai tru nhau).
    $panel.on('change', '.pplus-m-apartment-type', function () {
      apartmentType = this.checked ? this.value : '';
    });

    $panel.on('click', '.list-value-select .list-value-item', function () {
      var $group = $(this).closest('.list-value-select');
      $group.find('.list-value-item').removeClass('action');
      $(this).addClass('action');
      $('#' + $group.data('input')).val($(this).data('value'));
    });

    $panel.on('input', '.ranger-input', function () {
      this.value = this.value.replace(/[^0-9]/g, '');
    });

    // "Xac nhan" (clickSubmitSearch): gop cac truong loc thanh query string, dieu huong toi trang loc.
    $panel.on('click', '.btnSearchMenu', function () {
      var filters = {
        price_from: $('#pplus-m-price-from').val(),
        price_to: $('#pplus-m-price-to').val(),
        bedroom_qty: $('#pplus-m-bedroom-qty').val(),
        bathroom_qty: $('#pplus-m-bathroom-qty').val(),
        area_from: $('#pplus-m-area-from').val(),
        area_to: $('#pplus-m-area-to').val(),
        floor_from: $('#pplus-m-floor-from').val(),
        floor_to: $('#pplus-m-floor-to').val(),
        has_bathtub: $('#pplus-m-has-bathtub').is(':checked') ? '1' : '',
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
      var baseUrl = String($panel.data('base-url')).replace(/\/+$/, '') + (selectedAlias ? '/' + selectedAlias : '');
      window.location.href = baseUrl + (params.length ? '?' + params.join('&') : '');
    });
  });
})(jQuery);
