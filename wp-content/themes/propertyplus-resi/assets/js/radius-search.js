// "Tim theo ban kinh" (nut canh "So sanh", page-office-list.php) - port tu cac ham lien quan trong
// search-base.component.ts: actionCustomMap()/ClickActiveRoaMingMap()/keyupFilterSearchNoBase()/
// RoamMingMapNoSearch()/CreateMackerRoaMingMap()/loadDataWithDistance()/MapFilter()/ClearRoaMingMap().
//
// CHI port che do "Tim theo ban kinh" (typeRoaming==1, ve hinh tron) - che do "Khoanh vung tim
// kiem" (polygon, typeRoaming==2) la CODE CHET tren CHINH production that: <select> chuyen doi
// typeRoaming bi comment het trong search-base.component.html (dong 644-654), nen typeRoaming
// LUON = 1, khong bao gio doi sang 2 duoc trong thuc te - khong can port.
//
// Khac Angular (SPA, loadData() goi lai AJAX + Angular tu render lai ket qua NGAY tren trang):
// WP la site nhieu trang, nut "Tim kiem" trong panel nay DIEU HUONG lai chinh trang voi
// ne_lat/ne_lng/sw_lat/sw_lng (tu hinh vuong noi tiep hinh tron, dung CHINH XAC thuat toan
// MapFilter() ben Angular) + price_from/price_to moi qua query string - xem doan doc lai
// $_GET['ne_lat'/'ne_lng'/'sw_lat'/'sw_lng'] trong page-office-list.php (pplus_get_office_list()
// da san sang nhan 4 tham so nay tu truoc, chi thieu phan UI/JS nay).
(function ($) {
  'use strict';

  var $panel = $('#pplus-roam-panel');
  var $toggleBtn = $('#pplus-roam-toggle');
  var $handBtn = $('#pplus-roam-hand');
  var $handIcon = $('#pplus-roam-hand-icon');
  if (!$panel.length || !$toggleBtn.length) {
    return;
  }

  var roamingClick = false; // che do bam-chon-tam tren ban do dang bat/tat (ClickActiveRoaMingMap)
  var centerMarker = null;
  var circleOverlay = null;
  var placesService = null;
  var geocoder = null;
  var suggestTimer = null;

  function getMap() {
    return window.pplusOfficeMap || null;
  }

  // Ban do duoc pplus-maps.js tao BAT DONG BO (cho Google Maps API tai xong) - co the chua san
  // sang luc file nay chay. Cho qua su kien "pplus:map-ready" (xem assets/js/pplus-maps.js) neu
  // vay, goi lai ngay neu da co san (khong phu thuoc thu tu 2 file <script>).
  function withMap(cb) {
    var map = getMap();
    if (map) {
      cb(map);
      return;
    }
    document.addEventListener('pplus:map-ready', function onReady(e) {
      document.removeEventListener('pplus:map-ready', onReady);
      cb(e.detail.map);
    });
  }

  function currentDistanceKm() {
    var val = parseFloat($('#pplus-roam-distance').val());
    return (isFinite(val) && val > 0) ? val : 2;
  }

  // Ve marker "Tam vung" + hinh tron ban kinh hien tai - port CreateMackerRoaMingMap()/
  // loadDataWithDistance() (phan ve circleMap).
  function drawCircle(map, latLng) {
    if (centerMarker) {
      centerMarker.setMap(null);
    }
    centerMarker = new google.maps.Marker({ position: latLng, map: map, title: 'Tâm vùng' });
    if (circleOverlay) {
      circleOverlay.setMap(null);
    }
    circleOverlay = new google.maps.Circle({
      map: map,
      center: latLng,
      radius: currentDistanceKm() * 1000,
      fillColor: '#4285F4',
      fillOpacity: 0.2,
      strokeColor: '#4285F4',
      strokeWeight: 2
    });
    map.panTo(latLng);
  }

  function redrawCircleRadius() {
    if (!circleOverlay) {
      return;
    }
    circleOverlay.setRadius(currentDistanceKm() * 1000);
  }

  // === Mo/dong panel - port actionCustomMap() ===
  function openPanel() {
    $panel.show();
    $handBtn.show();
  }

  function closePanel() {
    $panel.hide();
    $handBtn.hide();
    setRoamingClick(false);
  }

  $toggleBtn.on('click', function () {
    if ($panel.is(':visible')) {
      closePanel();
    } else {
      openPanel();
    }
  });

  // === Nut "ban tay" - bat/tat che do bam chon diem tren ban do - port ClickActiveRoaMingMap() ===
  function setRoamingClick(active) {
    roamingClick = active;
    $handIcon.toggleClass('active', active);
  }
  $handBtn.on('click', function () {
    setRoamingClick(!roamingClick);
  });

  // === Bam vao ban do de chon tam (CHI khi nut "ban tay" dang bat) - port doan click listener
  // trong ngAfterViewInit() (dong 502-516, nhanh typeRoaming==1). ===
  withMap(function (map) {
    map.addListener('click', function (e) {
      if (!roamingClick) {
        return;
      }
      drawCircle(map, e.latLng);
    });
  });

  // === Goi y dia chi (Places AutocompleteService, gioi han Viet Nam) - port
  // keyupFilterSearchNoBase(), debounce 300ms (Angular goc goi ngay moi phim, o day debounce them
  // cho do tai API). ===
  $('#pplus-roam-address').on('input', function () {
    var text = $(this).val();
    clearTimeout(suggestTimer);
    if (!text) {
      $('#pplus-roam-suggestions').empty();
      return;
    }
    suggestTimer = setTimeout(function () {
      if (!window.google || !google.maps.places) {
        return;
      }
      if (!placesService) {
        placesService = new google.maps.places.AutocompleteService();
      }
      placesService.getPlacePredictions({
        input: text,
        componentRestrictions: { country: ['VN'] }
      }, function (predictions, status) {
        var $box = $('#pplus-roam-suggestions');
        $box.empty();
        if (status !== google.maps.places.PlacesServiceStatus.OK || !predictions) {
          return;
        }
        predictions.slice(0, 5).forEach(function (item) {
          var $row = $('<div class="item-content-data"></div>').text(item.description);
          $row.on('click', function () {
            selectPlace(item);
          });
          $box.append($row);
        });
      });
    }, 300);
  });

  // Chon 1 goi y dia chi -> geocode ra toa do, canh giua ban do + ve lai hinh tron tai do - port
  // RoamMingMapNoSearch().
  function selectPlace(item) {
    if (!geocoder) {
      geocoder = new google.maps.Geocoder();
    }
    geocoder.geocode({ placeId: item.place_id }, function (results, status) {
      if (status !== 'OK' || !results || !results[0] || !results[0].geometry) {
        return;
      }
      var location = results[0].geometry.location;
      $('#pplus-roam-address').val(results[0].formatted_address || item.description);
      $('#pplus-roam-suggestions').empty();
      withMap(function (map) {
        drawCircle(map, location);
      });
    });
  }

  // === Chon nhanh khoang cach (danh sach 1/2/5/10/20km) + go tay - ve lai ban kinh hinh tron neu
  // da co tam san. ===
  $(document).on('click', '.distance-options li', function () {
    $('#pplus-roam-distance').val($(this).data('value'));
    redrawCircleRadius();
  });
  $('#pplus-roam-distance').on('input', function () {
    redrawCircleRadius();
  });

  // === Tinh bounds (hinh vuong noi tiep hinh tron, khong bao gio chua diem ngoai hinh tron) - port
  // CHINH XAC MapFilter() (dong 606-624 search-base.component.ts). ===
  function computeBoundsFromCircle() {
    if (!circleOverlay) {
      return null;
    }
    var center = circleOverlay.getCenter();
    var radius = circleOverlay.getRadius(); // met
    var innerRadius = radius / Math.SQRT2;
    var metersPerDegLat = 111320;
    var deltaLat = innerRadius / metersPerDegLat;
    var latRad = (center.lat() * Math.PI) / 180;
    var metersPerDegLng = metersPerDegLat * Math.cos(latRad);
    var deltaLng = innerRadius / (metersPerDegLng || metersPerDegLat);
    return {
      ne_lat: center.lat() + deltaLat,
      ne_lng: center.lng() + deltaLng,
      sw_lat: center.lat() - deltaLat,
      sw_lng: center.lng() - deltaLng
    };
  }

  // === Nut "Tim kiem" - dieu huong lai CHINH trang nay voi bounds + gia moi (thay AJAX loadData()
  // ben Angular, xem ghi chu dau file) - port loadDataWithDistance(true). ===
  $('#pplus-roam-search').on('click', function () {
    var map = getMap();
    if (!circleOverlay) {
      // Chua chon diem nao (chua bam ban do/chua chon dia chi) - lay tam ban do HIEN TAI lam tam
      // vung, giong dung fallback cua Angular goc (loadDataWithDistance(): "if (markerMap == null)
      // { centerMap = map.getCenter(); ... }").
      if (!map) {
        return;
      }
      drawCircle(map, map.getCenter());
    }
    var bounds = computeBoundsFromCircle();
    if (!bounds) {
      return;
    }

    // Luon dieu huong ve URL TONG QUAT (khong gan quan/huyen cu the), CUNG 1 nguyen tac voi
    // "#box-search-base" trong main.js: doi vung dia ly thi phai bo dieu kien quan/huyen cu, neu
    // khong ket qua se bi giao voi vung cu -> RONG (bug thuc te da bao cao: chon diem o quan khac
    // van bi giu dieu kien quan dang xem). Chi giu lai cac dieu kien loc KHONG PHAI dia ly
    // (project_type/office_type/rating_level, gia) - doc tu data-* tren chinh panel nay.
    var baseUrl = window.PPLUS_OFFICE_SEARCH_URL || (window.location.origin + '/cho-thue-van-phong/');
    var url = new URL(baseUrl, window.location.origin);

    url.searchParams.set('ne_lat', bounds.ne_lat);
    url.searchParams.set('ne_lng', bounds.ne_lng);
    url.searchParams.set('sw_lat', bounds.sw_lat);
    url.searchParams.set('sw_lng', bounds.sw_lng);

    var priceFrom = $('#pplus-roam-price-from').val();
    var priceTo = $('#pplus-roam-price-to').val();
    if (priceFrom !== '') {
      url.searchParams.set('price_from', priceFrom);
    }
    if (priceTo !== '') {
      url.searchParams.set('price_to', priceTo);
    }

    var projectType = $panel.data('project-type');
    var officeType = $panel.data('office-type');
    var ratingLevel = $panel.data('rating-level');
    if (projectType !== undefined && projectType !== '') {
      url.searchParams.set('project_type', projectType);
    }
    if (officeType !== undefined && officeType !== '') {
      url.searchParams.set('office_type', officeType);
    }
    if (ratingLevel !== undefined && ratingLevel !== '') {
      url.searchParams.set('rating_level', ratingLevel);
    }

    url.searchParams.set('paged', '1');
    window.location.href = url.toString();
  });

  // === Nut "Huy" - xoa marker/hinh tron + dong panel - port ClearRoaMingMap(). ===
  $('#pplus-roam-cancel').on('click', function () {
    if (centerMarker) {
      centerMarker.setMap(null);
      centerMarker = null;
    }
    if (circleOverlay) {
      circleOverlay.setMap(null);
      circleOverlay = null;
    }
    closePanel();
  });

  // === Trang dang o KET QUA cua 1 lan "Tim theo ban kinh" truoc do (URL da co ne_lat/sw_lat...) -
  // tu mo san panel + ve lai hinh tron tai dung vi tri cu, de nguoi dung thay ro dang xem ket qua
  // loc theo vung nao thay vi 1 panel dong im lim kho hieu. Uoc luong lai ban kinh (met) tu chinh
  // deltaLat da luu trong bounds (dao nguoc cong thuc innerRadius=radius/sqrt(2) o tren). ===
  (function restoreFromUrl() {
    var params = new URLSearchParams(window.location.search);
    if (!params.has('ne_lat') || !params.has('sw_lat')) {
      return;
    }
    var neLat = parseFloat(params.get('ne_lat'));
    var neLng = parseFloat(params.get('ne_lng'));
    var swLat = parseFloat(params.get('sw_lat'));
    var swLng = parseFloat(params.get('sw_lng'));
    if (![neLat, neLng, swLat, swLng].every(isFinite)) {
      return;
    }
    var centerLat = (neLat + swLat) / 2;
    var centerLng = (neLng + swLng) / 2;
    var deltaLat = neLat - centerLat;
    var innerRadius = deltaLat * 111320;
    var radius = innerRadius * Math.SQRT2;

    openPanel();
    $('#pplus-roam-distance').val(Math.max(1, Math.round(radius / 1000)));
    withMap(function (map) {
      drawCircle(map, new google.maps.LatLng(centerLat, centerLng));
      if (circleOverlay) {
        circleOverlay.setRadius(radius);
      }
    });
  })();
})(jQuery);
