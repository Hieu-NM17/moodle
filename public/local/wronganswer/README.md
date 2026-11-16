# local_wronganswer — Làm lại câu hỏi sai trong Moodle

Plugin local dành cho Moodle **5.3+** (được tạo theo nhánh `project-dev` của repository `Hieu-NM17/moodle`, core build `20261030`).

## Phạm vi tính năng

- Hiển thị nút **Làm lại câu hỏi sai** sau nội dung trang review của Quiz, nếu người học đang xem lần làm bài của chính mình, lần làm bài đã hoàn tất và cấu hình Quiz cho phép xem tính đúng/sai.
- Lấy câu sai, đúng một phần, bỏ trống và câu được chấm thủ công là sai.
- Không đưa câu tự luận/chưa được chấm thủ công vào danh sách sai.
- Tạo một Question Usage độc lập thuộc component `local_wronganswer`, dùng hành vi `deferredfeedback`.
- Cho phép nộp lượt luyện tập, xem kết quả và tiếp tục luyện những câu vẫn sai.
- Lưu session riêng; không tạo `quiz_attempts`, không cập nhật gradebook và không thay đổi bài làm gốc.
- Có callback phục vụ ảnh/tệp đính kèm của câu hỏi theo quyền của Question Engine.
- Tự dọn các lượt luyện tập khi attempt Quiz gốc bị xóa hoặc khi gỡ plugin.
- Có khai báo Privacy API để xuất/xóa dữ liệu luyện tập.

## Cấu trúc

```text
local_wronganswer/
├── classes/
│   ├── hook_callbacks.php
│   ├── local/practice_manager.php
│   └── privacy/provider.php
├── db/
│   ├── hooks.php
│   └── install.xml
├── lang/
│   ├── en/local_wronganswer.php
│   └── vi/local_wronganswer.php
├── lib.php
├── practice.php
├── styles.css
└── version.php
```

## Cài vào repository Hieu-NM17/moodle

Trong repository này, Moodle code root nằm ở thư mục `public/`. Đặt plugin tại:

```text
<repository>/public/local/wronganswer/
```

Giải nén gói ZIP và copy thư mục plugin vào đúng vị trí. Ví dụ nếu clone repo ở `~/moodle`:

```bash
unzip -q /path/to/local_wronganswer.zip -d /tmp/local_wronganswer_package
mkdir -p ~/moodle/public/local
cp -r /tmp/local_wronganswer_package/local_wronganswer ~/moodle/public/local/wronganswer
cd ~/moodle
php public/admin/cli/upgrade.php --non-interactive
php public/admin/cli/purge_caches.php
```

Trên Windows PowerShell, có thể dùng:

```powershell
Expand-Archive .\local_wronganswer.zip $env:TEMP\local_wronganswer_package -Force
New-Item -ItemType Directory -Force .\public\local | Out-Null
Copy-Item -Recurse $env:TEMP\local_wronganswer_package\local_wronganswer .\public\local\wronganswer
```

Nếu Moodle code root của môi trường local đã trỏ thẳng vào thư mục `public/`, đường dẫn plugin tương ứng là:

```text
<MOODLE_DIR>/local/wronganswer/
```

Các lệnh CLI phải chạy trong môi trường PHP có thể đọc đúng `public/config.php` và kết nối được tới database của Moodle. Nếu Moodle đang chạy bằng Docker, thực hiện lệnh bên trong container PHP/Web phù hợp với compose project của bạn. Có thể thay lệnh CLI bằng cách đăng nhập Moodle với tài khoản quản trị, mở **Site administration → Notifications** và hoàn tất nâng cấp. Sau đó purge caches trong **Site administration → Development → Purge caches**.

## Cấu hình để kiểm thử

1. Mở một Quiz có một số câu tự động chấm, ví dụ Multiple choice, True/False hoặc Short answer.
2. Trong phần **Quiz settings → Review options**, bật **Whether correct** (Tính đúng/sai) ở thời điểm người học được phép xem lại bài, ví dụ **After the quiz is closed**. Nếu thiết lập này tắt, plugin sẽ không hiển thị nút và trang luyện tập cũng từ chối truy cập trực tiếp.
3. Làm một bài có ít nhất một câu đúng và một câu sai/bỏ trống, sau đó submit.
4. Mở trang review của lần làm bài vừa nộp. Nút **Làm lại câu hỏi sai** sẽ xuất hiện bên dưới nội dung review nếu có câu phù hợp.
5. Bấm nút, trả lời các câu được đưa ra, rồi chọn **Nộp bài luyện tập**.
6. Kiểm tra kết quả. Nếu vẫn còn câu sai, chọn **Làm lại những câu vẫn còn sai** để tạo lượt luyện tập tiếp theo.

## Các ca kiểm thử cần xác nhận

- Tất cả câu đúng: không có nút luyện lại.
- Có câu sai: chỉ câu sai được đưa vào lượt luyện tập.
- Có câu đúng một phần: câu đó được đưa vào lượt luyện tập.
- Câu bỏ trống: câu đó được đưa vào lượt luyện tập.
- Câu tự luận chưa chấm: không bị kết luận là sai và không được đưa vào lượt luyện tập.
- Review options tắt **Whether correct**: không hiển thị nút; URL luyện tập trực tiếp cũng bị từ chối.
- Người dùng khác: không thể mở session của người học khác.
- Sau khi nộp lượt luyện tập: số lần làm Quiz và điểm Quiz gốc không thay đổi.
- Sau khi làm đúng hết: không còn nút luyện lại những câu sai.

## Lưu ý triển khai

- Plugin dùng Hook API `after_standard_main_region_html_generation` của Moodle 5.3, không ghi đè `review.php`, `attempt.php`, Question Engine hoặc file core nào.
- Plugin lưu metadata session trong `local_wronganswer_sess`; phần nội dung/response câu hỏi được Moodle Question Engine lưu trong `question_usages` với component `local_wronganswer`.
- Nếu Quiz không cho phép người học xem tính đúng/sai, plugin cố ý không cung cấp tính năng để tôn trọng cấu hình của giảng viên.
- Đây là bản triển khai đầu tiên theo source code hiện tại. Cần kiểm thử tích hợp trực tiếp trên Moodle local với nhiều loại question type trước khi đưa lên môi trường dùng chung.
