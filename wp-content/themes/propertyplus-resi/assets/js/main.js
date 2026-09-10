// PropertyPlus theme JS - thay the cho cac ham showMenu()/ActionFooter()/ModalContainerPpus()
// truoc day nam trong teamplate-header.component.ts / teamplate-footer.component.ts (Angular).
// Dung jQuery (da enqueue qua officebase) de tuong thich voi CSS/hanh vi cu.
(function ($) {
  'use strict';

  $(function () {
    // Home page: slick carousel + lazy-load anh, giong ham BaseAction() trong home.component.ts
    if ($.fn.slick) {
      var slickArrows = {
        prevArrow: "<button type='button' aria-label='button' class='slick-prev pull-left'><i class='fa fa-angle-left' aria-hidden='false' title='prev'></i></button>",
        nextArrow: "<button type='button' aria-label='button' class='slick-next pull-right'><i class='fa fa-angle-right' aria-hidden='false' title='next'></i></button>",
      };
      if ($('.offices-col').length) {
        $('.offices-col').slick($.extend({
          slidesToShow: 3, slidesToScroll: 1, infinite: true, autoplaySpeed: 5000, autoplay: true, lazyLoad: 'ondemand',
          responsive: [
            { breakpoint: 1024, settings: { slidesToShow: 2, slidesToScroll: 1 } },
            { breakpoint: 768, settings: { slidesToShow: 2, slidesToScroll: 1 } },
            { breakpoint: 480, settings: { slidesToShow: 1, slidesToScroll: 1 } },
          ],
        }, slickArrows));
      }
      if ($('.item-blog').length) {
        $('.item-blog').slick($.extend({
          slidesToShow: 4, slidesToScroll: 1, infinite: true, autoplaySpeed: 10000, autoplay: true, lazyLoad: 'ondemand',
          responsive: [
            { breakpoint: 1024, settings: { slidesToShow: 2, slidesToScroll: 1 } },
            { breakpoint: 768, settings: { slidesToShow: 1, slidesToScroll: 1 } },
            { breakpoint: 480, settings: { slidesToShow: 1, slidesToScroll: 1 } },
          ],
        }, slickArrows));
      }
      if ($('.silder-newspapers').length && $(window).width() < 992) {
        $('.silder-newspapers').slick($.extend({ slidesToShow: 1, slidesToScroll: 1, infinite: true, autoplaySpeed: 10000, autoplay: true, lazyLoad: 'ondemand' }, slickArrows));
      }
    }
    // Swiper carousel "Video du an" - giong swiperParams trong home.component.ts BaseAction()
    var swiperEl = document.querySelector('.silder-video swiper-container');
    if (swiperEl && typeof swiperEl.initialize === 'function') {
      var isMobileWidth = $(window).width() < 992;
      var swiperParams = isMobileWidth ? {
        pagination: true,
        loop: true,
        centeredSlides: true,
        slidesPerView: 1,
        navigation: { nextEl: '.video-next', prevEl: '.video-prev' },
        autoplay: { delay: 5000 },
      } : {
        effect: 'coverflow',
        loop: true,
        centeredSlides: true,
        slidesPerView: 3,
        // QUAN TRONG: code Angular goc (home.component.ts) dat sai ten key la "coverflow"
        // thay vi "coverflowEffect" (ten dung theo Swiper API) - Swiper AM THAM BO QUA config
        // nay va dung mac dinh cua no (rotate:50, stretch:0, depth:100, modifier:1, slideShadows:true).
        // Da kiem chung bang cach doc truc tiep container.swiper.params tren localhost:4200.
        // KHONG tu sua thanh gia tri "dung theo y dinh code" - phai giu dung hanh vi THAT SU
        // dang chay, neu khong se lech voi ban Angular that.
        coverflowEffect: { rotate: 50, stretch: 0, depth: 100, modifier: 1, slideShadows: true },
        navigation: { nextEl: '.video-next', prevEl: '.video-prev' },
        autoplay: { delay: 5000 },
      };
      Object.assign(swiperEl, swiperParams);
      swiperEl.initialize();
    }


    // Toggle mobile menu (thay the showMenu())
    $('[data-pplus-toggle="menu"]').on('click', function () {
      $('.header-navigation').toggleClass('showMenu');
    });

    // Bam vao muc menu co submenu (Ha Noi/TPHCM, "Cho thue van phong dich vu", Tin tuc) tren
    // MOBILE se MO submenu thay vi dieu huong luon - thay the showChildrenMenu()/AddClassMenu()
    // ben Angular (teamplate-header.component.ts dong 135-142: preventDefault() + gan class "open"
    // cho <li> cha, CSS ".dropdown.open .dropdown-menu" da co san tu truoc nhung chua co gi gan
    // class "open" ca nen submenu KHONG BAO GIO mo duoc tren mobile - bug thuc te, khong phai
    // thieu CSS). Desktop KHONG doi (van dua vao bootstrap-hover-dropdown.js co san tu-hover-mo).
    $('.header-navigation li.dropdown > a.dropdown-toggle').on('click', function (e) {
      if ($(window).width() > 800) {
        return;
      }
      var $li = $(this).closest('li.dropdown');
      if (!$li.children('.dropdown-menu').length) {
        return;
      }
      e.preventDefault();
      $li.toggleClass('open');
    });

    // Toggle search box mobile (thay the showSearchBase())
    $('[data-pplus-toggle="search"]').on('click', function () {
      $('#box-search-base').toggleClass('show-search');
    });

    // Toggle dropdown chon catalog tren trang Tin tuc mobile (".dropdownListCatalog",
    // archive-news.php) - KHONG dung Bootstrap "data-bs-toggle=dropdown" (loi Popper, xem ghi
    // chu trong archive-news.php), tu toggle class "show" nhu cac dropdown khac trong theme.
    $(document).on('click', '[data-pplus-toggle="blog-catalog"]', function (e) {
      e.stopPropagation();
      $(this).attr('aria-expanded', function (i, val) { return val === 'true' ? 'false' : 'true'; });
      $(this).next('.dropdown-menu').toggleClass('show');
    });
    $(document).on('click', function (e) {
      if (!$(e.target).closest('.dropdownListCatalog').length) {
        $('.dropdownListCatalog .dropdown-menu').removeClass('show');
        $('.dropdownListCatalog [data-pplus-toggle="blog-catalog"]').attr('aria-expanded', 'false');
      }
    });

    // Toggle "che do Ban do" tren mobile (A1/A4 khi co #map) - thay the showMap() ben Angular
    // goc (search-base.component.ts): bam ".btnBackToMap-gr" (2 nut noi goc duoi-phai,
    // .GroupMoblieActive) de chuyen sang xem full ban do; bam ".action-close-map" (nut tron "<",
    // A2) hoac ".action-close-map-label" (nut "Quay lai" co chu, A1/A4 - cung 1 vi tri/co che,
    // chi doi giao dien theo phan hoi nguoi dung) - ca 2 deu NAM TRONG chinh .box-map, tu dong
    // an/hien theo cung opacity voi map - de quay lai danh sach.
    // Ngoai toggle ".invision-map" tren .box-map (CSS co san lo an/hien ca map lan nut "<" ben
    // trong), con phai AN LUON ".box-list.init" khi sang che do map: ".box-list-body" (con cua
    // no) co san z-index:99 that su (position:relative) trong khi .box-map o che do "map" khong
    // co z-index rieng - neu khong an list se DE LEN map (z-index:99 > auto), map hien "co ve
    // nhu bien mat" du opacity/position da dung (bug thuc te da gap khi tu kiem tra).
    $(document).on('click', '.box-map .action-close-map, .box-map .action-close-map-label, .GroupMoblieActive .btnBackToMap-gr', function () {
      var $mapOffice = $(this).closest('.map-office, .box-content').length
        ? $(this).closest('.map-office, .box-content')
        : $(this).closest('.row');
      var $boxMap = $mapOffice.find('.box-map');
      // Toi uu toc do (13/8/2026): tren mobile, script Google Maps CHI tai luc nguoi dung THUC SU
      // bam xem ban do (xem assets/js/pplus-maps.js) - "invision-map" dang co mat = dang o che do
      // danh sach (ban do an), bam nut nay se GO class do di = chuyen SANG xem ban do, dung luc can
      // bat dau tai. Kiem tra TRUOC khi toggleClass() de biet dung chieu (bam lai lan nua de quay ve
      // danh sach thi KHONG bat lai su kien nay).
      var isOpeningMap = $boxMap.hasClass('invision-map');
      $boxMap.toggleClass('invision-map');
      $mapOffice.find('.box-list.init').toggleClass('pplus-hide-mobile-map-mode');
      $mapOffice.find('.GroupMoblieActive').toggle();
      if (isOpeningMap) {
        document.dispatchEvent(new CustomEvent('pplus:map-view-opened'));
      }
    });

    // Nut "Hiển thị thông tin" (.btnShowThongTin) / "Quay lại" (.btnBackOffThongTin) - thay the
    // showThongTin() ben Angular goc (ca 2 nut CUNG goi 1 ham, chi toggle class "active" tren
    // "#web-content"). Angular goc mobile duoi 880px KHONG de bai viet SEO nam inline trong luong
    // cuon binh thuong - "#web-content" mac dinh nam NGOAI man hinh (position:fixed;left:-100vw)
    // va TRUOT VAO (left:0, CSS co san du transition, xem reference-index-component.css) khi bam
    // "Hiển thị thông tin", thanh 1 man hinh rieng full-width/full-height, dong lai bang nut "Quay
    // lại" - port truoc day don gian hoa sai thanh "cuon toi bai viet" (khong dung thiet ke goc,
    // theo phan hoi truc tiep tu nguoi dung sau khi doi chieu voi propertyplus.vn that).
    $('[data-pplus-toggle="thong-tin"]').on('click', function () {
      $('#web-content').toggleClass('active');
    });

    // Chuyen tab tinh/thanh trong panel "Bo loc" (parts/office-filter-panel.php) - thay the
    // *ngIf tab-pane Angular. Doi tab thi doi luon radio "province" dang checked cho dung tinh.
    $('.pplus-province-tab').on('click', function () {
      var index = $(this).data('province-index');
      // "aria-selected" (them 20/8/2026, xem ghi chu day du trong office-filter-panel.php) - dong bo
      // lai theo dung tab moi active, khong chi luc render dau (PHP chi biet trang thai BAN DAU).
      $('.pplus-province-tab').removeClass('active').attr('aria-selected', 'false');
      $(this).addClass('active').attr('aria-selected', 'true');
      $('.pplus-province-panel').hide().removeClass('show active');
      var $activePanel = $('.pplus-province-panel[data-province-index="' + index + '"]');
      $activePanel.show().addClass('show active').find('input[name="province"]').prop('checked', true);
      // Bo chon Quan/Phuong cua CAC tinh KHONG active (vd panel duoc server pre-check san theo
      // trang dang xem, xem $args['current'] trong office-filter-panel.php) - tranh khi submit
      // $form.serialize() gui kem dia diem cu (thuoc tinh cu) voi tinh moi vua chon, ra to hop
      // khong ton tai (bug thuc te da gap: doi tu "Ha Noi > Hoan Kiem" sang "TP.HCM" van con gui
      // kem "district=Hoàn Kiếm", loc ra 0 ket qua).
      $('.pplus-province-panel').not($activePanel).find('input[name="district"], input[name="ward"]').prop('checked', false);
    });

    // Toggle "Quan"/"Phuong" trong tung tinh (thay the isShowWard cua Angular) - chi anh huong
    // panel dang active (tranh doi nham panel tinh khac dang an).
    $('.pplus-mode-tab').on('click', function () {
      var $panel = $(this).closest('.pplus-province-panel');
      var mode = $(this).data('mode');
      $panel.find('.pplus-mode-tab').removeClass('active');
      $panel.find('.pplus-mode-tab[data-mode="' + mode + '"]').addClass('active');
      $panel.find('.pplus-mode-panel').hide();
      $panel.find('.pplus-mode-panel[data-mode="' + mode + '"]').show();

      // Bo chon dieu kien cua tab VUA AN (bug thuc te da gap: dang chon Quan Hoan Kiem o tab
      // "Quan", chuyen sang tab "Phuong" chon 1 phuong thuoc Dong Da -> form gui CA "district=Hoan
      // Kiem" (van con checked, chi bi an) LAN "ward=<phuong Dong Da>" cung luc, 2 dieu kien khac
      // quan nen loc ra 0 ket qua). Cung 1 nguyen tac voi doan xu ly doi tinh o tren (dong ~158).
      var otherMode = mode === 'phuong' ? 'quan' : 'phuong';
      $panel.find('.pplus-mode-panel[data-mode="' + otherMode + '"] input[name="district"], .pplus-mode-panel[data-mode="' + otherMode + '"] input[name="ward"]').prop('checked', false);
    });

    // Che do "Phuong": chon 1 quan o cot trai -> hien danh sach phuong tuong ung o cot phai
    // (dataListWard da co san trong cung response HeaderInvokeAsync, khong goi API rieng).
    $('.pplus-ward-district-select').on('click', function () {
      var $wardPanel = $(this).closest('.pplus-mode-panel');
      var index = $(this).data('district-index');
      $wardPanel.find('.pplus-ward-district-select').removeClass('active');
      $(this).addClass('active');
      $wardPanel.find('.pplus-ward-group').hide();
      $wardPanel.find('.pplus-ward-group[data-district-index="' + index + '"]').show();
    });

    // Toggle "Tim theo quan"/"Tim theo phuong" trong mega-menu header (thay the isShowWard cua
    // Angular, teamplate-header.component.html dong 124-129) - cung co che voi
    // .pplus-mode-tab/.pplus-ward-district-select o tren, chi khac ten class vi dung lai CSS
    // co san rieng cua header (.menu-quan-tab/.menu-ward-tab, khong phai .pplus-mode-panel).
    $('.pplus-header-mode-tab').on('click', function () {
      var $group = $(this).closest('.menu-quan-from');
      var mode = $(this).data('mode');
      $group.find('.pplus-header-mode-tab').removeClass('active');
      $(this).addClass('active');
      $group.find('.pplus-header-mode-panel').hide();
      $group.find('.pplus-header-mode-panel[data-mode="' + mode + '"]').show();
    });
    $('.pplus-header-ward-district').on('click', function () {
      var $wardTab = $(this).closest('.menu-ward-tab');
      var index = $(this).data('district-index');
      $wardTab.find('.pplus-header-ward-district').removeClass('active');
      $(this).addClass('active');
      $wardTab.find('.pplus-header-ward-item').hide();
      $wardTab.find('.pplus-header-ward-item[data-district-index="' + index + '"]').show();
    });

    // Nut chon 1 dieu kien (quan/phuong/loai van phong/hang toa nha) - bam lai chinh no thi
    // BO CHON (khac voi radio thuong), bam nut khac trong cung nhom thi tu bo chon nut truoc do.
    // Dung <input type="checkbox"> (giong initSearchOfficeType()/initSearchBuildingClass() ben
    // Angular that su dung checkbox chu khong phai radio de lam duoc dieu nay) thay vi radio.
    $('.pplus-toggle-check').on('click', function () {
      var name = $(this).attr('name');
      var $self = $(this);
      if ($self.prop('checked')) {
        $('.pplus-toggle-check[name="' + name + '"]').not($self).prop('checked', false);
      }
    });

    // Doi "Loai van phong" (Truyen thong/Tron goi) tren panel Bo loc - port initSearchOfficeType()
    // (search-base.component.ts dong 887-908 ben Angular goc): doi don vi tien te ($/VND, Truyen
    // thong niem yet $/m2 con Tron goi niem yet cu the tung goi theo VND), an/hien khoi 4 loai con
    // (chi Tron goi moi co), va dat lai gia mac dinh - port initPriceBase() (dong 868-882): Truyen
    // thong mac dinh 0-80, Tron goi de trong (khong co khoang gia chung). Bo chon loai con khi doi
    // SANG Truyen thong (Tron goi moi dung "office_type", port dong 903-905: reset officeType khi
    // doi khac projectType=1).
    $('input[name="project_type"]').on('change', function () {
      var $panel = $(this).closest('#box-search-base');
      var isService = $(this).val() === '1';
      $panel.find('.pplus-ranger-currency-usd').toggle(!isService);
      $panel.find('.pplus-ranger-currency-vnd').toggle(isService);
      $panel.find('.office-type-list').toggle(isService);
      if (isService) {
        $panel.find('input[name="price_from"], input[name="price_to"]').val('');
      } else {
        $panel.find('input[name="office_type"]').prop('checked', false);
        $panel.find('input[name="price_from"]').val('0');
        $panel.find('input[name="price_to"]').val('80');
      }
    });

    // 4 loai con cua "Van phong tron goi" (Van phong rieng/Cho ngoi co dinh/Cho ngoi linh hoat/
    // Dang ky kinh doanh) - dung <input type="radio"> that (khong phai .pplus-toggle-check) vi can
    // GIU checked khi bam nut KHAC trong nhom (hanh vi radio chuan), nhung radio thuong khong tu bo
    // chon duoc khi bam LAI chinh no dang checked - port onRadioClickOfficeType() (search-base.
    // component.ts dong 910-915): bam lai chinh muc dang chon se bo chon (cho phep "khong loc theo
    // loai con nao ca" sau khi da chon 1 loai).
    $('input[name="office_type"][data-pplus-deselectable]').on('click', function () {
      var $self = $(this);
      if ($self.data('pplus-was-checked')) {
        $self.prop('checked', false);
      }
    }).on('mousedown', function () {
      $(this).data('pplus-was-checked', $(this).prop('checked'));
    });

    // O gia "Tu"/"Den" tren panel Bo loc - chon toan bo noi dung ngay khi bam vao (port hieu ung
    // (click)="ActionBlacken($event)" ben Angular goc, dong 226/237 search-base.component.html):
    // gia mac dinh (0-80, xem office-filter-panel.php) la 1 gia tri THAT trong o, khong phai
    // placeholder - neu khong co hieu ung nay, nguoi dung phai tu bam dung truoc/sau chu so "0" roi
    // xoa tay moi go duoc so moi (kho dung, theo phan hoi thuc te) - chon san toan bo thi go so moi
    // se tu thay the ngay, khong can xoa tay.
    $('#box-search-base').on('click', '.ranger-input', function () {
      $(this).trigger('select');
    });

    // Tu dong chen dau "." phan cach hang nghin/trieu khi go so (port appNumberFormat directive
    // ben Angular goc, dung tren cung 2 o gia nay - search-base.component.html dong 224/235). Gia
    // tri THAT gui len server van la so thuan (khong dau ".") - xem cho strip lai truoc submit ben
    // duoi, vi PHP/API khong hieu dinh dang co dau cham nay.
    function pplusFormatThousands(digitsOnly) {
      return digitsOnly.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }
    $('#box-search-base .ranger-input').each(function () {
      this.value = pplusFormatThousands(this.value.replace(/\D/g, ''));
    });
    $('#box-search-base').on('input', '.ranger-input', function () {
      var input = this;
      var digitsBeforeCaret = input.value.slice(0, input.selectionStart).replace(/\D/g, '').length;
      var formatted = pplusFormatThousands(input.value.replace(/\D/g, ''));
      input.value = formatted;
      var seenDigits = 0;
      var caret = formatted.length;
      for (var i = 0; i < formatted.length; i++) {
        if (formatted.charAt(i) !== '.') {
          seenDigits++;
        }
        if (seenDigits === digitsBeforeCaret) {
          caret = i + 1;
          break;
        }
      }
      if (digitsBeforeCaret === 0) {
        caret = 0;
      }
      input.setSelectionRange(caret, caret);
    });

    // Nut "Tim kiem" tren panel Bo loc (parts/office-filter-panel.php) - Gia/Loai van phong/Hang/
    // Phuong la dieu kien loc thuan tuy tren TRANG DANG DUNG (giu nguyen URL path, chi doi query
    // string). Rieng Tinh/Quan (province/district) thi KHAC: A4 (page-reference.php) la 1 trang SEO
    // rieng cho DUNG 1 quan/huyen (URL/H1/breadcrumb gan chet voi quan do) - neu doi sang quan khac
    // ma van giu nguyen URL path cu se lam H1/breadcrumb hien SAI ten quan so voi ket qua thuc te
    // dang hien (bug thuc te: dang o "/ha-noi/hai-ba-trung/", doi Quan sang "Ba Dinh" trong panel,
    // bam Tim kiem -> H1 van "Quan Hai Ba Trung" nhung ket qua toan toa nha o Ba Dinh - nguoi dung
    // bao cao "san pham ra bi sai"). Nen: neu Tinh HOAC Quan submit khac voi Tinh/Quan CUA CHINH
    // trang dang dung (doc tu data-current-province/district, PHP gan san - xem office-filter-
    // panel.php), KHONG giu nguyen path nua - de submit GET tu nhien toi "/cho-thue-van-phong/"
    // (action goc cua form, trang tim kiem chung khong gan voi 1 quan cu the nen khong bi sai H1).
    $('#box-search-base').on('submit', function (e) {
      // Bo dau "." hien thi (xem pplusFormatThousands o tren) truoc khi gui - ca 2 nhanh (giu
      // nguyen URL qua serialize() ben duoi, lan submit GET tu nhien) deu can gia tri so thuan,
      // khong co dau cham.
      $(this).find('.ranger-input').each(function () {
        this.value = this.value.replace(/\./g, '');
      });
      if (!$('#result-box').length) {
        return;
      }
      var $form = $(this);
      var curProvince = $form.data('current-province') || '';
      var curDistrict = $form.data('current-district') || '';
      var submitProvince = $form.find('[name="province"]:checked').val() || '';
      var submitDistrict = $form.find('[name="district"]:checked').val() || '';
      if (curProvince !== '' && (submitProvince !== curProvince || submitDistrict !== curDistrict)) {
        // Tinh/Quan da doi khac trang dang dung - de submit tu nhien toi "/cho-thue-van-phong/".
        return;
      }
      e.preventDefault();
      window.location.href = window.location.pathname + '?' + $form.serialize();
    });

    // Thanh tim kiem trang chu (front-page.php): moi o "Loai van phong"/"Muc gia"/"Hang" la 1
    // o readonly, bam vao thi mo popup rieng cua no (.focus-popup) - dong cac popup khac dang
    // mo truoc, dong lai khi bam ra ngoai. Thay the (focus)="focusFunction()" ben Angular.
    // Bind tren ca ".control" (bao gom nhan label + input) chu KHONG chi rieng input
    // ".pplus-popup-trigger" - nguoi dung hay bam vao chu nhan "Loai van phong"/"Muc gia"/"Hang"
    // (nam TREN o input) va truoc day khong co phan hoi gi vi nhan khong co handler rieng.
    $('.pplus-popup-field .control').on('click', function (e) {
      e.stopPropagation();
      var $popup = $(this).closest('.search-option').find('.focus-popup');
      var isOpen = $popup.hasClass('pplus-popup-open');
      $('.focus-popup').removeClass('pplus-popup-open');
      if (!isOpen) {
        $popup.addClass('pplus-popup-open');
      }
    });
    $(document).on('click', function (e) {
      if (!$(e.target).closest('.focus-popup, .pplus-popup-field .control').length) {
        $('.focus-popup').removeClass('pplus-popup-open');
      }
    });

    // Cap nhat lai text hien thi trong o readonly khi chon trong popup (giong inputText.
    // textProjectType/ranKText ben Angular - search-home.component.ts).
    $('.pplus-office-type-radio').on('change', function () {
      if ($(this).prop('checked')) {
        $(this).closest('.pplus-popup-field').find('.pplus-popup-trigger').val($(this).closest('.office-type').find('label').text().trim());
      }
    });

    // An/hien 4 loai con (Van phong rieng/Cho ngoi co dinh/Cho ngoi linh hoat/Dang ky kinh doanh)
    // tren thanh tim kiem trang chu (front-page.php) khi doi "Van phong truyen thong"/"Van phong
    // tron goi" - cung y tuong voi handler input[name="project_type"] cua #box-search-base o tren.
    // Doi ky hieu tien te ($/VND) cung logic voi panel Bo loc (parts/office-filter-panel.php +
    // handler #box-search-base o tren): thanh tim kiem trang chu CO o gia rieng (".pplus-price-
    // display") nhung truoc day luon hardcode "$" du chon Van phong tron goi (niem yet VND) - bug
    // thuc te nguoi dung bao cao. Scope theo #office-form (khong phai .pplus-popup-field) vi o
    // "Muc gia" nam o field KHAC voi o "Loai van phong" trong cung form.
    function pplusUpdateHomePriceDisplay($form) {
      var isService = $form.find('input[name="project_type"]:checked').val() === '1';
      var $priceField = $form.find('.pplus-price-display').closest('.pplus-popup-field');
      var from = $priceField.find('.pplus-price-from').val();
      var to = $priceField.find('.pplus-price-to').val();
      var currency = isService ? 'VND' : '$';
      $priceField.find('.pplus-price-display').val('Từ: ' + (from || '0') + currency + ' - Đến: ' + (to || '0') + currency);
    }
    $('#office-form input[name="project_type"]').on('change', function () {
      var $form = $(this).closest('#office-form');
      var $field = $(this).closest('.pplus-popup-field');
      var isService = $(this).val() === '1';
      $field.find('.office-type-list').toggle(isService);
      if (!isService) {
        $field.find('input[name="office_type"]').prop('checked', false);
      }
      $form.find('.pplus-ranger-currency-usd').toggle(!isService);
      $form.find('.pplus-ranger-currency-vnd').toggle(isService);
      pplusUpdateHomePriceDisplay($form);
    });
    $('.pplus-price-from, .pplus-price-to').on('keyup change', function () {
      pplusUpdateHomePriceDisplay($(this).closest('#office-form'));
    });
    $('.pplus-rating-display').closest('.pplus-popup-field').find('input[name="rating_level"]').on('change', function () {
      var $field = $(this).closest('.pplus-popup-field');
      if ($(this).prop('checked')) {
        $field.find('.pplus-rating-display').val($(this).closest('.search-rating').find('.class-name').text().trim());
      } else if ($field.find('input[name="rating_level"]:checked').length === 0) {
        $field.find('.pplus-rating-display').val('');
      }
    });

    // Mo modal-container-ppus (thay the ActionFooter()/ActionChildrenFooter()/ActionPost())
    $('[data-pplus-open-modal]').on('click', function (e) {
      e.preventDefault();
      var target = $(this).data('pplus-open-modal');
      $('.modal-container-ppus').removeClass('out').removeClass('two');
      $('body').removeClass('modal-active');
      $('#' + target).removeClass('out').removeClass('two').addClass('two');
      $('body').addClass('modal-active');
    });

    // Dong modal (thay the CancelContainer4())
    $('[data-pplus-close-modal]').on('click', function () {
      var target = $(this).data('pplus-close-modal');
      $('#' + target).removeClass('two').addClass('out');
      $('body').removeClass('modal-active');
    });

    // Bam 1 slide "Video dự án" (trang chu) - mo modal xem video, thay the openModalVideo()
    // (home.component.ts): bom san "content" (HTML iframe YouTube tu API, xem front-page.php
    // data-video-content) vao modal roi mo bang chinh co che modal-container-ppus co san o tren.
    $(document).on('click', '.js-open-video', function () {
      var content = $(this).attr('data-video-content') || '';
      $('#modal-video-player .js-video-content').html(content);
      $('.modal-container-ppus').removeClass('out').removeClass('two');
      $('body').removeClass('modal-active');
      $('#modal-video-player').removeClass('out').removeClass('two').addClass('two');
      $('body').addClass('modal-active');
    });

    // Dong modal video - xoa rong noi dung (iframe) de video ngung phat ngam, khong chi an di.
    $(document).on('click', '#modal-video-player [data-pplus-close-modal]', function () {
      $('#modal-video-player .js-video-content').empty();
    });
    $('#modal-video-player .modal-background').on('click', function (e) {
      if (e.target === this) {
        $('#modal-video-player .js-video-content').empty();
      }
    });

    // Lightbox anh trang chi tiet van phong (A2) - thay the viewAllPhotos()/showPhotos() cua
    // detail.component.ts. showPhotos() BAN THAN da co san trong Common/Scripts/common.js (nam
    // trong officebase bundle, enqueue toan site) - o day chi can doc lai du lieu tu attribute
    // data-photos (JSON, xem page-office-detail.php) va goi dung ham do, KHONG viet lai lightbox.
    // ".photoContainer-mobile" la ANH EM (khong phai con) cua ".photoContainer" - "data-photos" chi
    // gan tren ".photoContainer", nen phai tim qua ".basic" (cha chung cua ca 2) thay vi .closest()
    // truc tiep tu ".photoContainer" (se khong ra gi khi bam anh trong khoi mobile).
    $('.photoContainer .photoLink, .photoContainer .btn-show-photos, .photoContainer-mobile .photoLink').on('click', function (e) {
      var $container = $(this).closest('.basic').find('.photoContainer').first();
      var photos = $container.data('photos');
      if (!photos || !photos.length || typeof showPhotos !== 'function') {
        return; // khong co du lieu/thu vien -> giu hanh vi mac dinh (mo anh o tab moi qua href)
      }
      e.preventDefault();
      var index = parseInt($(this).data('photo-index'), 10) || 0;
      showPhotos(photos, index);
    });

    // Nut tron "<" quay lai trang truoc, goc tren-trai trang chi tiet van phong (A2), chi hien
    // tren mobile - thay the backClicked() ben Angular goc (detail.component.ts: this._location.
    // back()).
    $('.url-callBack').on('click', function () {
      window.history.back();
    });

    // Hien 1 khoi thong bao THUONG TRUC (khong tu an nhu toastr) ngay trong chinh form vua gui -
    // theo yeu cau nguoi dung sau khi doi chieu voi ban chinh propertyplus.vn that (ban chinh
    // chuyen han sang 1 trang rieng bao ket qua, khong tu an). O day KHONG chuyen trang (giu ngu
    // canh nguoi dung dang xem, theo quyet dinh da chot), thay bang cach an form + hien 1 khoi
    // ".pplus-advise-success" cung cho, nguoi dung tu bam "Dong" (PPLUS_ADVISE_CLOSE) khi doc xong.
    // Neu form nam trong modal (footer #CompleteModalForm), nut "Dong" nay CUNG dong luon modal -
    // dung LAI chinh co che class "two"/"out" cua handler "[data-pplus-close-modal]" o duoi, tranh
    // 2 noi giu 2 logic dong modal khac nhau roi lech nhau dan.
    function pplusShowAdviseSuccess($form, message) {
      $form[0].reset();
      var $success = $form.next('.pplus-advise-success');
      if (!$success.length) {
        $success = $(
          '<div class="pplus-advise-success">' +
            '<div class="pplus-advise-success-icon">&#10003;</div>' +
            '<div class="pplus-advise-success-text"></div>' +
            '<button type="button" class="btn btn-primary pplus-advise-success-close">' + PPLUS_ADVISE_CLOSE + '</button>' +
          '</div>'
        );
        $form.after($success);
      }
      $success.find('.pplus-advise-success-text').text(message);
      $form.hide();
      $success.show();
    }
    $(document).on('click', '.pplus-advise-success-close', function () {
      var $success = $(this).closest('.pplus-advise-success');
      $success.hide();
      $success.prev('.pplus-advise-form').show();
      var $modalContainer = $(this).closest('[data-pplus-modal]');
      if ($modalContainer.length) {
        $modalContainer.removeClass('two').addClass('out');
        $('body').removeClass('modal-active');
      }
    });

    // Submit form "Gui yeu cau tu van" / "Lien he" (A2/A3/A4 + modal footer #CompleteModalForm) -
    // thay the IssueNewApartment() ben Angular. Ca 4 form deu cung 1 class "pplus-advise-form" va
    // cung ten field (OwnerName/OwnerEmail/OwnerMobile/AdviseContent/Acreage) nen dung chung 1
    // handler, POST toi REST route WordPress rieng (inc/advise-form.php) - route do moi la noi
    // proxy sang .NET that, xem chi tiet trong file do.
    $(document).on('submit', '.pplus-advise-form', function (e) {
      e.preventDefault();
      var $form = $(this);
      var $submitBtn = $form.find('button[type="submit"], button.pplus-advise-submit');

      if (!$form.find('[name="OwnerEmail"]').val() || !$form.find('[name="OwnerMobile"]').val()) {
        toastr.error('Bạn chưa nhập Email hoặc Số điện thoại', 'Cảnh báo');
        return;
      }

      // Hoi xac nhan TRUOC khi gui - dung nguyen ban chinh that (SweetAlert "Ban co chac chan
      // muon gui thong tin ho tro?"), nhung dung window.confirm() thay vi SweetAlert (thu vien
      // nay da bi go khoi theme tu truoc vi khong con noi nao khac dung toi).
      if (!window.confirm(PPLUS_ADVISE_CONFIRM)) {
        return;
      }

      var payload = {
        OwnerName: $form.find('[name="OwnerName"]').val() || '',
        OwnerEmail: $form.find('[name="OwnerEmail"]').val() || '',
        OwnerMobile: $form.find('[name="OwnerMobile"]').val() || '',
        AdviseContent: $form.find('[name="AdviseContent"]').val() || '',
        Acreage: $form.find('[name="Acreage"]').val() || '',
        FavoriteLink: window.location.href,
        // Bug thuc te phat hien khi kiem thu gui form that (13/8/2026): inc/advise-form.php KHONG
        // the tu doan dung ngon ngu (URL request AJAX luon la "/wp-json/...", khong bao gio co
        // "/en") - phai gui kem ngon ngu cua TRANG HIEN TAI (da xac dinh dung luc render, dung
        // chung bien PPLUS_LOCATION_LANG co san - xem inc/location-search.php cung 1 co che nay).
        lang: (typeof PPLUS_LOCATION_LANG !== 'undefined') ? PPLUS_LOCATION_LANG : 'vi'
      };

      $submitBtn.prop('disabled', true);
      $.ajax({
        url: PPLUS_ADVISE_URL,
        method: 'POST',
        contentType: 'application/json; charset=utf-8',
        data: JSON.stringify(payload)
      }).done(function (rs) {
        if (rs && rs.success) {
          pplusShowAdviseSuccess($form, rs.message || 'Gửi yêu cầu thành công!');
        } else {
          toastr.error((rs && rs.message) || 'Gửi yêu cầu thất bại, vui lòng thử lại sau.', 'Cảnh báo');
        }
      }).fail(function () {
        toastr.error('Gửi yêu cầu thất bại, vui lòng thử lại sau.', 'Cảnh báo');
      }).always(function () {
        $submitBtn.prop('disabled', false);
      });
    });

    // Click ra ngoai (vung nen mo) thi dong (thay the ModalContainerPpus()).
    // QUAN TRONG: ".modal-background" (display:table-cell, width/height 100%) phu KIN toan bo
    // "[data-pplus-modal]" - click chuot that su LUON trung ".modal-background" (hoac sau hon,
    // ".modal" ben trong no), KHONG BAO GIO trung dung phan tu "[data-pplus-modal]" nhu ban dau
    // gan nham (check "e.target === this" o do khong bao gio dung voi click that). Phai bat
    // click tren chinh ".modal-background" (khong phai con no) moi la "click ra ngoai" dung nghia.
    $('[data-pplus-modal] .modal-background').on('click', function (e) {
      if (e.target !== this) {
        return;
      }
      var $container = $(this).closest('[data-pplus-modal]');
      if ($container.attr('id') === 'modal-container4') {
        return;
      }
      $container.removeClass('two').addClass('out');
      $('body').removeClass('modal-active');
    });

    // "Địa điểm" autocomplete (o input name="search_key", dung chung boi front-page.php +
    // parts/office-filter-panel.php) - goi y "Tên dự án"/"Tên đường" khi go text, xem
    // inc/location-search.php cho logic xep hang/goi API phia server. Debounce 300ms de tranh
    // goi API lien tuc tung phim bam.
    if (typeof PPLUS_LOCATION_SEARCH_URL !== 'undefined') {
      var pplusLocationTimer = null;
      var pplusLocationXhr = null;
      var pplusPinSvg = '<svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M12 2C7.86 2 4.5 5.36 4.5 9.5c0 5.5 6.5 11.5 7 12 .3.28.7.28 1 0 .5-.5 7-6.5 7-12C19.5 5.36 16.14 2 12 2zm0 10.5a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/></svg>';

      // Cache trong bo nho trinh duyet (session hien tai) theo dung "q" (khong phan biet hoa/
      // thuong, da trim) - backend .NET dang sau route nay LUON ~1000ms/lan goi MOI (xem ghi chu
      // pplus_location_search_fetch() trong inc/location-search.php), nen di tim lai CHINH XAC 1 tu
      // khoa da go truoc do (vd go roi xoa roi go lai, hoac quay lai sua) se duoc tra loi TUC THI (0ms,
      // khong qua debounce/mang) thay vi cho lai tu dau. Khong can gioi han kich thuoc: so tu khoa
      // khac nhau nguoi dung go trong 1 phien lam viec thuc te rat nho.
      var pplusLocationCache = {};

      // ===== Autocomplete "Địa điểm" goi THANG .NET (khong qua /wp-json/) de tranh tax bootstrap WP +
      // 503 moi keystroke - nhanh nhu Angular. pplusLocationTransform DUOI khop y het logic server cua
      // dev kia (inc/location-search.php pplus_handle_location_search): full-text 1 call -> dung thang
      // projectNameList + streetList, dedup + gan tinh tu locationLat, top 3. KHONG tokenize/score. =====
      function pplusLocProvince(lat) {
        lat = parseFloat(lat) || 0;
        if (lat <= 0) { return { label: '', alias: '' }; }
        return lat >= 15
          ? { label: (PPLUS_LOCATION_LANG === 'en' ? 'Hanoi' : 'Hà Nội'), alias: 'ha-noi' }
          : { label: (PPLUS_LOCATION_LANG === 'en' ? 'Ho Chi Minh City' : 'TP. Hồ Chí Minh'), alias: 'ho-chi-minh' };
      }
      function pplusLocationTransform(raw) {
        var data = (raw && raw.result && raw.result.data) || {};
        var projects = [], seenP = {};
        (data.projectNameList || []).forEach(function (it) {
          var name = (it.projectName || '').trim(), id = parseInt(it.projectId, 10) || 0;
          if (!name || id <= 0 || seenP[id]) { return; }
          seenP[id] = true;
          var pv = pplusLocProvince(it.locationLat);
          projects.push({ name: name, id: id, province: pv.label, provinceAlias: pv.alias });
        });
        var streets = [], seenS = {};
        (data.streetList || []).forEach(function (it) {
          var name = (it.street || '').trim();
          if (!name) { return; }
          var pv = pplusLocProvince(it.locationLat);
          var key = name + '|' + pv.label; // dedup ten+tinh: 2 duong trung ten o 2 tinh giu ca 2
          if (seenS[key]) { return; }
          seenS[key] = true;
          streets.push({ name: name, province: pv.label, provinceAlias: pv.alias });
        });
        return { projects: projects.slice(0, 3), streets: streets.slice(0, 3) };
      }

      function pplusLocationRender($suggest, rs) {
        $suggest.find('.pplus-location-suggest-loading').hide();
        pplusLocationFillGroup($suggest, 'projects', rs && rs.projects);
        pplusLocationFillGroup($suggest, 'streets', rs && rs.streets);
        var hasAny = (rs && rs.projects && rs.projects.length) || (rs && rs.streets && rs.streets.length);
        $suggest.toggle(!!hasAny);
      }

      function pplusLocationBuildSuggest() {
        return $(
          '<div class="pplus-location-suggest">' +
            // "pplus-location-suggest-loading" - API goi y (Office/_GetFilterGroupList phia .NET)
            // ~1000ms/lan goi BAT KE tu khoa (xem inc/location-search.php) - hien ngay luc bat dau
            // goi (KHONG doi ket qua) de nguoi dung thay dang xu ly thay vi tuong o dang "dung im"/
            // hong trong luc cho, giam cam giac "chậm" du thoi gian goi thuc te khong doi.
            '<div class="pplus-location-suggest-loading"><span class="pplus-location-spinner"></span>' + $('<div/>').text(PPLUS_LOCATION_LABEL_LOADING).html() + '</div>' +
            '<div class="pplus-location-suggest-group" data-group="projects">' +
              '<div class="pplus-location-suggest-title">' + pplusPinSvg + '<span>' + $('<div/>').text(PPLUS_LOCATION_LABEL_PROJECT).html() + '</span></div>' +
              '<ul></ul>' +
            '</div>' +
            '<div class="pplus-location-suggest-group" data-group="streets">' +
              '<div class="pplus-location-suggest-title">' + pplusPinSvg + '<span>' + $('<div/>').text(PPLUS_LOCATION_LABEL_STREET).html() + '</span></div>' +
              '<ul></ul>' +
            '</div>' +
          '</div>'
        );
      }

      // "projects": [{name, id, province, provinceAlias}], "streets": [{name, province, provinceAlias}] -
      // <li data-name="..." data-province-alias="..."> giu TEN GOC (khong co hau to tinh/thanh) +
      // alias tinh THAT (dung de tu dong doi tab tinh, xem pplusLocationSelect() ben duoi). "province"
      // (nhan hien thi) CHI ghep vao CHU HIEN THI trong dropdown ("- Tỉnh/Thành", theo yeu cau nguoi
      // dung 15/8/2026: phan biet 2 duong/du an TRUNG TEN o 2 tinh khac nhau), KHONG duoc dua vao gia
      // tri submit - se lam pplus_resolve_street_code() (inc/api-client.php) khop that bai vi can
      // dung CHINH XAC ten duong tu du lieu that.
      function pplusLocationFillGroup($suggest, groupName, items) {
        var $group = $suggest.find('[data-group="' + groupName + '"]');
        var $list = $group.find('ul').empty();
        if (!items || !items.length) {
          $group.hide();
          return;
        }
        items.forEach(function (item) {
          var displayText = item.province ? (item.name + ' - ' + item.province) : item.name;
          var $li = $('<li/>').text(displayText).attr('data-name', item.name).attr('data-province-alias', item.provinceAlias || '');
          if (groupName === 'projects') {
            $li.attr('data-project-id', item.id);
          }
          $list.append($li);
        });
        $group.show();
      }

      // Chon 1 goi y -> dien "search_key" (hien thi) + hidden "project_id" hoac "street_name"
      // (dieu kien loc THAT, xem page-office-list.php/page-reference.php doc 2 field nay). Go lai
      // tay (khong qua click) -> XOA CA HAI, tranh submit nham id/ten cu voi text nguoi dung vua
      // sua (vd chon "Kim Mã" roi go them "abc" - khong con dung y "loc theo dung du an/duong do"
      // nua, phai quay lai tim goi y binh thuong).
      //
      // Bug thuc te nguoi dung bao cao (15/8/2026): dang o tab "Hà Nội", chon 1 goi y duong/du an
      // THAT SU thuoc TPHCM (xac nhan qua "provinceAlias" moi them, xem inc/location-search.php) -
      // truoc day CHI dien search_key/street_name/project_id, KHONG dong bo lai tab tinh dang active
      // tren panel "Bo loc" -> submit van giu nguyen radio province="Hà Nội" cu, loc SAI hoan toan
      // sang van phong Ha Noi thay vi TPHCM (goi y dung, ket qua sai). Sua bang cach: neu
      // provinceAlias khac tab dang active, TU CLICK lai dung tab tinh tuong ung TRUOC khi dien cac
      // field con lai - dung LAI nguyen handler click co san cua tab (".pplus-province-tab", xem
      // ngay tren) de tu dong ca doi radio "province" LAN xoa Quan/Phuong cu cua tinh khac, khong
      // lam rieng lai logic do o day.
      function pplusLocationSelect($input, name, projectId, provinceAlias) {
        var $form = $input.closest('form');
        if (provinceAlias) {
          var $targetTab = $('.pplus-province-tab[data-province-alias="' + provinceAlias + '"]');
          if ($targetTab.length && !$targetTab.hasClass('active')) {
            $targetTab.trigger('click');
          }
        }
        $input.val(name);
        $form.find('input[name="project_id"]').val(projectId || '');
        $form.find('input[name="street_name"]').val(projectId ? '' : name);
      }

      function pplusLocationClearSelection($input) {
        var $form = $input.closest('form');
        $form.find('input[name="project_id"]').val('');
        $form.find('input[name="street_name"]').val('');
      }

      $(document).on('focus input', 'input[name="search_key"]', function (e) {
        var $input = $(this);
        var $wrap = $input.closest('.control');
        var $suggest = $wrap.data('pplusLocationSuggest');

        if (!$suggest) {
          $suggest = pplusLocationBuildSuggest();
          $wrap.append($suggest).data('pplusLocationSuggest', $suggest);
          $suggest.on('click', 'li', function () {
            var $li = $(this);
            var isProject = $li.closest('[data-group]').data('group') === 'projects';
            // "data-name" (ten GOC, khong co hau to "- Tỉnh/Thành") - KHONG dung $li.text() nua
            // (gio la chu HIEN THI da ghep them tinh/thanh, xem pplusLocationFillGroup() o tren).
            pplusLocationSelect($input, $li.attr('data-name'), isProject ? $li.attr('data-project-id') : null, $li.attr('data-province-alias'));
            $suggest.hide();
          });
        }

        if (e.type === 'input') {
          pplusLocationClearSelection($input);
        }

        var q = $.trim($input.val());
        clearTimeout(pplusLocationTimer);
        // "< 2" (truoc day "< 1") - 1 ky tu qua ngan de loc co ich (backend chi tra ~6 ket qua/
        // nhom nen ra toan ket qua khong lien quan) nhung VAN ton 1 lan goi ~1000ms toi backend cham
        // - bo qua luon, khong dang goi (xem ghi chu doi xung phia server trong inc/location-
        // search.php).
        if (q.length < 2) {
          $suggest.hide();
          return;
        }

        // Cache-hit: da tung go DUNG cau nay trong phien nay -> hien KET QUA NGAY (0ms, bo qua ca
        // debounce 300ms LAN vong mang), khong can cho lai. Xem khai bao pplusLocationCache o tren.
        var cacheKey = PPLUS_LOCATION_LANG + '|' + q.toLowerCase();
        if (pplusLocationCache.hasOwnProperty(cacheKey)) {
          if (pplusLocationXhr) {
            pplusLocationXhr.abort();
          }
          pplusLocationRender($suggest, pplusLocationCache[cacheKey]);
          return;
        }

        pplusLocationTimer = setTimeout(function () {
          if (pplusLocationXhr) {
            pplusLocationXhr.abort();
          }
          // Hien trang thai "Đang tìm..." NGAY (truoc khi co ket qua) - xem ghi chu
          // pplusLocationBuildSuggest(). An 2 nhom ket qua CU (neu co tu lan tim truoc) trong luc
          // cho, tranh nguoi dung tuong nham ket qua cu la ket qua cho tu khoa moi.
          // ".css('display', 'flex')" (khong dung ".show()") - phan tu nay CSS dinh "display:none"
          // qua stylesheet (khong phai inline), jQuery ".show()" se tu doan display mac dinh cua
          // THE <div> (block) thay vi "flex" ta muon (spinner + chu nam ngang hang) - dat thang gia
          // tri can, tranh phu thuoc vao logic doan cua jQuery.
          $suggest.find('.pplus-location-suggest-group').hide();
          $suggest.find('.pplus-location-suggest-loading').css('display', 'flex');
          $suggest.show();
          pplusLocationXhr = $.ajax({
            // Goi THANG .NET (giong Angular + khop logic server dev kia): full-text q, 1 call.
            url: PPLUS_LOCATION_API_BASE + '/' + PPLUS_LOCATION_LANG + '/Office/_GetFilterGroupList',
            method: 'GET',
            data: { FilterText: q }
          }).done(function (raw) {
            var rs = pplusLocationTransform(raw);
            pplusLocationCache[cacheKey] = rs;
            pplusLocationRender($suggest, rs);
          }).fail(function (xhr) {
            // "abort" (bi huy boi 1 lan go tiep theo, xem pplusLocationXhr.abort() o tren) khong
            // phai loi that - lan goi MOI se tu lo hien/an trang thai loading, khong dong gi o day
            // ca de tranh nhap nhay. Loi THAT (mang/API that bai) moi can an "Đang tìm..." di, tranh
            // ket dinh mai tren man hinh.
            if (xhr.statusText !== 'abort') {
              $suggest.hide();
            }
          });
        }, 300);
      });

      // Click ra ngoai thi dong goi y - khong dung "blur" (chay TRUOC "click" tren <li>, se an
      // mat goi y truoc khi kip ghi nhan lua chon cua nguoi dung).
      $(document).on('click', function (e) {
        var $target = $(e.target);
        if ($target.closest('.pplus-location-suggest').length || $target.is('input[name="search_key"]')) {
          return;
        }
        $('.pplus-location-suggest').hide();
      });
    }
  });
})(jQuery);
