
<?php

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/lib.php');

$THEME->name = 'ilap';
$THEME->parents = ['boost'];

$THEME->sheets = [];
$THEME->editor_sheets = [];

$THEME->scss = function($theme) {
    return theme_boost_get_main_scss_content($theme);
};

$THEME->prescsscallback = 'theme_ilap_get_pre_scss';
$THEME->extrascsscallback = 'theme_ilap_get_extra_scss';

$THEME->rendererfactory = 'theme_overridden_renderer_factory';
