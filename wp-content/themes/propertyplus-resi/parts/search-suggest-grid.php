<?php
/**
 * Luoi "Hoặc chọn theo gợi ý" (".focus-popup.first-popup#pplus-search-suggest-list") - DUNG CHUNG boi popup tim kiem (parts/search-modal.php) va o
 * tim kiem trang chu (front-page.php), nen 2 noi luon cung noi dung. Nguon: pplus_search_suggestion_items() (16 muc Admin + cac muc cua menu con thieu).
 * Hanh vi/JS: assets/js/pplus-search-modal.js (id nay la goc uy quyen su kien click cho ".item-suggest" - KHONG doi).
 *
 * Lich su layout (giu lai de khong lam lai): luoi nam TRONG ".search-option" cua o Location, ".pplus-show-district" KHONG long trong
 * ".long-term-s1-item" (2 nhanh anh em trong search-base.component.html dong 262-325; long nham se ke thua "width:530px" lam vo luoi); ".focus-popup.first-popup"
 * that khong co width co dinh - shrink-to-fit theo noi dung ".list-suggest" (CSS Grid minmax(0,1fr)).
 */
if (!defined('ABSPATH')) exit;

$all_districts = pplus_search_suggestion_items();
?>
<div class="focus-popup first-popup" id="pplus-search-suggest-list">
    <div class="pplus-show-district">
        <div class="suggestDistrict">
            <div class="textHeader"><?php echo esc_html(pplus_localize('Hoặc chọn theo gợi ý')); ?></div>
            <div class="list-suggest">
                <?php foreach ($all_districts as $item) :
                    $item_alias = (string) ($item['alias'] ?? '');
                    if ($item_alias === '') continue;
                    $item_label = trim((string) ($item['subTitle'] ?? ''));
                ?>
                    <div class="item-content-district-data item-suggest" role="button" tabindex="0" data-alias="<?php echo esc_attr(trim($item_alias)); ?>" data-label="<?php echo esc_attr($item_label); ?>"<?php $pplus_sm_filter = pplus_suggestion_option_filter($item); echo $pplus_sm_filter ? ' data-filter="' . esc_attr(wp_json_encode($pplus_sm_filter)) . '"' : ''; ?>>
                        <figure class="icon-suggest">
                            <img title="ListProject" src="<?php echo esc_url(pplus_admin_asset_url($item['img'] ?? '')); ?>" alt="" onerror="this.src='<?php echo esc_url(get_template_directory_uri() . '/assets/officebase/Common/Images/logo-96.webp'); ?>'" loading="lazy" />
                        </figure>
                        <div class="content-suggest">
                            <p class="content-suggest-text1"><?php echo esc_html($item_label); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
