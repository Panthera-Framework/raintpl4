<?php

require "../library/Rain/autoload.php";

use Rain\Tpl;

$rain = new \Rain\RainTPL4();

$rain->setConfiguration(array(
    "base_url"      => null,
    "tpl_dir"       => "../templates/nested-loop/",
    "cache_dir"     => "/tmp/",
    "debug"         => true
));


$user = array(
    array(
        'name' => 'Jupiter',
        'color' => 'yellow',
        'orders' => array(
            array('order_id' => '123', 'order_name' => 'o1d'),
            array('order_id' => '1sn24', 'order_name' => 'o2d')
        )
    ),
    array(
        'name' => 'Mars',
        'color' => 'red',
        'orders' => array(
            array('order_id' => '3rf22', 'order_name' => '¶©µ¥Aj')
        )
    ),
    array(
        'name' => 'Empty',
        'color' => 'blue',
        'orders' => array(
        )
    ),
    array(
        'name' => 'Earth',
        'color' => 'blue',
        'orders' => array(
            array('order_id' => '2315', 'order_name' => '¶©µ¥15'),
            array('order_id' => 'rf2123', 'order_name' => '¶©µ¥215'),
            array('order_id' => '0231', 'order_name' => '¶©µ¥315'),
            array('order_id' => 'sn09-0fsd', 'order_name' => '¶©µ¥45415')
        )
    )
);

$rain->assign(array('user' => $user));

echo $rain->draw("test");