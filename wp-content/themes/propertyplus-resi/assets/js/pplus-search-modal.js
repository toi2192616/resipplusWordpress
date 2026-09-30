// #searchModal - logic tim kiem (parts/search-modal.php). Port tu search-base.component.ts:
// DataCoreroupToBack() (dong 574-727), ChangeApartmentType() (dong 937-948), clickSubmitSearch()
// (dong 265-271, nhanh "isMapshow===false" - trang WP nay luon full-page-reload, khong co nhanh
// AJAX "isMapshow===true" cua ban Angular).
(function ($) {
  'use strict';

  $(function () {
    var $modal = $('#searchModal');
    if (!$modal.length) {
      return;
    }

    var $suggestList = $('#pplus-search-suggest-list');
    var $locationInput = $('#search-modal-input1');
    var $detailInput = $('#search-modal-input2');
    var selectedAlias = '';

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
      $suggestList.addClass('pplus-force-hide');
      $detailInput.trigger('focus');
    });

    $locationInput.on('focus', function () {
      $suggestList.removeClass('pplus-force-hide');
    });

    // Goi y khi go (du an / dia diem Google) - xem pplus-search-suggest.js. Chon xong: dong popup, sang o
    // "Chi tiết" (giong chon 1 muc trong luoi goi y o tren).
    var selection = null;
    if (window.PplusSearchSuggest) {
      window.PplusSearchSuggest.attach({
        input: $locationInput,
        box: $suggestList,
        grid: $suggestList.find('.pplus-show-district'),
        variant: 'desktop',
        onType: function () {
          selection = null;
          selectedAlias = '';
        },
        onPick: function (sel) {
          selection = sel;
          selectedAlias = '';
          $suggestList.addClass('pplus-force-hide');
          $detailInput.trigger('focus');
        }
      });
      var q = window.PplusSearchSuggest.currentQuery();
      if (q) {
        $locationInput.val(q);
      }
    }

    // "Loại nhà/phòng" - 2 checkbox CUNG ten nhung KHONG loai tru nhau (khong phai radio, dung y
    // het ban goc) - ChangeApartmentType() that: checked -> ghi de gia tri, unchecked -> xoa ve
    // rong BAT KE checkbox con lai the nao (gia tri CUOI CUNG thang doi, khong phai "gop" ca 2).
    var apartmentType = '';
    $modal.on('change', '.pplus-apartment-type-check', function () {
      apartmentType = this.checked ? this.value : '';
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
    // {alias da chon}/ (hoac trang goc "/ha-noi/" neu chua bam goi y nao) - page-reference.php doc
    // lai cac tham so nay va gop vao $filter truoc khi goi Apartment/_GetApartments (xac nhan qua
    // goi API That: ca 10 truong duoi day DA CO SAN trong "filter" tra ve tu Reference/GetIndex,
    // khong phai bia them).
    $modal.on('click', '.btn-search', function () {
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

      var baseUrl = $modal.data('base-url') + (selectedAlias ? selectedAlias + '/' : '');
      window.location.href = baseUrl + (params.length ? '?' + params.join('&') : '');
    });
  });
})(jQuery);
