RainTPL 4
=========

[![Latest Stable Version](https://poser.pugx.org/pantheraframework/raintpl4/v/stable)](https://packagist.org/packages/pantheraframework/raintpl4)
[![License](https://poser.pugx.org/pantheraframework/raintpl4/license)](https://packagist.org/packages/pantheraframework/raintpl4)
[![Total Downloads](https://poser.pugx.org/pantheraframework/raintpl4/downloads)](https://packagist.org/packages/pantheraframework/raintpl4)
[![Build status](https://travis-ci.org/Panthera-Framework/raintpl4.svg)](https://travis-ci.org/Panthera-Framework/raintpl4)
[![Code Climate](https://codeclimate.com/github/Panthera-Framework/raintpl4/badges/gpa.svg)](https://codeclimate.com/github/Panthera-Framework/raintpl4)
[![Test Coverage](https://codeclimate.com/github/Panthera-Framework/raintpl4/badges/coverage.svg)](https://codeclimate.com/github/Panthera-Framework/raintpl4/coverage)

[RainTPL](http://raintpl.com) is an easy template engine for PHP that enables designers and developers to work better together, it loads HTML template to separate the view from the logic.

RainTPL4 was created as a refactored and improved RainTPL3 engine, with improved performance, new tags, better syntax and compatibility with Smarty templating engine.

Originally by Federico Ulfo and a lot [awesome contributors](https://github.com/rainphp/raintpl3/network)

Features
--------
* New technology - supports embedding LESS, SASS, SCSS and Coffescript into &lt;script&gt;, &lt;link&gt; and &lt;style&gt; tags, code is automaticaly re-compilled into pure CSS and Javascript by RainTPL4 using external tools
* Faster performance than any other templating engine for PHP offering same possibilities
* Easy for designers, simple and understandable tags known from Smarty
* Easy for developers, 5 methods to load and draw templates
* Powerful - modifiers on variables, strings and functions
* Extensible, load plugins and register new tags
* Secure, sandbox using PHP Parser


Installation / Usage
--------------------

1. Install composer https://github.com/composer/composer
2. Create a composer.json inside your application folder:

    ``` json
    {
        "require": {
            "rain/raintpl": ">=4.0.0"
        }
    }
    ```
3. Run the following code

    ``` sh
    $ php composer.phar install
    ```

4. Run one example of RainTPL with your browser: ```http://localhost/raintpl3/examples/example-all.php```

Documentation
-------------
The [documentation](https://github.com/rainphp/raintpl3/wiki/Documentation) of RainTPL is divided in [documentation for web designers](https://github.com/rainphp/raintpl3/wiki/Documentation-for-web-designers) and [documentation for PHP developers](https://github.com/rainphp/raintpl3/wiki/Documentation-for-PHP-developers).
