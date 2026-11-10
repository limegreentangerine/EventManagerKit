<?php defined('C5_EXECUTE') or die('Access Denied.');
$id = $entity ? $entity->getID() : ($formContent['id'] ?? '');
$posted = (array) ($formContent['name'] ?? []);
$selectedParent = (string) ($formContent['parent'] ?? ($entity && method_exists($entity, 'getParent') && $entity->getParent() ? $entity->getParent()->getID() : ''));

if ($entity) {
    View::element('dashboard/named/delete', ['token' => $token, 'basePath' => $basePath, 'id' => $id, 'label' => $label, 'view' => $view], 'event_manager_kit');
}
?>

<form method="post" action="<?php echo h($view->action('save')); ?>">
    <?php echo $token->output('submit'); ?>
    <?php echo $id ? $form->hidden('id', $id) : ''; ?>

    <fieldset>
        <legend><?php echo t('Names'); ?></legend>
        <?php foreach ($locales as $locale) {
            $value = $posted[$locale] ?? ($entity ? (($row = array_values(array_filter($entity->getNames()->toArray(), fn($n) => $n->getLocale() === $locale))[0] ?? null) ? $row->getName() : '') : '');
            $required = $locale === $defaultLocale;
            ?>
            <div class="form-group mb-3">
                <?php echo $form->label('name_' . $locale, h($locale)); ?>
                <?php if ($required) { ?><span class="text-muted small float-end"><?php echo t('Required'); ?></span><?php } ?>
                <?php echo $form->text('name[' . $locale . ']', $value, ['id' => 'name_' . $locale]); ?>
            </div>
        <?php } ?>
    </fieldset>

    <?php if (!empty($parents)) { ?>
        <fieldset>
            <div class="form-group mb-3">
                <?php echo $form->label('parent', t('Parent location')); ?>
                <?php echo $form->select('parent', $parents, $selectedParent); ?>
            </div>
        </fieldset>
    <?php } ?>

    <?php if (!empty($place)) { ?>
        <fieldset>
            <legend><?php echo t('Place'); ?></legend>
            <div class="form-group mb-3">
                <?php echo $form->label('address', t('Address')); ?>
                <?php echo $form->text('address', $place['address'], ['maxlength' => 500]); ?>
            </div>
            <div class="row">
                <div class="col-md-6 form-group mb-3">
                    <?php echo $form->label('latitude', t('Latitude')); ?>
                    <?php echo $form->text('latitude', $place['latitude'], ['placeholder' => '51.5072']); ?>
                </div>
                <div class="col-md-6 form-group mb-3">
                    <?php echo $form->label('longitude', t('Longitude')); ?>
                    <?php echo $form->text('longitude', $place['longitude'], ['placeholder' => '-0.1276']); ?>
                </div>
            </div>
            <p class="text-muted small"><?php echo t('Leave the coordinates blank to look them up from the address (needs the Mapbox package and API key). Clear them and save to look up again.'); ?></p>
            <div class="form-group mb-3">
                <?php echo $form->label('onlineUrl', t('Online event URL')); ?>
                <?php echo $form->text('onlineUrl', $place['onlineUrl'], ['placeholder' => 'https://']); ?>
            </div>
        </fieldset>
    <?php } ?>

    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <a href="<?php echo h(URL::to($basePath)); ?>" class="btn btn-secondary float-start"><?php echo t('Cancel'); ?></a>
            <?php echo $form->submit('save', $entity ? t('Update %s', $label) : t('Save %s', $label), ['class' => 'btn btn-primary float-end']); ?>
        </div>
    </div>
</form>
