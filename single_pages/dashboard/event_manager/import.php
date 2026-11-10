<?php defined('C5_EXECUTE') or die('Access Denied.');
$first = $locales[0] ?? 'en_GB';
$second = $locales[1] ?? null;
?>

<form method="post" enctype="multipart/form-data" action="<?php echo h($view->action('run_import')); ?>">
    <?php echo $token->output('em-import'); ?>

    <fieldset>
        <legend><?php echo t('Import events from CSV'); ?></legend>

        <div class="form-group mb-3">
            <label class="form-label" for="csv_file"><?php echo t('CSV file'); ?></label>
            <input type="file" name="csv_file" id="csv_file" accept=".csv,text/csv" class="form-control" required>
        </div>

        <div class="form-check mb-3">
            <input type="checkbox" class="form-check-input" name="validate_only" id="validate_only" value="1">
            <label class="form-check-label" for="validate_only"><?php echo t('Check the file only, do not import'); ?></label>
        </div>
    </fieldset>

    <?php if (!empty($errors)) { ?>
        <div class="alert alert-danger">
            <strong><?php echo t('%d problem(s) found:', count($errors)); ?></strong>
            <ul class="mb-0">
                <?php foreach ($errors as $error) { ?>
                    <li><?php echo t('Line %d: %s', $error['line'], h($error['message'])); ?></li>
                <?php } ?>
            </ul>
        </div>
    <?php } ?>

    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <a href="<?php echo h(URL::to('/dashboard/event_manager/events')); ?>" class="btn btn-secondary float-start"><?php echo t('Cancel'); ?></a>
            <button type="submit" class="btn btn-primary float-end"><?php echo t('Upload'); ?></button>
        </div>
    </div>
</form>

<hr>
<h5><?php echo t('File format'); ?></h5>
<p><?php echo t('The first row must be a header. Columns can be in any order. Locales available on this site: %s.', h(implode(', ', $locales))); ?></p>
<table class="table table-sm">
    <tr><td><code>start</code>, <code>end</code></td><td><?php echo t('Required. YYYY-MM-DD HH:MM or DD/MM/YYYY HH:MM. Dates without a time make an all-day event.'); ?></td></tr>
    <tr><td><code>all_day</code></td><td><?php echo t('Optional. 1, yes or true.'); ?></td></tr>
    <tr><td><code>name_<?php echo h($first); ?></code></td><td><?php echo t('Required in at least one locale: one name column per locale.'); ?></td></tr>
    <tr><td><code>description_<?php echo h($first); ?></code></td><td><?php echo t('Optional.'); ?></td></tr>
    <tr><td><code>location_<?php echo h($first); ?></code>, <code>sub_location_<?php echo h($first); ?></code></td><td><?php echo t('Optional. A sub location needs a location.'); ?></td></tr>
    <tr><td><code>type_<?php echo h($first); ?></code></td><td><?php echo t('Optional event type.'); ?></td></tr>
    <tr><td><code>tags_<?php echo h($first); ?></code></td><td><?php echo t('Optional, separated by semicolons. Every locale must list the same number of tags, in the same order.'); ?></td></tr>
</table>
<p class="text-muted"><?php echo t('Example header: %s', h('start,end,name_' . $first . ($second ? ',name_' . $second : '') . ',location_' . $first . ',type_' . $first . ',tags_' . $first)); ?></p>
<p class="text-muted"><?php echo t('Re-uploading a file will not duplicate events that were already imported (same start and name in the default language).'); ?></p>
