<form action="<?php echo url_for('@editable_content_service_update') ?>" method="post" class="editable_content_form">

  <input type="hidden" name="model" value="<?php echo $model ?>" />
  <input type="hidden" name="pk" value="<?php echo $pk ?>" />
  <input type="hidden" name="form" value="<?php echo $formClass ?>" />
  <input type="hidden" name="form_partial" value="<?php echo $formPartial ?>" />
  <?php foreach ($fields as $field): ?>
    <input type="hidden" name="fields[]" value="<?php echo $field ?>" />
  <?php endforeach; ?>
  <input type="hidden" name="partial" value="<?php echo $partial ?>" />

  <?php echo $form->renderHiddenFields() ?>

  <div class="form_body">
    <?php echo $form->renderGlobalErrors() ?>
    <?php include_partial($formPartial, array('form' => $form)); ?>
  </div>

  <input type="button" class="cancel" value="cancel" />
  <input type="submit" value="save" />
</form>