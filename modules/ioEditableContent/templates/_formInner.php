<?php if ($form->hasErrors() && $message = sfConfig::get('app_editable_content_form_error_message')): ?>
  <div class="editable_content_form_error">
    <?php echo $message ?>
  </div>
<?php endif; ?>

<?php include_stylesheets_for_form($form) ?>
<?php include_javascripts_for_form($form) ?>

<input type="hidden" name="model" value="<?php echo $model ?>" />
<input type="hidden" name="pk" value="<?php echo $pk ?>" />
<input type="hidden" name="form" value="<?php echo $formClass ?>" />
<input type="hidden" name="form_partial" value="<?php echo $formPartial ?>" />
<?php foreach ($fields as $field): ?>
  <input type="hidden" name="fields[]" value="<?php echo $field ?>" />
<?php endforeach; ?>
<?php foreach ($default_values as $key => $value): ?>
  <input type="hidden" name="default_values[<?php echo $key ?>]" value="<?php echo $value ?>" />
<?php endforeach; ?>
<input type="hidden" name="partial" value="<?php echo $partial ?>" />
<input type="hidden" name="method" value="<?php echo $method ?>" />

<?php echo $form->renderHiddenFields() ?>


<?php echo $form->renderGlobalErrors() ?>
<?php include_partial($formPartial, array('form' => $form)); ?>
