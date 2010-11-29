<?php
  // prepare the non-hidden field list.
  // If there is only one field being, hide its label so it looks more natural 
  $renderableFields = array();
  foreach ($form as $key => $formField)
  {
    if (!$formField->isHidden())
    {
      $renderableFields[] = $key;
    }
  }
?>

<?php if (count($renderableFields) == 1): ?>
  <?php if(sfConfig::get('app_editable_content_single_field_label')): ?>
    <?php echo $form[$renderableFields[0]]->renderLabel() ?>
    <br />
  <?php endif; ?>
  
  <?php echo $form[$renderableFields[0]]->renderError() ?>
  <?php echo $form[$renderableFields[0]]->render() ?>
<?php else: ?>
  <table>
    <?php foreach ($renderableFields as $field): ?>
      <tr>
        <td>
          
          <?php echo $form[$field]->renderLabel() ?>
          
          <?php if ($form[$field]->hasError()): ?>
            <br/>
            <?php echo $form[$field]->renderError() ?>
          <?php endif; ?>
          
          <br/>
          
          <?php echo $form[$field]->render() ?>
          
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>