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

<table>
  <?php if (count($renderableFields) == 1): ?>
    <?php echo $form[$renderableFields[0]]->renderError() ?>
    <?php echo $form[$renderableFields[0]]->render() ?>
  <?php else: ?>
    <?php foreach ($renderableFields as $field): ?>
        <?php echo $form[$field]->renderRow() ?>
    <?php endforeach; ?>
  <?php endif; ?>
</table>