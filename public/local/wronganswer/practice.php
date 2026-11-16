<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

use local_wronganswer\local\practice_manager;

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/question/engine/lib.php');

$sessionid = optional_param('sessionid', 0, PARAM_INT);
$attemptid = optional_param('attemptid', 0, PARAM_INT);
$sourcesessionid = optional_param('sourcesessionid', 0, PARAM_INT);
$submitpractice = optional_param('submitpractice', 0, PARAM_BOOL);

if (!$sessionid && !$attemptid) {
    throw new moodle_exception('invalidpracticeurl', 'local_wronganswer');
}

if ($sessionid) {
    $session = $DB->get_record('local_wronganswer_sess', [
        'id' => $sessionid,
        'userid' => $USER->id,
    ], '*', MUST_EXIST);
    $attemptid = (int)$session->quizattemptid;
} else {
    $session = null;
}

$attempt = $DB->get_record('quiz_attempts', [
    'id' => $attemptid,
    'userid' => $USER->id,
], '*', MUST_EXIST);

if ($attempt->preview || $attempt->state !== 'finished') {
    throw new moodle_exception('invalidoriginalattempt', 'local_wronganswer');
}

$quiz = $DB->get_record('quiz', ['id' => $attempt->quiz], '*', MUST_EXIST);
$course = $DB->get_record('course', ['id' => $quiz->course], '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('quiz', $quiz->id, $quiz->course, false, MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, false, $cm);

$reviewoptions = quiz_get_review_options($quiz, $attempt, $context);
if (empty($reviewoptions->attempt) || empty($reviewoptions->correctness)) {
    throw new moodle_exception('reviewnotallowed', 'local_wronganswer');
}

$PAGE->set_url(new moodle_url('/local/wronganswer/practice.php', $sessionid ?
        ['sessionid' => $sessionid] : ['attemptid' => $attemptid]));
$PAGE->set_context($context);
$PAGE->set_cm($cm);
$PAGE->set_pagelayout('incourse');
$PAGE->add_body_class('local-wronganswer-page');
$PAGE->set_title(get_string('practicepagetitle', 'local_wronganswer'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->requires->css(new moodle_url('/local/wronganswer/styles.css'));

if (!$session) {
    $sourceusageid = (int)$attempt->uniqueid;

    if ($sourcesessionid) {
        $previoussession = $DB->get_record('local_wronganswer_sess', [
            'id' => $sourcesessionid,
            'userid' => $USER->id,
            'quizattemptid' => $attemptid,
            'cmid' => $cm->id,
            'status' => 'finished',
        ], '*', MUST_EXIST);
        $sourceusageid = (int)$previoussession->usageid;
    }

    $sessionid = practice_manager::create_session($USER->id, (int)$cm->id, $attemptid, $sourceusageid);
    redirect(new moodle_url('/local/wronganswer/practice.php', ['sessionid' => $sessionid]));
}

if (!in_array($session->status, ['inprogress', 'finished'], true)) {
    throw new moodle_exception('invalidpracticeusage', 'local_wronganswer');
}

$usage = question_engine::load_questions_usage_by_activity((int)$session->usageid);
if ((int)$usage->get_owning_context()->id !== (int)$context->id ||
        $usage->get_owning_component() !== 'local_wronganswer') {
    throw new moodle_exception('invalidpracticeusage', 'local_wronganswer');
}

if ($submitpractice) {
    require_sesskey();
    practice_manager::finish_session($session, $usage);
    redirect(new moodle_url('/local/wronganswer/practice.php', ['sessionid' => $sessionid]));
}

$slots = $usage->get_slots();
if (!$slots) {
    throw new moodle_exception('emptypracticesession', 'local_wronganswer');
}

// Initialise each question type's assets and the Question Engine flag JavaScript.
foreach ($slots as $slot) {
    $usage->render_question_head_html($slot);
}
question_engine::initialise_js();

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($quiz->name));
echo $OUTPUT->notification(get_string('practicebasedon', 'local_wronganswer', $attempt->attempt), 'info');

$reviewurl = new moodle_url('/mod/quiz/review.php', ['attempt' => $attemptid]);
$backlink = html_writer::link($reviewurl, get_string('backtoreview', 'local_wronganswer'));
echo html_writer::div($backlink, 'local-wronganswer-back mb-3');

if ($session->status === 'inprogress') {
    $options = new question_display_options();
    $options->readonly = false;
    $options->hide_all_feedback();
    $options->marks = question_display_options::HIDDEN;
    $options->flags = question_display_options::HIDDEN;

    echo html_writer::tag('p', get_string('practiceinstructions', 'local_wronganswer'));
    echo html_writer::start_tag('form', [
        'method' => 'post',
        'enctype' => 'multipart/form-data',
        'action' => (new moodle_url('/local/wronganswer/practice.php', ['sessionid' => $sessionid]))->out(false),
        'class' => 'local-wronganswer-form',
    ]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'slots', 'value' => implode(',', $slots)]);

    foreach ($slots as $index => $slot) {
        echo $usage->render_question($slot, clone $options, (string)($index + 1));
    }

    echo html_writer::div(html_writer::tag('button', get_string('submitpractice', 'local_wronganswer'), [
        'type' => 'submit',
        'name' => 'submitpractice',
        'value' => '1',
        'class' => 'btn btn-primary',
    ]), 'local-wronganswer-submit mt-4');
    echo html_writer::end_tag('form');
} else {
    $wrongslots = practice_manager::get_wrong_slots($usage);
    $correctcount = 0;
    foreach ($slots as $slot) {
        if ($usage->get_question_state($slot)->is_correct()) {
            $correctcount++;
        }
    }

    echo html_writer::div(get_string('practiceresult', 'local_wronganswer', (object)[
        'correct' => $correctcount,
        'total' => count($slots),
    ]), 'alert alert-info');

    $resultoptions = clone $reviewoptions;
    $resultoptions->readonly = true;
    $resultoptions->flags = question_display_options::HIDDEN;
    $resultoptions->history = question_display_options::HIDDEN;
    $resultoptions->questionreviewlink = null;
    $resultoptions->manualcomment = question_display_options::HIDDEN;
    $resultoptions->manualcommentlink = null;
    foreach ($slots as $index => $slot) {
        echo $usage->render_question($slot, clone $resultoptions, (string)($index + 1));
    }

    if ($wrongslots) {
        $retryurl = new moodle_url('/local/wronganswer/practice.php', [
            'attemptid' => $attemptid,
            'sourcesessionid' => $sessionid,
        ]);
        echo html_writer::div(
            html_writer::tag('p', get_string('stillwrong', 'local_wronganswer', count($wrongslots))) .
            html_writer::link($retryurl, get_string('retryremaining', 'local_wronganswer'), [
                'class' => 'btn btn-primary',
            ]),
            'local-wronganswer-retry alert alert-warning mt-4'
        );
    } else {
        echo $OUTPUT->notification(get_string('allcorrect', 'local_wronganswer'), 'success');
    }
}

echo $OUTPUT->footer();
