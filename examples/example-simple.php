<?php

require_once "../library/Rain/autoload.php";

use Rain\Tpl;

$rain = new Rain\RainTPL4;

$rain->setConfiguration(array(
    "tpl_dir"       => "../templates/simple/",
    "cache_dir"     => "/tmp/",
    "debug"         => true
));

// assign a variable
$rain->assign(array(
    'name' => 'Obi Wan Kenoby',
    'week' => array( "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday" ),
));



echo $rain->draw( "simple_template" );
?>