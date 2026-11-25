<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<form method="post" action="<?php echo h($view->action('save')); ?>">
    <?php echo $token->output('em-settings'); ?>

    <fieldset>
        <legend><?php echo t('Activities'); ?></legend>
        <div class="form-check mb-2">
            <?php echo $form->checkbox('activities', '1', $activitiesEnabled, ['id' => 'activities']); ?>
            <?php echo $form->label('activities', t('Enable activities'), ['class' => 'form-check-label']); ?>
        </div>
        <div class="form-text"><?php echo t('Adds age groups, price and a short summary to events, an Age Groups page, and age, price and "events or activities" options to the blocks. Turning it off hides these again; nothing is deleted.'); ?></div>
    </fieldset>

    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <?php echo $form->submit('save', t('Save'), ['class' => 'btn btn-primary float-end']); ?>
        </div>
    </div>
</form>
