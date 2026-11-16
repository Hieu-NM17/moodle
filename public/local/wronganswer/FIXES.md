# Phân tích lỗi và các sửa đổi

## 1. Cấu hình review options không khớp với tài liệu

Mã cũ chỉ hiển thị nút và cho phép mở trang luyện tập khi cả `$reviewoptions->attempt` và `$reviewoptions->correctness` đều bật. Nhưng README/hướng dẫn cũ chỉ dặn bật `Whether correct`. Khi `The attempt` bị tắt, điều kiện trong hook luôn return và người học không thấy nút.

**Xử lý:** giữ nguyên điều kiện kiểm soát quyền để không vượt qua review options của giảng viên; cập nhật README và hướng dẫn cài đặt để yêu cầu bật đồng thời hai mục tại cùng thời điểm review.

## 2. Gọi sai API kiểm tra quyền tệp câu hỏi

Mã cũ gọi:

```php
$usage->check_file_access($slot, $isreviewing, $context->id,
        $component, $filearea, $args, $forcedownload);
```

Trong Moodle 5.3, `question_usage_by_activity::check_file_access()` nhận 6 tham số: `slot`, `question_display_options`, `component`, `filearea`, `args`, `forcedownload`. Cách truyền trên dùng sai overload của `mod_quiz\quiz_attempt::check_file_access()`, khiến đối số bị lệch kiểu/vị trí.

**Xử lý:** xây dựng `question_display_options` đúng với session đang luyện hoặc đã hoàn tất và gọi API của Question Usage bằng đúng 6 tham số.

## 3. Bảo vệ trang luyện tập

**Xử lý:** trước khi render, trang luyện tập xác nhận quyền review attempt và để Quiz access manager áp dụng các điều kiện truy cập chuẩn. Session vẫn phải thuộc người dùng hiện tại; attempt gốc phải là attempt đã nộp, không phải preview.

## 4. Phạm vi dữ liệu

Không tạo hoặc cập nhật `quiz_attempts`, gradebook hay attempt gốc. Mỗi lượt luyện tập có Question Usage riêng thuộc `local_wronganswer` và metadata session riêng trong `local_wronganswer_sess`.

## 5. Kiểm thử

Đã kiểm tra cú pháp PHP và XML tĩnh. Chưa có Moodle runtime/database trong phiên này nên chưa khẳng định được kiểm thử tích hợp; cần chạy các test cases trong `INSTALL_TEST.md`.
