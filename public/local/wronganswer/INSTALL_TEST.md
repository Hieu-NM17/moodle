# Hướng dẫn apply và test `local_wronganswer`

## 1. Sao lưu và tạo nhánh

```bash
git status
git switch -c fix/wrong-answer-practice
```

Đảm bảo working tree sạch trước khi chép plugin.

## 2. Chép plugin

Giải nén gói và chép thư mục `wronganswer` vào:

```text
public/local/wronganswer/
```

Nếu đã có thư mục cũ, hãy sao lưu rồi thay thế toàn bộ bằng thư mục mới; không chép chồng từng file để tránh giữ lại file cũ.

## 3. Cài/cập nhật plugin

Chạy từ thư mục gốc repository, trong môi trường PHP truy cập được `public/config.php` và database:

```bash
php public/admin/cli/upgrade.php --non-interactive
php public/admin/cli/purge_caches.php
```

Nếu dùng Docker, chạy trong container PHP/Web phù hợp với compose project. Hoặc mở Moodle bằng admin, vào **Site administration → Notifications**, sau đó purge caches.

## 4. Cấu hình Quiz

Trong **Review options**, bật đồng thời:

- **The attempt**
- **Whether correct**

Bật cả hai tại cùng khoảng thời gian, ví dụ **After the quiz is closed**.

## 5. Kiểm thử luồng chính

Tạo Quiz có các câu tự động chấm và thử các trường hợp:

1. Tất cả câu đúng: không có nút luyện tập.
2. Có câu sai: chỉ câu sai được đưa vào phiên.
3. Có câu đúng một phần: câu đó được đưa vào phiên.
4. Có câu bỏ trống: câu đó được đưa vào phiên.
5. Có câu Essay chưa chấm: câu đó không bị xem là sai và không làm hỏng cả phiên.
6. Nộp phiên luyện tập: kết quả phản ánh câu vừa chọn; không tự động biến tất cả thành bỏ trống.
7. Còn sai: nút luyện tiếp chỉ đưa các câu vẫn sai vào phiên mới.
8. Làm đúng hết: không còn nút luyện tiếp.

## 6. Xác nhận không tác động Quiz gốc

Sau khi luyện tập, kiểm tra:

- attempt number chính thức không tăng;
- điểm trong Quiz/gradebook không thay đổi;
- câu trả lời trong attempt gốc giữ nguyên;
- plugin chỉ ghi session trong `local_wronganswer_sess` và Question Usage thuộc `local_wronganswer`.

## 7. Kiểm tra quyền truy cập

- Tắt một trong hai review options: nút không hiển thị và URL trực tiếp bị từ chối.
- Đăng nhập bằng tài khoản khác, thử mở `sessionid` của người học trước: phải bị từ chối.
- Làm bài không có câu sai: nút không hiển thị.

## 8. Gỡ plugin

Vào **Site administration → Plugins → Plugins overview**, gỡ `Wrong-answer practice`. Moodle sẽ xóa bảng plugin và Question Usage thuộc component `local_wronganswer`.
