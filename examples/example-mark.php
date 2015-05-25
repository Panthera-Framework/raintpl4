<?php

// include
include "../library/Rain/autoload.php";

// namespace
use Rain\Tpl;

$rain = new Rain\RainTPL4;
$rain->setConfiguration(array(
    "base_url"      => null,
    "tpl_dir"       => "../templates/test/",
    "cache_dir"     => "/tmp/",
    "remove_comments" => true,
    "debug"         => true,
    "ignore_unknown_tags"   => true,
));

// Marks examples.
echo $rain->drawString("{goto test}This code should not execute.{mark test}This is a first test. Amazingly, it works!");