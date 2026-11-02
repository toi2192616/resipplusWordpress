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
            ],
            'ja' => [
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
            ],
        ];
    }
    return $all[$lang] ?? [];
}
