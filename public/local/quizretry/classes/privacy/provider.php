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

namespace local_quizretry\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use local_quizretry\retry_manager;

/**
 * Privacy provider for local_quizretry.
 *
 * @package   local_quizretry
 * @copyright 2026 Hieu-NM17
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\core_userlist_provider,
        \core_privacy\local\request\plugin\provider {

    /**
     * Describe the data stored by this plugin.
     *
     * @param collection $items
     * @return collection
     */
    public static function get_metadata(collection $items): collection {
        $items->add_database_table('local_quizretry', [
            'userid' => 'privacy:metadata:local_quizretry:userid',
            'quizid' => 'privacy:metadata:local_quizretry:quizid',
            'sourceattemptid' => 'privacy:metadata:local_quizretry:sourceattemptid',
            'parentid' => 'privacy:metadata:local_quizretry:parentid',
            'qubaid' => 'privacy:metadata:local_quizretry:qubaid',
            'status' => 'privacy:metadata:local_quizretry:status',
            'totalquestions' => 'privacy:metadata:local_quizretry:totalquestions',
            'correctcount' => 'privacy:metadata:local_quizretry:correctcount',
            'timecreated' => 'privacy:metadata:local_quizretry:timecreated',
            'timefinished' => 'privacy:metadata:local_quizretry:timefinished',
        ], 'privacy:metadata:local_quizretry');

        // The answers themselves are stored by the question engine.
        $items->add_subsystem_link('core_question', [], 'privacy:metadata:core_question');
        return $items;
    }

    /**
     * Contexts (quiz modules) where the user has practice sessions.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {local_quizretry} r ON r.cmid = ctx.instanceid
                 WHERE ctx.contextlevel = :modlevel
                   AND r.userid = :userid";
        $contextlist->add_from_sql($sql, ['modlevel' => CONTEXT_MODULE, 'userid' => $userid]);
        return $contextlist;
    }

    /**
     * Users with practice sessions in a context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }
        $userlist->add_from_sql('userid', "SELECT r.userid FROM {local_quizretry} r WHERE r.cmid = :cmid",
                ['cmid' => $context->instanceid]);
    }

    /**
     * Export the practice sessions of a user.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $user = $contextlist->get_user();
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }
            $retries = $DB->get_records('local_quizretry', ['userid' => $user->id, 'cmid' => $context->instanceid], 'id');
            foreach ($retries as $retry) {
                $subcontext = [get_string('privacy:path', 'local_quizretry'), $retry->id];
                writer::with_context($context)->export_data($subcontext, (object) [
                    'sourceattemptid' => $retry->sourceattemptid,
                    'parentid' => $retry->parentid,
                    'status' => $retry->status,
                    'totalquestions' => $retry->totalquestions,
                    'correctcount' => $retry->correctcount,
                    'timecreated' => transform::datetime($retry->timecreated),
                    'timefinished' => $retry->timefinished ? transform::datetime($retry->timefinished) : null,
                ]);

                $options = retry_manager::get_display_options($context, true);
                \core_question\privacy\provider::export_question_usage($user->id, $context, $subcontext,
                        $retry->qubaid, $options, true);
            }
        }
    }

    /**
     * Delete all practice sessions in a context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }
        retry_manager::delete_where('cmid = :cmid', ['cmid' => $context->instanceid]);
    }

    /**
     * Delete the practice sessions of one user in the approved contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }
            retry_manager::delete_where('userid = :userid AND cmid = :cmid',
                    ['userid' => $userid, 'cmid' => $context->instanceid]);
        }
    }

    /**
     * Delete the practice sessions of several users in one context.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }
        [$insql, $inparams] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
        retry_manager::delete_where("cmid = :cmid AND userid $insql", ['cmid' => $context->instanceid] + $inparams);
    }
}
