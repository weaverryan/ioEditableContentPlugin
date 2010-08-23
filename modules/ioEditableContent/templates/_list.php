<?php $inner_options = ($inner_options instanceof sfOutputEscaper) ? $inner_options->getRawValue() : $inner_options ?>
<?php $fields = ($fields instanceof sfOutputEscaper) ? $fields->getRawValue() : $fields ?>
<?php $new_options = $inner_options ?>
<?php
  if (isset($new_options['class']))
  {
    $new_options['class'] = $new_options['class'] .' io_new_tag';
  }
  else
  {
    $new_options['class'] = 'io_new_tag';
  }
?>

<?php
  if (should_show_io_editor() && $sortable)
  {
    $attributes['id'] = $sortable;
  }
?>

<<?php echo $outer_tag ?><?php echo _tag_options($attributes) ?>>
  <?php $inner_options['class'] = (isset($inner_options['class'])) ? $inner_options['class'].' editable_content_list_item' : 'editable_content_list_item'; ?>
  <?php foreach ($collection as $obj): ?>
    <?php $inner_options['id'] = 'item_'.$obj->id ?>
    <?php if(should_show_io_editor() && $with_delete): ?>
      <a class="editable_content_list_delete"
      rel="<?php echo $inner_options['id'] ?>"
      href="<?php echo url_for('editable_content_service_list_delete', array('id' => $obj->id, 'class' => $class)) ?>">
          delete
      </a>
    <?php endif; ?>
    <?php echo editable_content_tag($inner_tag, $obj, $fields, $inner_options) ?>
  <?php endforeach; ?>

  <?php if (should_show_io_editor() && $with_new): ?>
    <?php echo editable_content_tag($inner_tag, $new, $fields, $new_options) ?>
  <?php endif; ?>
</<?php echo $outer_tag ?>>

<?php if(should_show_io_editor()): ?>
  <script type="text/javascript">
    <?php if($sortable): ?>
      $(function() {
        $("#<?php echo $sortable ?>").sortable({
          items: '.editable_content_list_item',
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