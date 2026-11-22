<?php
// This file is part of Moodle - http://moodle.org/

namespace theme_ilap\output;

use html_writer;
use mod_quiz\question\display_options;
use mod_quiz\quiz_attempt;

defined('MOODLE_INTERNAL') || die();

/**
 * Quiz renderer override for theme_ilap.
 */
class mod_quiz_renderer extends \mod_quiz\output\renderer {

    /**
     * Builds the review page, including a donut/pie chart summary of results.
     *
     * @param quiz_attempt $attemptobj
     * @param array $slots
     * @param int $page
     * @param bool $showall
     * @param bool $lastpage
     * @param display_options $displayoptions
     * @param \mod_quiz\output\attempt_summary_information|array $summarydata
     * @return string HTML to display.
     */
    public function review_page(
        quiz_attempt $attemptobj,
        $slots,
        $page,
        $showall,
        $lastpage,
        display_options $displayoptions,
        $summarydata
    ) {
        if (is_array($summarydata)) {
            debugging('Since Moodle 4.4, $summarydata passed to review_page should be an attempt_summary_information.',
                DEBUG_DEVELOPER);
            $summarydata = $this->filter_review_summary_table($summarydata, $page);
            $summarydata = \mod_quiz\output\attempt_summary_information::create_from_legacy_array($summarydata);
        }

        $output = '';
        $output .= $this->header();

        // On first page or when showing all, render summary info together with chart.
        $summaryhtml = $this->review_attempt_summary($summarydata, $page);
        $charthtml = $this->render_quiz_result_chart($attemptobj, $displayoptions, $page);

        if (!empty($charthtml)) {
            $output .= html_writer::start_div('row quiz-summary-chart-row mb-4 align-items-center');
            $output .= html_writer::div($summaryhtml, 'col-lg-7 col-md-12 mb-3 mb-lg-0');
            $output .= html_writer::div($charthtml, 'col-lg-5 col-md-12');
            $output .= html_writer::end_div();
        } else {
            $output .= $summaryhtml;
        }

        $output .= $this->review_form($page, $showall, $displayoptions,
            $this->questions($attemptobj, true, $slots, $page, $showall, $displayoptions),
            $attemptobj);

        $output .= $this->review_next_navigation($attemptobj, $page, $lastpage, $showall);
        $output .= $this->footer();

        return $output;
    }

    /**
     * Renders a pie chart of correct/incorrect/partial questions and score.
     *
     * @param quiz_attempt $attemptobj
     * @param display_options $displayoptions
     * @param int $page
     * @return string
     */
    protected function render_quiz_result_chart(quiz_attempt $attemptobj, display_options $displayoptions, int $page): string {
        // Show chart on page 0 or when review is displayed.
        if ($page > 0) {
            return '';
        }

        $correct = 0;
        $incorrect = 0;
        $partial = 0;
        $totalquestions = 0;

        $slots = $attemptobj->get_slots();
        foreach ($slots as $slot) {
            $qa = $attemptobj->get_question_attempt($slot);
            if (!$qa) {
                continue;
            }

            $maxmark = $qa->get_max_mark();
            if ($maxmark <= 0) {
                continue; // Description or information item (no score), skip.
            }
            $totalquestions++;

            $state = $attemptobj->get_question_state($slot);
            if ($state->is_correct()) {
                $correct++;
            } else if ($state->is_partially_correct()) {
                $partial++;
            } else {
                $incorrect++;
            }
        }

        if ($totalquestions === 0) {
            return '';
        }

        $chart = new \core\chart_pie();
        $chart->set_title('Thống kê kết quả');
        $chart->set_doughnut(true);

        $labels = ['Đúng (' . $correct . ')', 'Sai (' . $incorrect . ')'];
        $seriesdata = [$correct, $incorrect];
        $colors = ['#10B981', '#EF4444']; // Green, Red

        if ($partial > 0) {
            $labels[] = 'Đúng một phần (' . $partial . ')';
            $seriesdata[] = $partial;
            $colors[] = '#F59E0B'; // Amber
        }

        $series = new \core\chart_series('Số câu', $seriesdata);
        $series->set_colors($colors);
        $chart->add_series($series);
        $chart->set_labels($labels);

        $quiz = $attemptobj->get_quiz();
        $summarks = $attemptobj->get_sum_marks();
        $quizgrade = $quiz->grade;
        $scaledgrade = quiz_rescale_grade($summarks, $quiz, false);

        $scoreinfo = '';
        if ($displayoptions->marks >= \question_display_options::MARK_AND_MAX && !is_null($scaledgrade)) {
            $formattedgrade = quiz_format_grade($quiz, $scaledgrade);
            $formattedmax = quiz_format_grade($quiz, $quizgrade);
            $percent = ($quizgrade > 0) ? round(($scaledgrade / $quizgrade) * 100, 1) : 0;

            $scoreinfo = html_writer::div(
                html_writer::tag('span', 'Điểm đạt được: ', ['class' => 'text-muted fw-bold']) .
                html_writer::tag('span', "{$formattedgrade} / {$formattedmax} ({$percent}%)", ['class' => 'fw-bold text-primary fs-5 ms-1']),
                'text-center mt-2'
            );
        }

        $html = html_writer::start_div('card border shadow-sm p-3 quiz-chart-card mb-3');
        $html .= $this->render($chart);
        $html .= $scoreinfo;
        $html .= html_writer::end_div();

        return $html;
    }

    /**
     * Renders the view page (when finish review or viewing quiz summary).
     *
     * @param \stdClass $course the course settings row from the database.
     * @param \stdClass $quiz the quiz settings row from the database.
     * @param \stdClass $cm the course_module settings row from the database.
     * @param \context_module $context the quiz context.
     * @param \mod_quiz\output\view_page $viewobj
     * @return string HTML to display
     */
    public function view_page($course, $quiz, $cm, $context, $viewobj) {
        $output = '';

        $output .= $this->view_page_tertiary_nav($viewobj);
        $output .= $this->view_information($quiz, $cm, $context, $viewobj->infomessages);
        $output .= $this->view_result_info($quiz, $context, $cm, $viewobj);

        // Add line chart for quiz attempt history progression if there are multiple attempts.
        $linecharthtml = $this->render_attempts_progression_chart($quiz, $viewobj);
        if (!empty($linecharthtml)) {
            $output .= $linecharthtml;
        }

        $output .= $this->render($viewobj->attemptslist);
        $output .= $this->box($this->view_page_buttons($viewobj), 'quizattempt');
        return $output;
    }

    /**
     * Renders a line chart showing score progression across attempts.
     *
     * @param \stdClass $quiz
     * @param \mod_quiz\output\view_page $viewobj
     * @return string
     */
    protected function render_attempts_progression_chart($quiz, $viewobj): string {
        if (empty($viewobj->attemptobjs) || empty($viewobj->gradecolumn)) {
            return '';
        }

        // Filter finished attempts and sort chronologically (Attempt 1, 2, 3...).
        $finishedattempts = [];
        foreach ($viewobj->attemptobjs as $attemptobj) {
            if ($attemptobj->is_finished()) {
                $finishedattempts[] = $attemptobj;
            }
        }

        // Need at least 1 finished attempt to display progression.
        if (count($finishedattempts) < 1) {
            return '';
        }

        // Sort by attempt number ascending.
        usort($finishedattempts, function($a, $b) {
            return $a->get_attempt_number() <=> $b->get_attempt_number();
        });

        $labels = [];
        $grades = [];
        foreach ($finishedattempts as $attemptobj) {
            $num = $attemptobj->get_attempt_number();
            $labels[] = get_string('attempt', 'mod_quiz', $num);

            $summarks = $attemptobj->get_sum_marks();
            $scaledgrade = quiz_rescale_grade($summarks, $quiz, false);
            $grades[] = !is_null($scaledgrade) ? round($scaledgrade, 2) : 0;
        }

        $chart = new \core\chart_line();
        $chart->set_title('Biểu đồ theo dõi điểm số qua các lần làm bài');
        $chart->set_smooth(true);
        $chart->set_labels($labels);

        $series = new \core\chart_series('Điểm đạt được', $grades);
        $series->set_color('#2563EB'); // Primary blue color
        $chart->add_series($series);

        // Configure Y-axis (from 0 to max quiz grade).
        $yaxis = $chart->get_yaxis(0, true);
        $yaxis->set_min(0);
        if (!empty($quiz->grade) && $quiz->grade > 0) {
            $yaxis->set_max(ceil($quiz->grade));
        }
        $yaxis->set_label(get_string('gradenoun'));

        $xaxis = $chart->get_xaxis(0, true);
        $xaxis->set_label(get_string('attempts', 'quiz'));

        $html = html_writer::start_div('card border shadow-sm p-3 quiz-progression-chart-card mb-4');
        $html .= $this->render($chart);
        $html .= html_writer::end_div();

        return $html;
    }
}
