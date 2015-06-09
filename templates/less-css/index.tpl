<html>
    <head>
        <title>RainTPL4 and less css integration</title>

        <!-- CSSLess.baseDir configuration key was set to point at directory where is example-less-css.php script,
             but the template itself should be writed into directory where this template is placed -->

        <link rel="stylesheet" type="text/sass" href="../../templates/less-css/test.css" source="test.scss">
        <style type="text/less">
            @base: #f938ab;

            .box-shadow(@style, @c) when (iscolor(@c)) {
                -webkit-box-shadow: @style @c;
                box-shadow:         @style @c;
            }

            .box-shadow(@style, @alpha: 50%) when (isnumber(@alpha)) {
                .box-shadow(@style, rgba(0, 0, 0, @alpha));
            }

            .box {
                color: saturate(@base, 5%);
                border-color: lighten(@base, 30%);
                div { .box-shadow(0 0 5px, 30%) }
            }
        </style>

        <!-- this is a regular CSS style, should not be touched by CSSLess plugin -->
        <style>
            body {
                background-color: white;
            }
        </style>
    </head>

    <body>
        <div class="box">This is a test of RainTPL4 integration with new technologies - less css, a better, smarter language compilable to CSS.</div>

        <div class="sassTestBox">And this is a test of SASS compiled code into CSS, look, we have two languages less and sass at one template compiled into single css, that's amazing!</div>
    </body>
</html>