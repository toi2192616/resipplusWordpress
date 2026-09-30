/**
 * Ban do gia don gian cho A3/A4 (trang danh sach can ho theo quan/tinh/loai). KHONG PHAI ban port
 * day du cua search-base.component.ts that (~1750 dong: marker OverlayView tu tay ve VOI popup
 * mini-card, Places Autocomplete, Geocoder, reload danh sach khi pan/zoom ban do - xem TODO dau
 * page-reference.php, qua nang de port 1:1 trong lan nay) - chi ve marker gia tinh KHOP DUNG danh
 * sach dang hien tren trang (khong tu dong tai lai khi pan/zoom). Bam marker dieu huong thang sang
 * trang chi tiet can ho (thay vi mo popup mini-card nhu ban goc).
 *
 * Dung Maps JavaScript API co dien (google.maps.Map/OverlayView), KHONG dung importLibrary()/
 * AdvancedMarkerElement - 2 cai do can 1 doan script "bootstrap loader" rieng cua Google (khong
 * phai chi 1 the <script src> thuong) + 1 Map ID cau hinh rieng, phuc tap hon can thiet cho 1 ban
 * do rut gon chi hien marker tinh.
 */
(function () {
    'use strict';

    function PriceMarker(position, text, url, map, id, onClick) {
        this.onClick = onClick;
        this.position = position;
        this.text = text;
        this.url = url;
        this.id = id;
        this.active = false;
        this.div = null;
        this.setMap(map);
    }

    // Gan prototype BEN TRONG ham nay (chi goi sau khi script Google Maps da tai xong), KHONG phai
    // o dinh file - "google.maps.OverlayView" CHUA ton tai luc file JS nay duoc trinh duyet parse
    // (Maps duoc tai lazy, xem loadGoogleMapsScript() ben duoi) - gan o dinh file se loi ngay
    // "google is not defined".
    function setupPriceMarkerPrototype() {
        PriceMarker.prototype = new google.maps.OverlayView();

        PriceMarker.prototype.onAdd = function () {
            var div = document.createElement('div');
            div.className = 'pplus-map-price-pin';
            div.textContent = '$' + this.text;
            div.style.position = 'absolute';
            if (this.id !== undefined && this.id !== null && this.id !== '') {
                div.setAttribute('data-marker-id', this.id);
            }
            if (this.url) {
                var self = this;
                div.addEventListener('click', function () {
                    // Callback (mobile che do ban do) tra true = da xu ly (cuon dai the), khong chuyen trang.
                    if (self.onClick && self.onClick(self)) {
                        return;
                    }
                    window.location.href = self.url;
                });
            }
            this.div = div;
            var panes = this.getPanes();
            panes.overlayMouseTarget.appendChild(div);
        };

        PriceMarker.prototype.draw = function () {
            if (!this.div) {
                return;
            }
            var overlayProjection = this.getProjection();
            if (!overlayProjection) {
                return;
            }
            var point = overlayProjection.fromLatLngToDivPixel(this.position);
            if (point) {
                this.div.style.left = point.x + 'px';
                this.div.style.top = point.y + 'px';
                // Scale ghep chung vao cung 1 transform (khong tach class CSS rieng) vi draw() co the
                // duoc Maps goi lai bat ky luc nao (pan/zoom/resize) va se ghi de moi transform dat
                // qua CSS - phai tu tinh lai ca translate + scale moi lan dua vao co "active" hay
                // khong de khong bi mat trang thai phong to khi ban do ve lai.
                var scale = this.active ? 1.35 : 1;
                this.div.style.transform = 'translate(-50%, -100%) scale(' + scale + ')';
            }
        };

        // Them vao (khong co trong ban goc - ban that co dinh nghia hover nhung bi loi selector
        // id/data-id nen khong bao gio chay, xem ghi chu cuoi file): phong to marker khi tro chuot
        // vao the can ho tuong ung trong danh sach, theo yeu cau nguoi dung (14/9).
        PriceMarker.prototype.setActive = function (active) {
            this.active = active;
            if (this.div) {
                this.div.classList.toggle('is-active', active);
            }
            this.draw();
        };

        PriceMarker.prototype.onRemove = function () {
            if (this.div && this.div.parentNode) {
                this.div.parentNode.removeChild(this.div);
                this.div = null;
            }
        };
    }

    // Che do mobile (<=880px, khop breakpoint ".box-list-body-mobile" trong search-base.less): bam
    // marker KHONG dieu huong ngay ma cuon dai the o duoi toi dung the tuong ung (port
    // touchendListMobile() cua search-base.component.ts). Tren PC van dieu huong thang nhu truoc.
    function isMobileMapMode() {
        return document.documentElement.clientWidth <= 880 && !!document.querySelector('.box-list-body-mobile');
    }

    function stripEl() {
        return document.querySelector('.box-list-body-mobile');
    }

    // SUA 30/9 (theo yeu cau nguoi dung: "chỗ bản đồ hiện mình đang cài đặt google hiển thị các
    // loại gì ấy nhi... cho tôi giảm bớt đi nhìn bản đồ cho đỡ rối mắt") - ban do rut gon nay (dung
    // cho A1/A3/A4/"Dự án cụ thể") TRUOC DAY khong co "styles" nao ca, hien nguyen ban 100% icon POI
    // mac dinh cua Google (cua hang/nha hang/quan cafe, diem tham quan, khu the thao, co quan nha
    // nuoc, den/chua/nha tho...) - rat day, de lan voi marker gia cua chinh site. Nguoi dung chon
    // AN HAN (khong chi an chu, an ca icon: "visibility:off" tren CA featureType, khong gioi han
    // "elementType:labels.icon" nhu pplus-maps.js dang lam cho A2) - 5 nhom dau tien theo yeu cau
    // 30/9 lan 1: poi.business (cua hang/nha hang/quan cafe), poi.attraction (diem tham quan),
    // poi.sports_complex (khu the thao), poi.government (co quan nha nuoc), poi.place_of_worship
    // (den/chua/nha tho). "poi.medical" (benh vien) THEM VAO lan 2 (30/9, "tôi muốn ẩn các icon bộ
    // ban ngành, bệnh viện đi") - nguoi dung lan dau CHON GIU LAI benh vien nhung sau do doi y,
    // muon an luon. GIU LAI: cong vien (poi.park), truong hoc (poi.school), tram xe bus/tau dien
    // (transit).
    var PPLUS_HIDDEN_POI_STYLES = [
        { featureType: 'poi.business', stylers: [{ visibility: 'off' }] },
        { featureType: 'poi.attraction', stylers: [{ visibility: 'off' }] },
        { featureType: 'poi.sports_complex', stylers: [{ visibility: 'off' }] },
        { featureType: 'poi.government', stylers: [{ visibility: 'off' }] },
        { featureType: 'poi.place_of_worship', stylers: [{ visibility: 'off' }] },
        { featureType: 'poi.medical', stylers: [{ visibility: 'off' }] },
    ];

    function initMap(container, markers) {
        setupPriceMarkerPrototype();

        var map = new google.maps.Map(container, {
            center: { lat: 21.0285, lng: 105.8048 },
            zoom: 13,
            fullscreenControl: true,
            streetViewControl: false,
            mapTypeControl: false,
            styles: PPLUS_HIDDEN_POI_STYLES,
            // SUA 30/9 (theo yeu cau nguoi dung: "cuộn chuột là được, kể cả không giữ ctrl"):
            // Google Maps mac dinh (gestureHandling: "auto" khi khong khai bao) dung che do
            // "cooperative" cho map nhung tren 1 trang co the cuon duoc - can giu Ctrl+cuon moi zoom
            // (cuon thuong se cuon CA TRANG, khong zoom ban do), hien 1 tooltip "Use ctrl + scroll to
            // zoom" khi nguoi dung cuon ma khong giu Ctrl. "greedy" cho map tu xu ly MOI thao tac cuon/
            // keo ngay lap tuc, khong can phim tat - dung y het gia tri ma pplus-maps.js (A2/A1 Van
            // phong) DA dung san tu truoc, chi rieng ban do rut gon nay (A1/A3/A4 Can ho) truoc gio
            // thieu.
            gestureHandling: 'greedy',
        });

        // Registry id -> PriceMarker, dung de tim dung marker can phong to khi hover the trong
        // danh sach (xem bindListHoverToMarkers ben duoi). Them vao, khong co trong ban goc.
        var markerRegistry = {};
        bindListHoverToMarkers(markerRegistry);

        function setActiveMarker(id) {
            Object.keys(markerRegistry).forEach(function (key) {
                markerRegistry[key].setActive(String(key) === String(id));
            });
        }

        function addMarkers(list, fit) {
            var bounds = new google.maps.LatLngBounds();
            var count = 0;
            list.forEach(function (item) {
                if (!item.lat || !item.lng) {
                    return;
                }
                var position = new google.maps.LatLng(item.lat, item.lng);
                var marker = new PriceMarker(position, item.price, item.url, map, item.id, function (m) {
                    // Mobile che do ban do: cuon dai the toi the tuong ung, khong chuyen trang.
                    var card = isMobileMapMode()
                        ? stripEl().querySelector('.item-office[data-marker-id="' + m.id + '"]')
                        : null;
                    if (!card) {
                        return false;
                    }
                    card.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
                    setActiveMarker(m.id);
                    return true;
                });
                if (item.id !== undefined && item.id !== null && item.id !== '') {
                    markerRegistry[item.id] = marker;
                }
                bounds.extend(position);
                count++;
            });
            if (fit && count) {
                map.fitBounds(bounds);
                // SUA 30/9 (bug thuc te nguoi dung bao cao: "click vào dự án bản đồ bị zoom sát
                // quá"): trang "Dự án cụ thể" (page-project-detail.php) hau nhu MOI can deu cung toa
                // do (chung 1 toa nha) - bounds cua fitBounds() gan nhu bang 0 khien Google tu dong
                // zoom toi mac TOI DA (~21, sat den muc chi thay 1 vai toa nha) thay vi 1 khung nhin
                // tong quan khu vuc. Dung LAI y het co che da co san cho ban do Van phong (assets/js/
                // pplus-maps.js, nghe "bounds_changed" 1 LAN sau fitBounds() de ep tran zoom) - PHAI
                // qua "bounds_changed" (khong set zoom ngay sau fitBounds()) vi fitBounds() la thao
                // tac BAT DONG BO, doc getZoom() ngay sau do van tra ve gia tri CU truoc khi tinh
                // xong.
                google.maps.event.addListenerOnce(map, 'bounds_changed', function () {
                    if (map.getZoom() > 16) {
                        map.setZoom(16);
                    }
                });
            }
        }

        function clearMarkers() {
            Object.keys(markerRegistry).forEach(function (key) {
                markerRegistry[key].setMap(null);
                delete markerRegistry[key];
            });
        }

        addMarkers(markers, true);

        // --- Nut "Tìm kiếm khu vực này" (port isShowToolSearchMobile/searchToolMobile) ---
        var areaBtn = document.getElementById('pplus-search-area');
        var ready = false;
        google.maps.event.addListenerOnce(map, 'idle', function () {
            // idle dau tien (sau fitBounds/khoi tao) KHONG tinh la nguoi dung keo ban do.
            setTimeout(function () { ready = true; }, 600);
        });
        function showAreaBtn() {
            if (ready && areaBtn && document.documentElement.clientWidth <= 768) {
                areaBtn.style.display = 'block';
            }
        }
        map.addListener('dragend', showAreaBtn);
        map.addListener('zoom_changed', showAreaBtn);

        if (areaBtn) {
            areaBtn.addEventListener('click', function () {
                var b = map.getBounds();
                if (!b || !PPLUS_MAP_CONFIG.areaUrl) {
                    return;
                }
                var ne = b.getNorthEast();
                var sw = b.getSouthWest();
                var url = PPLUS_MAP_CONFIG.areaUrl
                    + (PPLUS_MAP_CONFIG.areaUrl.indexOf('?') > -1 ? '&' : '?')
                    + 'ne_lat=' + ne.lat() + '&ne_lng=' + ne.lng()
                    + '&sw_lat=' + sw.lat() + '&sw_lng=' + sw.lng()
                    + '&lang=' + encodeURIComponent(PPLUS_MAP_CONFIG.lang || 'vi');
                areaBtn.style.display = 'none';
                fetch(url, { credentials: 'same-origin' })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        clearMarkers();
                        addMarkers(data.markers || [], false);
                        var strip = stripEl();
                        if (strip) {
                            var list = strip.querySelector('.list-product-office');
                            if (list) {
                                list.innerHTML = data.html || '';
                            }
                            strip.scrollLeft = 0;
                        }
                    })
                    .catch(function () {
                        areaBtn.style.display = 'block';
                    });
            });
        }

        // Vuot dai the -> phong to marker cua the o giua (port touchendListMobile()).
        var strip = stripEl();
        if (strip) {
            var timer = null;
            strip.addEventListener('scroll', function () {
                clearTimeout(timer);
                timer = setTimeout(function () {
                    var center = strip.scrollLeft + strip.clientWidth / 2;
                    var best = null;
                    var bestDist = Infinity;
                    strip.querySelectorAll('.item-office').forEach(function (card) {
                        var dist = Math.abs(center - (card.offsetLeft + card.offsetWidth / 2));
                        if (dist < bestDist) {
                            bestDist = dist;
                            best = card;
                        }
                    });
                    if (best) {
                        setActiveMarker(best.getAttribute('data-marker-id'));
                    }
                }, 80);
            }, { passive: true });
        }
    }

    // Them vao (khong co trong ban goc - ban that co code y het muc dich nay trong common.js
    // ".hover-office" nhung bi loi vi doc "$(this).attr('id')" trong khi the chi co "data-id", nen
    // khong bao gio chay; hieu ung goc neu chay dung cung chi doi mau/dich chuyen nhe, khong phong
    // to). Dung "mouseover"/"mouseout" + closest() thay vi "mouseenter"/"mouseleave" vi 2 su kien
    // sau khong noi bot (khong dung duoc voi event delegation tren document).
    function bindListHoverToMarkers(markerRegistry) {
        document.addEventListener('mouseover', function (e) {
            var card = e.target.closest && e.target.closest('.item-office[data-marker-id]');
            if (!card) {
                return;
            }
            var marker = markerRegistry[card.getAttribute('data-marker-id')];
            if (marker) {
                marker.setActive(true);
            }
        });
        document.addEventListener('mouseout', function (e) {
            var card = e.target.closest && e.target.closest('.item-office[data-marker-id]');
            if (!card) {
                return;
            }
            var marker = markerRegistry[card.getAttribute('data-marker-id')];
            if (marker) {
                marker.setActive(false);
            }
        });
    }

    function loadGoogleMapsScript(apiKey, callback) {
        if (window.google && window.google.maps) {
            callback();
            return;
        }
        // Script co the dang duoc tai boi pplus-search-suggest.js (goi y o tim kiem) - doi no xong, khong tai lan 2
        // (Google bao loi "included the Google Maps JavaScript API multiple times").
        if (document.querySelector('script[src*="maps.googleapis.com/maps/api/js"]')) {
            var waited = 0;
            (function tick() {
                if (window.google && window.google.maps) {
                    callback();
                } else if ((waited += 100) <= 15000) {
                    setTimeout(tick, 100);
                }
            })();
            return;
        }
        var script = document.createElement('script');
        script.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent(apiKey);
        script.async = true;
        script.onload = callback;
        document.head.appendChild(script);
    }

    // SUA 22/9 (nguoi dung phat hien qua anh chup that: thanh tim kiem tran rong hon man hinh + menu
    // chan trang khong thay tren mobile that, KHONG PHAI rieng gia lap): tai script Google Maps NGAY
    // luc DOMContentLoaded (nhu ban cu) gay xung dot voi buoc emulator/trinh duyet dat lai kich thuoc
    // khung nhin mobile - "getComputedStyle().width" cua CA ".searching-tool" LAN ".footer-mobile"
    // (2 phan tu "position:fixed" khac, site-wide) do duoc CHIEU RONG/CAO CU (nhu man hinh PC) SAU
    // khi Maps tai xong, du "document.documentElement.clientWidth" van bao dung - xac nhan qua thu
    // tat han Maps roi do lai: het loi ngay. Ap dung LAI y het co che "tai tre" DA CO SAN cho ban Van
    // phong (assets/js/pplus-maps.js dong 382-450, "toi uu toc do 13/8/2026") ma trang nay TRUOC DAY
    // CHUA lam theo (chi co trong 1 dong comment, khong co code that): tren mobile (<=800px, dung y
    // het nguong cua ".searching-tool"), CHI tai Maps khi nguoi dung THUC SU bam nut "Bản đồ" (bat su
    // kien "pplus:map-view-opened" co san tu main.js) - vua sua loi tren, vua tiet kiem ~1MB cho da so
    // nguoi dung mobile khong bao gio bam xem ban do. Tren PC/tablet rong, van tai ngay (map hien san
    // trong che do xem song song, khong the doi "bam moi tai").
    function pplusLoadReferenceMap() {
        var container = document.getElementById('pplus-reference-map');
        if (!container || typeof PPLUS_MAP_CONFIG === 'undefined' || !PPLUS_MAP_CONFIG.key) {
            return;
        }
        var markers = [];
        try {
            markers = JSON.parse(container.getAttribute('data-markers') || '[]');
        } catch (e) {
            markers = [];
        }
        loadGoogleMapsScript(PPLUS_MAP_CONFIG.key, function () {
            initMap(container, markers);
        });
    }

    function pplusInitReferenceMapTrigger() {
        if (document.documentElement.clientWidth > 800) {
            pplusLoadReferenceMap();
        } else {
            document.addEventListener('pplus:map-view-opened', pplusLoadReferenceMap, { once: true });
        }
    }
    // SUA 22/9 LAN 2 (nguoi dung phat hien qua thu that: bam icon "Bản đồ" tren mobile KHONG ra gi
    // ca): "document.addEventListener('DOMContentLoaded', ...)" KHONG BAO GIO chay neu script nay
    // tai SAU khi su kien do da xay ra roi - ma script nay dang enqueue "in_footer=true" (xem
    // functions.php), nen thuc te LUON tai sau khi DOM da "interactive"/DOMContentLoaded da qua roi
    // tu lau, khien nhanh "else" (dang ky lang nghe "pplus:map-view-opened") KHONG BAO GIO duoc dang
    // ky - bam nut chi doi trang thai CSS (".invision-map") ma khong co gi tai Maps ca, loi thuc te
    // giong het loi da gap 1 lan voi ".footer-mobile" o cac file khac (cung 1 lop bai hoc: dung
    // "DOMContentLoaded" mu quang khong kiem tra "document.readyState" truoc). Kiem tra readyState
    // truoc, chi cho DOMContentLoaded khi THUC SU chua toi ("loading").
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', pplusInitReferenceMapTrigger);
    } else {
        pplusInitReferenceMapTrigger();
    }

    // SUA 22/9: PHP luon render ".box-map" voi class "invision-map" (mac dinh che do danh sach tren
    // mobile - xem page-reference.php, can de mac dinh vi server khong biet truoc kich thuoc man
    // hinh). Tren PC/tablet rong (>800px, dung y het nguong cua ".searching-tool"/".GroupMoblieActive"
    // trong layout.less), khoi phuc lai che do xem SONG SONG list+map that su (bo class nay di) -
    // thieu buoc nay se khien ban do LUON AN tren PC (regression thuc te phat hien khi tu kiem tra
    // man hinh rong sau khi them class mac dinh cho mobile).
    function pplusRestoreDesktopMapView() {
        // "document.documentElement.clientWidth" (khong phai "window.innerWidth") - do that voi
        // scrollbar/toolbar nen dung hon cho ngat responsive (cung 1 quy uoc da dung o cac diem khac
        // trong theme nay khi can biet chinh xac chieu rong khung nhin).
        if (document.documentElement.clientWidth > 800) {
            var el = document.querySelector('.box-map.invision-map');
            if (el && el.querySelector('#pplus-reference-map')) {
                el.classList.remove('invision-map');
            }
        }
    }
    // Cung 1 loi "DOMContentLoaded co the da qua roi luc script nay moi tai" nhu tren - kiem tra
    // readyState truoc khi dang ky.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', pplusRestoreDesktopMapView);
    } else {
        pplusRestoreDesktopMapView();
    }
})();
