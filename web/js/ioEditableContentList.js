/**
 * When applied to an element, this makes that element into a special
 * container/list of ioEditableContent areas, adding functionality such
 * as add, delete, and reorder.
 *
 * This requires a number of options to be passed in:
 *   * form_url  The base url to render and form
 *   * show_url  The base url for rendering an element
 *   * delete_url The base url for deleting an element
 *   * new_ele   A jQuery element that represents a "blank" entry for
 *               on of the entries in this list (used for create).
 *
 * Optional options
 *   * with_new    Whether to allow the addition of new element [default=false]
 *   * with_delete Whether to allow for deleting of items
 *   * add_new_label Label to be used for the "new" url [default=Add another]
 *
 * Due to the many options, this is not usually called directly, but is
 * instead handled by the jsSuccess.js.php file, which applies this to
 * elements output using the special editable_content_tag() PHP helper.
 */
(function($) {

$.widget('ui.ioEditableContentList', {

  options: {
    mode:           'fancybox',
    add_new_label:  'Add another',
    with_new:       false,
    with_delete:    false
  },

  _create: function() {
    this.initialize();
  },

  initialize: function() {
    var self = this;

    if (self.option('with_new'))
    {
      var add_new = self._getClonedBlankItem();
      add_new.html('<a class="add_new" href="#">'+self.option('add_new_label')+'</a>');
      add_new.bind('click', function() {
        self.addNewTag();

        return false;
      });

      self.element.append(add_new);
      self._setOption('add_new_element', add_new);
    }
  },

  addNewTag: function() {
    /**
     * Adds a new entry to this list and opens up its editor
     */
    var self = this;

    // clone the new element and set it up with ioEditableContent
    var newEle = self._getClonedBlankItem();
    var options = newEle.metadata();
    options.form_url = self.option('form_url');
    options.show_url = self.option('show_url');
    options.delete_url = self.option('delete_url');
    newEle.ioEditableContent(options);

    // bind to the "closeEditor" event so we can remove the new entry if
    // the user doesn't actually persist it (just adds new then closes)
    newEle.bind('closeEditor', function() {
      var pk = $(this).ioEditableContent('option', 'pk');

      // we don't have a pk (it wasn't saved), so kill the new element
      if (!pk)
      {
        $(this).ioEditableContent('destroy');
        $(this).remove();
      }
    });

    // add the new entry right before the "add new" button and open its editor
    self._getAddNewElement().before(newEle);
    newEle.ioEditableContent('openEditor');
  },

  _getAddNewElement: function() {
    /*
     * Returns the "add new" link element
     */

    return this.option('add_new_element');
  },

  _getClonedBlankItem: function () {
    /**
     * Helper to return a cloned version 
     */
    var self = this;

    var newEle = self.option('new_ele').clone();
    newEle.removeClass('io_new_tag');

    return newEle;
  },

  blockUI: function() {
    if ($.blockUI)
    {
      $.blockUI();
    }
  },

  unblockUI: function() {
    if ($.blockUI)
    {
      $.unblockUI();
    }
  }

});

})(jQuery);