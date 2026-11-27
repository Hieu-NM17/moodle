<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Language strings for local_quizretry (Vietnamese).
 *
 * @package   local_quizretry
 * @copyright 2026 Hieu-NM17
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['backtoquiz'] = 'Quay lại bài kiểm tra';
$string['backtoreview'] = 'Quay lại xem lại bài làm';
$string['cardallcorrect'] = 'Làm tốt lắm! Bạn đã trả lời đúng tất cả các câu trong lần làm bài này nên không có câu nào cần làm lại.';
$string['cardlatestallcorrect'] = 'Bạn đã trả lời đúng tất cả các câu trong lần làm bài gần nhất (lần {$a->attempt}).';
$string['cardlatestwrongcount'] = 'Trong lần làm bài gần nhất (lần {$a->attempt}), bạn đã trả lời sai hoặc bỏ trống {$a->count} câu. Bạn có thể làm lại riêng các câu đó. Điểm của bài kiểm tra sẽ không thay đổi.';
$string['cardnoanswers'] = 'Tính năng làm lại câu sai sẽ khả dụng khi bài kiểm tra này cho phép bạn xem đáp án đúng.';
$string['cardtitle'] = 'Luyện tập lại các câu làm sai';
$string['cardwrongcount'] = 'Trong lần làm bài này, bạn đã trả lời sai hoặc bỏ trống {$a->count} câu. Bạn có thể làm lại riêng các câu đó ngay bây giờ. Điểm của bài kiểm tra sẽ không thay đổi.';
$string['error_disabled'] = 'Tính năng làm lại câu sai đang bị tắt trên hệ thống này.';
$string['error_noanswers'] = 'Chưa thể làm lại câu sai vì bài kiểm tra này hiện chưa cho phép bạn xem đáp án đúng.';
$string['error_nocapability'] = 'Bạn không có quyền làm lại câu sai trong bài kiểm tra này.';
$string['error_noreview'] = 'Không thể làm lại câu sai vì bài kiểm tra này hiện chưa cho phép bạn xem lại câu nào đúng/sai.';
$string['error_notfinished'] = 'Bạn chỉ có thể làm lại câu sai từ lần làm bài đã nộp.';
$string['error_notowner'] = 'Bài làm này không thuộc về bạn.';
$string['finishpractice'] = 'Nộp bài luyện tập';
$string['historydate'] = 'Bắt đầu lúc';
$string['historyempty'] = 'Bạn chưa luyện tập câu sai nào trong bài kiểm tra này. Hãy nộp một lần làm bài, mở phần xem lại và chọn "Làm lại câu sai".';
$string['historyquestions'] = '{$a} câu';
$string['historyresult'] = 'Kết quả';
$string['historyresultvalue'] = 'Đúng {$a->correct} / {$a->total}';
$string['historysource'] = 'Dựa trên';
$string['historysourceattempt'] = 'Lần làm bài #{$a}';
$string['historysourceretry'] = 'Lần luyện tập #{$a}';
$string['historystatus'] = 'Trạng thái';
$string['historytitle'] = 'Lịch sử luyện tập: {$a}';
$string['introfromattempt'] = 'Dưới đây là {$a} câu bạn đã trả lời sai hoặc bỏ trống. Hãy làm lại; lần luyện tập này không làm thay đổi điểm của bài kiểm tra.';
$string['introfromretry'] = 'Dưới đây là {$a} câu vẫn còn sai trong lần luyện tập trước. Lần luyện tập này không làm thay đổi điểm của bài kiểm tra.';
$string['nothingtoretry'] = 'Không có câu sai nào để luyện tập.';
$string['openreview'] = 'Xem lại';
$string['pluginname'] = 'Làm lại câu hỏi sai trong bài kiểm tra';
$string['practiceallcorrect'] = 'Tuyệt vời, bây giờ bạn đã làm đúng tất cả các câu này!';
$string['practicetitle'] = 'Luyện tập: các câu sai trong {$a}';
$string['privacy:metadata:core_question'] = 'Các câu trả lời khi luyện tập được lưu bởi hệ thống câu hỏi (question engine).';
$string['privacy:metadata:local_quizretry'] = 'Các lần luyện tập trong đó người học làm lại các câu đã trả lời sai trong một lần làm bài kiểm tra.';
$string['privacy:metadata:local_quizretry:correctcount'] = 'Số câu trả lời đúng trong lần luyện tập.';
$string['privacy:metadata:local_quizretry:parentid'] = 'Lần luyện tập trước mà lần này được tạo từ đó.';
$string['privacy:metadata:local_quizretry:qubaid'] = 'Question usage lưu câu hỏi và câu trả lời của lần luyện tập.';
$string['privacy:metadata:local_quizretry:quizid'] = 'Bài kiểm tra mà lần luyện tập thuộc về.';
$string['privacy:metadata:local_quizretry:sourceattemptid'] = 'Lần làm bài kiểm tra mà lần luyện tập dựa trên.';
$string['privacy:metadata:local_quizretry:status'] = 'Lần luyện tập đang làm dở hay đã hoàn thành.';
$string['privacy:metadata:local_quizretry:timecreated'] = 'Thời điểm bắt đầu luyện tập.';
$string['privacy:metadata:local_quizretry:timefinished'] = 'Thời điểm nộp bài luyện tập.';
$string['privacy:metadata:local_quizretry:totalquestions'] = 'Số câu hỏi trong lần luyện tập.';
$string['privacy:metadata:local_quizretry:userid'] = 'Người dùng đã luyện tập.';
$string['privacy:path'] = 'Luyện tập bài kiểm tra';
$string['quizretry:use'] = 'Luyện tập lại các câu sai của một lần làm bài kiểm tra đã nộp';
$string['resultsummary'] = 'Bạn đã trả lời đúng {$a->correct} / {$a->total} câu trong lần luyện tập này.';
$string['resumeretry'] = 'Tiếp tục luyện tập';
$string['retrystillwrong'] = 'Làm lại {$a} câu vẫn còn sai';
$string['setting_behaviour'] = 'Cách hoạt động của câu hỏi khi luyện tập';
$string['setting_behaviour_desc'] = 'Cách câu hỏi hoạt động khi người học luyện tập. "Phản hồi ngay" cho phép kiểm tra từng câu ngay lập tức.';
$string['setting_enabled'] = 'Bật tính năng làm lại câu sai';
$string['setting_enabled_desc'] = 'Hiển thị nút trên trang xem lại bài làm để người học luyện tập các câu đã làm sai.';
$string['setting_includepartial'] = 'Bao gồm cả câu đúng một phần';
$string['setting_includepartial_desc'] = 'Nếu bật, các câu chỉ đúng một phần cũng được đưa vào luyện tập. Câu sai và câu bỏ trống luôn được đưa vào.';
$string['setting_requirerightanswer'] = 'Yêu cầu đáp án đúng đã hiển thị trong phần xem lại';
$string['setting_requirerightanswer_desc'] = 'Nếu bật (khuyến nghị), người học chỉ được luyện tập khi cài đặt xem lại của bài kiểm tra đã cho phép xem đáp án đúng. Việc này tránh dùng luyện tập để vượt qua cài đặt xem lại của giáo viên.';
$string['startretry'] = 'Làm lại {$a} câu sai';
$string['statusfinished'] = 'Đã hoàn thành';
$string['statusinprogress'] = 'Đang làm';
$string['viewhistory'] = 'Lịch sử luyện tập của tôi';
