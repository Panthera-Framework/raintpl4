<?php

require_once "../library/Rain/autoload.php";

use Rain\Tpl;

$rain = new Rain\RainTPL4;

$rain->setConfiguration(array(
    'base_url'	=> null,
    'tpl_dir'	=> "../templates/raintpl3/",
    'cache_dir'	=> "/tmp/",
    'debug'     => true
));

global $global_variable;
$global_variable = "I'm Global";

$rain->assign(array(
    "version"	=> "4.1 Beta",
    "menu"		=> array(
        array("name" => "Home", "selected" => true ),
        array("name" => "FAQ", "selected" => null ),
        array("name" => "Documentation", "selected" => null )
    ),
    "title"		=> "RainTPL4 - Easy and Fast template engine",
    "copyright" => "Copyright 2006 - 2015 Rain TPL<br>Project By Rain Team",
));

function test( $params ){
    $value = $params[0];
    return "Translate: <b>$value</b>";
};
echo $rain->draw("page");