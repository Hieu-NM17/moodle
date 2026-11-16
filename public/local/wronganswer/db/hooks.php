<?php
// This file is part of Moodle - http://moodle.org/.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

$callbacks = [
    [
        'hook' => \core\hook\output\after_standard_main_region_html_generation::class,
        'callback' => [\local_wronganswer\hook_callbacks::class, 'after_standard_main_region_html_generation'],
    ],
];
