<?php
/**
 * Footer dung chung - PORT 1:1 tu Angular Resi:
 *   src/app/partials/teamplate-footer/teamplate-footer.component.html (+ .ts)
 * Du lieu: pplus_get_footer_data() = {lang}/Home/FooterInvokeAsync -> .result (bien datatFooter).
 * Field: dataLists[], myHistories[]{alias,id,title}, company{facebook,youtube,instagram},
 *        phone[]{value,stringExtension1}, email[]{value,stringExtension1},
 *        listUser[]{imgSizeV,title,content}, rsPhone[], rsEmail[].
 * currentDomainAdmin -> pplus_asset_base_url().
 */

if (!defined('ABSPATH')) {
    exit;
}

$footer = pplus_get_footer_data();
if (!$footer) {
    return;
}

$asset_base = pplus_asset_base_url();
$apartment  = pplus_localize('cho-thue-can-ho');
$about      = pplus_localize('ve-chung-toi');
$company    = (array) ($footer['company'] ?? []);
// PORT footer.ts: rsPhone/rsEmail = company.phone/company.email tach theo "|".
$rsPhone    = !empty($company['phone']) ? explode('|', (string) $company['phone']) : [];
$rsEmail    = !empty($company['email']) ? explode('|', (string) $company['email']) : [];
?>
<footer>
  <div class="footer">
    <div class="container">
      <div class="row clear-margin">
        <div class="col-md-5">
          <div class="menu-title"><?php echo esc_html(pplus_localize('Liên hệ')); ?></div>
          <div class="lg-f-content"><?php echo esc_html(pplus_localize('CÔNG TY CỔ PHẦN THƯƠNG MẠI VÀ TƯ VẤN BẤT ĐỘNG SẢN ĐẠI LỢI')); ?></div>
          <div class="pplus-info"><?php echo esc_html(pplus_localize('Tầng 06, tòa nhà Kinh Đô, 292 Tây Sơn, phường Trung Liệt, quận Đống Đa, Hà Nội')); ?></div>
          <div class="menu-0">
            <div class="list-unstyled">
              <div class="menu-title-2"><?php echo esc_html(pplus_localize('Phòng kinh doanh')); ?></div>
              <?php if (isset($rsPhone[0])) : ?><?php echo esc_html($rsPhone[0]); ?>;<br /><?php endif; ?>
              <?php if (isset($rsEmail[0])) : ?><?php echo esc_html($rsEmail[0]); ?>;<br /><?php endif; ?>
            </div>
            <div class="hr-style-1"></div>
            <div class="list-unstyled">
              <div class="menu-title-2"><?php echo esc_html(pplus_localize('Phòng chăm sóc khách hàng')); ?></div>
              <?php if (isset($rsPhone[1])) : ?><?php echo esc_html($rsPhone[1]); ?>;<br /><?php endif; ?>
              <?php if (isset($rsEmail[1])) : ?><?php echo esc_html($rsEmail[1]); ?>;<br /><?php endif; ?>
            </div>
          </div>
          <div class="hr-style-1"></div>
        </div>
        <div class="col-md-7">
          <div class="footer-right">
            <div class="menu-1">
              <div class="menu-title"><?php echo esc_html(pplus_localize('Tìm căn hộ')); ?></div>
              <div class="list-unstyled">
                <?php foreach ((array) ($footer['dataLists'] ?? []) as $item) :
                  $alias = (string) ($item['alias'] ?? '');
                  $sub   = (string) ($item['subTitle'] ?? ''); ?>
                  <div class="list-unstyled-item"><a class="district-name" href="<?php echo esc_url(pplus_lang_url($apartment . '/ha-noi/' . $alias)); ?>"><?php echo esc_html($sub); ?></a></div>
                <?php endforeach; ?>
              </div>
            </div>
            <div class="menu-2">
              <div class="menu-title"><?php echo esc_html(pplus_localize('Chính sách')); ?></div>
              <div class="list-unstyled">
                <?php
                // SUA 30/9 (kiem tra da ngon ngu theo yeu cau nguoi dung): xac nhan qua goi API that
                // - 2/3 muc trong "myHistories" ("Use of Term"/"Apartment Rental Policy", id 44/46)
                // co "alias" RONG (null) o CA EN LAN JA (chi ban VI la co du lieu day du) - day la
                // thieu du lieu phia backend (khong phai loi port), nhung neu de nguyen se sinh URL
                // hong dang "about-us/-44" (thieu han phan chu truoc dau "-") -> 404 100% khi bam.
                // Fallback: khi alias rong o ngon ngu hien tai, dung TAM alias cua BAN VI (goi 1 lan
                // duy nhat, CHI khi thuc su can - da co cache transient rieng trong pplus_api_get()
                // nen khong ton them request that o cac lan tai trang sau) - noi dung trang van hien
                // dung ngon ngu dang xem (API doc theo "id" la chinh, "alias" chi de doi chieu/SEO),
                // chi rieng DUONG DAN la dung tam alias VI thay vi 1 URL hong hoan toan.
                $vi_history_alias_by_id = null;
                foreach ((array) ($footer['myHistories'] ?? []) as $item) :
                  $alias = (string) ($item['alias'] ?? '');
                  $id    = (string) ($item['id'] ?? '');
                  $title = (string) ($item['title'] ?? '');
                  if ($alias === '' && $id !== '') {
                      if ($vi_history_alias_by_id === null) {
                          $vi_footer = pplus_api_get('vi/Home/FooterInvokeAsync', [], 900);
                          $vi_history_alias_by_id = [];
                          foreach ((array) ($vi_footer['result']['myHistories'] ?? []) as $vi_item) {
                              $vi_history_alias_by_id[(string) ($vi_item['id'] ?? '')] = (string) ($vi_item['alias'] ?? '');
                          }
                      }
                      $alias = $vi_history_alias_by_id[$id] ?? '';
                  }
                  if ($alias === '') {
                      continue; // Van khong co alias nao ca (ke ca VI) - bo qua, tranh sinh link hong.
                  }
                  ?>
                  <a class="chinh-sach" href="<?php echo esc_url(pplus_lang_url($about . '/' . $alias . '-' . $id)); ?>"><?php echo esc_html($title); ?></a>
                <?php endforeach; ?>
                <br />
              </div>
              <iframe title="<?php echo esc_attr(pplus_localize('Địa chỉ')); ?>" src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d14898.552851937988!2d105.8228791!3d21.0071347!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3135ac83d7aaa7c9%3A0xee4412aece650f2e!2sPropertyPlus%20Viet%20Nam_Apartment%20for%20rent%20in%20Ha%20Noi!5e0!3m2!1svi!2s!4v1696585344584!5m2!1svi!2s" width="400" height="300" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
          </div>
        </div>
        <div class="col-md-12">
          <div class="list-unstyled contact-list">
            <?php if (!empty($company['facebook'])) : ?><a href="<?php echo esc_url($company['facebook']); ?>" target="_blank" rel="nofollow" aria-label="facebook"><i class="fa fa-facebook"></i></a><?php endif; ?>
            <?php if (!empty($company['youtube'])) : ?><a href="<?php echo esc_url($company['youtube']); ?>" target="_blank" rel="nofollow" aria-label="youtube"><i class="fa fa-youtube-play"></i></a><?php endif; ?>
            <?php if (!empty($company['instagram'])) : ?><a class="instagram" href="<?php echo esc_url($company['instagram']); ?>" target="_blank" rel="nofollow" aria-label="instagram"><i class="fa fa-instagram"></i></a><?php endif; ?>
          </div>
          <div class="pplus-info">Copyright © <?php echo esc_html(date('Y')); ?> Property Plus</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Footer fixed PC -->
  <div class="footer-fix-pc">
    <div class="action-footer action-call" data-id="modal-container2" data-pplus-open-modal="modal-container2" title="<?php echo esc_attr(pplus_localize('Call')); ?>">
      <i><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 25" fill="none"><path d="M20.0002 11.0141H22.0002C22.0002 5.88414 18.1272 2.01514 12.9902 2.01514V4.01514C17.0522 4.01514 20.0002 6.95814 20.0002 11.0141Z" fill="#3E5FB4"/><path d="M12.9998 8.01513C15.1028 8.01513 15.9998 8.91213 15.9998 11.0151H17.9998C17.9998 7.79013 16.2248 6.01513 12.9998 6.01513V8.01513ZM16.4218 13.4581C16.2296 13.2835 15.9771 13.1903 15.7176 13.1984C15.4581 13.2064 15.2118 13.3149 15.0308 13.5011L12.6378 15.9621C12.0618 15.8521 10.9038 15.4911 9.71179 14.3021C8.51979 13.1091 8.15879 11.9481 8.05179 11.3761L10.5108 8.98213C10.6972 8.80126 10.8059 8.55495 10.814 8.29534C10.822 8.03572 10.7287 7.78317 10.5538 7.59113L6.85879 3.52813C6.68384 3.33549 6.44067 3.21864 6.18095 3.20239C5.92122 3.18615 5.66539 3.27179 5.46779 3.44113L3.29779 5.30213C3.1249 5.47565 3.02171 5.70658 3.00779 5.95113C2.99279 6.20113 2.70679 12.1231 7.29879 16.7171C11.3048 20.7221 16.3228 21.0151 17.7048 21.0151C17.9068 21.0151 18.0308 21.0091 18.0638 21.0071C18.3083 20.9934 18.5391 20.8898 18.7118 20.7161L20.5718 18.5451C20.7413 18.3477 20.8271 18.0919 20.811 17.8322C20.795 17.5725 20.6783 17.3292 20.4858 17.1541L16.4218 13.4581Z" fill="#3E5FB4"/></svg></i>
    </div>
    <div class="action-footer action-send-mail" data-id="modal-container3" data-pplus-open-modal="modal-container3" title="<?php echo esc_attr(pplus_localize('Send email')); ?>">
      <i><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 25" fill="none"><path d="M20.4072 3.60693H3.59212C2.43083 3.60693 1.50074 4.54753 1.50074 5.70882L1.49023 18.3201C1.49023 19.4814 2.43083 20.422 3.59212 20.422H20.4072C21.5685 20.422 22.5091 19.4814 22.5091 18.3201V5.70882C22.5091 4.54753 21.5685 3.60693 20.4072 3.60693ZM20.4072 7.8107L11.9997 13.0654L3.59212 7.8107V5.70882L11.9997 10.9635L20.4072 5.70882V7.8107Z" fill="#3E5FB4"/></svg></i>
    </div>
    <div class="action-footer action-chat-support" data-id="modal-container6" data-pplus-open-modal="modal-container6" title="<?php echo esc_attr(pplus_localize('Chat support')); ?>">
      <i class="chat-contact-1"><svg class="chat-contact-1" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 25" fill="none"><mask id="mf0" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="24" height="25"><rect y="0.0151367" width="24" height="24" fill="#D9D9D9"/></mask><g mask="url(#mf0)"><path d="M2 20.5901V5.01514C2 4.46514 2.196 3.99414 2.588 3.60214C2.97933 3.2108 3.45 3.01514 4 3.01514H20C20.55 3.01514 21.021 3.2108 21.413 3.60214C21.8043 3.99414 22 4.46514 22 5.01514V17.0151C22 17.5651 21.8043 18.0361 21.413 18.4281C21.021 18.8195 20.55 19.0151 20 19.0151H6L3.7 21.3151C3.38333 21.6318 3.02067 21.7025 2.612 21.5271C2.204 21.3525 2 21.0401 2 20.5901Z" fill="#3E5FB4"/></g></svg></i>
    </div>
  </div>

  <!-- Modal Facebook / Zalo -->
  <div class="modal fade MesvsZaloModal" id="ZaloModal" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document"><div class="modal-content">
      <div class="modal-header"><p class="modal-title"><?php echo esc_html(pplus_localize('Kết nối')); ?></p></div>
      <div class="modal-body"><?php echo esc_html(pplus_localize('Có phải bạn muốn kết nối với chúng tôi')); ?></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" id="ZaloHuy" data-bs-dismiss="modal"><?php echo esc_html(pplus_localize('Hủy')); ?></button>
        <a href="https://zalo.me/0865364866" target="_blank" class="btn btn-primary chat-zalo-contact"><?php echo esc_html(pplus_localize('Zalo Chat')); ?></a>
      </div>
    </div></div>
  </div>
  <div class="modal fade MesvsZaloModal" id="FaceModal" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document"><div class="modal-content">
      <div class="modal-header"><p class="modal-title"><?php echo esc_html(pplus_localize('Kết nối')); ?></p></div>
      <div class="modal-body"><?php echo esc_html(pplus_localize('Có phải bạn muốn kết nối với chúng tôi')); ?></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" id="FaceHuy" data-bs-dismiss="modal"><?php echo esc_html(pplus_localize('Hủy')); ?></button>
        <a href="https://www.facebook.com/propertyplusgroup/" target="_blank" class="btn btn-primary chat-meseger-contact web-show"><?php echo esc_html(pplus_localize('Messenger Chat')); ?></a>
        <a href="https://m.me/100076484342575" class="btn btn-primary chat-meseger-contact web-hidden"><?php echo esc_html(pplus_localize('Messenger Chat')); ?></a>
      </div>
    </div></div>
  </div>

  <!-- modal-container2: Goi dien -->
  <div class="modal-container-ppus" id="modal-container2" data-pplus-modal>
    <div class="modal-background"><div class="modal">
      <?php foreach ((array) ($footer['phone'] ?? []) as $i => $item) : ?>
        <div class="content-modal-mobile">
          <a class="action-2 phone-contact-<?php echo (int) $i; ?>" href="tel:<?php echo esc_attr($item['stringExtension1'] ?? ''); ?>">
            <label class="phone-contact-1"><?php echo esc_html($item['value'] ?? ''); ?></label><br />
            <span class="phone-contact-1"><?php echo esc_html($item['stringExtension1'] ?? ''); ?></span>
          </a>
        </div>
      <?php endforeach; ?>
    </div></div>
  </div>

  <!-- modal-container3: Email list -->
  <div class="modal-container-ppus" id="modal-container3" data-pplus-modal>
    <div class="modal-background"><div class="modal">
      <?php foreach ((array) ($footer['email'] ?? []) as $item) : ?>
        <div class="content-modal-mobile">
          <div class="action-2 action-post email-contact-1" data-id="modal-container4" data-pplus-open-modal="modal-container4">
            <label class="email-contact-1"><?php echo esc_html($item['value'] ?? ''); ?></label><br />
            <span class="email-contact-1"><?php echo esc_html($item['stringExtension1'] ?? ''); ?></span>
          </div>
        </div>
      <?php endforeach; ?>
    </div></div>
  </div>

  <!-- modal-container4: Form gop y (IssueNewApartment) -->
  <div class="modal-container-ppus" id="modal-container4" data-pplus-modal>
    <div class="modal-background"><div class="modal">
      <div class="from-send-mail-footer">
        <div class="send-mail-header">
          <label><?php echo esc_html(pplus_localize('Hòm thư góp ý')); ?></label>
          <span data-pplus-close-modal="modal-container4"><?php echo esc_html(pplus_localize('Hủy')); ?></span>
        </div>
        <div class="mt-15">
          <form name="CompleteModalForm" id="CompleteModalForm" class="form-validation form-group-lg pplus-advise-form" spellcheck="false">
            <input hidden name="FavoriteLink" />
            <div class="form-group form-group-pc"><i class="fa fa-user"></i><input class="form-control" type="text" name="OwnerName" placeholder="<?php echo esc_attr(pplus_localize('Họ và tên')); ?> ..." /></div>
            <div class="form-group form-group-pc"><i class="fa fa-phone"></i><input class="form-control" type="text" name="OwnerEmail" placeholder="<?php echo esc_attr(pplus_localize('Địa chỉ email')); ?>" /></div>
            <div class="form-group form-group-pc mr-0"><i class="fa fa-envelope"></i><input class="form-control" type="text" name="OwnerMobile" placeholder="<?php echo esc_attr(pplus_localize('Số điện thoại')); ?>" /></div>
            <div class="form-group"><textarea class="form-control" style="height:110px" name="AdviseContent" placeholder="<?php echo esc_attr(pplus_localize('Viết lời nhắn của bạn..')); ?>"></textarea></div>
            <div class="form-group text-center mt-15"><button class="btn btn-primary" id="HoanTatDangKyDichVu" type="button"><?php echo esc_html(pplus_localize('Gửi email')); ?></button></div>
          </form>
        </div>
      </div>
    </div></div>
  </div>

  <!-- modal-container5: Danh sach user lien he (mobile) -->
  <div class="modal-container-ppus" id="modal-container5" data-pplus-modal>
    <div class="modal-background"><div class="modal">
      <div class="content-modal-mobile contact-list-user-moblie">
        <?php foreach ((array) ($footer['listUser'] ?? []) as $item) : ?>
          <div class="action-2 show-contact-user" data-id="modal-container4" data-pplus-open-modal="modal-container4" data-content="<?php echo esc_attr(pplus_localize('Gửi') . ' ' . ($item['title'] ?? '')); ?>">
            <div class="item">
              <div><img src="<?php echo esc_url($asset_base . '/' . ltrim((string) ($item['imgSizeV'] ?? ''), '/')); ?>" alt="<?php echo esc_attr($item['title'] ?? ''); ?>" loading="lazy" /></div>
              <div class="item-content">
                <label class="text-1"><?php echo esc_html($item['title'] ?? ''); ?></label><br />
                <div class="text-2"><?php echo wp_kses_post($item['content'] ?? ''); ?></div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="content-modal-mobile action-children-footer" data-id="modal-container3" data-pplus-open-modal="modal-container3"><div class="action-2"><label><?php echo esc_html(pplus_localize('Gửi email')); ?></label></div></div>
      <div class="content-modal-mobile action-children-footer" data-id="modal-container2" data-pplus-open-modal="modal-container2"><div class="action-2"><label><?php echo esc_html(pplus_localize('Gọi liên hệ')); ?></label></div></div>
      <div class="content-modal-mobile action-children-footer" data-id="modal-container6" data-pplus-open-modal="modal-container6"><div class="action-2"><label><?php echo esc_html(pplus_localize('Chát trực tiếp')); ?></label></div></div>
    </div></div>
  </div>

  <!-- modal-container6: Chon kenh chat -->
  <div class="modal-container-ppus" id="modal-container6" data-pplus-modal>
    <div class="modal-background"><div class="modal">
      <div class="content-modal-mobile">
        <div class="action-2" data-bs-toggle="modal" data-bs-target="#FaceModal">
          <div><img src="<?php echo esc_url(PPLUS_THEME_URI . '/assets/officebase/Common/Images/Home/messenger-icon-free-png.webp'); ?>" width="100" height="100" loading="lazy" alt="chat messenger" /><label><?php echo esc_html(pplus_localize('Messenger Chat')); ?></label></div>
        </div>
      </div>
      <div class="content-modal-mobile">
        <div class="action-2" data-bs-toggle="modal" data-bs-target="#ZaloModal">
          <div><img src="<?php echo esc_url(PPLUS_THEME_URI . '/assets/officebase/Common/Images/Home/Icon_of_Zalo.svg.webp'); ?>" width="100" height="100" loading="lazy" alt="zalo" /><label><?php echo esc_html(pplus_localize('Zalo Chat')); ?></label></div>
        </div>
      </div>
    </div></div>
  </div>
</footer>
