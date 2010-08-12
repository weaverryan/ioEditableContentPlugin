<?php $inner_options = ($inner_options instanceof sfOutputEscaper) ? $inner_options->getRawValue() : $inner_options ?>
<?php $fields = ($fields instanceof sfOutputEscaper) ? $fields->getRawValue() : $fields ?>

<<?php echo $outer_tag ?> class="editable_content_list">
  <?php foreach ($collection as $obj): ?>
    <?php $inner_options['id'] = 'item_'.$obj->id ?>
    <?php echo editable_content_tag($inner_tag, $obj, $fields, $inner_options) ?>
  <?php endforeach; ?>
</<?php echo $outer_tag ?>>

<?php if ($with_new): ?>
  <?php echo editable_content_tag($inner_tag, new $class(), $fields, $inner_options) ?>
<?php endif; ?>

<?php if($sortable): ?>
  <script type="text/javascript">
    $(function() {
      $(".editable_content_list").sortable({
        items: '<?php echo $inner_tag ?>',
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
  </script>
<?php endif; ?>