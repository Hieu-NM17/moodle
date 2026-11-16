# local_wronganswer — Làm lại câu hỏi sai trong Moodle

Plugin local dành cho Moodle **5.3+**, được xây dựng trên nhánh `project-dev` của repository `Hieu-NM17/moodle`.

## Phạm vi tính năng

- Hiển thị nút **Làm lại câu hỏi sai** trên trang review của Quiz khi người học đang xem lần làm bài đã nộp của chính mình.
- Chỉ hoạt động khi review options cho phép hiển thị đồng thời **The attempt** và **Whether correct** tại thời điểm review.
- Lấy câu sai, đúng một phần và bỏ trống; câu tự luận chưa được chấm thủ công không bị kết luận là sai.
- Tạo Question Usage độc lập thuộc component `local_wronganswer`, sử dụng hành vi `deferredfeedback`.
- Cho phép nộp lượt luyện tập, xem kết quả và tiếp tục luyện các câu còn sai.
- Lưu session riêng; không tạo `quiz_attempts`, không cập nhật gradebook và không sửa câu trả lời trong Quiz gốc.
- Phục vụ tệp/ảnh của câu hỏi bằng quyền truy cập của Question Engine.
- Dọn dữ liệu luyện tập khi attempt Quiz gốc bị xóa hoặc khi gỡ plugin.
- Có Privacy API để xuất và xóa dữ liệu luyện tập.

## Cấu trúc

```text
local_wronganswer/
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

Trong repository `Hieu-NM17/moodle`, Moodle code root nằm trong `public/`. Đặt plugin tại:

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

Ví dụ: bật cả hai tại **After the quiz is closed**. Nếu chỉ bật `Whether correct` nhưng tắt `The attempt`, plugin sẽ không hiển thị nút và từ chối URL luyện tập trực tiếp. Đây là chủ đích để không vượt qua cấu hình review của Quiz.

## Kiểm tra luồng người học

1. Tạo Quiz có các câu tự động chấm, ví dụ Multiple choice, True/False hoặc Short answer.
2. Bật hai review options ở trên.
3. Làm bài có ít nhất một câu sai hoặc bỏ trống rồi submit.
4. Mở trang review của attempt vừa nộp.
5. Bấm **Làm lại câu hỏi sai**, trả lời và chọn **Nộp bài luyện tập**.
6. Xem kết quả; nếu còn sai, chọn **Làm lại những câu vẫn còn sai**.

## Các ca kiểm thử cần xác nhận

- Tất cả câu đúng: không xuất hiện nút luyện lại.
- Có câu sai: chỉ câu sai được đưa vào lượt luyện tập.
- Có câu đúng một phần: câu đó được đưa vào lượt luyện tập.
- Câu bỏ trống: câu đó được đưa vào lượt luyện tập.
- Câu tự luận chưa chấm: không bị kết luận là sai.
- Tắt một trong hai review options: không hiển thị nút; truy cập trực tiếp cũng bị từ chối.
- Người dùng khác: không thể mở session của người học trước.
- Sau khi nộp lượt luyện tập: attempt number và điểm Quiz gốc không thay đổi.
- Ảnh/tệp đính kèm trong câu hỏi hiển thị đúng trong lượt luyện tập.
- Sau khi làm đúng hết: không còn nút luyện tiếp.

## Giới hạn

Gói được rà soát mã nguồn và kiểm tra cú pháp PHP/XML tĩnh. Chưa được cài vào một Moodle runtime có database và Quiz thật trong phiên xử lý này; cần chạy các ca kiểm thử tích hợp trước khi merge.
