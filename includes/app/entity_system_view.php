<?php
declare(strict_types=1);
require __DIR__.'/entity_system_view_styles.php';
$view=__DIR__.'/entity_system_views/'.$tab.'.php';
if(!is_file($view))throw new RuntimeException('Entity workspace view is unavailable.');
require $view;
