<?php

/**
 * Implements hook_css_alter().
 * @TODO: Once http://drupal.org/node/901062 is resolved, determine whether
 * this can be implemented in the .info file instead.
 *
 * Omitted:
 * - color.css
 * - contextual.css
 * - dashboard.css
 * - field_ui.css
 * - image.css
 * - locale.css
 * - shortcut.css
 * - toolbar.css
 */
function tao_css_alter(&$css) {
  $exclude = array(
    'modules/aggregator/aggregator.css' => FALSE,
    'modules/block/block.css' => FALSE,
    'modules/book/book.css' => FALSE,
    'modules/comment/comment.css' => FALSE,
    'modules/dblog/dblog.css' => FALSE,
    'modules/file/file.css' => FALSE,
    'modules/filter/filter.css' => FALSE,
    'modules/forum/forum.css' => FALSE,
    'modules/help/help.css' => FALSE,
    'modules/menu/menu.css' => FALSE,
    'modules/node/node.css' => FALSE,
    'modules/openid/openid.css' => FALSE,
    'modules/poll/poll.css' => FALSE,
    'modules/profile/profile.css' => FALSE,
    'modules/search/search.css' => FALSE,
    'modules/shortcut/shortcut.css' => FALSE,
    'modules/simpletest/simpletest.css' => FALSE,
    'modules/statistics/statistics.css' => FALSE,
    'modules/syslog/syslog.css' => FALSE,
    'modules/system/admin.css' => FALSE,
    'modules/system/maintenance.css' => FALSE,
    'modules/system/system.css' => FALSE,
    'modules/system/system-behavior.css' => FALSE,
    'modules/system/system-menus.css' => FALSE,
    'modules/system/system-messages.css' => FALSE,
    'modules/taxonomy/taxonomy.css' => FALSE,
    'modules/tracker/tracker.css' => FALSE,
    'modules/update/update.css' => FALSE,
    'modules/user/user.css' => FALSE,
  );
  $css = array_diff_key($css, $exclude);
}

/**
 * Implementation of hook_theme().
 */
function tao_theme() {
  $items = array();

  // Consolidate a variety of theme functions under a single template type.
  $items['block'] = array(
    'arguments' => array('block' => NULL),
    'template' => 'object',
    'path' => drupal_get_path('theme', 'tao') .'/templates',
  );
  $items['box'] = array(
    'arguments' => array('title' => NULL, 'content' => NULL, 'region' => 'main'),
    'template' => 'object',
    'path' => drupal_get_path('theme', 'tao') .'/templates',
  );
  $items['comment'] = array(
    'arguments' => array('comment' => NULL, 'node' => NULL, 'links' => array()),
    'template' => 'object',
    'path' => drupal_get_path('theme', 'tao') .'/templates',
  );
  $items['node'] = array(
    'arguments' => array('node' => NULL, 'teaser' => FALSE, 'page' => FALSE),
    'template' => 'node',
    'path' => drupal_get_path('theme', 'tao') .'/templates',
  );
  $items['fieldset'] = array(
    'arguments' => array('element' => array()),
    'template' => 'fieldset',
    'path' => drupal_get_path('theme', 'tao') .'/templates',
  );

  // Print friendly page headers.
  $items['print_header'] = array(
    'arguments' => array(),
    'template' => 'print-header',
    'path' => drupal_get_path('theme', 'tao') .'/templates',
  );

  // Split out pager list into separate theme function.
  $items['pager_list'] = array('arguments' => array(
    'tags' => array(),
    'limit' => 10,
    'element' => 0,
    'parameters' => array(),
    'quantity' => 9,
  ));

  return $items;
}

/**
 * Print all child pages of a book.
 */
function tao_print_book_children($node) {
  // We use a semaphore here since this function calls and is called by the
  // node_view() stack so that it may be called multiple times for a single book tree.
  static $semaphore;

  if (module_exists('book') && book_type_is_allowed($node->type)) {
    if (isset($_GET['print']) && isset($_GET['book_recurse']) && !isset($semaphore)) {
      $semaphore = TRUE;

      $child_pages = '';
      $zomglimit = 0;
      $tree = array_shift(book_menu_subtree_data($node->book));
      if (!empty($tree['below'])) {
        foreach ($tree['below'] as $link) {
          _tao_print_book_children($link, $child_pages, $zomglimit);
        }
      }

      unset($semaphore);

      return $child_pages;
    }
  }

  return '';
}

/**
 * Book printing recursion.
 */
function _tao_print_book_children($link, &$content, &$zomglimit, $limit = 500) {
  if ($zomglimit < $limit) {
    $zomglimit++;
    if (!empty($link['link']['nid'])) {
      $node = node_load($link['link']['nid']);
      if ($node) {
        $content .= node_view($node);
      }
      if (!empty($link['below'])) {
        foreach ($link['below'] as $child) {
          _tao_print_book_children($child, $content);
        }
      }
    }
  }
}

/**
 * Preprocess functions ===============================================
 */
function tao_preprocess_html(&$vars) {
  $vars['classes_array'][] = 'tao';
}

/**
 * Implementation of preprocess_page().
 */
function tao_preprocess_page(&$vars) {
  // Replace screen/all stylesheets with print
  // We want a minimal print representation here for full control.
  if (isset($_GET['print'])) {
    $css = drupal_add_css();
    unset($css['all']);
    unset($css['screen']);
    $css['all'] = $css['print'];
    $vars['styles'] = drupal_get_css($css);

    // Add print header
    $vars['print_header'] = theme('print_header');

    // Replace all body classes
    $attr['class'] = 'print';

    // Use print template
    $vars['template_file'] = 'print-page';

    // Suppress devel output
    $GLOBALS['devel_shutdown'] = FALSE;
  }

  // Split primary and secondary local tasks
  $vars['primary_local_tasks'] = menu_primary_local_tasks();
  $vars['secondary_local_tasks'] = menu_secondary_local_tasks();

  // Link site name to frontpage
  $vars['site_name'] = l($vars['site_name'], '<front>');
}

/**
 * Implementation of preprocess_block().
 */
function tao_preprocess_block(&$vars) {
  // Hide blocks with no content.
  // $vars['hide'] = empty($vars['block']->content);

  $vars['attributes_array']['id'] = $vars['block_html_id'];

  // $attr = array();
  // $attr['id'] = "block-{$vars['block']->module}-{$vars['block']->delta}";
  // $attr['class'] = "block block-{$vars['block']->module}";
  // $vars['attr'] = $attr;

  $vars['hook'] = 'block';
  $vars['title'] = !empty($vars['block']->subject) ? $vars['block']->subject : '';
  $vars['is_prose'] = ($vars['block']->module == 'block') ? TRUE : FALSE;
}

/**
 * Implementation of preprocess_node().
 */
function tao_preprocess_node(&$vars) {
  $attr = array();
  $attr['id'] = "node-{$vars['node']->nid}";
  $attr['class'] = "node node-{$vars['node']->type}";
  $attr['class'] .= $vars['node']->sticky ? ' sticky' : '';
  $vars['attr'] = $attr;

  $vars['hook'] = 'node';
  $vars['is_prose'] = TRUE;

  // Add print customizations
  if (isset($_GET['print'])) {
    $vars['post_object'] = tao_print_book_children($vars['node']);
  }
}

/**
 * Implementation of preprocess_comment().
 */
function tao_preprocess_comment(&$vars) {
  $attr = array();
  $attr['id'] = "comment-{$vars['comment']->cid}";
  $attr['class'] = "comment {$vars['status']}";
  $vars['attr'] = $attr;

  $vars['hook'] = 'comment';
  $vars['is_prose'] = TRUE;
}

/**
 * Implementation of preprocess_fieldset().
 */
function tao_preprocess_fieldset(&$vars) {
  $element = $vars['element'];
  $vars['attributes'] = $element['#attributes'];

  $description = !empty($element['#description']) ? "<div class='description'>{$element['#description']}</div>" : '';
  $children = !empty($element['#children']) ? $element['#children'] : '';
  $value = !empty($element['#value']) ? $element['#value'] : '';
  $vars['content'] = $description . $children . $value;
  $vars['title'] = !empty($element['#title']) ? $element['#title'] : '';
  $vars['hook'] = 'fieldset';
}

/**
 * Preprocessor for theme_print_header().
 */
function tao_preprocess_print_header(&$vars) {
  $vars = array(
    'base_path' => base_path(),
    'theme_path' => base_path() .'/'. path_to_theme(),
    'site_name' => variable_get('site_name', 'Drupal'),
  );
  $count ++;
}

/**
 * Function overrides =================================================
 */

/**
 * Override of theme_menu_local_tasks().
 * Add argument to allow primary/secondary local tasks to be printed
 * separately. Use theme_links() markup to consolidate.
 */
function tao_menu_local_tasks($type = '') {
  if ($primary = menu_primary_local_tasks()) {
    $primary = "<ul class='links primary-tabs'>{$primary}</ul>";
  }
  if ($secondary = menu_secondary_local_tasks()) {
    $secondary = "<ul class='links secondary-tabs'>$secondary</ul>";
  }
  switch ($type) {
    case 'primary':
      return $primary;
    case 'secondary':
      return $secondary;
    default:
      return $primary . $secondary;
  }
}

/**
 * Override of theme_blocks().
 * Allows additional theme functions to be defined per region to
 * control block display on a per-region basis. Falls back to default
 * block region handling if no region-specific overrides are found.
 */
function tao_blocks($region) {
  // Allow theme functions some additional control over regions.
  $registry = theme_get_registry();
  if (isset($registry['blocks_'. $region])) {
    return theme('blocks_'. $region);
  }
  return module_exists('context') && function_exists('context_blocks') ? context_blocks($region) : theme_blocks($region);
}

/**
 * Override of theme_username().
 */
function tao_username($object) {
  if (!empty($object->name)) {
    // Shorten the name when it is too long or it will break many tables.
    $name = drupal_strlen($object->name) > 20 ? drupal_substr($object->name, 0, 15) .'...' : $object->name;
    $name = check_plain($name);

    // Default case -- we have a real Drupal user here.
    if ($object->uid && user_access('access user profiles')) {
      return l($name, 'user/'. $object->uid, array('attributes' => array('class' => 'username', 'title' => t('View user profile.'))));
    }
    // Handle cases where user is not registered but has a link or name available.
    else if (!empty($object->homepage)) {
      return l($name, $object->homepage, array('attributes' => array('class' => 'username', 'rel' => 'nofollow')));
    }
    // Produce an unlinked username.
    else {
      return "<span class='username'>{$name}</span>";
    }
  }
  return "<span class='username'>". variable_get('anonymous', t('Anonymous')) ."</span>";
}

/**
 * Override of theme_pager().
 * Easily one of the most obnoxious theming jobs in Drupal core.
 * Goals: consolidate functionality into less than 5 functions and
 * ensure the markup will not conflict with major other styles
 * (theme_item_list() in particular).
 */
function tao_pager($vars) {
  $tags = $vars['tags'];
  $element = $vars['element'];
  $parameters = $vars['parameters'];
  $quantity = $vars['quantity'];
  $pager_list = theme('pager_list', $vars);

  $links = array();
  $links['pager-first'] = theme('pager_first', array(
    'text' => (isset($tags[0]) ? $tags[0] : t('First')),
    'element' => $element,
    'parameters' => $parameters
  ));
  $links['pager-previous'] = theme('pager_previous', array(
    'text' => (isset($tags[1]) ? $tags[1] : t('Prev')),
    'element' => $element,
    'interval' => 1,
    'parameters' => $parameters
  ));
  $links['pager-next'] = theme('pager_next', array(
    'text' => (isset($tags[3]) ? $tags[3] : t('Next')),
    'element' => $element,
    'interval' => 1,
    'parameters' => $parameters
  ));
  $links['pager-last'] = theme('pager_last', array(
    'text' => (isset($tags[4]) ? $tags[4] : t('Last')),
    'element' => $element,
    'parameters' => $parameters
  ));
  $links = array_filter($links);
  $pager_links = theme('links', array(
    'links' => $links,
    'attributes' => array('class' => 'links pager pager-links')
  ));
  if ($pager_list) {
    return "<div class='pager clear-block'>$pager_list $pager_links</div>";
  }
}

/**
 * Split out page list generation into its own function.
 */
function tao_pager_list($vars) {
  $tags = $vars['tags'];
  $element = $vars['element'];
  $parameters = $vars['parameters'];
  $quantity = $vars['quantity'];

  global $pager_page_array, $pager_total;
  if ($pager_total[$element] > 1) {
    // Calculate various markers within this pager piece:
    // Middle is used to "center" pages around the current page.
    $pager_middle = ceil($quantity / 2);
    // current is the page we are currently paged to
    $pager_current = $pager_page_array[$element] + 1;
    // first is the first page listed by this pager piece (re quantity)
    $pager_first = $pager_current - $pager_middle + 1;
    // last is the last page listed by this pager piece (re quantity)
    $pager_last = $pager_current + $quantity - $pager_middle;
    // max is the maximum page number
    $pager_max = $pager_total[$element];
    // End of marker calculations.

    // Prepare for generation loop.
    $i = $pager_first;
    if ($pager_last > $pager_max) {
      // Adjust "center" if at end of query.
      $i = $i + ($pager_max - $pager_last);
      $pager_last = $pager_max;
    }
    if ($i <= 0) {
      // Adjust "center" if at start of query.
      $pager_last = $pager_last + (1 - $i);
      $i = 1;
    }
    // End of generation loop preparation.

    $links = array();

    // When there is more than one page, create the pager list.
    if ($i != $pager_max) {
      // Now generate the actual pager piece.
      for ($i; $i <= $pager_last && $i <= $pager_max; $i++) {
        if ($i < $pager_current) {
          $links["$i pager-item"] = theme('pager_previous', array(
            'text' => $i,
            'element' => $element,
            'interval' => ($pager_current - $i),
            'parameters' => $parameters
          ));
        }
        if ($i == $pager_current) {
          $links["$i pager-current"] = array('title' => $i);
        }
        if ($i > $pager_current) {
          $links["$i pager-item"] = theme('pager_next', array(
            'text' => $i,
            'element' => $element,
            'interval' => ($i - $pager_current),
            'parameters' => $parameters
          ));
        }
      }
      return theme('links', array(
        'links' => $links,
        'attributes' => array('class' => 'links pager pager-list')
      ));
    }
  }
  return '';
}

/**
 * Return an array suitable for theme_links() rather than marked up HTML link.
 */
function tao_pager_link($vars) {
  $text = $vars['text'];
  $page_new = $vars['page_new'];
  $element = $vars['element'];
  $parameters = $vars['parameters'];
  $attributes = $vars['attributes'];

  $page = isset($_GET['page']) ? $_GET['page'] : '';
  if ($new_page = implode(',', pager_load_array($page_new[$element], $element, explode(',', $page)))) {
    $parameters['page'] = $new_page;
  }

  $query = array();
  if (count($parameters)) {
    $query = drupal_get_query_parameters($parameters, array());
  }
  if ($query_pager = pager_get_query_parameters()) {
    $query = array_merge($query, $query_pager);
  }

  // Set each pager link title
  if (!isset($attributes['title'])) {
    static $titles = NULL;
    if (!isset($titles)) {
      $titles = array(
        t('« first') => t('Go to first page'),
        t('‹ previous') => t('Go to previous page'),
        t('next ›') => t('Go to next page'),
        t('last »') => t('Go to last page'),
      );
    }
    if (isset($titles[$text])) {
      $attributes['title'] = $titles[$text];
    }
    else if (is_numeric($text)) {
      $attributes['title'] = t('Go to page @number', array('@number' => $text));
    }
  }

  return array(
    'title' => $text,
    'href' => $_GET['q'],
    'attributes' => $attributes,
    'query' => count($query) ? implode('&', $query) : NULL,
  );
}

/**
 * Override of theme_views_mini_pager().
 */
function tao_views_mini_pager($tags = array(), $limit = 10, $element = 0, $parameters = array(), $quantity = 9) {
  global $pager_page_array, $pager_total;

  // Calculate various markers within this pager piece:
  // Middle is used to "center" pages around the current page.
  $pager_middle = ceil($quantity / 2);
  // current is the page we are currently paged to
  $pager_current = $pager_page_array[$element] + 1;
  // max is the maximum page number
  $pager_max = $pager_total[$element];
  // End of marker calculations.


  $links = array();
  if ($pager_total[$element] > 1) {
    $links['pager-previous'] = theme('pager_previous', (isset($tags[1]) ? $tags[1] : t('‹‹')), $limit, $element, 1, $parameters);
    $links['pager-current'] = array('title' => t('@current of @max', array('@current' => $pager_current, '@max' => $pager_max)));
    $links['pager-next'] = theme('pager_next', (isset($tags[3]) ? $tags[3] : t('››')), $limit, $element, 1, $parameters);
    return theme('links', $links, array('class' => 'links pager views-mini-pager'));
  }
}
