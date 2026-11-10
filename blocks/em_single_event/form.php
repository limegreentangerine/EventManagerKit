<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<fieldset>
    <div class="form-group mb-3">
        <?php echo $form->label('title', t('Title')); ?>
        <?php echo $form->text('title', $title ?? ''); ?>
    </div>
    <div class="form-group mb-3">
        <?php echo $form->label('eventID', t('Event')); ?>
        <?php echo $form->select('eventID', $events ?? [], $eventID ?? '', ['required' => 'required']); ?>
    </div>
</fieldset>
