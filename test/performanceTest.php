<?php
$time = microtime(true);
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
    "debug"         => true, // set to false to improve the speed
    "ignore_unknown_tags"   => true,
    /*"pluginsEnabled" => array(
        'pseudoSandboxing',
    )*/
));

// Add PathReplace plugin (necessary to load the CSS with path replace)
//$rain->registerPlugin( new Tpl\Plugin\PathReplace() );


// set variables
$rain->assign(array(
    "variable"  => "Hello World!",
    "bad_variable"  => "<script>alert('evil javascript here');</script>",
    "safe_variable"  => "<script>console.log('this is safe')</script>",
    "version"   => "4",
    "menu"		=> array(
        array("name" => "Home", "link" => "index.php", "selected" => true ),
        array("name" => "FAQ", "link" => "index.php/FAQ/", "selected" => null ),
        array("name" => "Documentation", "link" => "index.php/doc/", "selected" => null )
    ),
    "week"		=> array( "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday" ),
    "user"		=> (object) array("name"=>"Rain", "citizen" => "Earth", "race" => "Human" ),
    "numbers"	=> array( 3, 2, 1 ),
    "bad_text"	=> 'Hey this is a malicious XSS <script>alert("auto_escape is always enabled");</script>',
    "table"		=> array( array( "Apple", "1996" ), array( "PC", "1997", "1998" ) ),
    "title"		=> "Rain TPL 3 - Easy and Fast template engine",
    "copyright" => "Copyright 2006 - 2012 Rain TPL<br>Project By Rain Team",

));

// add a tag
/*$rain->registerTag(	"({@.*?@})", // preg split
    "{@(.*?)@}", // preg match
    function( $params ){ // function called by the tag
        $value = $params[1][0];
        return "Translate: <b>$value</b>";
    }
);*/


// add a tag
/*$rain->registerTag(	"({%.*?%})", // preg split
    "{%(.*?)(?:\|(.*?))%}", // preg match
    function( $params ){ // function called by the tag
        $value = $params[1][0];
        $value2 = $params[2][0];

        return "Translate: <b>$value</b> in <b>$value2</b>";
    }
);*/

// draw
$count = 1000;

/**
 * 1000 compilation + 1000 rendering of a simple demo template (with sandboxing)
 *
 * @debug
 */
print("1000 compilation + 1000 rendering of a simple demo template: \n");

for($i = 0; $i < $count; $i++)
{
    //print($i. "\n");
    $rain->draw("test", true);
}

print("\n" .(microtime(true) - $time). "s\n\n");

/**
 * 1000 compilations + 1000 rendering of a simple demo template (with sandboxing)
 *
 * @debug
 * @sandboxing
 */
$time = microtime(true);
print("1000 compilations + 1000 rendering of a simple demo template (with sandboxing): \n");
$rain->setConfigurationKey('pluginsEnabled', array(
    'pseudoSandboxing',
));

for($i = 0; $i < $count; $i++)
{
    //print($i. "\n");
    $rain->draw("test", true);
}

print("\n" .(microtime(true) - $time). "s\n\n");

/**
 * 1 compilation + 1000 rendering of a simple demo template (with sandboxing)
 *
 * @debug
 */
$time = microtime(true);
print("1 compilation + 1000 rendering of a simple demo template: \n");
$rain->setConfigurationKey('pluginsEnabled', array());

$rain->clean(-256);
$rain->setConfigurationKey('debug', false);
for($i = 0; $i < $count; $i++)
{
    //print($i. "\n");
    $rain->draw("test", true);
}

print("\n" .(microtime(true) - $time). "s\n\n");

/**
 * 1 compilation + 1 rendering of a simple demo template (with sandboxing)
 *
 * @debug
 */
$time = microtime(true);
print("1 compilation + 1 rendering of a simple demo template: \n");
$rain->clean(-256);
$rain->setConfigurationKey('pluginsEnabled', array());
$rain->setConfigurationKey('debug', false);
$rain->draw("test", true);

print("\n" .(microtime(true) - $time). "s\n\n");

/**
 * 0 compilation + 1 rendering of a simple demo template (with sandboxing)
 *
 * @debug
 */
$time = microtime(true);
print("0 compilation + 1 rendering of a simple demo template: \n");
$rain->setConfigurationKey('pluginsEnabled', array());
$rain->setConfigurationKey('debug', false);
$rain->draw("test", true);

print("\n" .(microtime(true) - $time). "s\n\n");

class Test{
    static public function method($variable){
        echo "Hi I am a static method, and this is the parameter passed to me: $variable!";
    }
}

// end