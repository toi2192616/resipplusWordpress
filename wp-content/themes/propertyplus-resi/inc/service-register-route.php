<?php
/**
 * Submit AJAX cho form "Dang ky dich vu" (TypeSite 4, parts/service-register.php) - thay the
 * RegisterServiceComponent.registerServiceSubmit() (register-service.component.ts dong 132-191):
 * goi serviceService.IssueLeaseApartmentService(DataInput) -> POST {lang}/Service/
 * IssueLeaseApartmentService.
 *
 * MOT ROUTE RIENG, KHONG PHAI cung route/form voi inc/advise-form.php (SupportCustomerForm cua
 * page-reference.php): field khac nhau (them ProjectName/BuildingName/Area/FloorNo/RoomNo/
 * BedroomQty/PersonQty/InputPrice, checkbox ten "DongYDieuKhoan" chu khong phai "OwnerCheck") va
 * endpoint .NET khac nhau (Service/IssueLeaseApartmentService, KHONG PHAI Favourite/
 * IssueLeaseApartment) - xac nhan qua doc day du register-service.component.ts, dung nham route
 * cua nhau se gui sai payload len sai endpoint.
 *
 * Cung 1 quy uoc voi inc/advise-form.php (proxy qua REST WordPress rieng tranh CORS; ngon ngu cua
 * message tra ve PHAI doc tu tham so "lang" trong chinh $request, KHONG duoc dung pplus_current_lang()
 * - xem ghi chu day du trong file do, ly do giong het o day: URL cua REST route nay khong bao gio
 * mang tien to "/en").
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', function () {
    register_rest_route('pplus/v1', '/service-register', [
        'methods'             => 'POST',
        'callback'            => 'pplus_handle_service_register_submit',
        'permission_callback' => '__return_true', // public, giong endpoint .NET goc khong yeu cau auth
    ]);
});

function pplus_handle_service_register_submit(WP_REST_Request $request) {
    $lang = ($request->get_param('lang') === 'en') ? 'en' : 'vi';
    $t    = static fn (string $vi, string $en): string => $lang === 'en' ? $en : $vi;

    $name  = sanitize_text_field((string) $request->get_param('OwnerName'));
    $email = sanitize_email((string) $request->get_param('OwnerEmail'));
    $phone = sanitize_text_field((string) $request->get_param('OwnerMobile'));

    // Cung thu tu kiem tra voi registerServiceSubmit() ben Angular (dong 133-159): thieu email/dt
    // -> chua dong y dieu khoan -> sai dinh dang email -> sai dinh dang dien thoai.
    if ($email === '' || $phone === '') {
        return new WP_REST_Response([
            'success' => false,
            'message' => $t('Bạn chưa nhập Email hoặc Số điện thoại', 'Please enter your Email or Phone number'),
        ], 200);
    }

    $agree_terms = filter_var($request->get_param('DongYDieuKhoan'), FILTER_VALIDATE_BOOLEAN);
    if (!$agree_terms) {
        return new WP_REST_Response([
            'success' => false,
            'message' => $t('Bạn chưa xác nhận đồng ý với điều khoản của chúng tôi!', 'Please confirm you agree to our terms!'),
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

    // servicePrice[] duoc JS gui nguyen ven lai (khong sua doi) lam InputPrice - chi loc lai cho
    // gon (name/type/value) truoc khi gui .NET, tranh gui thua field noi bo (numberDefault/listSelect)
    // khong can thiet ben backend.
    $input_price_raw = $request->get_param('InputPrice');
    $input_price      = [];
    if (is_array($input_price_raw)) {
        foreach ($input_price_raw as $row) {
            if (!is_array($row)) {
                continue;
            }
            $input_price[] = [
                'name'  => sanitize_text_field((string) ($row['name'] ?? '')),
                'type'  => (int) ($row['type'] ?? 0),
                'value' => sanitize_text_field((string) ($row['value'] ?? '')),
            ];
        }
    }

    $payload = [
        'OwnerName'       => $name,
        'OwnerEmail'      => $email,
        'OwnerMobile'     => $phone,
        'ProjectName'     => sanitize_text_field((string) $request->get_param('ProjectName')),
        'BuildingName'    => sanitize_text_field((string) $request->get_param('BuildingName')),
        'Area'            => sanitize_text_field((string) $request->get_param('Area')),
        'FloorNo'         => sanitize_text_field((string) $request->get_param('FloorNo')),
        'RoomNo'          => sanitize_text_field((string) $request->get_param('RoomNo')),
        'BedroomQty'      => sanitize_text_field((string) $request->get_param('BedroomQty')),
        'PersonQty'       => sanitize_text_field((string) $request->get_param('PersonQty')),
        'DongYDieuKhoan'  => true,
        'InputPrice'      => $input_price,
    ];

    $url = pplus_api_base_url() . '/' . $lang . '/Service/IssueLeaseApartmentService';

    $response = wp_remote_post($url, [
        'timeout' => 8,
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
        'message' => $t('Đăng ký dịch vụ thành công, chúng tôi sẽ liên hệ lại sớm nhất!', 'Your service registration was successful, we will contact you soon!'),
    ], 200);
}
