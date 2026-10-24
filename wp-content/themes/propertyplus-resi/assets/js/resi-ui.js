/**
 * Tuong tac header/footer cho ban Resi WordPress - viet thuan jQuery, thay cho cac ham
 * showMenu()/showChildrenMenu()/ActionFooter()/ModalContainerPpus() cua 2 component Angular
 * (teamplate-header/teamplate-footer). Hook qua data-attribute do parts/header.php + parts/footer.php
 * dat san (data-pplus-*), khong dung Angular event binding.
 */
(function ($) {
    'use strict';

    function isMobile() {
        return window.matchMedia && window.matchMedia('(max-width: 1024px)').matches; // 24/10/2026: cong tac mobile/PC duy nhat 1024/1025 (giong ban Van phong)
    }

    $(function () {
        // Toggle menu mobile: bat/tat class .showMenu tren .header-navigation ([data-pplus-menu]).
        $(document).on('click', '[data-pplus-toggle-menu]', function (e) {
            e.preventDefault();
            $('[data-pplus-menu]').toggleClass('showMenu');
        });

        // Tap mo/dong submenu (chi tren mobile - desktop dua vao hover cua bootstrap-hover-dropdown).
        $(document).on('click', '[data-pplus-submenu-toggle]', function (e) {
            if (!isMobile()) {
                return; // desktop: giu nguyen hanh vi link/hover
            }
            e.preventDefault();
            $(this).closest('[data-pplus-submenu]').toggleClass('open');
        });

        // Mo modal goi/mail/chat (ActionFooter): xoa trang thai cu, them .two vao modal dich.
        $(document).on('click', '[data-pplus-open-modal]', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var id = $(this).attr('data-pplus-open-modal');
            $('.modal-container-ppus').removeClass('out two');
            $('#' + id).removeClass('out').addClass('two');
            $('body').addClass('modal-active');
        });

        // Dong modal bang nut Huy ([data-pplus-close-modal]).
        $(document).on('click', '[data-pplus-close-modal]', function (e) {
            e.preventDefault();
            var id = $(this).attr('data-pplus-close-modal');
            $('#' + id).removeClass('two').addClass('out');
            $('body').removeClass('modal-active');
        });

        // Click ra nen (ModalContainerPpus): dong khi bam vao chinh container hoac .modal-background.
        $(document).on('click', '[data-pplus-modal]', function (e) {
            if (e.target === this || $(e.target).hasClass('modal-background')) {
                $(this).removeClass('two').addClass('out');
                $('body').removeClass('modal-active');
            }
        });
        // Nut "Tim kiem" tren footer-mobile / o nhap + nut "Bo loc" tren ".searching-tool": logic da
        // chuyen sang assets/js/pplus-search-mobile.js (mo panel #search-tab-mobile theo dung buoc).
    });
})(jQuery);
