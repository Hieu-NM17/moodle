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

namespace local_studytimer;

use core\hook\output\before_footer_html_generation;
use moodle_url;

/**
 * Hook callbacks: injects the floating study timer popup across Moodle pages.
 *
 * @package   local_studytimer
 * @copyright 2026 Hieu-NM17
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Inject the floating Pomodoro popup and load its script before footer generation.
     *
     * @param before_footer_html_generation $hook
     */
    public static function before_footer_html_generation(before_footer_html_generation $hook): void {
        global $PAGE, $USER;

        if (during_initial_install() || !isloggedin() || isguestuser()) {
            return;
        }

        // Skip embedded/popup layouts where a floating widget would obstruct minimal frames.
        if (in_array($PAGE->pagelayout, ['embedded', 'maintenance', 'print', 'redirect'], true)) {
            return;
        }

        $PAGE->requires->js(new moodle_url('/local/studytimer/js/studytimer.js'));

        $isquizattempt = ($PAGE->pagetype === 'mod-quiz-attempt');

        $context = [
            'userid' => (int) $USER->id,
            'isquizattempt' => $isquizattempt,
            'str' => [
                'title' => get_string('popuptitle', 'local_studytimer'),
                'close' => get_string('closepopup', 'local_studytimer'),
                'resize' => get_string('resizepopup', 'local_studytimer'),
                'preset25' => get_string('preset25_5', 'local_studytimer'),
                'preset50' => get_string('preset50_10', 'local_studytimer'),
                'presetcustom' => get_string('presetcustom', 'local_studytimer'),
                'studyminutes' => get_string('studyminutes', 'local_studytimer'),
                'breakminutes' => get_string('breakminutes', 'local_studytimer'),
                'start' => get_string('start', 'local_studytimer'),
                'pause' => get_string('pause', 'local_studytimer'),
                'resume' => get_string('resume', 'local_studytimer'),
                'reset' => get_string('reset', 'local_studytimer'),
                'autoloop' => get_string('autoloop', 'local_studytimer'),
                'cycleprefix' => get_string('cycleprefix', 'local_studytimer'),
                'statusready' => get_string('statusready', 'local_studytimer'),
                'statusstudying' => get_string('statusstudying', 'local_studytimer'),
                'statuspaused' => get_string('statuspaused', 'local_studytimer'),
                'statusbreak' => get_string('statusbreak', 'local_studytimer'),
                'sessiondone' => get_string('sessiondone', 'local_studytimer'),
                'takebreak' => get_string('takebreak', 'local_studytimer'),
                'continuestudy' => get_string('continuestudy', 'local_studytimer'),
                'breaklocktitle' => get_string('breaklocktitle', 'local_studytimer'),
                'breaklockdesc' => get_string('breaklockdesc', 'local_studytimer'),
                'endbreakearly' => get_string('endbreakearly', 'local_studytimer'),
                'quizpacingtitle' => get_string('quizpacingtitle', 'local_studytimer'),
                'quizpacingenable' => get_string('quizpacingenable', 'local_studytimer'),
                'secondsperquestion' => get_string('secondsperquestion', 'local_studytimer'),
                'questionlabel' => get_string('questionlabel', 'local_studytimer'),
                'pacingovertime' => get_string('pacingovertime', 'local_studytimer'),
            ],
        ];

        $hook->add_html($hook->renderer->render_from_template('local_studytimer/floating_popup', $context));
    }
}
