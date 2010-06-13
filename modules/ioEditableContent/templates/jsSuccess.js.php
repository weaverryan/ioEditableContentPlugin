var formServiceUrl = '<?php echo url_for('@editable_content_service_form') ?>';
var showServiceUrl = '<?php echo url_for('@editable_content_service_show') ?>';

$(document).ready(function(){

  // initialize each editable content area
  $('.<?php echo $editableClassName ?>').each(function() {
    var options = $(this).metadata();
    options.form_url = formServiceUrl;
    options.show_url = showServiceUrl;

    $(this).ioEditableContent(options);
  });
});