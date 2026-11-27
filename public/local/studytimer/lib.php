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
 * Library functions for local_studytimer.
 *
 * @package   local_studytimer
 * @copyright 2026 Hieu-NM17
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Render the study timer toggle icon in the top navbar next to the notification bell.
 *
 * @param \renderer_base $output
 * @return string
 */
function local_studytimer_render_navbar_output(\renderer_base $output): string {
    if (during_initial_install() || !isloggedin() || isguestuser()) {
        return '';
    }

    return $output->render_from_template('local_studytimer/navbar_toggle', [
        'title' => get_string('navbartoggle', 'local_studytimer'),
    ]);
}
