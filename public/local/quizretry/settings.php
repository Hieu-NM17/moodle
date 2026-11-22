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
 * Admin settings for local_quizretry.
 *
 * @package   local_quizretry
 * @copyright 2026 Hieu-NM17
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_quizretry', get_string('pluginname', 'local_quizretry'));
    $ADMIN->add('localplugins', $settings);

    if ($ADMIN->fulltree) {
        $settings->add(new admin_setting_configcheckbox('local_quizretry/enabled',
                get_string('setting_enabled', 'local_quizretry'),
                get_string('setting_enabled_desc', 'local_quizretry'), 1));

        $settings->add(new admin_setting_configselect('local_quizretry/behaviour',
                get_string('setting_behaviour', 'local_quizretry'),
                get_string('setting_behaviour_desc', 'local_quizretry'),
                'immediatefeedback', [
                    'immediatefeedback' => get_string('pluginname', 'qbehaviour_immediatefeedback'),
                    'interactive' => get_string('pluginname', 'qbehaviour_interactive'),
                    'deferredfeedback' => get_string('pluginname', 'qbehaviour_deferredfeedback'),
                ]));

        $settings->add(new admin_setting_configcheckbox('local_quizretry/includepartial',
                get_string('setting_includepartial', 'local_quizretry'),
                get_string('setting_includepartial_desc', 'local_quizretry'), 0));

        $settings->add(new admin_setting_configcheckbox('local_quizretry/requirerightanswer',
                get_string('setting_requirerightanswer', 'local_quizretry'),
                get_string('setting_requirerightanswer_desc', 'local_quizretry'), 1));
    }
}
