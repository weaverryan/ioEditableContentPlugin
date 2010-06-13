var formServiceUrl = '<?php echo url_for('@editable_content_service_form') ?>';
var showServiceUrl = '<?php echo url_for('@editable_content_service_show') ?>';

$(document).ready(function(){

  // initialize each slot object
  $('.<?php echo $editableClassName ?>').each(function() {
    /*
    // generate the full get_url
    var get_url = getServiceUrl+'?model='+$(this).metadata().model+'&pk='+$(this).metadata().pk;

    // add the array of fields to the request
    $.each($(this).metadata().fields, function(index, value) {
      get_url = get_url+'&fields[]='+value;
    });
    */

    var options = $(this).metadata();
    options.form_url = formServiceUrl;
    options.show_url = showServiceUrl;

    $(this).ioEditableContent(options);
  });
});