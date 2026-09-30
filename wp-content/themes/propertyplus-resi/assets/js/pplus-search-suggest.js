// Goi y khi go vao o "Bạn muốn đi đâu" - port keyupFilterSearch()/findNameGroupToBack()/initService()/
// SearchGroupAction()/DataCoreroupToBack(type 1|3) cua search-base + search-home.component.ts. Dung chung cho
// #searchModal (desktop), #search-tab-mobile va o tim kiem trang chu. 2 nhom nhu ban goc:
//   - "Tìm theo tên dự án": REST pplus/v1/search-group (Apartment/_GetFilterGroupList).
//   - "Tìm theo định vị google": Google Places AutocompleteService (script Maps chi tai khi bat dau go).
// Chon 1 muc -> onPick(sel): {type:'project', id, label} | {type:'area', ne_lat, ne_lng, sw_lat, sw_lng, label}.
(function ($) {
  'use strict';

  var cfg = window.PPLUS_SUGGEST_CONFIG || {};
  var i18n = cfg.i18n || {};
  // defaultBounds cua app-component-base.ts (tam Ha Noi).
  var CENTER = { lat: 21.027764, lng: 105.834160 };
  var DEFAULT_BOUNDS = { north: CENTER.lat + 1.1, south: CENTER.lat - 30.1, east: CENTER.lng + 5.1, west: CENTER.lng - 5.1 };
  // Dia diem Google chi la 1 diem (khong co bounds): ban goc zoom ban do toi do va loc theo khung nhin ban do.
  // Trang WP loc phia server nen dung 1 khung ~2km quanh diem, tuong duong 1 khung nhin ban do da zoom.
  var POINT_BOX = { lat: 0.01, lng: 0.012 };

  var placesPromise = null;

  function waitForMaps(ok, fail) {
    var waited = 0;
    (function tick() {
      if (window.google && window.google.maps) {
        ok();
      } else if ((waited += 100) > 15000) {
        fail();
      } else {
        setTimeout(tick, 100);
      }
    })();
  }

  // Trang danh sach/chi tiet tu tai Maps (reference-map-simple.js/pplus-maps.js) - khong tai lan 2 (Google bao
  // loi "included multiple times"): da co thi importLibrary('places'), dang tai do thi cho no xong.
  function loadPlaces() {
    if (placesPromise) {
      return placesPromise;
    }
    placesPromise = new Promise(function (resolve, reject) {
      function ready() {
        if (window.google.maps.places) {
          resolve();
        } else if (window.google.maps.importLibrary) {
          window.google.maps.importLibrary('places').then(function () { resolve(); }, reject);
        } else {
          reject(new Error('places unavailable'));
        }
      }
      if (window.google && window.google.maps) {
        ready();
        return;
      }
      if (document.querySelector('script[src*="maps.googleapis.com/maps/api/js"]')) {
        waitForMaps(ready, reject);
        return;
      }
      if (!cfg.mapKey) {
        reject(new Error('no key'));
        return;
      }
      var s = document.createElement('script');
      s.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(cfg.mapKey) +
        '&libraries=places&language=' + encodeURIComponent(cfg.lang || 'vi');
      s.async = true;
      s.onload = ready;
      s.onerror = reject;
      document.head.appendChild(s);
    });
    placesPromise.catch(function () { placesPromise = null; });
    return placesPromise;
  }

  function esc(text) {
    return $('<div>').text(text == null ? '' : String(text)).html();
  }

  function groupHtml(kind, title) {
    var cls = kind === 'project' ? 'pplus-map-box' : 'google-map-box';
    return '<div class="pplus-text ' + cls + ' pplus-show-group" data-group="' + kind + '"' + (kind === 'google' ? ' style="display:none"' : '') + '>' +
      '<div class="text-data" role="button" tabindex="0">' +
      '<span class="locations-ico" style="background-image:url(' + esc(cfg.logoIcon) + ')"></span>' + esc(title) +
      '<i class="icon-show fa fa-angle-right" aria-hidden="true"></i></div>' +
      '<div class="content-data"></div></div>';
  }

  function buildList(variant) {
    var groups = groupHtml('project', i18n.byProject) + groupHtml('google', i18n.byGoogle);
    if (variant === 'mobile') {
      return $('<div class="long-term-s1-item pplus-show-group-list" style="display:none">' +
        '<div class="long-term-s1-t1">' + esc(i18n.location) + '</div>' +
        '<div class="longterm-s1-result">' + groups + '</div></div>');
    }
    return $('<div class="long-term-s1-item pplus-show-group-list" style="display:none">' +
      '<div class="pplus-text-header"><span class="locations-ico" style="background-image:url(' + esc(cfg.pinIcon) + ')"></span>' +
      esc(i18n.location) + ' <span class="txt-filter-text"></span></div>' + groups + '</div>');
  }

  function itemsHtml(items) {
    return items.map(function (it) {
      return '<div class="item-content-data" role="button" tabindex="0" data-index="' + it.index + '"><div class="dot-item"></div>' + esc(it.label) + '</div>';
    }).join('');
  }

  function geocodePlace(placeId, label, done) {
    new window.google.maps.Geocoder().geocode({ placeId: placeId }, function (results, status) {
      if (status !== 'OK' || !results || !results[0]) {
        done({ type: 'text', label: label });
        return;
      }
      var g = results[0].geometry;
      var sel = { type: 'area', label: label };
      if (g.bounds) {
        sel.ne_lat = g.bounds.getNorthEast().lat();
        sel.ne_lng = g.bounds.getNorthEast().lng();
        sel.sw_lat = g.bounds.getSouthWest().lat();
        sel.sw_lng = g.bounds.getSouthWest().lng();
      } else {
        var lat = g.location.lat();
        var lng = g.location.lng();
        sel.ne_lat = lat + POINT_BOX.lat;
        sel.ne_lng = lng + POINT_BOX.lng;
        sel.sw_lat = lat - POINT_BOX.lat;
        sel.sw_lng = lng - POINT_BOX.lng;
      }
      done(sel);
    });
  }

  /**
   * opts: { input, box (noi chen danh sach), grid (luoi "Hoặc chọn theo gợi ý", an khi co ket qua - co the null),
   *         variant: 'desktop'|'mobile', onPick(sel), onType() }
   */
  function attach(opts) {
    var $input = $(opts.input);
    var $box = $(opts.box);
    if (!$input.length || !$box.length) {
      return;
    }
    var $grid = $(opts.grid || []);
    var $list = buildList(opts.variant).appendTo($box);
    var $project = $list.find('[data-group="project"]');
    var $google = $list.find('[data-group="google"]');
    var data = { project: [], google: [] };
    var seq = 0;
    var timer = null;

    function reset() {
      seq++;
      data = { project: [], google: [] };
      $list.hide();
      $google.hide();
      $grid.show();
      if (opts.onToggle) {
        opts.onToggle(false);
      }
    }

    function showList() {
      $list.show();
      $grid.hide();
      if (opts.onToggle) {
        opts.onToggle(true);
      }
    }

    function search(q) {
      var mySeq = ++seq;
      $list.find('.txt-filter-text').text(q);

      $.getJSON(cfg.restUrl, { q: q, lang: cfg.lang }).done(function (res) {
        if (mySeq !== seq) {
          return;
        }
        data.project = (res.projects || []).map(function (p, i) { return { index: i, id: p.id, label: p.name }; });
        $project.find('.content-data').html(itemsHtml(data.project));
        $project.toggleClass('show-data', data.project.length > 0);
        showList();
      });

      loadPlaces().then(function () {
        new window.google.maps.places.AutocompleteService().getPlacePredictions({
          input: q,
          bounds: DEFAULT_BOUNDS,
          componentRestrictions: { country: ['vn'] }
        }, function (predictions, status) {
          if (mySeq !== seq) {
            return;
          }
          if (status !== window.google.maps.places.PlacesServiceStatus.OK || !predictions) {
            return;
          }
          data.google = predictions.map(function (p, i) { return { index: i, placeId: p.place_id, label: p.description }; });
          $google.find('.content-data').html(itemsHtml(data.google));
          $google.toggleClass('show-data', data.google.length > 0).show();
          showList();
        });
      }).catch(function () { /* khong co Places: van con nhom du an */ });
    }

    $input.on('input', function () {
      var q = $.trim($input.val());
      if (opts.onType) {
        opts.onType();
      }
      clearTimeout(timer);
      if (!q) {
        reset();
        return;
      }
      timer = setTimeout(function () { search(q); }, 250);
    });

    // SearchGroupAction(): bam tieu de nhom de mo/dong.
    $list.on('click keydown', '.text-data', function (e) {
      if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') {
        return;
      }
      e.preventDefault();
      $(this).closest('.pplus-show-group').toggleClass('show-data');
    });

    $list.on('click keydown', '.item-content-data', function (e) {
      if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') {
        return;
      }
      e.preventDefault();
      var kind = $(this).closest('.pplus-show-group').data('group');
      var item = data[kind][+$(this).data('index')];
      if (!item) {
        return;
      }
      $input.val(item.label);
      if (kind === 'project') {
        opts.onPick({ type: 'project', id: item.id, label: item.label });
      } else {
        geocodePlace(item.placeId, item.label, opts.onPick);
      }
    });

    return { reset: reset };
  }

  // URL trang danh sach tong (A1 /cho-thue-can-ho/) cho 1 lua chon + cac tham so loc them (price_from...).
  function listUrl(sel, params) {
    var pairs = [];
    function add(k, v) {
      if (v !== '' && v !== null && typeof v !== 'undefined') {
        pairs.push(encodeURIComponent(k) + '=' + encodeURIComponent(v));
      }
    }
    if (sel.type === 'project') {
      add('project_id', sel.id);
    } else if (sel.type === 'area') {
      add('ne_lat', sel.ne_lat.toFixed(6));
      add('ne_lng', sel.ne_lng.toFixed(6));
      add('sw_lat', sel.sw_lat.toFixed(6));
      add('sw_lng', sel.sw_lng.toFixed(6));
    }
    add('q', sel.label);
    $.each(params || {}, add);
    return cfg.listUrl + (pairs.length ? '?' + pairs.join('&') : '');
  }

  // Dien lai chu da tim vao o khi vua chuyen toi trang ket qua (?q=...).
  function currentQuery() {
    var m = window.location.search.match(/[?&]q=([^&]*)/);
    return m ? decodeURIComponent(m[1].replace(/\+/g, ' ')) : '';
  }

  window.PplusSearchSuggest = { attach: attach, listUrl: listUrl, currentQuery: currentQuery };

  // O tim kiem trang chu (front-page.php, search-home.component): popup chi hien khi co ket qua; chon 1 muc roi
  // bam kinh lup -> trang danh sach tong voi lua chon do (submitSearchHome()).
  $(function () {
    var $input = $('#pplus-home-search-key');
    var $popup = $('#pplus-home-suggest');
    if (!$input.length || !$popup.length) {
      return;
    }
    var selection = null;
    attach({
      input: $input,
      box: $popup,
      variant: 'desktop',
      onToggle: function (visible) {
        $popup.toggle(visible).removeClass('pplus-force-hide');
      },
      onType: function () {
        selection = null;
      },
      onPick: function (sel) {
        selection = sel;
        $popup.addClass('pplus-force-hide');
        $('#pplus-home-search-detail').trigger('focus');
      }
    });
    $input.on('focus', function () {
      $popup.removeClass('pplus-force-hide');
    });
    $input.closest('form').on('submit', function (e) {
      if (selection) {
        e.preventDefault();
        window.location.href = listUrl(selection, {});
      }
    });
  });
})(jQuery);
