<?php
/**
 * Submit AJAX cho tat ca form "Gui yeu cau tu van" (A2/A3/A4) va form lien he trong footer
 * (parts/footer.php #CompleteModalForm - da co san field dung ten, chi thieu noi submit, xem
 * TODO Dev B trong file do). CA 4 noi deu goi CUNG 1 endpoint that: officeService.IssueNewApartment()
 * -> POST {lang}/Favourite/IssueLeaseApartment - da doi chieu qua FavouriteController.cs
 * (`IssueLeaseApartment([FromBody] RentApartmentDto input)`), KHONG can JWT (public), khong co
 * CAPTCHA/anti-spam ben Angular goc.
 *
 * Proxy qua 1 REST route WordPress (khong goi thang tu browser sang admin.propertyplus.vn) de
 * tranh phu thuoc CORS cua .NET (chua ro co mo cho domain WordPress hay khong) - giong nguyen tac
 * "goi API .NET tu server PHP" da dung cho moi trang khac, chi khac la POST ghi du lieu nen KHONG
 * cache transient.
 *
 * Ngon ngu cua message tra ve (thanh cong/loi) PHAI doc tu tham so "lang" trong chinh $request (JS
 * tu xac dinh dung ngon ngu cua trang luc render, gui kem trong payload - xem PPLUS_LOCATION_LANG/
 * assets/js/main.js), KHONG duoc dung pplus_t()/pplus_current_lang() truc tiep trong file nay (bug
 * da sua, 13/8/2026, PHAT HIEN THEM khi kiem thu gui form that): 2 ham do doc tien to "/en" tu
 * CHINH URL cua request REST nay ("/wp-json/...", khong bao gio co "/en") nen LUON ra 'vi' du trang
 * goc dang la ban EN - xac nhan qua goi that: gui form tu trang EN van tra ve "Gửi yêu cầu tư vấn
 * thành công..." (tieng Viet). Cung 1 nguyen nhan da tung sua cho inc/location-search.php truoc do
 * - o day CON THEM 1 hau qua nghiem trong hon: chinh URL goi API .NET that ("{lang}/Favourite/
 * IssueLeaseApartment") cung bi anh huong, nghia la MOI yeu cau tu ban EN truoc gio deu bi goi
 * NHAM sang endpoint tieng Viet cua .NET.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', function () {
    register_rest_route('pplus/v1', '/advise', [
        'methods'             => 'POST',
        'callback'            => 'pplus_handle_advise_submit',
        'permission_callback' => '__return_true', // public, giong endpoint .NET goc khong yeu cau auth
    ]);
});

function pplus_handle_advise_submit(WP_REST_Request $request) {
    // Xem ghi chu dau file: "lang" PHAI lay tu request param (JS gui kem), KHONG duoc goi
    // pplus_current_lang() trong file nay.
    $lang = ($request->get_param('lang') === 'en') ? 'en' : 'vi';
    $t    = static fn (string $vi, string $en): string => $lang === 'en' ? $en : $vi;

    $name  = sanitize_text_field((string) $request->get_param('OwnerName'));
    $email = sanitize_email((string) $request->get_param('OwnerEmail'));
    $phone = sanitize_text_field((string) $request->get_param('OwnerMobile'));

    if ($email === '' || $phone === '') {
        return new WP_REST_Response([
            'success' => false,
            'message' => $t('Bạn chưa nhập Email hoặc Số điện thoại', 'Please enter your Email or Phone number'),
        ], 200);
    }
    if (!is_email($email)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => $t('Email không đúng định dạng!', 'Invalid email format!'),
        ], 200);
    }
    // Cung 1 quy tac so dien thoai VN don gian nhu Angular (validatePhone(): 9-11 chu so).
    if (!preg_match('/^\d{9,11}$/', $phone)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => $t('Số điện thoại không đúng định dạng!', 'Invalid phone number format!'),
        ], 200);
    }

    $advise_content = sanitize_textarea_field((string) $request->get_param('AdviseContent'));
    $acreage        = sanitize_text_field((string) $request->get_param('Acreage'));
    if ($acreage !== '') {
        // Giong het footer that (teamplate-footer.component.ts): ghep Acreage vao dau AdviseContent
        // truoc khi gui, khong co field rieng o phia .NET cho dien tich.
        $advise_content = trim(sprintf('%s: %s. %s', $t('Diện tích quan tâm', 'Area of interest'), $acreage, $advise_content));
    }

    $favorite_link = esc_url_raw((string) $request->get_param('FavoriteLink'));

    $url = pplus_api_base_url() . '/' . $lang . '/Favourite/IssueLeaseApartment';

    $response = wp_remote_post($url, [
        'timeout' => 8,
        'headers' => ['Content-Type' => 'application/json; charset=utf-8', 'Accept' => 'application/json'],
        'body'    => wp_json_encode([
            'OwnerName'    => $name,
            'OwnerEmail'   => $email,
            'OwnerMobile'  => $phone,
            'AdviseContent' => $advise_content,
            'FavoriteLink' => $favorite_link,
        ]),
    ]);

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) >= 400) {
        return new WP_REST_Response([
            'success' => false,
            'message' => $t('Gửi yêu cầu thất bại, vui lòng thử lại sau.', 'Failed to send your request, please try again later.'),
        ], 200);
    }

    return new WP_REST_Response([
        'success' => true,
        'message' => $t('Gửi yêu cầu tư vấn thành công, chúng tôi sẽ liên hệ lại sớm nhất!', 'Your request has been sent successfully, we will contact you soon!'),
    ], 200);
}
