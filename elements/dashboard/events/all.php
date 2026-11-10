<?php defined('C5_EXECUTE') or die('Access Denied.'); ?>

<div class="ccm-dashboard-content-full">
    <form method="get" action="<?php echo h(View::url($basePath)); ?>" class="row row-cols-auto g-2 p-3">
        <div class="col">
            <input type="search" name="name" class="form-control" value="<?php echo h($params['name'] ?? ''); ?>" placeholder="<?php echo h(t('Search by name')); ?>">
        </div>
        <div class="col">
            <?php echo $form->select('type', ['' => t('All types')] + $typeOptions, $params['type'] ?? ''); ?>
        </div>
        <div class="col">
            <?php echo $form->select('location', ['' => t('All locations')] + $locationOptions, $params['location'] ?? ''); ?>
        </div>
        <div class="col"><button type="submit" class="btn btn-secondary"><?php echo t('Search'); ?></button></div>
    </form>

    <?php if (empty($items)) { ?>
        <div class="alert alert-warning m-3"><?php echo t('No events found.'); ?></div>
    <?php } else { ?>
        <div class="table-responsive">
            <table class="ccm-search-results-table">
                <thead>
                    <tr>
                        <?php foreach ($result->getColumns() as $column) { ?>
                            <?php if ($column->isColumnSortable()) { ?>
                                <th class="<?php echo $column->getColumnStyleClass(); ?>">
                                    <a href="<?php echo $column->getColumnSortURL(); ?>"><?php echo h($column->getColumnTitle()); ?></a>
                                </th>
                            <?php } else { ?>
                                <th><span><?php echo h($column->getColumnTitle()); ?></span></th>
                            <?php } ?>
                        <?php } ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item) { ?>
                        <tr data-details-url="<?php echo h($item->getViewUrl()); ?>">
                            <?php foreach ($item->getColumns() as $column) { ?>
                                <td><?php echo nl2br(h($column->getColumnValue())); ?></td>
                            <?php } ?>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <?php if ($pagination) { ?>
            <div class="ccm-search-results-pagination"><?php echo $pagination; ?></div>
        <?php } ?>
    <?php } ?>
</div>

<div class="ccm-dashboard-header-buttons">
    <a href="<?php echo h(View::url('/dashboard/event_manager/import')); ?>" class="btn btn-secondary"><?php echo t('Import CSV'); ?></a>
    <a href="<?php echo h(View::url($basePath . '/add')); ?>" class="btn btn-primary"><?php echo t('Add Event'); ?></a>
</div>

<script>
$(function () {
    $('tr[data-details-url]').css('cursor', 'pointer').on('click', function () {
        window.location.href = $(this).data('details-url');
    });
});
</script>
