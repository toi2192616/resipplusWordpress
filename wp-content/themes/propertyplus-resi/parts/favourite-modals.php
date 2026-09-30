<?php
/**
 * Cac modal cho tinh nang Yeu thich - port tu create-farourite / list-farourite (Angular). Nhung
 * san toan site (parts/footer.php) vi nut "Yeu thich" nam tren card van phong o nhieu trang
 * (office-list, reference, detail). JS pplus-favourite.js dieu khien mo/dong + do noi dung dong
 * (danh sach bo suu tap) vao day. Markup modal tu viet (khong dung ng-bootstrap), CSS o
 * favourite-component.css.
 *
 * Ban goc (Dev A) co them 1 modal "Danh sach so sanh" o day - da BO khi merge 2 nhanh Dev A/Dev B
 * (giu lai tinh nang "So sanh" cua Dev B, xem parts/compare-modal.php + assets/js/compare.js).
 */
if (!defined('ABSPATH')) exit;
?>
<!-- Modal: Tao bo suu tap moi -->
<div class="pplus-modal modal-create" id="pplus-modal-create" aria-hidden="true">
    <div class="pplus-modal-backdrop" data-pplus-close></div>
    <div class="modal-dialog">
        <div class="modal-header">
            <div class="icon-create-office"><i class="fa fa-plus"></i></div>
            <div class="modal-title"><?php echo esc_html(pplus_t('Tạo bộ sưu tập mới', 'Create new collection')); ?></div>
            <button type="button" class="pplus-modal-x" data-pplus-close aria-label="Close">&times;</button>
        </div>
        <div class="modal-body">
            <input class="form-control input-create mb-15" id="pplus-create-name" placeholder="<?php echo esc_attr(pplus_t('Điền tên bộ sưu tập mới của bạn...', 'Enter your new collection name...')); ?>" />
            <button class="btn btn-primary" id="pplus-create-add"><?php echo esc_html(pplus_t('Thêm vào', 'Add')); ?></button>
        </div>
    </div>
</div>

<!-- Modal: Them van phong vao bo suu tap (mo tu nut Yeu thich tren card) -->
<div class="pplus-modal modal-create" id="pplus-modal-addfa" aria-hidden="true">
    <div class="pplus-modal-backdrop" data-pplus-close></div>
    <div class="modal-dialog">
        <div class="modal-header">
            <i><svg width="15" height="15" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12.846 2.096c2.129 0 3.853 1.724 3.853 3.849 0 3.848-3.853 6.04-7.701 9.888-3.849-3.848-7.698-6.04-7.698-9.888 0-2.125 1.724-3.849 3.849-3.849 1.924 0 2.886.962 3.849 2.886.962-1.924 1.924-2.886 3.848-2.886z" fill="#7a68c0"/></svg></i>
            <div class="modal-title"><?php echo esc_html(pplus_t('Thêm vào ưa thích', 'Add to favourite')); ?></div>
            <button type="button" class="pplus-modal-x" data-pplus-close aria-label="Close">&times;</button>
        </div>
        <div class="modal-body">
            <button class="btn btn-create-favourite" id="pplus-addfa-new"><i class="fa fa-plus-circle"></i> <?php echo esc_html(pplus_t('Bộ sưu tập mới', 'New collection')); ?></button>
            <div class="list-favourite" id="pplus-addfa-list"><!-- JS render checkbox bo suu tap --></div>
            <button class="btn btn-primary" id="pplus-addfa-add"><?php echo esc_html(pplus_t('Thêm vào', 'Add')); ?></button>
            <a href="<?php echo esc_url(pplus_lang_url('danh-sach-yeu-thich')); ?>" class="btn btn-outline-primary" style="height:35px;margin-top:5px"><?php echo esc_html(pplus_t('Trang yêu thích', 'Favourite page')); ?></a>
        </div>
    </div>
</div>
