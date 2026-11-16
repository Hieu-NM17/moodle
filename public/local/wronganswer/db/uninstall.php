<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

/**
 * Remove Question Engine usages left by this plugin when it is uninstalled.
 *
 * @return bool
 */
function xmldb_local_wronganswer_uninstall(): bool {
    global $CFG, $DB;

    require_once($CFG->dirroot . '/question/engine/lib.php');
    $usages = $DB->get_records('question_usages', ['component' => 'local_wronganswer'], '', 'id');
    foreach ($usages as $usage) {
        question_engine::delete_questions_usage_by_activity((int)$usage->id);
    }

    return true;
}
