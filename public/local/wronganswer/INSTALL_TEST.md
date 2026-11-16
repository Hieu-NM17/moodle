# Hướng dẫn apply và test `local_wronganswer`

## 1. Sao lưu và tạo nhánh làm tính năng

Trong bản clone dự án:

```bash
git status
git switch -c feat/wrong-answer-practice
```

Đảm bảo working tree sạch trước khi chép plugin.

## 2. Chép plugin vào đúng thư mục

Repository `Hieu-NM17/moodle` hiện tổ chức Moodle code dưới `public/`, vì vậy copy thư mục plugin vào:

```text
public/local/wronganswer/
```

Sau khi chép, cần có `public/local/wronganswer/version.php` và `public/local/wronganswer/db/install.xml`.

## 3. Cài plugin và tạo bảng

Chạy từ thư mục gốc repository, trong môi trường PHP có thể đọc `public/config.php` và kết nối tới đúng database:

```bash
php public/admin/cli/upgrade.php --non-interactive
php public/admin/cli/purge_caches.php
```

Nếu Moodle đang chạy trong Docker, chạy hai lệnh này bên trong container PHP/Web của Moodle (tên service phụ thuộc file compose của dự án).

Hoặc mở Moodle bằng tài khoản admin và vào **Site administration → Notifications**, sau đó purge caches.

Plugin tạo bảng `local_wronganswer_sess`. Không cần sửa `config.php`, không cần chỉnh Composer và không cần chạy build JavaScript.

## 4. Cấu hình Quiz

Trong Quiz cần test, bật **Review options → Whether correct** tại thời điểm review mong muốn. Khuyến nghị bật tại **After the quiz is closed** để không lộ kết quả khi quiz còn mở.

## 5. Kiểm tra luồng người học

Tạo một Quiz gồm 3–5 câu tự động chấm. Làm thử một bài có:

- một câu trả lời đúng;
- một câu trả lời sai;
- một câu bỏ trống;
- có thể thêm một câu tự luận để xác nhận câu chưa chấm không bị hiểu là sai.

Submit Quiz và mở review. Nút luyện tập chỉ xuất hiện khi có câu sai/bỏ trống và review options cho phép xem đúng/sai. Mở lượt luyện tập, trả lời, submit và xem kết quả. Nếu còn sai, bấm nút luyện tiếp.

## 6. Xác nhận không tác động Quiz gốc

Sau khi luyện tập, quay lại review của Quiz và kiểm tra:

- attempt number chính thức không tăng;
- điểm trong Quiz/gradebook không thay đổi;
- câu trả lời trong attempt gốc vẫn giữ nguyên;
- nếu câu hỏi có ảnh/tệp đính kèm, các file vẫn hiển thị trong màn hình luyện tập;
- plugin chỉ thêm session trong `local_wronganswer_sess` và Question Usage thuộc `local_wronganswer`.

## 7. Kiểm tra quyền truy cập

- Tắt **Whether correct** trong review options: nút không hiển thị và URL `/local/wronganswer/practice.php?attemptid=...` không cho truy cập.
- Đăng nhập bằng tài khoản khác và thử mở `sessionid` của người học trước: phải bị từ chối.
- Làm một bài không có câu sai: nút không hiển thị.

## 8. Gỡ plugin khi cần

Từ **Site administration → Plugins → Plugins overview**, gỡ `Wrong-answer practice`. Moodle sẽ xóa bảng plugin trong quá trình uninstall. Việc xóa plugin không yêu cầu khôi phục bất kỳ file core nào.

## 9. Giới hạn kiểm thử của gói này

Gói được kiểm tra cú pháp PHP và cấu trúc XML tĩnh. Nó chưa được cài vào một Moodle runtime có database và quiz thật trong phiên làm việc này; do đó cần thực hiện các bước kiểm thử tích hợp ở trên trước khi merge.
