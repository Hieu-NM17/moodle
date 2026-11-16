# Phân tích lỗi và các sửa đổi

## 1. Câu hỏi chấm thủ công có thể làm hỏng toàn bộ phiên luyện tập

Mã cũ chủ động đưa trạng thái `mangrwrong` vào danh sách câu sai, nhưng mọi câu mới đều được khởi tạo bằng hành vi `deferredfeedback`. Trong Moodle 5.3, hành vi này chỉ tương thích với câu hỏi triển khai `question_automatically_gradable`; câu Essay/câu chấm thủ công có thể làm `start_question()` ném lỗi khi tạo phiên.

**Xử lý:** lọc câu theo interface `question_automatically_gradable` trước khi thêm vào phiên. Chỉ câu tự động chấm sai, đúng một phần hoặc bỏ trống được luyện tập.

## 2. Nộp bài có thể âm thầm đánh dấu câu là bỏ trống khi thiếu sequence-check

`question_usage_by_activity::process_all_actions()` chỉ xử lý một slot khi sequence-check của câu đó có trong POST và hợp lệ. Nếu token bị thiếu, phương thức bỏ qua câu mà không nhất thiết báo lỗi rõ ràng; sau đó `finish_all_questions()` có thể biến câu thành trạng thái bỏ cuộc, làm kết quả luyện tập không phản ánh câu trả lời vừa nhập.

**Xử lý:** truyền rõ dữ liệu `$_POST` vào Question Engine; kiểm tra danh sách slot và sequence-check cho tất cả câu trước khi chấm. Nếu biểu mẫu thiếu hoặc cũ, báo lỗi để người học tải lại thay vì lưu kết quả sai.

## 3. Nộp biểu mẫu không phụ thuộc tên của nút submit

Mã cũ dùng chính nút submit mang tên `submitpractice` để phân biệt request. Khi trình duyệt gửi form bằng phím Enter hoặc cơ chế submit khác, nút có thể không được gửi kèm.

**Xử lý:** chuyển `submitpractice=1` thành hidden field và yêu cầu POST trước khi kết thúc phiên.

## 4. Giữ nguyên ranh giới với Moodle core

Các thay đổi chỉ nằm trong plugin `public/local/wronganswer`. Không sửa `mod/quiz`, Question Engine hoặc file core. Mỗi phiên vẫn có Question Usage riêng thuộc `local_wronganswer`, không ghi vào attempt/gradebook gốc.

## 5. Cấu hình review options

Plugin chỉ hiển thị CTA và cho truy cập URL khi cả **The attempt** và **Whether correct** được bật tại cùng thời điểm review. Đây là ràng buộc quyền riêng tư/chính sách review của Moodle, không phải lỗi PHP.

## 6. Kiểm thử

Đã kiểm tra cú pháp PHP và XML tĩnh cho các file trong plugin. Chưa chạy được kiểm thử tích hợp end-to-end do không có Moodle runtime/database trong môi trường đóng gói.
