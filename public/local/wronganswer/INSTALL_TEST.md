# Hướng dẫn apply và test `local_wronganswer`

## 1. Sao lưu và tạo nhánh làm tính năng

```bash
git status
git switch -c fix/wrong-answer-practice
```

Đảm bảo working tree sạch trước khi chép plugin.

## 2. Chép plugin

Repository `Hieu-NM17/moodle` tổ chức Moodle code trong `public/`. Chép toàn bộ thư mục `local_wronganswer` từ gói này vào:

```text
public/local/wronganswer/
```

## 3. Cài plugin và tạo bảng

Chạy từ thư mục gốc repository, trong môi trường PHP truy cập được `public/config.php` và database:

```bash
php public/admin/cli/upgrade.php --non-interactive
php public/admin/cli/purge_caches.php
```

Nếu dùng Docker, chạy trong container PHP/Web phù hợp với compose project. Hoặc mở Moodle bằng admin, vào **Site administration → Notifications** để nâng cấp, sau đó vào **Site administration → Development → Purge caches**.

Plugin tạo bảng `local_wronganswer_sess`; không sửa `config.php`, không chỉnh Composer và không cần build JavaScript.

## 4. Cấu hình Quiz — bước quan trọng

Trong Quiz cần kiểm thử, vào **Review options** và bật đồng thời:

- **The attempt**
- **Whether correct**

Bật cả hai ở cùng khoảng thời gian, ví dụ **After the quiz is closed**. Bật riêng `Whether correct` là chưa đủ vì plugin chủ động yêu cầu người học được phép review nội dung attempt.

## 5. Kiểm tra luồng người học

Tạo Quiz gồm 3–5 câu tự động chấm. Làm thử một bài có:

- một câu trả lời đúng;
- một câu trả lời sai;
- một câu bỏ trống;
- có thể thêm câu tự luận chưa chấm để xác nhận câu chưa chấm không bị xem là sai.

Submit Quiz và mở review. Nút luyện tập xuất hiện nếu có câu sai/bỏ trống và hai review options cho phép. Mở lượt luyện tập, trả lời, submit và xem kết quả. Nếu còn sai, bấm nút luyện tiếp.

## 6. Xác nhận không tác động Quiz gốc

Sau khi luyện tập, quay lại review của Quiz và kiểm tra:

- attempt number chính thức không tăng;
- điểm trong Quiz/gradebook không thay đổi;
- câu trả lời trong attempt gốc giữ nguyên;
- ảnh/tệp đính kèm của câu hỏi hiển thị trong màn hình luyện tập;
- plugin chỉ thêm session trong `local_wronganswer_sess` và Question Usage thuộc `local_wronganswer`.

## 7. Kiểm tra quyền truy cập

- Tắt `The attempt` hoặc `Whether correct`: nút không hiển thị và URL `/local/wronganswer/practice.php?attemptid=...` bị từ chối.
- Đăng nhập bằng tài khoản khác và thử mở `sessionid` của người học trước: phải bị từ chối.
- Làm bài không có câu sai: nút không hiển thị.

## 8. Gỡ plugin

Vào **Site administration → Plugins → Plugins overview**, gỡ `Wrong-answer practice`. Moodle sẽ xóa bảng plugin và Question Usage thuộc component `local_wronganswer`.

## 9. Trạng thái kiểm thử

Đã rà soát các API được gọi trong mã plugin, sửa lời gọi `question_usage_by_activity::check_file_access()` và chạy kiểm tra cú pháp PHP/XML tĩnh. Chưa thể xác nhận end-to-end nếu chưa cài plugin vào Moodle runtime với database và Quiz thật.
