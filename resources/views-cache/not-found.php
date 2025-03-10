<!DOCTYPE html>
<html lang="en">
<head>
    <title>Page Not Found</title>
    <link rel="icon" href="<?php echo htmlspecialchars(strval(favicon('cropped-Favicon.png'))); ?>">
    <script defer src="<?php echo htmlspecialchars(strval(assetsUrl('/js/scripts.js'))); ?>"></script>
</head>

<body>
    <h1 style="text-align: center;">
        <?php echo htmlspecialchars(strval($_view->err ?? '404 Page Not Found')); ?>
    </h1>
</body>

</html>
