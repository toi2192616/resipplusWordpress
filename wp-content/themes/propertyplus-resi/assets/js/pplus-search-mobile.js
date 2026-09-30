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
    function writeStore(label, alias) {
      try {
        window.sessionStorage.setItem(STORE_KEY, JSON.stringify({ label: label, alias: alias }));
      } catch (e) { /* private mode: bo qua */ }
    }
    var saved = readStore();
    if (saved && saved.alias && window.location.pathname.indexOf('/' + saved.alias) > -1) {
      $toolInput.val(saved.label);
      $filter.val(saved.label);
      selectedAlias = saved.alias;
    }

    function syncFilterButtons() {
      var hasText = $.trim($filter.val()) !== '';
      $btnSearch.toggleClass('hidden', !hasText);
      $btnDelete.toggleClass('hidden', !hasText);
    }
    syncFilterButtons();

    function applyStep(n) {
      $panel.attr('step', n);
      $panel.find('.m-step').toggle(n === 1);
      $panel.find('.m-step-content').toggle(n === 2);
      $panel.find('.btnNextStep').toggle(n !== 2);
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
      var v = $(this).attr('data-pplus-search-mobile');
      if (v === '1' || v === '2') {
        showSearchBase(parseInt(v, 10));
      } else {
        // Nut "Tim kiem" o menu chan trang: header.showSearchBase() CHI toggle class, giu nguyen buoc.
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

    // Lua chon tu goi y khi go (du an / dia diem Google - pplus-search-suggest.js); null = khong chon gi.
    var selection = null;
    if (window.PplusSearchSuggest) {
      window.PplusSearchSuggest.attach({
        input: $filter,
        box: $panel.find('.m-step'),
        grid: $panel.find('.pplus-show-district'),
        variant: 'mobile',
        onType: function () {
          selection = null;
          selectedAlias = '';
        },
        onPick: function (sel) {
          selection = sel;
          selectedAlias = '';
          $toolInput.val(sel.label);
          syncFilterButtons();
          applyStep(currentStep() + 1);
        }
      });
      var q = window.PplusSearchSuggest.currentQuery();
      if (q) {
        $toolInput.val(q);
        $filter.val(q);
      }
    }

    $filter.on('input keyup', syncFilterButtons);
    $btnDelete.on('click', function () {
      $filter.val('').trigger('input').trigger('focus');
      selectedAlias = '';
      syncFilterButtons();
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
      $filter.val(label);
      $toolInput.val(label);
      writeStore(label, selectedAlias);
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
      var baseUrl = $panel.data('base-url') + (selectedAlias ? selectedAlias + '/' : '');
      window.location.href = baseUrl + (params.length ? '?' + params.join('&') : '');
    });
  });
})(jQuery);
