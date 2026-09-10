// Google Maps cho A1 (danh sach van phong) va A2 (chi tiet van phong) - port rut gon tu
// google-maps-loader.service.ts + myMapOptionsBase (app-component-base.ts dong 251-359).
//
// Marker van phong (renderListMap) PORT LAI DUNG co che that cua Angular: 1 custom OverlayView
// rieng (khong dung google.maps.Marker/icon SVG) voi cau truc HTML/class y het HTMLMarker() trong
// app-component-base.ts dong 373-456 (".office-marker > .marker-cnt > .svg-ctn" cho icon toa nha
// theo hang A/B/C tu Common/Icons/officeRate{A,B,C}.png, ".marker-title" cho nhan hang+ten khi
// hover/active). CSS cho toan bo cac class nay DA CO SAN tu truoc trong officebase/style/layout.less
// (chua bao gio dung toi) - chi can dung dung markup la khop ngay, khong can viet CSS moi.
//
// A1 KHONG lam lai hanh vi "keo map -> tu dong fetch lai du lieu" cua search-base.component that
// (can 1 REST endpoint moi nhan ne_lat/ne_lng/sw_lat/sw_lng, chua co) - chi hien marker cho DUNG
// cac van phong da co san o trang hien tai (server-side render), khong fetch lai khi pan/zoom.
(function () {
  'use strict';

  var mapOptionsBase = {
    mapTypeId: 'terrain',
    mapTypeControl: false,
    streetViewControl: false,
    gestureHandling: 'greedy',
    styles: [
      { featureType: 'transit', elementType: 'labels.icon', stylers: [{ visibility: 'off' }] },
      { featureType: 'road.arterial', elementType: 'labels.icon', stylers: [{ visibility: 'off' }] },
      { featureType: 'road.local', elementType: 'labels.icon', stylers: [{ visibility: 'off' }] },
      { featureType: 'road.highway', elementType: 'labels.icon', stylers: [{ visibility: 'off' }] },
      { featureType: 'road.highway.controlled_access', elementType: 'labels.icon', stylers: [{ visibility: 'off' }] },
      { featureType: 'poi.sports_complex', elementType: 'labels.icon', stylers: [{ visibility: 'off' }] },
      { featureType: 'poi.attraction', elementType: 'labels.icon', stylers: [{ visibility: 'off' }] },
      { featureType: 'poi.business', elementType: 'labels.icon', stylers: [{ visibility: 'off' }] },
      { featureType: 'poi.government', elementType: 'labels.icon', stylers: [{ visibility: 'off' }] },
      { featureType: 'poi.medical', elementType: 'labels.icon', stylers: [{ visibility: 'off' }] },
      { featureType: 'poi.park', elementType: 'labels.icon', stylers: [{ visibility: 'off' }] },
      { featureType: 'poi.place_of_worship', elementType: 'labels.icon', stylers: [{ visibility: 'off' }] },
      { featureType: 'landscape.natural.terrain', elementType: 'labels.icon', stylers: [{ visibility: 'off' }] },
      { featureType: 'landscape.natural.landcover', elementType: 'labels.icon', stylers: [{ visibility: 'off' }] },
      { featureType: 'landscape.natural', elementType: 'labels.icon', stylers: [{ visibility: 'off' }] },
      { featureType: 'administrative.country', elementType: 'labels.icon', stylers: [{ visibility: 'off' }] },
      { featureType: 'administrative.land_parcel', elementType: 'labels.icon', stylers: [{ visibility: 'off' }] },
      { featureType: 'administrative.locality', elementType: 'labels.icon', stylers: [{ visibility: 'off' }] },
      { featureType: 'administrative.neighborhood', elementType: 'labels.icon', stylers: [{ visibility: 'off' }] },
      { featureType: 'administrative.province', elementType: 'labels.icon', stylers: [{ visibility: 'off' }] }
    ]
  };

  // Mau dung DUNG theo bang hang A/B/C da co san cua site (xem header.less dong 875-885,
  // ".classA/.classB/.classC" dung cho badge hang trong menu "Tim theo hang") - KHONG tu bia mau
  // rieng cho marker (loi truoc do: xanh duong/xanh la/cam, sai hoan toan voi mau that cua site).
  var markerColors = { A: '#CB2A2A', B: '#E61DEA', C: '#3E5FB4' };

  // Icon teardrop don gian - CHI con dung cho renderDetailMap() (A2, 1 marker duy nhat, khong
  // can hover-list/nhan hang nhu renderListMap() ben duoi da doi sang OfficeMarkerOverlay rieng).
  function markerIcon(rating) {
    var color = markerColors[rating] || '#e74c3c';
    return {
      path: 'M12 0C7.6 0 4 3.6 4 8c0 6 8 16 8 16s8-10 8-16c0-4.4-3.6-8-8-8z',
      fillColor: color,
      fillOpacity: 1,
      strokeColor: '#ffffff',
      strokeWeight: 1.5,
      scale: 1.4,
      anchor: new google.maps.Point(12, 24)
    };
  }

  var loading = false;
  var callbacks = [];

  function pplusLoadGoogleMaps(cb) {
    if (window.google && window.google.maps) {
      cb();
      return;
    }
    callbacks.push(cb);
    if (loading || !window.PPLUS_MAP_CONFIG || !window.PPLUS_MAP_CONFIG.key) {
      return;
    }
    loading = true;
    var script = document.createElement('script');
    script.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(window.PPLUS_MAP_CONFIG.key) +
      '&libraries=places&language=' + encodeURIComponent(window.PPLUS_MAP_CONFIG.lang || 'vi');
    script.async = true;
    script.onload = function () {
      callbacks.forEach(function (fn) { fn(); });
      callbacks = [];
    };
    document.head.appendChild(script);
  }

  // A2 - #map-detail: 1 marker duy nhat (vi tri van phong dang xem), doc tu data-lat/data-lng/
  // data-title/data-rating tren chinh div do (xem page-office-detail.php).
  function renderDetailMap() {
    var el = document.getElementById('map-detail');
    if (!el) {
      return;
    }
    var lat = parseFloat(el.getAttribute('data-lat'));
    var lng = parseFloat(el.getAttribute('data-lng'));
    if (!lat || !lng) {
      return;
    }
    var map = new google.maps.Map(el, Object.assign({ center: { lat: lat, lng: lng }, zoom: 16 }, mapOptionsBase));
    new google.maps.Marker({
      position: { lat: lat, lng: lng },
      map: map,
      title: el.getAttribute('data-title') || '',
      icon: markerIcon(el.getAttribute('data-rating'))
    });
  }

  var officeRateIcons = {
    A: window.PPLUS_MAP_CONFIG && window.PPLUS_MAP_CONFIG.themeUrl + '/assets/officebase/Common/Icons/officeRateA.png',
    B: window.PPLUS_MAP_CONFIG && window.PPLUS_MAP_CONFIG.themeUrl + '/assets/officebase/Common/Icons/officeRateB.png',
    C: window.PPLUS_MAP_CONFIG && window.PPLUS_MAP_CONFIG.themeUrl + '/assets/officebase/Common/Icons/officeRateC.png'
  };

  function escapeHtml(str) {
    return String(str || '').replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  // Nhan "hang + ten" (.marker-title) TU CO GIAN theo do dai ten that su (feedback: co toa can
  // ~153px moi du, co toa chi can ~100px, ep 1 do rong co dinh se lam toa ten ngan bi thua khoang
  // trang) - CSS rieng (main.css, ".marker-title { width:auto; max-width:210px }") da bo min-width
  // co dinh cua layout.less va bat white-space:nowrap tren <p> de no rong ra ngang thay vi xuong
  // dong.
  //
  // Cat gon o RANH GIOI TU (khong cat lung chung ky tu) khi ten thuc su vuot qua max-width - DO
  // BANG DOM THAT (khong phai canvas 2D uoc luong font) vi con so can biet la TONG do rong CA
  // ".marker-title" (gom padding + badge ".class-label" + <p>), khong chi rieng phan chu trong
  // <p> - uoc luong rieng do rong badge/padding rat de sai so (thuc te da gap: chu bi hut mat 1
  // doan vi tinh thieu do rong badge "A"). Dung 1 sandbox AN (visibility:hidden nhung VAN CO
  // layout that, KHONG dung display:none vi se khong do duoc kich thuoc) de render y het cau truc
  // that (badge + <p>), giam dan tung tu cho toi khi vua khop max-width.
  var TITLE_MAX_WIDTH = 210; // trung voi ".marker-title { max-width }" trong main.css
  var measureSandbox = document.createElement('div');
  measureSandbox.className = 'marker-title';
  measureSandbox.style.cssText = 'position:absolute;left:-9999px;top:-9999px;visibility:hidden;display:block;white-space:nowrap;';
  document.body.appendChild(measureSandbox);
  function fitTitleText(title, rankHtml) {
    title = String(title || '');
    measureSandbox.innerHTML = rankHtml + '<p>' + escapeHtml(title) + '</p>';
    if (measureSandbox.getBoundingClientRect().width <= TITLE_MAX_WIDTH) {
      return title;
    }
    var words = title.split(' ');
    var result = '';
    for (var i = 0; i < words.length; i++) {
      var candidate = (result ? result + ' ' + words[i] : words[i]) + '…';
      measureSandbox.innerHTML = rankHtml + '<p>' + escapeHtml(candidate) + '</p>';
      if (measureSandbox.getBoundingClientRect().width > TITLE_MAX_WIDTH) {
        break;
      }
      result = result ? result + ' ' + words[i] : words[i];
    }
    return (result || words[0]) + '…';
  }

  // Marker van phong tuy chinh - port lai HTMLMarker() (app-component-base.ts dong 373-456):
  // ".office-marker" (khung dinh vi) > ".marker-cnt > .svg-ctn" (icon toa nha, mau theo hang qua
  // anh officeRate{A,B,C}.png rieng, KHONG phai to mau 1 icon trung) + ".marker-title" (nhan
  // "Hạng X" + ten, CSS co san chi hien khi ".office-marker:hover/.open/.active-hover").
  function OfficeMarkerOverlay(item, map, infoWindow) {
    this.item = item;
    this.map = map;
    this.infoWindow = infoWindow;
    this.position = new google.maps.LatLng(item.lat, item.lng);
    this.div = null;
    this.setMap(map);
  }
  // QUAN TRONG: KHONG duoc goi "new google.maps.OverlayView()" ngay tai day (top-level cua file,
  // chay ngay khi <script> nay duoc parse) - luc nay Google Maps API CHUA chac da tai xong (no
  // duoc tai bat dong bo rieng qua pplusLoadGoogleMaps(), xem duoi file). Goi som se nem loi
  // "google is not defined" ngay lap tuc, lam HONG toan bo phan con lai cua IIFE nay - ke ca
  // document.addEventListener('DOMContentLoaded', ...) o cuoi file cung KHONG BAO GIO duoc dang
  // ky nua (loi thuc te da gap: ca ban do bien mat hoan toan, khong chi marker moi). Ke thua
  // prototype tu OverlayView duoc hoan lai, chi thuc hien lan dau tien renderListMap() chay
  // (luc do chac chan google.maps da san sang).
  function ensureOfficeMarkerOverlayInherits() {
    if (OfficeMarkerOverlay.prototype instanceof google.maps.OverlayView) {
      return;
    }
    var proto = OfficeMarkerOverlay.prototype;
    OfficeMarkerOverlay.prototype = new google.maps.OverlayView();
    Object.keys(proto).forEach(function (key) {
      OfficeMarkerOverlay.prototype[key] = proto[key];
    });
  }
  OfficeMarkerOverlay.prototype.onAdd = function () {
    var item = this.item;
    var map = this.map;
    var infoWindow = this.infoWindow;
    // CHI hien ten du an trong phan chu - hang da hien rieng qua ".class-label" (o vuong mau
    // "A"/"B"/"C" phia truoc), khong lap lai "Hạng X" trong chu nhu ban truoc (feedback: "Hiện
    // tại bạn đang lặp lại... hạng toà nhà").
    var rankHtml = item.rating ? '<div class="class-label class' + escapeHtml(item.rating) + '">' + escapeHtml(item.rating) + '</div>' : '';
    var titleText = fitTitleText(item.title, rankHtml);
    var div = document.createElement('div');
    div.className = 'marker up office-marker';
    div.id = item.id || '';
    div.setAttribute('data-id', item.id || '');
    // KHONG dat thuoc tinh HTML "title" (o day hay tren ".marker-title" ben duoi) - trinh duyet se
    // tu hien 1 tooltip native (khong theo CSS/vi tri cua ta) khi chuot dung yen tren do, chong len
    // va lech hoan toan khoi khung ".marker-title" da tu thiet ke (bug da gap thuc te: ten du an
    // "hien lech ra khoi khung hien thi"). Ten du an da hien du qua noi dung ben trong ".marker-title"
    // roi, khong can them title attribute.
    div.innerHTML =
      '<div class="marker-cnt">' +
      '<div class="svg-ctn" style="background-image:url(' + (officeRateIcons[item.rating] || officeRateIcons.A) + ')"></div>' +
      '</div>' +
      '<div class="marker-title">' +
      rankHtml +
      '<p>' + escapeHtml(titleText) + '</p>' +
      '<div class="marker-square"></div>' +
      '</div>';

    // Tro chuot vao icon -> hien nhan "Hạng + ten" (them class active-hover, CSS co san trong
    // layout.less se tu to icon + hien ".marker-title"), giong dung hanh vi hover tu danh sach
    // ben trai (setHovered() ben duoi dung chung co che nay).
    div.addEventListener('mouseenter', function () { div.classList.add('active-hover'); });
    div.addEventListener('mouseleave', function () { div.classList.remove('active-hover'); });

    // Bam vao ".marker-title" (nhan da hien) -> sang thang trang chi tiet, giong dung Angular
    // that (dong 411-416: "Neu click trung .marker-title -> di vao trang chi tiet"). Bam vao
    // icon (ngoai .marker-title):
    // - Mobile (<=880px, khop breakpoint ".box-list-body-mobile" trong search-base.less): cuon
    //   toi dung the san pham tuong ung trong dai the co dinh duoi ban do THAY VI mo InfoWindow -
    //   theo yeu cau nguoi dung ("thay cho cách hiện ở PC là click vào là hiện pop sản phẩm để
    //   click, thì mobile hiện ở chỗ này"). Khop qua "data-id" (deu la "office-{projectId}",
    //   xem $map_markers trong page-office-list.php/page-reference.php va office-card.php).
    // - Desktop: giu nguyen popup day du (anh/hang/gia/dia chi, xem .pplus-map-popup).
    div.addEventListener('click', function (e) {
      if (e.target.closest('.marker-title')) {
        if (item.url) {
          window.location.href = item.url;
        }
        return;
      }
      if (window.matchMedia('(max-width: 880px)').matches) {
        var card = document.querySelector('.box-list-body-mobile .item-office[data-id="' + item.id + '"]');
        if (card) {
          card.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
          return;
        }
      }
      var linkOpen = item.url ? '<a href="' + item.url + '">' : '';
      var linkClose = item.url ? '</a>' : '';
      var content = '<div class="pplus-map-popup">' +
        linkOpen +
        (item.photo ? '<img class="pplus-map-popup-photo" src="' + item.photo + '" alt="" />' : '') +
        '<div class="pplus-map-popup-body">' +
        (item.rating ? '<span class="pplus-map-popup-rank rank-' + escapeHtml(item.rating) + '">Hạng ' + escapeHtml(item.rating) + '</span>' : '') +
        (item.price ? '<span class="pplus-map-popup-price">' + escapeHtml(item.price) + '</span>' : '') +
        '<div class="pplus-map-popup-title">' + escapeHtml(item.title) + '</div>' +
        (item.address ? '<div class="pplus-map-popup-address">' + escapeHtml(item.address) + '</div>' : '') +
        '</div>' +
        linkClose +
        '</div>';
      infoWindow.setContent(content);
      infoWindow.setPosition(this.position);
      infoWindow.open(map);
    }.bind(this));

    this.div = div;
    this.getPanes().overlayMouseTarget.appendChild(div);
  };
  OfficeMarkerOverlay.prototype.draw = function () {
    if (!this.div) {
      return;
    }
    var projection = this.getProjection();
    if (!projection) {
      return;
    }
    var point = projection.fromLatLngToDivPixel(this.position);
    if (!point) {
      return;
    }
    // Icon rong 30px (xem ".office-marker" trong layout.less) - canh giua theo chieu ngang, day
    // xuong tu diem toa do (anchor o day marker, giong dung app-component-base.ts dong 550-556).
    this.div.style.left = (point.x - 15) + 'px';
    this.div.style.top = (point.y - 55) + 'px';
  };
  OfficeMarkerOverlay.prototype.onRemove = function () {
    if (this.div && this.div.parentNode) {
      this.div.parentNode.removeChild(this.div);
      this.div = null;
    }
  };
  OfficeMarkerOverlay.prototype.setHovered = function (hovered) {
    if (this.div) {
      this.div.classList.toggle('active-hover', hovered);
    }
  };

  // A1/A4 - #map trong .box-map: marker cho DUNG cac van phong da co san tren trang hien tai
  // (khong fetch lai khi pan/zoom - xem ghi chu dau file). Doc danh sach tu data-markers (JSON)
  // tren chinh div do (xem page-office-list.php/page-reference.php).
  function renderListMap() {
    var el = document.getElementById('map');
    if (!el || !el.closest('.box-map')) {
      return;
    }
    ensureOfficeMarkerOverlayInherits();
    var raw = el.getAttribute('data-markers');
    var items = [];
    try {
      items = raw ? JSON.parse(raw) : [];
    } catch (e) {
      items = [];
    }
    items = items.filter(function (m) { return m.lat && m.lng; });
    if (!items.length) {
      // Truoc day bo qua luon khong tao map khi 0 marker (vd quan/huyen chua co van phong nao) -
      // de #map trang hoan toan thay vi 1 ban do that (khong phai loi CSS, xac nhan qua thuc te).
      // Van tao map, chi khong co pin, tam theo data-fallback-lat/-lng (PHP tinh tu vung tim kiem,
      // xem page-reference.php) hoac fallback cuoi cung ve trung tam Ha Noi.
      var fallbackLat = parseFloat(el.getAttribute('data-fallback-lat'));
      var fallbackLng = parseFloat(el.getAttribute('data-fallback-lng'));
      var fallbackCenter = (fallbackLat && fallbackLng) ? { lat: fallbackLat, lng: fallbackLng } : { lat: 21.0278, lng: 105.8342 };
      var fallbackMap = new google.maps.Map(el, Object.assign({ center: fallbackCenter, zoom: 12 }, mapOptionsBase));
      // "Tim theo ban kinh" (assets/js/radius-search.js) can truy cap CHINH map instance nay de
      // ve marker/circle - "map" o day von la bien cuc bo (khong ra ngoai duoc ham nay), nen luu
      // lai qua window + ban 1 su kien de radius-search.js biet luc nao co the bat dau dung (co
      // the load truoc/sau file nay, khong phu thuoc thu tu).
      window.pplusOfficeMap = fallbackMap;
      document.dispatchEvent(new CustomEvent('pplus:map-ready', { detail: { map: fallbackMap } }));
      return;
    }

    var bounds = new google.maps.LatLngBounds();
    var map = new google.maps.Map(el, Object.assign({ zoom: 13 }, mapOptionsBase));
    window.pplusOfficeMap = map;
    var infoWindow = new google.maps.InfoWindow();
    var markersById = {};

    items.forEach(function (item) {
      bounds.extend({ lat: item.lat, lng: item.lng });
      var overlay = new OfficeMarkerOverlay(item, map, infoWindow);
      if (item.id) {
        markersById[item.id] = overlay;
      }
    });

    // Hover tu danh sach ben trai (.item-office data-id) -> highlight marker tuong ung (them
    // class "active-hover", CSS co san se tu to icon + hien ".marker-title" - xem layout.less).
    document.querySelectorAll('.box-list .item-office[data-id]').forEach(function (cardEl) {
      var overlay = markersById[cardEl.getAttribute('data-id')];
      if (!overlay) {
        return;
      }
      cardEl.addEventListener('mouseenter', function () { overlay.setHovered(true); });
      cardEl.addEventListener('mouseleave', function () { overlay.setHovered(false); });
    });

    map.fitBounds(bounds);
    // Bug thuc te nguoi dung bao cao (17/8/2026): chon 1 du an cu the tu goi y "Địa điểm" (project_id,
    // luon ra DUNG 1 ket qua) lam ban do zoom qua sat toa nha, khong con thay ten duong xung quanh de
    // dinh vi. Nguyen nhan: fitBounds() tren 1 diem DUY NHAT (hoac nhieu diem qua gan nhau) khong co
    // chieu rong/cao that de tinh - Google Maps tu dong zoom toi MUC TOI DA co the cho khu vuc do (co
    // the len toi 20-21, sat muc mai nha), khac han truong hop binh thuong (nhieu van phong rai rac
    // tren 1 quan/duong) fitBounds() tu tinh ra zoom hop ly san. Cap lai ve toi da zoom=16 (CUNG gia
    // tri voi renderDetailMap() o tren - "1 marker duy nhat" da co san quy uoc nay cho trang A2) khi
    // fitBounds() cho ra zoom cao hon muc do - dung 'bounds_changed' (bat 1 lan DUY NHAT, chay SAU
    // khi fitBounds() da tinh xong zoom) thay vi doc map.getZoom() ngay sau fitBounds() (gia tri chua
    // on dinh ngay lap tuc trong 1 so trinh duyet, theo khuyen nghi chinh thuc cua Google Maps JS API).
    // Muc zoom toi da co the ghi de qua data-max-zoom tren #map (mac dinh 16). Trang DUONG (loai=6)
    // dat thap hon (vd 14) vi moi van phong nam tren CUNG 1 con duong -> fitBounds ra dai/hep, zoom
    // van sat, khong thay ten duong xung quanh de dinh vi (phan hoi nguoi dung 18/8/2026). Trang
    // quan/single-project giu 16 nhu cu.
    var pplusMaxZoom = parseInt(el.getAttribute('data-max-zoom'), 10);
    if (!pplusMaxZoom) { pplusMaxZoom = 16; }
    google.maps.event.addListenerOnce(map, 'bounds_changed', function () {
      if (map.getZoom() > pplusMaxZoom) {
        map.setZoom(pplusMaxZoom);
      }
    });
    document.dispatchEvent(new CustomEvent('pplus:map-ready', { detail: { map: map } }));
  }

  // Toi uu toc do (theo yeu cau nguoi dung, 13/8/2026): truoc day script Google Maps (~1MB, gom
  // ca tile/marker) tai NGAY luc DOMContentLoaded, canh tranh bang thong HTTP/1.1 voi moi tai
  // nguyen khac (anh, font, CSS) - la nguyen nhan chinh khien Time-to-Interactive len toi 13s (xac
  // nhan qua Lighthouse: script Maps khong bat dau tai cho toi giay thu 15). Doi sang tai TRE, chia
  // 3 cach khac nhau theo dung dac diem hien thi cua tung khu vuc ban do (KHONG doi hanh vi render/
  // marker, chi doi THOI DIEM bat dau tai):
  //
  // 1. #map-detail (A2) - nam duoi cung 1 luong cuon binh thuong (khong co the che do PC/mobile
  //    rieng nhu A1/A4), luon can cuon toi moi thay - dung IntersectionObserver, tai ngay TRUOC khi
  //    thuc su lot vao khung nhin (rootMargin 200px) de nguoi dung khong thay khoang trong khi cuon
  //    toi dung cho.
  // 2. #map trong .box-map, PC (>880px) - ban do LUON hien san o cot phai (nut "Bản đồ" khong co
  //    tac dung tren PC, xem ghi chu $office_args o functions.php), nen KHONG the doi "bam moi tai"
  //    duoc (se doi han giao dien nguoi dung da quen). Dung requestIdleCallback (uu tien cac tac vu
  //    quan trong hon xong truoc, ban do tu dong hien tiep ngay sau do - KHONG can bam gi) - fallback
  //    "window.load" cho trinh duyet chua ho tro requestIdleCallback (Safari).
  // 3. #map trong .box-map, mobile (<=880px) - ban do von da AN SAN qua CSS (".invision-map", chi
  //    hien khi bam nut "Bản đồ" noi goc duoi-phai), nen tai luon TU LUC BAM (khong tai ngam truoc
  //    khi chua ai bam) - da dieu kien nhat, da so nguoi dung mobile khong bao gio bam xem ban do
  //    nen tiet kiem duoc toan bo ~1MB. Bat 1 su kien tuy chinh "pplus:map-view-opened" tu handler
  //    click co san (assets/js/main.js, nut "Bản đồ") de biet dung luc nao nguoi dung THUC SU chuyen
  //    sang xem map lan dau (chi 1 lan - {once:true} - tranh goi lai renderListMap() nhieu lan tao
  //    marker trung lap neu nguoi dung bam qua lai list/map).
  function pplusDeferIdle(fn) {
    if ('requestIdleCallback' in window) {
      requestIdleCallback(fn, { timeout: 3000 });
    } else if (document.readyState === 'complete') {
      fn();
    } else {
      window.addEventListener('load', fn);
    }
  }

  function pplusLoadWhenVisible(el, fn) {
    if (!('IntersectionObserver' in window)) {
      fn();
      return;
    }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          io.disconnect();
          fn();
        }
      });
    }, { rootMargin: '200px' });
    io.observe(el);
  }

  document.addEventListener('DOMContentLoaded', function () {
    var detailEl = document.getElementById('map-detail');
    if (detailEl) {
      pplusLoadWhenVisible(detailEl, function () {
        pplusLoadGoogleMaps(renderDetailMap);
      });
    }

    var listEl = document.getElementById('map');
    if (listEl && listEl.closest('.box-map')) {
      if (window.matchMedia('(max-width: 880px)').matches) {
        document.addEventListener('pplus:map-view-opened', function () {
          pplusLoadGoogleMaps(renderListMap);
        }, { once: true });
      } else {
        pplusDeferIdle(function () {
          pplusLoadGoogleMaps(renderListMap);
        });
      }
    }
  });

  // Trang /office-favourite (assets/js/pplus-favourite.js "#mapfarourite") tu tao 1 ban do RIENG
  // (khong dung renderListMap()/renderDetailMap() o tren - khong biet truoc se hien VP nao luc PHP
  // render, chi co du lieu sau khi doc localStorage) nen can goi lai duoc pplusLoadGoogleMaps() tu
  // BEN NGOAI file nay - truoc day ham nay chi la bien noi bo trong IIFE, khong file nao khac goi
  // toi duoc, khien "#mapfarourite" khong bao gio thuc su tai script Google Maps (nut "Xem bang
  // Ban do" bam khong co gi xay ra - bug thuc te nguoi dung bao cao).
  window.pplusLoadGoogleMaps = pplusLoadGoogleMaps;

  // Cung ly do voi pplusLoadGoogleMaps o tren: trang /office-favourite tu ve marker rieng (khong
  // qua renderListMap(), vi khong biet truoc se hien VP nao luc PHP render) - truoc day dung THANG
  // "new google.maps.Marker(...)" mac dinh (icon giot nuoc do chuan Google, khong co nhan hang/ten)
  // thay vi marker tuy chinh ".office-marker" giong het moi trang khac - bug thuc te: "map và icon
  // toà nhà... chưa giống bản chính". Expose factory de pplus-favourite.js dung LAI dung
  // OfficeMarkerOverlay (tu lo ensureOfficeMarkerOverlayInherits(), xem ghi chu tren dau ham do ve
  // ly do phai goi tre, khong goi som tai top-level).
  window.pplusCreateOfficeMarker = function (item, map, infoWindow) {
    ensureOfficeMarkerOverlayInherits();
    return new OfficeMarkerOverlay(item, map, infoWindow);
  };
  // Style ban do (terrain + an bot nhan POI mac dinh cua Google, xem dau file) - trang Yeu thich
  // truoc day dung map option "tho" (khong co style nay) nen nhin khac han moi trang khac.
  window.pplusMapOptionsBase = mapOptionsBase;
})();
