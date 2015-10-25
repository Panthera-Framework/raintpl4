<?php
include "../library/Rain/autoload.php";

// namespace
use Rain\Tpl;

$rain = new Rain\RainTPL4;
$rain->setConfiguration(array(
    'auto_escape'   => true,
    "base_url"      => null,
    "tpl_dir"       => "../templates/test/",
    "cache_dir"     => "/tmp/",
    "remove_comments" => true,
    "debug"         => true, // set to false to improve the speed
    "ignore_unknown_tags"   => true,
));

// Add PathReplace plugin (necessary to load the CSS with path replace)
//$rain->registerPlugin( new Tpl\Plugin\PathReplace() );


// set variables
$rain->assign(array(
    "variable"  => "Hello World!",
    "bad_variable"  => "<script>alert('evil javascript here');</script>",
    "safe_variable"  => "<script>console.log('this is safe')</script>",
    "version"   => "3.1 Beta",
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

/**
 * Add a custom non-regexp tag
 */
function translate($message, $language)
{
    $messages = array(
        'english' => array(
            'message to translate' => 'message translated',
        ),
    );

    if (isset($messages[$language][$message]))
    {
        return $messages[$language][$message];
    }

    return $message;
}

$rain->tags['translate'] = true;
$rain->blockParserCallbacks['translate'] = function(&$tagData, &$part, &$tag, $templateFilePath, $blockIndex, $blockPositions, $code, &$passAllBlocksTo, $lowerPart) {
    $opening = substr($part, 0, 2);

    if ($opening == '{@' || $opening == '{%')
    {
        $message = substr($part, 2, -2);
        $delimiterPos = strpos($message, '|');
        $language = 'english';

        if ($delimiterPos !== false)
        {
            $language = substr($message, ($delimiterPos + 1));
            $message = substr($message, 0, $delimiterPos);
        }

        // replace code block
        $part = translate($message, $language);

        // set parsing status as successful (tag was detected and replaced)
        return true;
    }
};

// draw
echo $rain->draw("test");


class Test{
    static public function method($variable){
        echo "Hi I am a static method, and this is the parameter passed to me: $variable!";
    }
}

// end