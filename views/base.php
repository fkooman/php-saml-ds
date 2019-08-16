<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="application-name" content="php-saml-ds">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SAML Discovery Service</title>
    <link rel="stylesheet" type="text/css" href="css/screen.css">
    <?php if ($useLogos): ?>
        <link rel="stylesheet" type="text/css" href="logo/idp/<?=$this->e($encodedEntityID); ?>.css?mTime=<?=$this->e($mTime); ?>">
    <?php endif; ?>
    <script type="text/javascript" src="js/filter.js"></script>
</head>
<body>
    <div class="header">
        <?=$this->section('header'); ?>
    </div> <!-- header -->
    <div class="container">
        <?=$this->section('container'); ?>
    </div> <!-- container -->

    <div class="footer">
        <a href="https://git.tuxed.net/fkooman/php-saml-ds">SAML Discovery Service</a>
    </div> <!-- footer -->
</body>
</html>
