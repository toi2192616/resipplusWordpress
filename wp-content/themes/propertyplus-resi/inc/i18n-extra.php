<?php
/**
 * Ban dich BO SUNG cho cac chuoi tinh cua theme ma tu dien GetLangApi cua web chinh (API {lang}/Home/GetLangApi) KHONG co:
 * 30 khoa tieng Viet (quet 2/11/2026: moi pplus_localize('...') trong theme doi chieu voi tu dien en + ja) - cac muc "bo suu tap yeu
 * thich", nut ban do, nhan form... Truoc day chung roi ve TIENG VIET tren trang tieng Anh/Nhat (vd aria-label "Yêu thích", "Đóng bản đồ",
 * nhan "Tôi muốn nhận thông tin từ Property Plus", thong bao PDF/yeu thich).
 *
 * Thu tu tra cuu cua pplus_localize()/pplus_t(): tu dien API (neu ban quan tri da them ban dich thi uu tien) -> bang nay -> (pplus_t: ban
 * tieng Anh) -> khoa goc. Khi bien tap vien them ban dich vao CSDL tu dien thi dong tuong ung o day tu het tac dung, khong can xoa.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @return array<string,string> khoa tieng Viet => ban dich cua $lang ('en' | 'ja'); [] voi vi.
 */
function pplus_extra_dictionary(string $lang): array {
    static $all = null;
    if ($all === null) {
        $all = [
            'en' => [
                // Dia chi footer/JSON-LD sau khi van phong chuyen xuong tang 4 (4/11/2026) - tu dien API chi co khoa "Tầng 06..." cu
                'Tầng 4, tòa nhà Kinh Đô, 292 Tây Sơn, phường Trung Liệt, quận Đống Đa, Hà Nội' => '4th floor, Kinh Do building, 292 Tay Son street, Trung Liet ward, Dong Da district, Ha Noi',
                'Chưa có bộ sưu tập nào'                        => 'No collections yet',
                'Bạn chưa chọn danh mục yêu thích'              => "You haven't selected a favourite collection",
                'Thêm mới bộ sưu tập thành công'                => 'Collection created successfully',
                'Cập nhật bộ sưu tập thành công'                => 'Collection updated successfully',
                'Xóa bộ sưu tập thành công'                     => 'Collection deleted successfully',
                'Bạn muốn bỏ bộ sưu tập yêu thích này?'         => 'Do you want to remove this favourite collection?',
                'Copy link yêu thích thành công!'               => 'Favourite link copied!',
                'Bạn xác nhận muốn tải file PDF bộ sưu tập này?' => 'Do you want to download the PDF of this collection?',
                'Tải file PDF thành công!'                      => 'PDF downloaded successfully!',
                'Không tải được file PDF!'                      => 'Could not download the PDF file!',
                'Không thấy căn hộ nào'                         => 'No apartments found',
                'Sửa'                                           => 'Edit',
                'Đổi tên bộ sưu tập'                            => 'Rename collection',
                'Tạo bộ sưu tập mới'                            => 'Create new collection',
                'Thêm vào'                                      => 'Add to',
                'Lưu ngày'                                      => 'Saved on',
                'Email không đúng định dạng!'                   => 'Invalid email format!',
                'Gửi yêu cầu thất bại, vui lòng thử lại sau.'   => 'Failed to send the request, please try again later.',
                'Trước'                                         => 'Previous',
                'Chỗ ở'                                         => 'Accommodation',
                'Chưa có dữ liệu ...'                           => 'No data yet ...',
                'Bản đồ'                                        => 'Map',
                'Không tìm thấy căn hộ phù hợp.'                => 'No matching apartments found.',
                'Đóng bản đồ'                                   => 'Close map',
                'Hà Nội'                                        => 'Hanoi',
                'Hiển thị thông tin'                            => 'Show information',
                'Tôi muốn nhận thông tin từ  Property Plus'     => 'I want to receive information from Property Plus',
                'Gửi'                                           => 'Send',
                'Yêu thích'                                     => 'Favourite',
                'Nhập số'                                       => 'Enter number',
                // Trang chi tiet danh sach yeu thich (4/11/2026)
                'Chi tiết yêu thích'                            => 'Favourite details',
                'Link chia sẻ'                                  => 'Shared link',
                'Copy link'                                     => 'Copy link',
                'Danh sách này chưa có căn hộ nào'              => 'This list has no apartments yet.',
                'Danh sách này không có trên thiết bị này'      => 'This list is not on this device. Favourite lists are stored only in the browser that created them - use "Copy link" on the original device to share one.',
                'Không tải được danh sách yêu thích'            => 'Could not load the favourite list. Please try again.',
                'Bạn muốn bỏ yêu thích căn hộ này?'             => 'Remove this apartment from your favourites?',
                'Đã bỏ yêu thích'                               => 'Removed from favourites',
                'Quay lại danh sách yêu thích'                  => 'Back to the favourite list',
            ],
            'ja' => [
                'Tầng 4, tòa nhà Kinh Đô, 292 Tây Sơn, phường Trung Liệt, quận Đống Đa, Hà Nội' => 'ハノイ、ドンダー区、チュンリエット区、タイソン292、キンドビル4階',
                'Chưa có bộ sưu tập nào'                        => 'コレクションはまだありません',
                'Bạn chưa chọn danh mục yêu thích'              => 'お気に入りのコレクションが選択されていません',
                'Thêm mới bộ sưu tập thành công'                => 'コレクションを追加しました',
                'Cập nhật bộ sưu tập thành công'                => 'コレクションを更新しました',
                'Xóa bộ sưu tập thành công'                     => 'コレクションを削除しました',
                'Bạn muốn bỏ bộ sưu tập yêu thích này?'         => 'このお気に入りコレクションを削除しますか？',
                'Copy link yêu thích thành công!'               => 'お気に入りのリンクをコピーしました！',
                'Bạn xác nhận muốn tải file PDF bộ sưu tập này?' => 'このコレクションのPDFをダウンロードしますか？',
                'Tải file PDF thành công!'                      => 'PDFをダウンロードしました！',
                'Không tải được file PDF!'                      => 'PDFをダウンロードできませんでした！',
                'Không thấy căn hộ nào'                         => 'アパートが見つかりません',
                'Sửa'                                           => '編集',
                'Đổi tên bộ sưu tập'                            => 'コレクション名を変更',
                'Tạo bộ sưu tập mới'                            => '新しいコレクションを作成',
                'Thêm vào'                                      => '追加',
                'Lưu ngày'                                      => '保存日',
                'Email không đúng định dạng!'                   => 'メールアドレスの形式が正しくありません！',
                'Gửi yêu cầu thất bại, vui lòng thử lại sau.'   => '送信に失敗しました。後でもう一度お試しください。',
                'Trước'                                         => '前へ',
                'Chỗ ở'                                         => '宿泊施設',
                'Chưa có dữ liệu ...'                           => 'データがありません ...',
                'Bản đồ'                                        => '地図',
                'Không tìm thấy căn hộ phù hợp.'                => '条件に合うアパートが見つかりません。',
                'Đóng bản đồ'                                   => '地図を閉じる',
                'Hà Nội'                                        => 'ハノイ',
                'Hiển thị thông tin'                            => '情報を表示',
                'Tôi muốn nhận thông tin từ  Property Plus'     => 'プロパティプラスからの情報を受け取りたい',
                'Gửi'                                           => '送信',
                'Yêu thích'                                     => 'お気に入り',
                'Nhập số'                                       => '数字を入力',
                // Trang chi tiet danh sach yeu thich (4/11/2026)
                'Chi tiết yêu thích'                            => 'お気に入りの詳細',
                'Link chia sẻ'                                  => '共有リンク',
                'Danh sách này chưa có căn hộ nào'              => 'このリストにはまだ物件がありません。',
                'Danh sách này không có trên thiết bị này'      => 'このリストはこの端末にありません。お気に入りリストは作成したブラウザにのみ保存されます。共有するには、元の端末で「リンクをコピーする」を使ってください。',
                'Không tải được danh sách yêu thích'            => 'お気に入りリストを読み込めませんでした。もう一度お試しください。',
                'Bạn muốn bỏ yêu thích căn hộ này?'             => 'この物件をお気に入りから削除しますか？',
                'Đã bỏ yêu thích'                               => 'お気に入りから削除しました',
                'Quay lại danh sách yêu thích'                  => 'お気に入りリストに戻る',
                // Thong bao cua form tu van (inc/advise-form.php) - 4/11/2026
                'Bạn chưa nhập Email hoặc Số điện thoại' => 'メールアドレスまたは電話番号を入力してください',
                'Số điện thoại không đúng định dạng!' => '電話番号の形式が正しくありません！',
                'Gửi yêu cầu tư vấn thành công, chúng tôi sẽ liên hệ lại sớm nhất!' => 'ご相談を受け付けました。担当者より折り返しご連絡いたします。',
                // Thong bao cua form Dang ky dich vu / Dang ky cho thue nha (inc/service-register-route.php, owner-register-route.php) - 4/11/2026
                'Đăng ký dịch vụ thành công, chúng tôi sẽ liên hệ lại sớm nhất!' => 'サービスのお申し込みを受け付けました。担当者より折り返しご連絡いたします。',
                'Đăng ký cho thuê nhà thành công, chúng tôi sẽ liên hệ lại sớm nhất!' => '物件掲載のお申し込みを受け付けました。担当者より折り返しご連絡いたします。',
                'Bạn chưa xác nhận đồng ý với điều khoản của chúng tôi!' => '利用規約への同意が確認されていません！',
                // Hop "Them vao uu thich": tick = vao ngay, bo tick = ra ngay (4/11/2026)
                'Đã thêm vào “%s”' => '「%s」に追加しました',
                'Đã gỡ khỏi “%s”' => '「%s」から削除しました',
                'Xong' => '完了',
                // Lop cho khi tao PDF yeu thich (4/11/2026)
                'Đang tạo file PDF, vui lòng chờ...' => 'PDFを作成中です。しばらくお待ちください...',
                // Trang Lien he (4/11/2026) - web chinh hien tieng Viet tren /ja/contact vi tu dien chua co cac cau nay
                'Chúng tôi hân hạnh được tư vấn cho bạn' => 'ご相談を心よりお待ちしております',
                'Tư vẫn miến phí - Giá thuê tốt - Gửi báo giá nhanh chóng' => '無料相談・お得な賃料・迅速なお見積もり',
                'Thông tin liên hệ' => 'お問い合わせ情報',
                'Tầng 4, tòa nhà Kinh Đô, 292 Tây Sơn, Trung Liệt, Đống Đa, Hà Nội' => 'ハノイ市ドンダー区チュンリェット坊 タイソン通り292番地 キンドービル4階',
                'Địa chỉ của chúng tôi' => '所在地',
                'Tìm chúng tôi ở' => '公式SNS',
                'Để chúng tôi giúp bạn…' => 'お気軽にご相談ください…',
                'Property Plus - Hà Nội' => 'Property Plus - ハノイ',
                'Bản đồ Property Plus' => 'Property Plus の地図',
                // Popup tim kiem: vung/bo loc dien san (11/11/2026) - EN dung thang tham so pplus_t(), JA tra cuu o day
                'Xóa bộ lọc' => '条件をクリア',
                'Xóa vùng đã chọn' => '選択したエリアを削除',
                'Vùng' => 'エリア',
                'Đổi' => '変更',
                'PN' => '寝室',
                'WC' => 'バス',
                'Liên hệ Property Plus để được tư vấn miễn phí và báo giá nhanh căn hộ cho thuê tại Hà Nội. Hotline 08 5666 2566, văn phòng tầng 4 tòa nhà Kinh Đô, 292 Tây Sơn, Đống Đa.' => 'Property Plusへのお問い合わせ。ハノイのアパート賃貸について無料でご相談いただけ、迅速にお見積もりをお送りします。ホットライン 08 5666 2566。ハノイ市ドンダー区タイソン通り292番地 キンドービル4階。',
            ],
        ];
    }
    return $all[$lang] ?? [];
}
