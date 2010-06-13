<table>
  <?php foreach ($form as $key => $formField): ?>
    <?php if (!$formField->isHidden()): ?>
      <?php echo $formField->renderRow() ?>
    <?php endif; ?>
  <?php endforeach; ?>
</table>