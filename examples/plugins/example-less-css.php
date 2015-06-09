<?php
/**
 * Example of using LESS CSS language with RainTPL4
 *
 * @package Rain\examples
 */

include __DIR__. '/../../library/Rain/autoload.php';

$rain = new Rain\RainTPL4;
$rain->setConfiguration(array(
    'base_url'            => null,
    'tpl_dir'             => __DIR__. '/../../templates/',
    'cache_dir'           => "/tmp/",
    'remove_comments'     => true,
    'debug'               => true,
    'ignore_unknown_tags' => true,
    'pluginsEnabled'      => array(
        'CSSLess',
    ),
    'CSSLess.baseDir'     => __DIR__,
));

$rain->draw('less-css/index.tpl');