<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<fieldset>
    <div class="form-group mb-3">
        <?php echo $form->label('title', t('Title')); ?>
        <?php echo $form->text('title', $title ?? ''); ?>
    </div>
    <div class="form-check mb-3">
        <?php echo $form->checkbox('showFilters', '1', !empty($showFilters)); ?>
        <?php echo $form->label('showFilters', t('Show type, location and tag filters'), ['class' => 'form-check-label']); ?>
    </div>
</fieldset>
