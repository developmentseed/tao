// $Id$

Drupal.behaviors.tao = function (context) {
  $('div.fieldset:not(.tao-processed)').each(function() {
    $(this).addClass('tao-processed');
    if ($(this).is('.collapsible')) {
      if ($('input.error, textarea.error, select.error', this).size() > 0) {
        $(this).removeClass('collapsed');
      }
      // Note that .children() only returns the immediate ancestors rather than
      // recursing down all children.
      $(this).children('.fieldset-title').click(function() {
        if ($(this).parent().is('.collapsed')) {
          $(this).siblings('.fieldset-content').show();
          $(this).parent().removeClass('collapsed');
        }
        else {
          $(this).siblings('.fieldset-content').hide();
          $(this).parent().addClass('collapsed');
        }
        return false;
      });
    }
  });
}
