<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<fieldset>
    <div class="form-group mb-3">
        <?php echo $form->label('title', t('Title')); ?>
        <?php echo $form->text('title', $title ?? ''); ?>
    </div>
    <div class="form-group mb-3">
        <?php echo $form->label('numEvents', t('Number of events')); ?>
        <?php echo $form->number('numEvents', $numEvents ?? 5, ['min' => 1, 'max' => 50]); ?>
    </div>
</fieldset>
