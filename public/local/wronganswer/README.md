# local_wronganswer — Làm lại câu hỏi sai trong Moodle

Plugin local dành cho Moodle **5.3+**, được xây dựng cho repository `Hieu-NM17/moodle`.

## Phạm vi tính năng

- Hiển thị nút **Làm lại câu hỏi sai** trên trang review của Quiz khi người học đang xem lần làm bài đã nộp của chính mình.
- Chỉ hoạt động khi review options cho phép hiển thị đồng thời **The attempt** và **Whether correct** tại thời điểm review.
- Lấy câu sai, đúng một phần và bỏ trống thuộc nhóm câu hỏi tự động chấm.
- Không đưa câu hỏi tự luận/câu hỏi chờ chấm thủ công vào lượt luyện tập vì hành vi `deferredfeedback` dùng ở phiên luyện tập không tương thích với nhóm câu hỏi này.
- Tạo Question Usage độc lập thuộc component `local_wronganswer`, sử dụng hành vi `deferredfeedback`.
- Cho phép nộp lượt luyện tập, xem kết quả và tiếp tục luyện các câu còn sai.
- Kiểm tra POST, session key, danh sách slot và sequence-check của Question Engine trước khi chấm bài.
- Lưu session riêng; không tạo `quiz_attempts`, không cập nhật gradebook và không sửa câu trả lời trong Quiz gốc.
- Phục vụ tệp/ảnh của câu hỏi bằng quyền truy cập của Question Engine.
- Dọn dữ liệu luyện tập khi attempt Quiz gốc bị xóa hoặc khi gỡ plugin.
- Có Privacy API để xuất và xóa dữ liệu.

## Cấu trúc

```text
wronganswer/
├── classes/
│   ├── hook_callbacks.php
│   ├── local/practice_manager.php
│   ├── observer.php
│   └── privacy/provider.php
├── db/
│   ├── events.php
│   ├── hooks.php
│   ├── install.xml
│   └── uninstall.php
├── lang/en/local_wronganswer.php
├── lang/vi/local_wronganswer.php
├── lib.php
├── practice.php
├── styles.css
└── version.php
```

## Cài đặt

Đặt toàn bộ thư mục `wronganswer` tại:

```text
<repository>/public/local/wronganswer/
```

Sau đó chạy từ thư mục gốc repository, trong môi trường PHP có thể đọc `public/config.php` và kết nối đúng database:

```bash
php public/admin/cli/upgrade.php --non-interactive
php public/admin/cli/purge_caches.php
```

Nếu Moodle chạy bằng Docker, thực hiện hai lệnh bên trong container PHP/Web tương ứng. Có thể thay lệnh nâng cấp bằng **Site administration → Notifications**, sau đó purge caches.

## Cấu hình Quiz bắt buộc

Trong **Quiz settings → Review options**, bật cả hai mục sau tại cùng thời điểm người học được phép review:

- **The attempt**
- **Whether correct**

Ví dụ: bật cả hai tại **After the quiz is closed**. Nếu chỉ bật một trong hai, plugin chủ động không hiển thị nút và từ chối URL luyện tập trực tiếp để không vượt qua cấu hình review của giảng viên.

## Kiểm tra luồng người học

1. Tạo Quiz có các câu hỏi tự động chấm, ví dụ Multiple choice, True/False hoặc Short answer.
2. Bật hai review options ở trên.
3. Làm bài có ít nhất một câu sai hoặc bỏ trống rồi submit.
4. Mở trang review của attempt vừa nộp.
5. Bấm **Làm lại câu hỏi sai**, trả lời và chọn **Nộp bài luyện tập**.
6. Xem kết quả; nếu còn sai, chọn **Làm lại những câu vẫn còn sai**.

## Giới hạn

- Phiên luyện tập sử dụng `deferredfeedback`, nên chỉ nhận câu hỏi tự động chấm (`question_automatically_gradable`). Câu Essay/câu tự luận và các câu cần giáo viên chấm không được đưa vào phiên luyện tập.
- Không chạy được kiểm thử tích hợp trong gói này vì không có Moodle runtime/database/Quiz thật. Cần cài plugin trong bản Moodle của bạn và chạy các ca kiểm thử ở trên trước khi merge.
