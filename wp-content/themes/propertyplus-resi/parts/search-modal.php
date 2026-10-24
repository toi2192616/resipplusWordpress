<?php
/**
 * #searchModal - popup tim kiem mo tu nut tron xanh cuoi hang "suggest-header" (".btn-modal-search",
 * xem inc/render-helpers.php pplus_render_suggest_header(), da co san "data-bs-target=#searchModal"
 * tu truoc nhung modal nay CHUA TUNG duoc dung - nut bam khong co tac dung, dung y het pattern
 * "written but never wired" da gap nhieu lan trong theme nay).
 *
 * SUA 17/9 LAN 3 (theo yeu cau nguoi dung, sau khi doi chieu LAI voi source that va duoc xac nhan
 * "làm đầy đủ như gốc"): port DAY DU tu search-base.component.html dong 220-519 (khong con cat bot
 * o "Chi tiết" nhu 2 lan truoc) + CSS tu search-base.component.less (#searchModal, .box-search,
 * .search-input-group, #btnSearch) + teamplate-header.component.less (.searching-bar,
 * .search-controls, .focus-popup, .btn-search, .long-term-s3* - toan bo CSS 2 o input + panel Chi
 * tiết nam o file NAY, khong phai search-base.component.less, vi search-home.component.less cua
 * Resi RONG 0 dong, xem ghi chu trong functions.php).
 *
 * LICH SU cac lan sua truoc (giu lai de biet boi canh, KHONG lam lai):
 * 1. Luoi "Hoặc chọn theo gợi ý" ep hien qua "position:static" tren ".focus-popup" -> bi bo gon
 *    vao dung cot "s1" hep (~255px), sai giao dien.
 * 2. Sua (1) bang cach chuyen luoi RA NGOAI ".search-option"/"s1" thanh 1 khoi rieng LUON HIEN
 *    (".pplus-search-suggest-always") - dung theo 1 yeu cau nguoi dung LUC DO ("để ô gợi ý đầu
 *    tiên luôn hiện ngay từ đầu").
 * 3. SUA LAN NAY (nguoi dung xem lai anh chup, dinh chinh LAI yeu cau (2) - "khác chứ, bản gốc là
 *    chỉ hiện thanh ngang" sau do MOI bam vao tung o de hien goi y CUA RIENG o do): BO HAN cach lam
 *    o (2), quay ve DUNG co che goc - luoi goi y (VA panel "Chi tiết") CHI hien khi CHINH o input
 *    tuong ung dang focus (native ":focus-within" tren ".search-option", KHONG con "luon hien" mac
 *    dinh nua) - ca 2 o KHONG hien gi khi modal moi mo (dung boi canh yeu cau: "chỉ hiện thanh
 *    ngang" = chi thay 2 o nhap + nut tim, chua co goi y nao). Markup: luoi goi y quay lai NAM
 *    TRONG ".search-option" cua o Location (id "pplus-search-suggest-list" - PHAI giu id nay tren
 *    dung the ".focus-popup.first-popup", khong duoc quen khi di chuyen markup, vi
 *    pplus-search-modal.js dung id nay lam goc uy quyen su kien click cho ".item-suggest" - thieu
 *    id nay khien bam goi y KHONG co tac dung gi, loi thuc te gap va sua trong lan nay). Nho vay
 *    CUNG khong con can override rieng nao cho ".focus-popup" nua - ca 2 luoi/panel dung LAI 100%
 *    CSS ":focus-within" + kich thuoc thoat cot hep (position:absolute, width rieng 530px/657px)
 *    co san TU TRUOC trong header.less, khong sua/them gi o CSS ca.
 * - Bam 1 goi y (vd "Quận Tây Hồ") KHONG dieu huong ngay - dung y het DataCoreroupToBack() that:
 *   dien ten vao o Location, RIENG chuyen focus sang o "Chi tiết" ("$('#search-input2').focus()",
 *   dong 723 ts that) - viec doi focus nay TU DONG dong luoi goi y cua Location (khong con phan tu
 *   nao trong ".search-option" cua no giu focus nua) VA mo panel "Chi tiết" (dung native
 *   ":focus-within" co san cho CA HAI chieu, khong can JS rieng goi show()/hide() nao ca - da xac
 *   nhan qua test that trong browser: luoi Location tu dong an khi rê chuot ra ngoai/focus roi khoi
 *   no, panel Chi tiết tu dong hien dung luc).
 * - Panel "Chi tiết" (".long-term-s3", teamplate-header.component.less dong 1457+): port DAY DU -
 *   "Loại nhà/phòng" (Căn hộ dịch vụ/Chung cư), khoang gia (Tối thiểu/Tối đa, PPlusConst.MinPrice=0/
 *   MaxPrice=15000 - GlobalConstants.ts, KHONG phai app-component-base.ts 1500 sai), "Chi tiết
 *   phòng" (Phòng ngủ/Phòng tắm, listValue=[1..8] - search-base.component.ts dong 63), "Diện tích"/
 *   "Tầng số" (from/to), "Bồn tắm". KHONG dung <nouislider> (bi COMMENT trong chinh HTML goc, dong
 *   383 - "code chet", khong can port) va KHONG dung nut submit rieng trong panel (cung "hidden"
 *   trong ban goc, dong 471-473 - nut tim kiem TRON XANH ben ngoai (".btn-search") moi la nut that).
 * - Nut "Tìm kiếm" gio goi THAT pplus_get_apartment_list() qua page-reference.php: dieu huong toi
 *   URL /cho-thue-can-ho/ha-noi/{alias da chon}/ (hoac trang goc "/ha-noi/" neu chua chon goi y nao)
 *   kem query string cho MOI truong loc da dien (price_from/price_to/bedroom_qty/bathroom_qty/
 *   area_from/area_to/floor_from/floor_to/has_bathtub/apartment_type) - xac nhan qua goi API That
 *   (Reference/GetIndex?alias=quan-tay-ho) CA 10 truong nay DA CO SAN trong "filter" tra ve (gia tri
 *   mac dinh null/0), tuc BE THAT SU support loc theo dung 10 truong nay - khong bia them. Xem phan
 *   doc GET tuong ung moi them trong page-reference.php.
 * - "Tìm theo tên"/tab thu 2, tim theo Google Map, tim theo ten du an (listSearchGroup/
 *   listSearchGroupGoogleMap): VAN KHONG lam - can API rieng (Apartment/GetFilterGroupList), ngoai
 *   pham vi ngay ca sau khi mo rong lan nay (nguoi dung chi yeu cau lam day du nhanh "Chi tiết" +
 *   hanh vi chuyen focus, khong nhac toi 2 tab/nguon du lieu nay).
 * - Nut dong "X" (button.close, data-bs-dismiss=modal): ban goc co "hidden" (an di, that su khong
 *   dung toi) - GIU y het, dong bang cach bam ra ngoai backdrop hoac phim Esc (hanh vi Bootstrap 5
 *   mac dinh).
 */
if (!defined('ABSPATH')) exit;

$lang_prefix    = pplus_lang_prefix();
$all_districts  = pplus_get_all_districts();
$bedbath_values = range(1, 8); // listValue = [1..8] (search-base.component.ts dong 63)
?>
<div class="modal fade" tabindex="-1" id="searchModal" role="dialog" aria-labelledby="searchModalLabel" aria-hidden="true"
    data-base-url="<?php echo esc_url(home_url($lang_prefix . '/' . pplus_apartment_list_root() . '/ha-noi')); ?>">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <div class="search-box">
                    <form autocomplete="off" id="office-form" spellcheck="false">
                        <?php // "id" DOI THANH "pplus-search-modal-panel" (KHAC "box-search-base" cua
                        // ban that) - "box-search-base" da la ID rieng cua parts/office-filter-panel.php
                        // (panel truot Bo loc Van phong) trong CHINH theme nay, selector hop
                        // "#box-search-base.box-search" (search-base.less dong 151) se BAT NHAM modal
                        // nay thanh panel truot position:fixed off-canvas (dun het noi dung ra ngoai
                        // man hinh) neu dung lai id that - bug thuc te phat hien khi test (modal mo ra
                        // "rong", noi dung nam lech hoan toan ngoai khung nhin). Giu nguyen class
                        // "box-search" (khong bi loi vi rule nguy hiem yeu cau CA id LAN class). ?>
                        <div class="box-search box-search-pane row" id="pplus-search-modal-panel">
                            <div class="tab-container" id="search-container">
                                <div class="tab-content" id="pills-tabContent">
                                    <div class="tab-pane box-search-pane fade show active" id="pills-home">
                                        <?php // SUA 18/9: XOA wrapper "<div class=row>" quanh s1/s3/s4 - markup that
                                        // (search-base.component.html dong 248-249) KHONG co the "row" nao ca, ".searching-bar"
                                        // TRUC TIEP chua "col-md-5 search-input-group s1/s3" va "col-md-1 s4" (dung Bootstrap
                                        // grid CHI de lay % width, KHONG can ".row" flex context - real CSS tu dat float rieng:
                                        // ".s1{float:left}"/".s3{float:left}"/".s4{float:right}", xem search-base.less). Wrapper
                                        // "row" ta tung them BIA THEM khien Bootstrap flex "align-items:stretch" ep ".s4" cao
                                        // bang ca hang (67px thay vi 44px that), day nut kinh lup + popup goi y sai vi tri theo
                                        // chieu doc so voi production - loi nguoi dung phat hien qua anh chup so sanh. ?>
                                        <div class="searching-bar">
                                                <div class="col-md-5 search-input-group s1">
                                                    <div class="search-option">
                                                        <div class="search-controls">
                                                            <div class="control">
                                                                <label for="search-modal-input1" class="search-label"><?php echo esc_html(pplus_localize('Nhập địa điểm')); ?> ...</label>
                                                                <input class="search-input form-control" id="search-modal-input1" name="search_key" placeholder="<?php echo esc_attr(pplus_localize('Bạn muốn đi đâu')); ?>" autocomplete="off" />
                                                            </div>
                                                        </div>
                                                        <?php // Luoi "Hoặc chọn theo gợi ý" - NAM TRONG ".search-option" cua o Location. QUAN
                                                        // TRONG: search-base.component.html dong 262-325 co 2 NHANH ANH EM (ngIf/else), KHONG
                                                        // long vao nhau - ".long-term-s1-item.pplus-show-group-list" (dong 263, chi hien khi
                                                        // "listSearchGroup != undefined" - tim theo ten du an/Google Map, tinh nang CHUA lam,
                                                        // xem docblock dau file) VA ".pplus-show-district" (dong 302, hien khi CA HAI bien tren
                                                        // undefined - luon dung TRUONG HOP nay o day) la 2 ANH EM cung cap, KHONG phai cha-con.
                                                        // Long nham ".pplus-show-district" vao trong ".long-term-s1-item" (sua o lan truoc) vo
                                                        // tinh ke thua CSS "width:530px" cua ".focus-popup .long-term-s1-item" (header.less,
                                                        // that ra danh rieng cho nhanh "pplus-show-group-list" chua dung toi) - ep het luoi 16
                                                        // muc vao 1 khung 530px hep (~117px/cot), lam icon+chu bi VO DONG thay vi nam CHUNG 1
                                                        // hang - loi nguoi dung phat hien qua anh chup so sanh voi ban that. Xac nhan qua do dac
                                                        // truc tiep tren production that (getComputedStyle): ".focus-popup.first-popup" that KHONG
                                                        // co width co dinh nao ca (chi "min-width:200px") - no tu nhien shrink-to-fit theo noi
                                                        // dung cua ".list-suggest" (CSS Grid "minmax(0,1fr)", text 1 dong khong wrap khi do
                                                        // max-content) -> tu dat ~903px (gan het chieu rong modal 940px) MA KHONG CAN bat ky
                                                        // "width"/"right" nao rieng - chi can XOA wrapper thua nay la du dung nguyen ban. ?>
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
                                                                            <div class="item-content-district-data item-suggest" role="button" tabindex="0" data-alias="<?php echo esc_attr(trim($item_alias)); ?>" data-label="<?php echo esc_attr($item_label); ?>">
                                                                                <figure class="icon-suggest">
                                                                                    <img title="ListProject" src="<?php echo esc_url(pplus_admin_asset_url($item['img'] ?? '')); ?>" alt="<?php echo esc_attr($item_label); ?>" onerror="this.src='<?php echo esc_url(get_template_directory_uri() . '/assets/officebase/Common/Images/logo.png'); ?>'" loading="lazy" />
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
                                                    </div>
                                                </div>
                                                <div class="col-md-5 search-input-group s3">
                                                    <div class="search-option">
                                                        <div class="search-controls">
                                                            <div class="control">
                                                                <label class="search-label" for="search-modal-input2"><?php echo esc_html(pplus_localize('Chi tiết')); ?></label>
                                                                <input class="search-input form-control" id="search-modal-input2" placeholder="<?php echo esc_attr(pplus_localize('Chi tiết căn hộ của bạn')); ?>" />
                                                            </div>
                                                        </div>
                                                        <?php // Panel "Chi tiết" - port DAY DU tu search-base.component.html dong 341-469, xem
                                                        // docblock dau file. ?>
                                                        <div class="focus-popup">
                                                            <div class="long-term-s3">
                                                                <div class="long-term-s3-item">
                                                                    <div class="long-term-s3-t1"><?php echo esc_html(pplus_localize('Loại nhà/ phòng')); ?></div>
                                                                    <div class="checkbox-term">
                                                                        <label><input type="checkbox" name="apartment_type" value="1" class="pplus-apartment-type-check" /> <?php echo esc_html(pplus_localize('Căn hộ dịch vụ')); ?></label>
                                                                        <label><input type="checkbox" name="apartment_type" value="2" class="pplus-apartment-type-check" /> <?php echo esc_html(pplus_localize('Chung cư')); ?></label>
                                                                    </div>
                                                                </div>
                                                                <div class="long-term-s3-item pt-0">
                                                                    <div class="choose-price">
                                                                        <div class="group-price-ranger-item">
                                                                            <label class="ranger-label group-price-ranger-item-t1"><?php echo esc_html(pplus_localize('Tối thiểu')); ?></label>
                                                                            <input class="ranger-input group-price-ranger-item-c1" type="text" inputmode="numeric" name="price_from" id="pplus-price-from" min="0" max="15000" placeholder="<?php echo esc_attr(pplus_localize('Nhập số')); ?> ..." />
                                                                            <span class="ranger-currency">$</span>
                                                                        </div>
                                                                        <div class="group-price-ranger-item group-price-ranger-item2">
                                                                            <label class="ranger-label group-price-ranger-item-t1"><?php echo esc_html(pplus_localize('Tối đa')); ?></label>
                                                                            <input class="ranger-input group-price-ranger-item-c1" type="text" inputmode="numeric" name="price_to" id="pplus-price-to" min="0" max="15000" placeholder="<?php echo esc_attr(pplus_localize('Nhập số')); ?> ..." />
                                                                            <span class="ranger-currency">$</span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="long-term-s3-item">
                                                                    <div class="long-term-s3-t1"><?php echo esc_html(pplus_localize('Chi tiết phòng')); ?></div>
                                                                    <div class="row">
                                                                        <div class="col-sm-12 list-value-customer">
                                                                            <label><?php echo esc_html(pplus_localize('Phòng ngủ')); ?></label>
                                                                            <input hidden name="bedroom_qty" type="number" id="pplus-bedroom-qty" />
                                                                            <div class="list-value-select" data-input="pplus-bedroom-qty">
                                                                                <div class="list-value-item action value-item-all" data-value=""><?php echo esc_html(pplus_localize('Bất kỳ')); ?></div>
                                                                                <?php foreach ($bedbath_values as $n) : ?>
                                                                                    <div class="list-value-item" data-value="<?php echo (int) $n; ?>"><?php echo (int) $n; ?></div>
                                                                                <?php endforeach; ?>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-sm-12 list-value-customer">
                                                                            <label><?php echo esc_html(pplus_localize('Phòng tắm')); ?></label>
                                                                            <input hidden name="bathroom_qty" type="number" id="pplus-bathroom-qty" />
                                                                            <div class="list-value-select" data-input="pplus-bathroom-qty">
                                                                                <div class="list-value-item action value-item-all" data-value=""><?php echo esc_html(pplus_localize('Bất kỳ')); ?></div>
                                                                                <?php foreach ($bedbath_values as $n) : ?>
                                                                                    <div class="list-value-item" data-value="<?php echo (int) $n; ?>"><?php echo (int) $n; ?></div>
                                                                                <?php endforeach; ?>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="row list-chose-content">
                                                                        <div class="col-sm-6 pl-0">
                                                                            <label><?php echo esc_html(pplus_localize('Diện tích')); ?></label>
                                                                            <div class="cus-type-1">
                                                                                <span><?php echo esc_html(pplus_localize('từ')); ?></span>
                                                                                <input type="number" name="area_from" id="pplus-area-from" /><text>m</text> -
                                                                                <input type="number" name="area_to" id="pplus-area-to" /><text>m</text>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-sm-6">
                                                                            <label><?php echo esc_html(pplus_localize('Tầng số')); ?></label>
                                                                            <div class="cus-type-2">
                                                                                <span><?php echo esc_html(pplus_localize('từ')); ?></span>
                                                                                <input type="number" name="floor_from" id="pplus-floor-from" /> -
                                                                                <input type="number" name="floor_to" id="pplus-floor-to" />
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="checkbox-term">
                                                                        <label><input type="checkbox" name="has_bathtub" id="pplus-has-bathtub" value="true" /> <?php echo esc_html(pplus_localize('Bồn tắm')); ?></label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-1 s4">
                                                    <button type="button" class="btn btn-primary btn-search pull-right" aria-label="<?php echo esc_attr(pplus_localize('Tìm kiếm')); ?>">
                                                        <?php // SUA 18/9: SVG that dung width/height DUNG BANG viewBox (30x33, 1:1, khong scale
                                                        // xuong) - ban truoc dung nham "20x22" (thu nho ~2/3) khien nut render nho hon that
                                                        // (do dac truc tiep: 41x33px thay vi 51x44px that), gop phan gay cam giac "khong can
                                                        // doi". Xac nhan qua doc thang outerHTML nut that tren production. ?>
                                                        <svg width="30" height="33" viewBox="0 0 30 33" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                            <path d="M25.69 32.749C25.86 32.749 26.04 32.689 26.18 32.579L29.35 29.999C29.51 29.869 29.61 29.679 29.63 29.479C29.65 29.269 29.59 29.069 29.46 28.909L21.49 19.099C22.79 17.399 23.6 15.379 23.82 13.199C24.14 10.079 23.23 7.009 21.25 4.579C17.16 -0.451002 9.73998 -1.22099 4.70998 2.86901C2.26998 4.84901 0.749983 7.65901 0.429983 10.779C0.109983 13.899 1.01998 16.969 2.99998 19.399C4.97998 21.839 7.78999 23.359 10.92 23.679C11.33 23.719 11.74 23.739 12.15 23.739C13.87 23.739 15.55 23.359 17.09 22.639L25.07 32.469C25.22 32.659 25.45 32.759 25.68 32.759L25.69 32.749ZM11.09 22.109C8.37999 21.829 5.93999 20.509 4.22999 18.399C2.50999 16.289 1.71998 13.629 1.99998 10.919C2.27998 8.20901 3.59998 5.76901 5.70998 4.05901C7.59998 2.52901 9.86999 1.77901 12.13 1.77901C15.1 1.77901 18.04 3.069 20.05 5.539C21.77 7.649 22.56 10.309 22.28 13.019C22 15.729 20.68 18.169 18.57 19.879C16.46 21.599 13.8 22.389 11.09 22.109ZM19.55 21.099C19.87 20.839 20.17 20.569 20.46 20.279L27.77 29.279L25.81 30.869L18.5 21.869C18.86 21.629 19.22 21.379 19.56 21.099H19.55Z" fill="#FFFEFF" />
                                                            <path d="M18.8207 6.53911C17.3707 4.74911 15.3107 3.6391 13.0307 3.4091C10.7407 3.1691 8.50074 3.83911 6.72074 5.28911C4.94074 6.73911 3.82074 8.7991 3.59074 11.0791C3.35074 13.3691 4.02074 15.6091 5.47074 17.3891C7.17074 19.4791 9.65074 20.5691 12.1607 20.5691C14.0707 20.5691 15.9907 19.9391 17.5807 18.6491C21.2607 15.6591 21.8307 10.2291 18.8307 6.53911H18.8207ZM16.5807 17.4391C13.5607 19.8891 9.12075 19.4291 6.67075 16.4091C5.48075 14.9491 4.94074 13.1091 5.13074 11.2391C5.32074 9.3691 6.24074 7.6791 7.70074 6.4991C8.97074 5.4691 10.5207 4.91911 12.1307 4.91911C12.3807 4.91911 12.6207 4.92911 12.8707 4.95911C14.7407 5.14911 16.4307 6.06911 17.6107 7.52911C20.0607 10.5491 19.6007 14.9891 16.5807 17.4391Z" fill="#FFFEFF" />
                                                        </svg>
                                                    </button>
                                                    <button hidden type="button" class="close" data-bs-dismiss="modal" aria-label="Close">&times;</button>
                                                </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
