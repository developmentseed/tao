<?php

/**
 * Implementation of hook_theme().
 */
function tao_theme() {
  return array(
    'fieldset' => array(
      'arguments' => array('element' => array()),
      'template' => 'object',
      'path' => drupal_get_path('theme', 'tao') .'/templates',
    ),
    'print_header' => array(
      'arguments' => array(),
      'template' => 'print-header',
      'path' => drupal_get_path('theme', 'tao') .'/templates',
    ),
    'pager_list' => array(),
  );
}

/**
 * Strips CSS files from a Drupal CSS array whose filenames start with
 * prefixes provided in the $match argument.
 */
function tao_css_stripped($match = array('modules/'), $exceptions = NULL) {
  // Set default exceptions
  if (!is_array($exceptions)) {
    $exceptions = array(
      'modules/system/system.css',
      'modules/update/update.css',
      'modules/openid/openid.css',
    );
  }
  $css = drupal_add_css();
  foreach (array_keys($css['all']['module']) as $filename) {
    foreach ($match as $prefix) {
      if (strpos($filename, $prefix) === 0 && !in_array($filename, $exceptions)) {
        unset($css['all']['module'][$filename]);
        continue;
      }
    }
  }

  // This servers to move the "all" CSS key to the front of the stack.
  // Mainly useful because modules register their CSS as 'all', while
  // Tao has a more media handling.
  ksort($css);
  return $css;
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

/**
 * Implementation of preprocess_page().
 */
function tao_preprocess_page(&$vars) {
  $attr = array();
  $attr['class'] = $vars['body_classes'];
  $attr['class'] .= ' tao'; // Add the tao class so that we can avoid using the 'body' selector

  // Replace screen/all stylesheets with print
  // We want a minimal print representation here for full control.
  if (isset($_GET['print'])) {
    $css = tao_css_stripped();
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
  }
  // Get minimalized CSS
  else {
    $vars['styles'] = drupal_get_css(tao_css_stripped());
  }

  // Split primary and secondary local tasks
  $vars['tabs'] = theme('menu_local_tasks', 'primary');
  $vars['tabs2'] = theme('menu_local_tasks', 'secondary');

  // Use the logo if it exists
  $vars['logo'] = file_exists($vars['logo']) ? l(theme('image', $logo), '<front>', array('attributes' => array('class' => 'logo'), 'html' => TRUE)) : NULL;
  $vars['site_name'] = empty($vars['logo']) ? l($vars['site_name'], '<front>') : NULL;

  // Don't render the attributes yet so subthemes can alter them
  $vars['attr'] = $attr;
}

/**
 * Implementation of preprocess_block().
 */
function tao_preprocess_block(&$vars) {
  $attr = array();
  $attr['id'] = "block-{$vars['block']->module}-{$vars['block']->delta}";
  $attr['class'] = "block block-{$vars['block']->module}";
  $vars['attr'] = $attr;

  $vars['hook'] = 'block';
  $vars['title'] = $vars['block']->subject;
  $vars['content'] = $vars['block']->content;
  $vars['is_prose'] = ($vars['block']->module == 'block') ? TRUE : FALSE;
}

/**
 * Implementation of preprocess_box().
 */
function tao_preprocess_box(&$vars) {
  $attr = array();
  $attr['class'] = "box";
  $vars['attr'] = $attr;
  $vars['hook'] = 'box';
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
  $vars['title'] = l($vars['title'], "node/{$vars['node']->nid}", array('html' => TRUE));
  $vars['is_prose'] = TRUE;

  // Add print customizations
  if (isset($_GET['print'])) {
    $vars['pre_object'] = theme('print_header');
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

  $attr = isset($element['#attributes']) ? $element['#attributes'] : array();
  $attr['class'] = !empty($attr['class']) ? $attr['class'] : '';
  $attr['class'] .= ' fieldset';
  $attr['class'] .= !empty($element['#collapsible']) || !empty($element['#collapsed']) ? ' collapsible' : '';
  $attr['class'] .= !empty($element['#collapsed']) ? ' collapsed' : '';
  $vars['attr'] = $attr;

  $description = !empty($element['#description']) ? "<div class='description'>{$element['#description']}</div>" : '';
  $children = !empty($element['#children']) ? $element['#children'] : '';
  $value = !empty($element['#value']) ? $element['#value'] : '';
  $vars['content'] = $description . $children . $value;
  $vars['title'] = !empty($element['#title']) ? $element['#title'] : '';
  if (!empty($element['#collapsible']) || !empty($element['#collapsed'])) {
    $vars['title'] = l($vars['title'], $_GET['q'], array('fragment' => 'fieldset'));
  }
  $vars['hook'] = 'fieldset';
}

/**
 * Preprocessor for theme_print_header().
 */
function tao_preprocess_print_header(&$vars) {
  static $count;
  $count = !isset($count) ? 1 : $count;
  global $base_url;
  $vars = array(
    'base_path' => base_path(),
    'theme_path' => base_path() .'/'. path_to_theme(),
    'count' => $count,
    'first' => ($count == 1) ? true : false,
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
 * Override of theme_form_element().
 * Take a more sensitive/delineative approach toward theming form elements.
 */
function tao_form_element($element, $value) {
  $output = '';

  // This is also used in the installer, pre-database setup.
  $t = get_t();

  // Add a wrapper id
  $attr = array('class' => '');
  $attr['id'] = !empty($element['#id']) ? "{$element['#id']}-wrapper" : NULL;

  // Type logic
  $label_attr = array();
  $label_attr['for'] = !empty($element['#id']) ? $element['#id'] : '';

  if (!empty($element['#type']) && in_array($element['#type'], array('checkbox', 'radio'))) {
    $label_type = 'label';
    $attr['class'] .= ' form-item form-option';
  }
  else {
    $label_type = 'label';
    $attr['class'] .= ' form-item';
  }

  // Generate required markup
  $required_title = $t('This field is required.');
  $required = !empty($element['#required']) ? "<span class='form-required' title='{$required_title}'>*</span>" : '';

  // Generate label markup
  if (!empty($element['#title'])) {
    $title = $t('!title: !required', array('!title' => filter_xss_admin($element['#title']), '!required' => $required));
    $label_attr = drupal_attributes($label_attr);
    $output .= "<{$label_type} {$label_attr}>{$title}</{$label_type}>";
    $attr['class'] .= ' form-item-labeled';
  }

  // Add child values
  $output .= "$value";

  // Description markup
  $output .= !empty($element['#description']) ? "<div class='description'>{$element['#description']}</div>" : '';

  // Render the whole thing
  $attr = drupal_attributes($attr);
  $output = "<div {$attr}>{$output}</div>";

  return $output;

}

/**
 * Override of theme_file().
 * Reduces the size of upload fields which are by default far too long.
 */
function tao_file($element) {
  _form_set_class($element, array('form-file'));
  $attr = $element['#attributes'] ? ' '. drupal_attributes($element['#attributes']) : '';
  return theme('form_element', $element, "<input type='file' name='{$element['#name']}' id='{$element['#id']}' size='15' {$attr} />");
}

/**
 * Override of theme_blocks().
 * Allows additional theme functions to be defined per region to
 * control block display on a per-region basis. Falls back to default
 * block region handling if no region-specific overrides are found.
 */
function tao_blocks($region) {
  static $list;

  $output = '';
  $list = module_exists('context') && function_exists('context_block_list') ? context_block_list($region) : block_list($region);

  // Allow theme functions some additional control over regions
  if ($list) {
    $registry = theme_get_registry();
    if (isset($registry['blocks_'. $region])) {
      $output .= theme('blocks_'. $region, $list);
    }
    else {
      foreach ($list as $key => $block) {
        $output .= theme("block", $block);
      }
      $output .= drupal_get_content($region);
    }
    return $output;
  }
  return '';
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
function tao_pager($tags = array(), $limit = 10, $element = 0, $parameters = array(), $quantity = 9) {
  $pager_list = theme('pager_list', $tags, $limit, $element, $parameters, $quantity);

  $links = array();
  $links['pager-first'] = theme('pager_first', ($tags[0] ? $tags[0] : t('First')), $limit, $element, $parameters);
  $links['pager-previous'] = theme('pager_previous', ($tags[1] ? $tags[1] : t('Prev')), $limit, $element, 1, $parameters);
  $links['pager-next'] = theme('pager_next', ($tags[3] ? $tags[3] : t('Next')), $limit, $element, 1, $parameters);
  $links['pager-last'] = theme('pager_last', ($tags[4] ? $tags[4] : t('Last')), $limit, $element, $parameters);
  $pager_links = theme('links', $links, array('class' => 'links pager pager-links'));

  if ($pager_list) {
    return "<div class='pager clear-block'>$pager_list $pager_links</div>";
  }
}

/**
 * Split out page list generation into its own function.
 */
function tao_pager_list($tags = array(), $limit = 10, $element = 0, $parameters = array(), $quantity = 9) {
  global $pager_page_array, $pager_total, $theme_key;
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
          $links["$i pager-item"] = theme('pager_previous', $i, $limit, $element, ($pager_current - $i), $parameters);
        }
        if ($i == $pager_current) {
          $links["$i pager-current"] = array('title' => $i);
        }
        if ($i > $pager_current) {
          $links["$i pager-item"] = theme('pager_next', $i, $limit, $element, ($i - $pager_current), $parameters);
        }
      }
      return theme('links', $links, array('class' => 'links pager pager-list'));
    }
  }
  return '';
}

/**
 * Return an array suitable for theme_links() rather than marked up HTML link.
 */
function tao_pager_link($text, $page_new, $element, $parameters = array(), $attributes = array()) {
  $page = isset($_GET['page']) ? $_GET['page'] : '';
  if ($new_page = implode(',', pager_load_array($page_new[$element], $element, explode(',', $page)))) {
    $parameters['page'] = $new_page;
  }

  $query = array();
  if (count($parameters)) {
    $query[] = drupal_query_string_encode($parameters, array());
  }
  $querystring = pager_get_querystring();
  if ($querystring != '') {
    $query[] = $querystring;
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
