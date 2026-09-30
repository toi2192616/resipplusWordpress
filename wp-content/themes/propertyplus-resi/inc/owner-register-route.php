<?php
/**
 * Submit AJAX cho form "Dang ky cho thue nha" (TypeSite 2, parts/owner-register-leasing.php) -
 * thay the RegisterLeasingComponent.IssueNewApartment() (register-leasing.component.ts dong
 * 125-177): goi serviceService.IssueNewApartment(this.Model) -> POST {lang}/Owner/
 * IssueNewApartment (service.service.ts dong 40-41 that - KHAC HAN Service/
 * IssueLeaseApartmentService cua inc/service-register-route.php, dung nham se 404/sai du lieu).
 *
 * Cung 1 quy uoc voi inc/advise-form.php va inc/service-register-route.php (proxy qua REST
 * WordPress rieng tranh CORS; ngon ngu cua message tra ve doc tu tham so "lang" trong chinh
 * $request, KHONG dung pplus_current_lang() - URL REST khong bao gio mang tien to "/en").
 *
 * "Model.People" (khai bao trong register-leasing.component.ts dong 41) KHONG duoc gan gia tri o
 * bat ky dau trong template that (khong co form field/binding nao) - luon la "null" thuc te, nen
 * KHONG gui field nay len (.NET nhan field thieu = null, tuong duong).
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', function () {
    register_rest_route('pplus/v1', '/owner-register', [
        'methods'             => 'POST',
        'callback'            => 'pplus_handle_owner_register_submit',
        'permission_callback' => '__return_true', // public, giong endpoint .NET goc khong yeu cau auth
    ]);
});

function pplus_handle_owner_register_submit(WP_REST_Request $request) {
    $lang = ($request->get_param('lang') === 'en') ? 'en' : 'vi';
    $t    = static fn (string $vi, string $en): string => $lang === 'en' ? $en : $vi;

    $name  = sanitize_text_field((string) $request->get_param('OwnerName'));
    $email = sanitize_email((string) $request->get_param('OwnerEmail'));
    $phone = sanitize_text_field((string) $request->get_param('OwnerMobile'));

    // Cung thu tu kiem tra voi IssueNewApartment() ben Angular (dong 127-146): thieu email/dt ->
    // sai dinh dang email -> sai dinh dang dien thoai. "Chua dong y dieu khoan" o day THEM VAO
    // (ban goc Angular KHONG kiem tra DongYDieuKhoan truoc khi goi API - checkbox chi hien thi,
    // khong co dieu kien chan nao trong IssueNewApartment() that) nhung van giu kiem tra nay ben WP
    // vi checkbox VAN hien thi tren giao dien y het ban goc (nguoi dung se hieu la bat buoc) - cung
    // 1 quyet dinh voi inc/service-register-route.php (TypeSite 4 CO kiem tra that trong ban goc,
    // owner THI KHONG nhung giu nhat quan UX giua 2 form dang ky trong CUNG 1 theme).
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
    if (!preg_match('/^\d{9,11}$/', $phone)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => $t('Số điện thoại không đúng định dạng!', 'Invalid phone number format!'),
        ], 200);
    }

    $photo_list_raw = $request->get_param('PhotoList');
    $photo_list      = [];
    if (is_array($photo_list_raw)) {
        foreach ($photo_list_raw as $item) {
            // Anh da la base64 data-URI tu chinh trinh duyet nguoi dung (FileReader.readAsDataURL,
            // xem MainTest() that) - chi ep kieu chuoi, KHONG sanitize_text_field() (se lam hong
            // ky tu base64 nhu "+"/"/") va KHONG gioi han so luong/dung luong o day (giao cho .NET
            // that tu xu ly/tu choi, dung y het ban goc khong gioi han client-side nao ca).
            if (is_string($item) && $item !== '') {
                $photo_list[] = $item;
            }
        }
    }

    $payload = [
        'OwnerName'        => $name,
        'OwnerEmail'       => $email,
        'OwnerMobile'      => $phone,
        'NameProject'      => sanitize_text_field((string) $request->get_param('NameProject')),
        'NameBuilding'     => sanitize_text_field((string) $request->get_param('NameBuilding')),
        'NumberFloor'      => sanitize_text_field((string) $request->get_param('NumberFloor')),
        'NumberApartment'  => sanitize_text_field((string) $request->get_param('NumberApartment')),
        'Area'             => sanitize_text_field((string) $request->get_param('Area')),
        'Bedroom'          => sanitize_text_field((string) $request->get_param('Bedroom')),
        'WCroom'           => sanitize_text_field((string) $request->get_param('WCroom')),
        'NTT'              => sanitize_text_field((string) $request->get_param('NTT')),
        'Remarks'          => sanitize_textarea_field((string) $request->get_param('Remarks')),
        'IsNegotiatePrice' => filter_var($request->get_param('IsNegotiatePrice'), FILTER_VALIDATE_BOOLEAN),
        'TotalPrice'       => sanitize_text_field((string) $request->get_param('TotalPrice')),
        'TotalPriceUnit'   => sanitize_text_field((string) $request->get_param('TotalPriceUnit')),
        'PhotoList'        => $photo_list,
        'FavoriteLink'     => esc_url_raw((string) $request->get_param('FavoriteLink')),
    ];

    $url = pplus_api_base_url() . '/' . $lang . '/Owner/IssueNewApartment';

    $response = wp_remote_post($url, [
        'timeout' => 15, // cao hon cac form khac (8s) vi payload co the mang nhieu anh base64
        'headers' => ['Content-Type' => 'application/json; charset=utf-8', 'Accept' => 'application/json'],
        'body'    => wp_json_encode($payload),
    ]);

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) >= 400) {
        return new WP_REST_Response([
            'success' => false,
            'message' => $t('Gửi yêu cầu thất bại, vui lòng thử lại sau.', 'Failed to send your request, please try again later.'),
        ], 200);
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (!is_array($body) || empty($body['success'])) {
        return new WP_REST_Response([
            'success' => false,
            'message' => $t('Gửi yêu cầu thất bại, vui lòng thử lại sau.', 'Failed to send your request, please try again later.'),
        ], 200);
    }

    return new WP_REST_Response([
        'success' => true,
        'message' => $t('Đăng ký cho thuê nhà thành công, chúng tôi sẽ liên hệ lại sớm nhất!', 'Your leasing registration was successful, we will contact you soon!'),
    ], 200);
}
