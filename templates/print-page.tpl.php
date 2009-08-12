<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php print $language->language ?>" lang="<?php print $language->language ?>">
<head>
  <?php print $head ?>
  <?php print $styles ?>

  <title><?php print $head_title ?></title>
</head>

<body <?php print drupal_attributes($attr) ?>>

  <div class='root'>

  <div id="page"><div class='limiter clear-block'>
    <div id="main" class='clear-block'>
      <div id='content' class='clear-block'>
        <?php print $content ?>
      </div>
    </div>
  </div></div>

  <div id="footer"><div class='limiter clear-block'>
    <?php if($footer_message) print $footer_message ?>
  </div></div>

  </div>

</body>

</html>
