<?php

include "../library/Rain/autoload.php";

use Rain\Tpl;

/**
 * Class PrintVariable
 *      Test object created to present RainTPL4 functionality
 *
 * Prints argument if method called `method` is called.
 */
class PrintVariable {
    static public function method($variable)
    {
        echo "Hi I am a static method, and this is the parameter passed to me: $variable!";
    }
}

$rain = new Rain\RainTPL4;

$rain->setConfiguration(array(
        'auto_escape'     => true,
        "base_url"        => null,
        "tpl_dir"         => "../templates/test/",
        "cache_dir"       => "/tmp/",
        "remove_comments" => true,
        "debug"           => true, // set to false to improve the speed

        "ignore_unknown_tags"   => true,
        'charset'               => 'utf-8',
));


// set variables
$rain->assign(array(
    "variable"       => "Hello World!",
    "bad_variable"   => "<script>alert('evil javascript here');</script>",
    "safe_variable"  => "<script>alert('this is safe')</script>",
    "version"        => "4.0 Alpha",

    "week"		=> array( "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday" ),
    "user"		=> (object) array("name"=>"Rain", "citizen" => "Earth", "race" => "Human" ),
    "numbers"	=> array( 3, 2, 1 ),
    "bad_text"	=> 'Hey this is a malicious XSS <script>alert("auto_escape is always enabled");</script>',
    "table"		=> array( array( "Apple", "1996" ), array( "PC", "1997", "1998" ) ),
    "title"		=> "RainTPL4 - Easy and Fast template engine",
    "copyright" => "Copyright 2006 - 2015 Rain TPL<br>Project By Rain Team",
));

echo $rain->draw("test");