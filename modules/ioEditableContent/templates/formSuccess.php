<?php include_stylesheets_for_form($form) ?>
<?php include_javascripts_for_form($form) ?>

<?php echo $form->renderFormTag(url_for('@editable_content_service_update'), array('class' => 'editable_content_form')) ?>

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

  <div class="form_body">
    <?php echo $form->renderGlobalErrors() ?>
    <?php include_partial($formPartial, array('form' => $form)); ?>
  </div>

  <input type="button" class="cancel" value="cancel" />
  <input type="submit" value="save" />
  <input type="button" class="done" value="done" />
</form>