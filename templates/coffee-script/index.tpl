<html>
<head>
    <title>RainTPL4 and CoffeeScript integration.</title>

    <!-- CoffeeScript.baseDir configuration key was set to point at directory where is example-coffee-script.php script,
         but the template itself should be written into directory where this template is placed -->

    <script type="text/coffeescript" src="../../templates/coffee-script/test.coffee"></script>

    <style type="text/css">
        #status {
            color: #ffffff;
            line-height: 50px;
            font-size: 20px;
            text-align: center;
        }
    </style>

</head>

<body>
    <p id="status">CoffeeScript is alive!</p>

    <script type="text/coffeescript">
        console.log "This was written from CoffeeScript."
        document.getElementById("status").style.background = 'green'
    </script>

</body>

</html>