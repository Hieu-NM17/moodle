# local_quizretry – Làm lại câu hỏi sai sau khi nộp bài quiz

Plugin dạng `local` cho Moodle 5.3+ (cấu trúc thư mục có `public/`). **Không sửa bất kỳ file core nào.**

## Tính năng

Sau khi nộp một lần làm bài quiz, người học thấy thẻ **"Luyện tập lại các câu làm sai"** ngay dưới trang *Xem lại bài làm*
(và dưới trang tổng quan quiz, cho lần làm gần nhất):

1. Bấm **"Làm lại N câu sai"** → hệ thống tạo một phiên luyện tập chỉ gồm các câu sai/bỏ trống (giữ nguyên biến thể câu hỏi,
   đảo lại thứ tự lựa chọn).
2. Làm bài theo cách "Phản hồi ngay" (nút *Check* từng câu) hoặc cách khác do admin chọn, rồi bấm **"Nộp bài luyện tập"**.
3. Xem kết quả, và nếu vẫn còn câu sai thì bấm **"Làm lại N câu vẫn còn sai"** để lặp lại đến khi đúng hết.
4. Mọi phiên luyện tập được lưu ở **Lịch sử luyện tập** (có thể làm dở rồi tiếp tục).

Điểm quan trọng: luyện tập **không tạo lần làm bài mới**, **không đổi điểm quiz, gradebook, số lần làm bài**.

## Cách hoạt động (không đụng core)

| Việc cần làm | Cách làm trong plugin |
|---|---|
| Hiện nút trên trang review/view của quiz | Hook `after_standard_main_region_html_generation` (db/hooks.php) |
| Lưu câu hỏi + câu trả lời luyện tập | Một `question_usage` riêng, component `local_quizretry` (không phải `mod_quiz`) |
| Trang làm bài / kết quả / lịch sử | `attempt.php`, `start.php`, `index.php` của plugin |
| Ảnh/file trong câu hỏi | Callback `local_quizretry_question_pluginfile` (lib.php) |
| Dọn dữ liệu khi xoá quiz / user / lần làm bài | Observer (db/events.php) + Privacy API |

### Câu nào được coi là "sai"?
Trả lời sai, hoặc bỏ trống. Tuỳ chọn admin: thêm câu đúng một phần. Không đưa vào: câu đúng, câu mô tả (không có điểm),
câu đang chờ chấm tay hoặc đã được giáo viên chấm/nhận xét tay.

### Bảo vệ cài đặt xem lại của giáo viên
Mặc định người học chỉ luyện tập được khi quiz **đã cho họ thấy đúng/sai và đáp án đúng** (theo "Tuỳ chọn xem lại" của quiz).
Nếu giáo viên cố tình ẩn đáp án tới khi đóng quiz, nút luyện tập sẽ chưa khả dụng (tránh lộ đáp án).
Admin có thể tắt yêu cầu đáp án đúng trong cài đặt plugin (đúng/sai vẫn bắt buộc phải hiển thị).

## Cài đặt

1. Copy thư mục `quizretry` vào `<thư-mục-dự-án>/public/local/quizretry`.
2. Từ thư mục gốc dự án: `php admin/cli/upgrade.php --non-interactive` (hoặc vào *Site administration → Notifications*).
3. `php admin/cli/purge_caches.php`.
4. Cài đặt: *Site administration → Plugins → Local plugins → Retry incorrect quiz questions*.

## Gỡ cài đặt
*Site administration → Plugins → Plugins overview → Local plugins → Uninstall*. Dữ liệu luyện tập sẽ bị xoá.

## Giới hạn hiện tại (v0.1.0)
- Tất cả câu sai hiển thị trên một trang (chưa phân trang).
- Giáo viên chưa có báo cáo tổng hợp việc luyện tập của học viên.
- Chưa có PHPUnit/Behat test.
