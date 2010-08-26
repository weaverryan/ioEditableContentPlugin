var formServiceUrl = '<?php echo url_for('@editable_content_service_form') ?>';
var showServiceUrl = '<?php echo url_for('@editable_content_service_show') ?>';
var deleteServiceUrl = '<?php echo url_for('@editable_content_service_delete') ?>';
var sortableServiceurl = '<?php echo url_for('@editable_content_service_list_sort') ?>';

$(document).ready(function(){
  initializeEditableContent('body');
});

function initializeEditableContent(wrapper)
{
  wrapper = $(wrapper);
  
  initializeEditableContentArea(wrapper);
  initializeEditableContentList(wrapper);
}

function initializeEditableContentArea(wrapper)
{
  wrapper = $(wrapper);
  
  // initialize each editable content area
  $('.<?php echo $editableClassName ?>', wrapper).each(function() {
    var options = $(this).metadata();
    options.form_url = formServiceUrl;
    options.show_url = showServiceUrl;
    options.delete_url = deleteServiceUrl;

    $(this).ioEditableContent(options);
  });
}

function initializeEditableContentList(wrapper)
{
  wrapper = $(wrapper);
  
  // initialize each editable content list
  $('.<?php echo $editableListClassName ?>', wrapper).each(function() {
    var options = $(this).metadata();
    options.form_url = formServiceUrl;
    options.show_url = showServiceUrl;
    options.delete_url = deleteServiceUrl;
    options.sortable_url = sortableServiceurl;
    options.child_class = '<?php echo $editableClassName ?>';
    options.new_ele = $(this).find('.io_new_tag').eq(0);

    $(this).ioEditableContentList(options);
  });
}