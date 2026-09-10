<?php
/**
 * Header dung chung - PORT 1:1 tu Angular Resi:
 *   src/app/partials/teamplate-header/teamplate-header.component.html (+ .ts)
 * Du lieu: pplus_get_header_data() = {lang}/Home/HeaderInvokeAsync -> .result (bien dataHeader).
 * Cac field dung: dataLists[], apartmentLuxury[], projectList[], projectListLuxury[],
 * apartmentVilla[], apartmentType[] (moi item: alias, subTitle; rieng projectList*: project.projectName).
 * Localizer(...) -> pplus_localize(...); urlLang -> pplus_lang_prefix(); routerLink -> pplus_lang_url().
 */

if (!defined('ABSPATH')) {
    exit;
}

$header       = pplus_get_header_data();
$theme_uri    = PPLUS_THEME_URI;
$cur_lang     = pplus_current_lang();
$apartment    = pplus_localize('cho-thue-can-ho'); // en: rental-apartment ...
$villa        = pplus_localize('biet-thu-cho-thue');
$is_home      = is_front_page();

// slug tinh mac dinh cua menu (Angular hard-code 'ha-noi')
$hn = 'ha-noi';

/** In 1 nhom <li> menu quan/loai (dataLists / apartmentLuxury / apartmentType / apartmentVilla). */
$render_alias_items = static function (array $items, string $base_slug) {
    foreach ($items as $item) {
        $alias    = (string) ($item['alias'] ?? '');
        $subTitle = (string) ($item['subTitle'] ?? '');
        if ($alias === '' && $subTitle === '') {
            continue;
        }
        printf(
            '<li class="menu-quan"><a class="district-name" href="%s">%s<i class="icon-menu-link"></i></a></li>',
            esc_url(pplus_lang_url($base_slug . '/ha-noi/' . $alias)),
            esc_html($subTitle)
        );
    }
};
?>
<div class="header head-all<?php echo $is_home ? ' headerHome' : ''; ?>">
  <div class="container">
    <?php if (!$header) : ?>
      <div class="col-md-12 loading-base-menu row">
        <div class="col-md-4"><div class="loading-base-div-2"><div class="pruzze"></div></div></div>
        <div class="col-md-4"><div class="loading-base-div-2"><div class="pruzze"></div></div></div>
        <div class="col-md-4"><div class="loading-base-div-2"><div class="pruzze"></div></div></div>
      </div>
    <?php else : ?>
    <div class="row">
      <div class="col-md-12">
        <a href="<?php echo esc_url(pplus_lang_url('/')); ?>" class="site-logo">
          <img src="<?php echo esc_url($theme_uri . '/assets/officebase/Common/Images/logo.png'); ?>" width="100" height="94" alt="logo" />
        </a>
        <div class="header-navigation font-transform-inherit" data-pplus-menu>
          <div class="mobi-toggler" data-pplus-toggle-menu><i class="toggle-menu-ico" aria-label="Toggle"></i></div>

          <div class="additional-nav-mobile">
            <div id="LanguageDropDown" class="btn-group dropdown pull-left">
              <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" data-delay="1000" data-close-others="true">
                <img src="<?php echo esc_url($theme_uri . '/assets/Content/Img/langue-icon.png'); ?>" width="18" height="18" alt="lang" /><span> <?php echo esc_html($cur_lang); ?></span>
              </button>
              <ul class="dropdown-menu dropdown-menu-right LanguageDropDown LanguageDropDown-link" role="menu">
                <?php foreach (pplus_language_options() as $lg) : if ($lg['Name'] === $cur_lang) { continue; } ?>
                  <li class="language-menu-item" data-lang="<?php echo esc_attr($lg['Name']); ?>">
                    <a href="<?php echo esc_url(pplus_language_switch_url($lg['Name'])); ?>"><span><?php echo esc_html($lg['DisplayName']); ?></span></a>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>

          <ul>
            <!-- Can ho tai Ha Noi (mega dropdown) -->
            <li class="dropdown" data-pplus-submenu>
              <a class="target-link dropdown-toggle target-link-hanoi" href="<?php echo esc_url(pplus_lang_url($apartment . '/ha-noi')); ?>" data-pplus-submenu-toggle>
                <?php echo esc_html(pplus_localize('Căn hộ tại Hà Nội')); ?>
                <i class="fa fa-chevron-right arrow-menu" aria-hidden="true"></i>
              </a>
              <div class="dropdown-menu dropdown-menu-quan">
                <div class="menu-quan-group">
                  <a class="target-link-click" href="<?php echo esc_url(pplus_lang_url($apartment . '/ha-noi')); ?>"><?php echo esc_html(pplus_localize('Xem tất cả')); ?></a>
                  <div class="menu-quan-from aparment-l">
                    <div class="menu-quan-title"><?php echo esc_html(pplus_localize('Tìm theo quận')); ?></div>
                    <ul class="menu-quan-dropdown"><?php $render_alias_items((array) ($header['dataLists'] ?? []), $apartment); ?></ul>
                  </div>
                  <div class="menu-quan-from aparment-l mt-25">
                    <ul class="menu-quan-dropdown"><?php $render_alias_items((array) ($header['apartmentLuxury'] ?? []), $apartment); ?></ul>
                  </div>
                  <div class="menu-hang-from">
                    <div class="menu-quan-title"><?php echo esc_html(pplus_localize('Tìm theo tòa nhà')); ?></div>
                    <ul class="menu-quan-dropdown">
                      <?php foreach ((array) ($header['projectList'] ?? []) as $item) :
                        $alias = (string) ($item['alias'] ?? '');
                        $name  = (string) ($item['project']['projectName'] ?? ''); ?>
                        <li class="menu-quan"><a class="district-name" href="<?php echo esc_url(pplus_lang_url($apartment . '/ha-noi/' . $alias)); ?>"><?php echo esc_html($name); ?><i class="icon-menu-link"></i></a></li>
                      <?php endforeach; ?>
                    </ul>
                  </div>
                  <div class="menu-hang-from aparment-2">
                    <div class="menu-quan-title"><?php echo esc_html(pplus_localize('Căn hộ Cao cấp')); ?></div>
                    <ul class="menu-quan-dropdown menu-quan-dropdown-one">
                      <?php foreach ((array) ($header['projectListLuxury'] ?? []) as $item) :
                        $alias = (string) ($item['alias'] ?? '');
                        $name  = (string) ($item['project']['projectName'] ?? ''); ?>
                        <li class="menu-quan"><a class="district-name" href="<?php echo esc_url(pplus_lang_url($apartment . '/ha-noi/' . $alias)); ?>"><?php echo esc_html($name); ?><i class="icon-menu-link"></i></a></li>
                      <?php endforeach; ?>
                    </ul>
                  </div>
                </div>
              </div>
            </li>

            <!-- Biet thu tai Ha Noi -->
            <li class="dropdown" data-pplus-submenu>
              <a class="dropdown-toggle" href="#" data-pplus-submenu-toggle>
                <?php echo esc_html(pplus_localize('Biệt thự tại Hà Nội')); ?>
                <i class="fa fa-chevron-right arrow-menu" aria-hidden="true"></i>
              </a>
              <div class="dropdown-menu">
                <div class="dropdown-menu-office-style">
                  <div class="menu-quan-title"><?php echo esc_html(pplus_localize('Tìm trên bản đồ')); ?></div>
                  <?php foreach ((array) ($header['apartmentVilla'] ?? []) as $item) :
                    $alias = (string) ($item['alias'] ?? '');
                    $sub   = (string) ($item['subTitle'] ?? ''); ?>
                    <li class="li-orther"><a href="<?php echo esc_url(pplus_lang_url($villa . '/ha-noi/' . $alias)); ?>"><?php echo esc_html($sub); ?></a><i class="icon-menu-link"></i></li>
                  <?php endforeach; ?>
                </div>
              </div>
            </li>

            <!-- Loai can ho -->
            <li class="dropdown" data-pplus-submenu>
              <a class="dropdown-toggle" href="#" data-pplus-submenu-toggle>
                <?php echo esc_html(pplus_localize('Loại căn hộ')); ?>
                <i class="fa fa-chevron-right arrow-menu" aria-hidden="true"></i>
              </a>
              <div class="dropdown-menu">
                <div class="dropdown-menu-office-style">
                  <div class="menu-quan-title"><?php echo esc_html(pplus_localize('Tìm trên bản đồ')); ?></div>
                  <?php foreach ((array) ($header['apartmentType'] ?? []) as $item) :
                    $alias = (string) ($item['alias'] ?? '');
                    $sub   = (string) ($item['subTitle'] ?? ''); ?>
                    <li class="li-orther"><a href="<?php echo esc_url(pplus_lang_url($apartment . '/ha-noi/' . $alias)); ?>"><?php echo esc_html($sub); ?></a><i class="icon-menu-link"></i></li>
                  <?php endforeach; ?>
                </div>
              </div>
            </li>

            <!-- Dich vu -->
            <li class="dropdown" data-pplus-submenu>
              <a class="dropdown-toggle" href="#" data-pplus-submenu-toggle>
                <?php echo esc_html(pplus_localize('Dịch vụ')); ?>
                <i class="fa fa-chevron-right arrow-menu" aria-hidden="true"></i>
              </a>
              <div class="dropdown-menu">
                <ul class="dropdown-menu-office-style">
                  <li><a href="<?php echo esc_url(pplus_lang_url('service/' . pplus_localize('dich-vu-quan-ly-cua-property-plus') . '-1')); ?>"><?php echo esc_html(pplus_localize('Dịch vụ Quản lý của Property Plus')); ?></a><i class="icon-menu-link"></i></li>
                  <li><a href="<?php echo esc_url(pplus_lang_url('service/' . pplus_localize('dich-vu-don-phong') . '-2')); ?>"><?php echo esc_html(pplus_localize('Dịch vụ dọn phòng')); ?></a><i class="icon-menu-link"></i></li>
                  <li><a href="<?php echo esc_url(pplus_lang_url('service/' . pplus_localize('dich-vu-ky-thuat-bao-duong') . '-3')); ?>"><?php echo esc_html(pplus_localize('Dịch vụ kỹ thuật - bảo dưỡng')); ?></a><i class="icon-menu-link"></i></li>
                  <li><a href="<?php echo esc_url(pplus_lang_url('service/' . pplus_localize('dang-ky-dich-vu') . '-4')); ?>"><?php echo esc_html(pplus_localize('Đăng ký dịch vụ')); ?></a><i class="icon-menu-link"></i></li>
                </ul>
              </div>
            </li>

            <!-- Chu nha -->
            <li class="dropdown" data-pplus-submenu>
              <a class="dropdown-toggle" href="#" data-pplus-submenu-toggle>
                <?php echo esc_html(pplus_localize('Chủ nhà')); ?>
                <i class="fa fa-chevron-right arrow-menu" aria-hidden="true"></i>
              </a>
              <div class="dropdown-menu">
                <ul class="dropdown-menu-office-style">
                  <li><a href="<?php echo esc_url(pplus_lang_url('owner/' . pplus_localize('loi-ich-chu-nha'))); ?>"><?php echo esc_html(pplus_localize('Lợi ích của chủ nhà')); ?></a><i class="icon-menu-link"></i></li>
                  <li><a href="<?php echo esc_url(pplus_lang_url('owner/' . pplus_localize('dang-ky-chu-nha'))); ?>"><?php echo esc_html(pplus_localize('Đăng ký cho thuê nhà')); ?></a><i class="icon-menu-link"></i></li>
                </ul>
              </div>
            </li>

            <li><a href="<?php echo esc_url(pplus_lang_url(pplus_localize('ve-chung-toi'))); ?>"><?php echo esc_html(pplus_localize('Về chúng tôi')); ?></a></li>
            <li><a href="<?php echo esc_url(pplus_lang_url('blogs')); ?>"><?php echo esc_html(pplus_localize('Blog')); ?></a></li>
          </ul>
        </div>

        <div class="action-righh-headed">
          <div class="moblie-show"></div>
          <div class="pc-show">
            <a href="<?php echo esc_url(pplus_lang_url('danh-sach-yeu-thich')); ?>" class="action-yeu-thich" aria-label="Like page">
              <img src="<?php echo esc_url($theme_uri . '/assets/Content/Img/heart-circle.png'); ?>" width="18" alt="lang" />
              <div class="dot-like"></div>
            </a>
            <div class="additional-nav pull-left action-langue">
              <div id="LanguageDropDown" class="btn-group dropdown pull-left">
                <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" data-delay="1000" data-close-others="true">
                  <img src="<?php echo esc_url($theme_uri . '/assets/Content/Img/LangDrop.svg'); ?>" width="16" height="16" alt="lang" /><span><?php echo esc_html($cur_lang); ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-right LanguageDropDown-link" role="menu">
                  <?php foreach (pplus_language_options() as $lg) : if ($lg['Name'] === $cur_lang) { continue; } ?>
                    <li class="language-menu-item" data-lang="<?php echo esc_attr($lg['Name']); ?>">
                      <a href="<?php echo esc_url(pplus_language_switch_url($lg['Name'])); ?>"><span><?php echo esc_html($lg['DisplayName']); ?></span></a>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Footer mobile bar -->
<div class="footer-mobile">
  <div class="action-footer action-search" data-pplus-search-mobile>
    <i style="padding-top:2px;"><svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21.4631 18.8707L16.8091 14.2167C17.7707 12.7675 18.3335 11.0322 18.3335 9.16676C18.3335 4.11221 14.2213 0 9.16676 0C4.11221 0 0 4.11221 0 9.16676C0 14.2213 4.11221 18.3335 9.16676 18.3335C11.0322 18.3335 12.7675 17.7707 14.2167 16.8091L18.8707 21.4631C19.5857 22.179 20.748 22.179 21.4631 21.4631C22.179 20.7471 22.179 19.5866 21.4631 18.8707ZM2.75003 9.16676C2.75003 5.62839 5.62839 2.75003 9.16676 2.75003C12.7051 2.75003 15.5835 5.62839 15.5835 9.16676C15.5835 12.7051 12.7051 15.5835 9.16676 15.5835C5.62839 15.5835 2.75003 12.7051 2.75003 9.16676Z" fill="#3E5FB4"/></svg></i>
    <span><?php echo esc_html(pplus_localize('Tìm kiếm')); ?></span>
  </div>
  <div class="action-footer action-chat-support" data-id="modal-container5" data-pplus-open-modal="modal-container5">
    <i><svg xmlns="http://www.w3.org/2000/svg" width="24" height="25" viewBox="0 0 24 25" fill="none"><mask id="mh0" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="24" height="25"><rect y="0.0151367" width="24" height="24" fill="#D9D9D9"/></mask><g mask="url(#mh0)"><path d="M2 20.5901V5.01514C2 4.46514 2.196 3.99414 2.588 3.60214C2.97933 3.2108 3.45 3.01514 4 3.01514H20C20.55 3.01514 21.021 3.2108 21.413 3.60214C21.8043 3.99414 22 4.46514 22 5.01514V17.0151C22 17.5651 21.8043 18.0361 21.413 18.4281C21.021 18.8195 20.55 19.0151 20 19.0151H6L3.7 21.3151C3.38333 21.6318 3.02067 21.7025 2.612 21.5271C2.204 21.3525 2 21.0401 2 20.5901Z" fill="#3E5FB4"/></g></svg></i>
    <span><?php echo esc_html(pplus_localize('Liên hệ')); ?></span>
  </div>
  <div class="action-footer action-heft-like">
    <a href="<?php echo esc_url(pplus_lang_url('danh-sach-yeu-thich')); ?>">
      <i><svg xmlns="http://www.w3.org/2000/svg" width="24" height="25" viewBox="0 0 24 25" fill="none"><mask id="mh1" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="24" height="25"><rect y="0.952393" width="24" height="24" fill="#D9D9D9"/></mask><g mask="url(#mh1)"><path d="M10.65 20.7524L8.925 19.1774C7.15833 17.5608 5.56267 15.9564 4.138 14.3644C2.71267 12.7731 2 11.0191 2 9.10242C2 7.53575 2.525 6.22742 3.575 5.17742C4.625 4.12742 5.93333 3.60242 7.5 3.60242C8.38333 3.60242 9.21667 3.78975 10 4.16442C10.7833 4.53975 11.45 5.05242 12 5.70242C12.55 5.05242 13.2167 4.53975 14 4.16442C14.7833 3.78975 15.6167 3.60242 16.5 3.60242C18.0667 3.60242 19.375 4.12742 20.425 5.17742C21.475 6.22742 22 7.53575 22 9.10242C22 11.0191 21.2917 12.7774 19.875 14.3774C18.4583 15.9774 16.85 17.5858 15.05 19.2024L13.35 20.7524C12.9667 21.1024 12.5167 21.2774 12 21.2774C11.4833 21.2774 11.0333 21.1024 10.65 20.7524Z" fill="#3E5FB4"/></g></svg></i>
      <span><?php echo esc_html(pplus_localize('Đã lưu')); ?></span>
    </a>
  </div>
  <div class="action-footer notshow-modal" data-pplus-toggle-menu>
    <div class="mobi-toggler">
      <i style="padding-top:5px;"><svg width="23" height="16" viewBox="0 0 23 16" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="23" height="3" rx="1.5" fill="#3E5FB4"/><rect y="6" width="23" height="3" rx="1.5" fill="#3E5FB4"/><rect y="13" width="23" height="3" rx="1.5" fill="#3E5FB4"/></svg></i>
      <span><?php echo esc_html(pplus_localize('Menu')); ?></span>
    </div>
  </div>
</div>
