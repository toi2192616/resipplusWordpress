# Thay đổi dữ liệu trong Admin — 26/11/2026 (Dev B)

Đã kiểm tra lại qua API công khai và trên web thật. Giá trị cũ ghi ở đây để khôi phục khi cần.

## 1. Meta Description bài blog (tab Ngôn ngữ, en/ja)

18 ô (bài 99, 105, 106, 107, 108, 109, 111, 131, 139 × en/ja). Trước đó **tất cả đều trống** → khôi phục = xóa trống ô.
Giá trị mới: `blog-meta-drafts-2026-11-26.json` (dòng `status: applied`). Bản tiếng Việt cố ý không sửa.
Nội dung bài và các ô khác không đổi (đối chiếu chữ cả 18 bài; 4 bài có bản sao gốc khớp từng byte).

## 2. Tiêu đề tiếng Nhật sai ngữ pháp

| Bài | Cũ | Mới |
|---|---|---|
| 99 | ハノイで外国人向けの賃貸物件探しのに最適な場所 | ハノイで外国人向けの賃貸物件探しに最適な場所 |
| 105 | ハノイのアパート賃貸契約の注意すべき9つポイント | ハノイのアパート賃貸契約で注意すべき9つのポイント |

## 3. Tên chuyên mục blog (Tin tức Blog → Danh sách loại)

| id | Ô | Cũ | Mới |
|---|---|---|---|
| 14 | tiếng Việt | Khám phá việt nam | Khám phá Việt Nam |
| 14 | ja | Khám phá Việt Nam | ベトナムを知る |
| 17 | ja | 通り | 参考資料 |
| 2 | ja | 在留 - 時在留 | 在留・一時滞在 |
| 20 | ja | プロパティプラスを探検 | Property Plusについて |
| 3 | en | Labouring | Employment |

## 4. Viết lại bài #66 (trường quốc tế) — vi/en/ja

- Lý do: bản ja hỏng chữ trong nội dung gốc; cả 3 bản có thông tin liên hệ đã cũ (UNIS đổi số điện thoại, SIS đổi website/email, JIS thêm cơ sở mầm non, HIS thêm số tổng đài).
- Nguồn: website chính thức của từng trường, đọc ngày 26/11/2026 (hisvietnam.com, vanphuc.sis.edu.vn, unishanoi.org, jis.edu.vn, nordangliaeducation.com/bis-hanoi, stpaulhanoi.com, jsh.edu.vn). Thêm mục tham khảo Trường Nhật Bản Hà Nội (không ghi địa chỉ vì trang chính thức không nêu).
- Nội dung mới: `blog-66-2026-11-26/{vi,en,ja}.html` (khớp tuyệt đối với bản đang chạy).
- Bản cũ: `rollback/blog-66-2026-11-26/` (nội dung + `fields.json`).
- Tiêu đề: vi giữ nguyên; en `Top 06 prestigious internation school in Hanoi city` → `Top 6 International Schools in Hanoi`; ja `ハノイのトップ06名門インターナショナルスクール` → `ハノイのインターナショナルスクール6校｜所在地・連絡先・カリキュラム`. Đường dẫn (alias), chuyên mục, ảnh giữ nguyên.
- Nên kiểm tra lại thông tin liên hệ các trường khoảng mỗi 6 tháng.

## Cách lưu an toàn cho bài blog (quan trọng)

Hộp thoại ngôn ngữ gửi **toàn bộ nội dung** qua CKEditor khi bấm Ghi (bộ lọc ACF mặc định đang bật) → có thể làm mất định dạng của bài giàu HTML / ảnh nhúng. Khi chỉ sửa ô tiêu đề/mô tả: gửi lại đúng nội dung gốc (đọc từ `/admin/KhamPhas/EditOrCreateLang/{bài}/{dòng}`, bỏ phần thụt lề của khuôn HTML ở đầu/cuối), rồi đối chiếu sau khi lưu.
Form tiếng Việt có ô **Hiển thị** và **Bỏ Nofollow** (IsIndex): chỉ bấm Ghi khi cả hai đang được tick.
