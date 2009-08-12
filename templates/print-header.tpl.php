<div class='print-header <?php if ($first) print "print-header-first" ?>'>
  <div class='count'><?php print $count ?></div>
  <?php if (file_exists("{$theme_path}/print/logo.png")): ?>
    <img class='logo' alt="<?php print $site_name ?>" src="<?php print $theme_path ?>/print/logo.png" />
  <?php else: ?>
    <h1 class='site-name'><?php print $site_name ?></h1>
  <?php endif; ?>
</div>
