<?php if(should_show_io_editor()): ?>
  <script type="text/javascript">
    <?php if($sortable): ?>
      $(function() {
        $("#<?php echo $sortable ?>").sortable({
          items: '.<?php echo get_editable_content_service()->getOption('editable_class_name', 'io_editable_content') ?>',
          update: function() {
            var data = $(this).sortable('serialize');
            $.post(
              "<?php echo url_for('editable_content_service_list_sort') ?>?"+data,
              {
                'class': '<?php echo $class ?>'
              }
            );
          }
        });
        $(this).disableSelection();
      });
    <?php endif; ?>
  </script>
<?php endif; ?>