<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace local_wronganswer\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\writer;
use local_wronganswer\local\practice_manager;

/**
 * Privacy API implementation for stored practice sessions and responses.
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider {

    /**
     * Describe personal data stored by this plugin.
     *
     * @param collection $items
     * @return collection
     */
    public static function get_metadata(collection $items): collection {
        $items->add_database_table('local_wronganswer_sess', [
            'userid' => 'privacy:metadata:userid',
            'cmid' => 'privacy:metadata:cmid',
            'quizattemptid' => 'privacy:metadata:quizattemptid',
            'sourceusageid' => 'privacy:metadata:sourceusageid',
            'usageid' => 'privacy:metadata:usageid',
            'status' => 'privacy:metadata:status',
            'questioncount' => 'privacy:metadata:questioncount',
            'timecreated' => 'privacy:metadata:timecreated',
            'timemodified' => 'privacy:metadata:timemodified',
            'timefinished' => 'privacy:metadata:timefinished',
        ], 'privacy:metadata:session');
        $items->add_subsystem_link('core_question', [], 'privacy:metadata:questionengine');
        return $items;
    }

    /**
     * Find module contexts containing this user's sessions.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT c.id
                  FROM {context} c
                  JOIN {local_wronganswer_sess} s ON s.cmid = c.instanceid
                 WHERE c.contextlevel = :contextlevel AND s.userid = :userid";
        $contextlist->add_from_sql($sql, [
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
        ]);
        return $contextlist;
    }

    /**
     * Export the user's practice sessions and submitted responses.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        if (!count($contextlist)) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_MODULE) {
                continue;
            }

            $sessions = $DB->get_records('local_wronganswer_sess', [
                'userid' => $userid,
                'cmid' => $context->instanceid,
            ], 'timecreated ASC');

            $export = [];
            foreach ($sessions as $session) {
                $sessiondata = [
                    'sessionid' => (int)$session->id,
                    'quizattemptid' => (int)$session->quizattemptid,
                    'status' => $session->status,
                    'questioncount' => (int)$session->questioncount,
                    'timecreated' => (int)$session->timecreated,
                    'timefinished' => (int)$session->timefinished,
                    'questions' => [],
                ];

                try {
                    $usage = \question_engine::load_questions_usage_by_activity((int)$session->usageid);
                    foreach ($usage->get_slots() as $slot) {
                        $questionattempt = $usage->get_question_attempt($slot);
                        $question = $usage->get_question($slot, false);
                        $sessiondata['questions'][] = [
                            'name' => format_string($question->name),
                            'question' => format_text($question->questiontext, $question->questiontextformat),
                            'response' => $questionattempt->get_response_summary(),
                            'state' => $questionattempt->get_state()->default_string(true),
                            'mark' => $questionattempt->get_mark(),
                        ];
                    }
                } catch (\Throwable $exception) {
                    // Keep the session metadata exportable even if an old usage is unavailable.
                    debugging('Could not export a Question Usage for local_wronganswer: ' . $exception->getMessage(),
                            DEBUG_DEVELOPER);
                }
                $export[] = $sessiondata;
            }

            if ($export) {
                writer::with_context($context)->export_data(
                    [get_string('privacy:exportpath', 'local_wronganswer')],
                    (object)['sessions' => $export]
                );
            }
        }
    }

    /**
     * Delete all plugin sessions in a quiz module context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        global $DB;

        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }

        $sessions = $DB->get_records('local_wronganswer_sess', ['cmid' => $context->instanceid]);
        practice_manager::delete_sessions($sessions);
    }

    /**
     * Delete this user's sessions in the approved contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel !== CONTEXT_MODULE) {
                continue;
            }

            $sessions = $DB->get_records('local_wronganswer_sess', [
                'userid' => $userid,
                'cmid' => $context->instanceid,
            ]);
            practice_manager::delete_sessions($sessions);
        }
    }
}
