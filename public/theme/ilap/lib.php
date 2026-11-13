
<?php

defined('MOODLE_INTERNAL') || die();

function theme_ilap_get_pre_scss($theme) {
    global $CFG;

    return file_get_contents($CFG->dirroot . '/theme/ilap/scss/pre.scss');
}

function theme_ilap_get_extra_scss($theme) {
    global $CFG;

    return file_get_contents($CFG->dirroot . '/theme/ilap/scss/post.scss');
}
