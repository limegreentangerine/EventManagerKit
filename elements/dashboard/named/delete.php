<?php defined('C5_EXECUTE') or die('Access Denied.');
/** @var string $basePath @var string $id @var string $label */
?>
<div class="ccm-dashboard-header-buttons">
    <button data-launch-dialog="delete-dialog" class="btn btn-danger"><?php echo t('Delete'); ?></button>
</div>

<div style="display: none" data-dialog="delete-dialog" class="ccm-ui">
    <form data-dialog-form="delete-form" method="POST" action="<?php echo h($view->action('delete', $id)); ?>">
        <?php echo $token->output('delete-' . $basePath . '-' . $id); ?>
        <p><?php echo t('Are you sure you want to permanently delete this %s?', strtolower($label)); ?></p>
        <p><strong><?php echo t('WARNING: this operation can not be undone!'); ?></strong></p>
    </form>
    <div class="dialog-buttons">
        <button class="btn btn-secondary float-start" data-dialog-action="cancel"><?php echo t('Cancel'); ?></button>
        <button class="btn btn-danger float-end" data-dialog-action="submit"><?php echo t('Delete'); ?></button>
    </div>
</div>

<script>
$(function () {
    var $dialog = $('div[data-dialog="delete-dialog"]');
    $('[data-launch-dialog="delete-dialog"]').on('click', function (e) {
        e.preventDefault();
        jQuery.fn.dialog.open({element: $dialog, modal: true, width: 420, height: 'auto', title: <?php echo json_encode(t('Confirm Delete')); ?>});
    });
    ConcreteEvent.subscribe('AjaxFormSubmitSuccess', function (e, data) {
        if (data.form === 'delete-form') {
            window.location.href = <?php echo json_encode((string) URL::to($basePath)); ?>;
        }
    });
});
</script>
