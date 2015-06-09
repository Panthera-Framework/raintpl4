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
    'debug'               => false,
    'ignore_unknown_tags' => true,
    'pluginsEnabled'      => array(
        'CSSLess',
    ),
    'CSSLess.baseDir'     => __DIR__,
    'CSSLess.less.executable' => 'lessc', // only lessc is supported
    'CSSLess.sass.executable' => 'sass', // could be for example: sass, sassc, pyscss
));

$rain->draw('less-css/index.tpl');