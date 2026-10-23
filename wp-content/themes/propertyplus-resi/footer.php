<?php if (!defined('ABSPATH')) exit; ?>
</main>
<?php // 23/10/2026: footer + popup lien he/tim kiem/yeu thich deu nam duoi man hinh dau hoac an cho toi khi bam -> hoan moi anh lazy toi load+1s / tuong tac dau tien (xem pplus_defer_lazy_imgs()). Dong buffer ngay truoc wp_footer(). ?>
<?php ob_start(); ?>
<footer class="pplus-footer">
    <?php get_template_part('parts/footer'); ?>
</footer>
<?php
// Modal Yeu thich / So sanh - nhung san toan site (nut tren card van phong mo cac modal nay).
get_template_part('parts/favourite-modals');

// #searchModal - popup tim kiem, nut mo da co san tu truoc trong pplus_render_suggest_header()
// (".btn-modal-search") nhung modal CHUA TUNG duoc render o dau (bug "written but never wired" -
// xem chi tiet trong parts/search-modal.php). Dat toan site giong 2 modal tren, vi nut mo cung nam
// site-wide trong suggest-header.
get_template_part('parts/search-modal');

// Thanh tim kiem mobile (.searching-tool) + panel tim kiem mobile 2 buoc (#search-tab-mobile) - ban
// mobile cua #searchModal, ban that <app-search-base> co mat o gan nhu moi trang; xem
// parts/search-mobile.php.
get_template_part('parts/search-mobile');

// Modal video/bai viet dung chung (".js-open-video" + "data-video-content" -> do vao
// ".js-video-content" roi mo bang co che modal-container-ppus chung, xem assets/js/main.js dong
// 484-499) - CO SAN tu truoc trong main.js nhung CHUA co PHP nao dung toi (dinh danh cho 1 khoi
// "Video du an" trang chu chua duoc xay), lan dau duoc dung that o day boi cot video/bai viet cua
// trang Dich vu (page-service.php, ._blog-item.js-open-video). Dat san toan site (khong rieng
// page-service.php) de trang nao khac sau nay can toi cung dung lai duoc, khong phai tao modal moi.
?>
<div class="modal-container-ppus" id="modal-video-player" data-pplus-modal>
    <div class="modal-background">
        <div class="modal">
            <div class="modal-videos modal-body">
                <button type="button" class="close" data-pplus-close-modal="modal-video-player" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <div class="video-content js-video-content"></div>
            </div>
        </div>
    </div>
</div>
<?php
echo pplus_defer_lazy_imgs((string) ob_get_clean());
wp_footer();
?>
</body>
</html>
