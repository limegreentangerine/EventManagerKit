<?php defined('C5_EXECUTE') or die('Access Denied.');
$p = (array) $formContent;
$id = $entity ? $entity->getID() : ($p['id'] ?? '');
$allDay = $formContent ? !empty($p['allDay']) : ($entity && $entity->isAllDay());
$published = $formContent ? !empty($p['published']) : ($entity ? $entity->isPublished() : true);
$dateFormat = $allDay ? 'Y-m-d' : 'Y-m-d\TH:i';
$start = $p['startDate'] ?? ($entity ? $entity->getStartDate()->format($dateFormat) : '');
$end = $p['endDate'] ?? ($entity ? $entity->getEndDate()->format($dateFormat) : '');
$selectedLocation = (string) ($p['location'] ?? ($entity && $entity->getLocation() ? $entity->getLocation()->getID() : ''));
$selectedType = (string) ($p['type'] ?? ($entity && $entity->getType() ? $entity->getType()->getID() : ''));
$selectedTags = $formContent ? (array) ($p['tags'] ?? []) : ($entity ? array_map(fn($t) => $t->getID(), $entity->getTags()->toArray()) : []);

$fm = app(\Concrete\Core\Application\Service\FileManager::class);
$ps = app(\Concrete\Core\Form\Service\Widget\PageSelector::class);
$fileOf = fn($key, $getter) => ($fid = (int) ($p[$key] ?? ($entity ? $entity->$getter() : 0))) ? \Concrete\Core\File\File::getByID($fid) : null;
$linkType = (string) ($p['linkType'] ?? ($entity ? $entity->getLinkType() : ''));
$linkValue = fn($type) => (string) ($p['link' . ucfirst($type)] ?? ($entity && $entity->getLinkType() === $type ? $entity->getLinkValue() : ''));

if ($entity) {
    View::element('dashboard/named/delete', ['token' => $token, 'basePath' => $basePath, 'id' => $id, 'label' => t('Event'), 'view' => $view], 'event_manager_kit');
}
?>

<form method="post" action="<?php echo h($view->action('save')); ?>">
    <?php echo $token->output('submit'); ?>
    <?php echo $id ? $form->hidden('id', $id) : ''; ?>

    <div class="form-check mb-4">
        <?php echo $form->checkbox('published', '1', $published, ['id' => 'published']); ?>
        <?php echo $form->label('published', t('Published (visible on the website)'), ['class' => 'form-check-label']); ?>
    </div>

    <fieldset>
        <legend><?php echo t('Content'); ?></legend>
        <?php foreach ($locales as $locale) {
            $content = $entity ? $entity->getContents()->filter(fn($c) => $c->getLocale() === $locale)->first() : null;
            $name = $p['name'][$locale] ?? ($content ? $content->getName() : '');
            $description = $p['description'][$locale] ?? ($content ? (string) $content->getDescription() : '');
            $slug = $p['slug'][$locale] ?? ($content ? (string) $content->getSlug() : '');
            ?>
            <div class="mb-4">
                <h6><?php echo h($locale); ?><?php if ($locale === $defaultLocale) { ?> <span class="text-muted small"><?php echo t('(default, required)'); ?></span><?php } ?></h6>
                <div class="form-group mb-2">
                    <?php echo $form->label('name_' . $locale, t('Name')); ?>
                    <?php echo $form->text('name[' . $locale . ']', $name, ['id' => 'name_' . $locale]); ?>
                </div>
                <div class="form-group mb-2">
                    <?php echo $form->label('slug_' . $locale, t('URL slug')); ?>
                    <?php echo $form->text('slug[' . $locale . ']', $slug, ['id' => 'slug_' . $locale, 'placeholder' => t('Generated from the name if left blank')]); ?>
                </div>
                <div class="form-group">
                    <?php echo $form->label('description_' . $locale, t('Description')); ?>
                    <?php echo $form->textarea('description[' . $locale . ']', $description, ['id' => 'description_' . $locale, 'rows' => 4]); ?>
                </div>
            </div>
        <?php } ?>
    </fieldset>

    <fieldset>
        <legend><?php echo t('When'); ?></legend>
        <div class="form-check mb-3">
            <?php echo $form->checkbox('allDay', '1', $allDay, ['id' => 'allDay']); ?>
            <?php echo $form->label('allDay', t('All day event'), ['class' => 'form-check-label']); ?>
        </div>
        <div class="row">
            <div class="col-md-6 form-group">
                <?php echo $form->label('startDate', t('Start')); ?>
                <input type="<?php echo $allDay ? 'date' : 'datetime-local'; ?>" id="startDate" name="startDate" class="form-control" value="<?php echo h($start); ?>" required>
            </div>
            <div class="col-md-6 form-group">
                <?php echo $form->label('endDate', t('End')); ?>
                <input type="<?php echo $allDay ? 'date' : 'datetime-local'; ?>" id="endDate" name="endDate" class="form-control" value="<?php echo h($end); ?>" required>
            </div>
        </div>
    </fieldset>

    <fieldset class="mt-4">
        <legend><?php echo t('Details'); ?></legend>
        <div class="form-group mb-3">
            <div class="d-flex justify-content-between align-items-baseline">
                <?php echo $form->label('location', t('Location')); ?>
                <button type="button" class="btn btn-secondary btn-sm" data-new="locations" data-target="#location"><?php echo t('+ New'); ?></button>
            </div>
            <?php echo $form->select('location', $locationOptions, $selectedLocation); ?>
        </div>
        <div class="form-group mb-3">
            <div class="d-flex justify-content-between align-items-baseline">
                <?php echo $form->label('type', t('Event Type')); ?>
                <button type="button" class="btn btn-secondary btn-sm" data-new="event_types" data-target="#type"><?php echo t('+ New'); ?></button>
            </div>
            <?php echo $form->select('type', $typeOptions, $selectedType); ?>
        </div>
        <div class="form-group mb-3">
            <div class="d-flex justify-content-between align-items-baseline">
                <?php echo $form->label('tags', t('Tags')); ?>
                <button type="button" class="btn btn-secondary btn-sm" data-new="event_tags" data-target="#tags"><?php echo t('+ New'); ?></button>
            </div>
            <?php echo $form->selectMultiple('tags', $tagOptions, $selectedTags); ?>
        </div>
    </fieldset>

    <fieldset class="mt-4">
        <legend><?php echo t('Media'); ?></legend>
        <div class="form-group mb-3">
            <?php echo $form->label('imageID', t('Image')); ?>
            <?php echo $fm->image('imageID', 'imageID', t('Choose Image'), $fileOf('imageID', 'getImageID')); ?>
        </div>
        <div class="form-group mb-3">
            <?php echo $form->label('videoID', t('Video')); ?>
            <?php echo $fm->video('videoID', 'videoID', t('Choose Video'), $fileOf('videoID', 'getVideoID')); ?>
        </div>
        <div class="form-group mb-3" id="poster-group">
            <?php echo $form->label('videoPosterID', t('Video poster image')); ?>
            <?php echo $fm->image('videoPosterID', 'videoPosterID', t('Choose Poster'), $fileOf('videoPosterID', 'getVideoPosterID')); ?>
            <div class="form-text"><?php echo t('Shown before the video plays. Ignored if there is no video.'); ?></div>
        </div>
    </fieldset>

    <fieldset class="mt-4">
        <legend><?php echo t('Link'); ?></legend>
        <div class="form-group mb-3">
            <?php echo $form->label('linkType', t('Link to')); ?>
            <?php echo $form->select('linkType', ['' => t('(none)'), 'page' => t('A page'), 'url' => t('External URL'), 'email' => t('Email address'), 'tel' => t('Telephone number')], $linkType); ?>
            <div class="form-text"><?php echo t('For example, where to buy tickets.'); ?></div>
        </div>
        <div class="form-group mb-3" data-link="page"><?php echo $ps->selectPage('linkPage', (int) $linkValue('page') ?: false); ?></div>
        <div class="form-group mb-3" data-link="url"><?php echo $form->text('linkUrl', $linkValue('url'), ['placeholder' => 'https://']); ?></div>
        <div class="form-group mb-3" data-link="email"><?php echo $form->text('linkEmail', $linkValue('email'), ['type' => 'email']); ?></div>
        <div class="form-group mb-3" data-link="tel"><?php echo $form->text('linkTel', $linkValue('tel'), ['type' => 'tel']); ?></div>
    </fieldset>

    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <a href="<?php echo h(URL::to($basePath)); ?>" class="btn btn-secondary float-start"><?php echo t('Cancel'); ?></a>
            <?php echo $form->submit('save', $entity ? t('Update Event') : t('Save Event'), ['class' => 'btn btn-primary float-end']); ?>
        </div>
    </div>
</form>

<script>
// Date-only inputs for all-day events; keep any date already entered.
$(function () {
    $('#allDay').on('change', function () {
        var type = this.checked ? 'date' : 'datetime-local';
        $('#startDate, #endDate').each(function () {
            var v = this.value;
            this.type = type;
            this.value = type === 'date' ? v.substring(0, 10) : (v ? v.substring(0, 10) + 'T00:00' : '');
        });
    });

    // Show only the input for the chosen link type.
    $('#linkType').on('change', function () {
        var t = this.value;
        $('[data-link]').each(function () { $(this).toggle($(this).data('link') === t); });
    }).trigger('change');

    // "+ New": show the existing add form for a location/type/tag in a dialog,
    // create it over AJAX and select it here without leaving the event.
    var base = <?php echo json_encode(rtrim((string) URL::to('/dashboard/event_manager'), '/')); ?>;
    $('[data-new]').on('click', function () {
        var path = $(this).data('new'), $target = $($(this).data('target')), url = base + '/' + path;
        $.get(url + '/add', function (html) {
            var $form = $('<div>').append($.parseHTML(html)).find('input[name^="name["]').first().closest('form');
            $form.find('.ccm-dashboard-form-actions-wrapper').remove();
            // Keep ids/labels from clashing with the event form's own name_<locale> fields.
            $form.find('[id]').each(function () { this.id = 'new_' + this.id; });
            $form.find('label[for]').each(function () { this.htmlFor = 'new_' + this.htmlFor; });
            var $dialog = $('<div class="ccm-ui">').append('<div class="alert alert-danger d-none" data-errors></div>', $form,
                '<div class="dialog-buttons"><button type="button" class="btn btn-secondary float-start" data-dialog-action="cancel"><?php echo t('Cancel'); ?></button>'
                + '<button type="button" class="btn btn-primary float-end" data-save><?php echo t('Save'); ?></button></div>');
            $dialog.find('[data-save]').on('click', function () {
                $.post(url + '/create', $form.serialize(), function (r) {
                    if (r && r.id) {
                        var opt = new Option(r.label, r.id, true, true);
                        $target.append(opt);
                        if (!$target.prop('multiple')) { $target.val(r.id); }
                        jQuery.fn.dialog.closeTop();
                    } else {
                        var $e = $dialog.find('[data-errors]').removeClass('d-none').empty();
                        $.each(r.errors || [], function (i, m) { $e.append($('<div>').text(m)); });
                    }
                }, 'json').fail(function () {
                    $dialog.find('[data-errors]').removeClass('d-none').text(<?php echo json_encode(t('Something went wrong. Please try again.')); ?>);
                });
            });
            $dialog.find('[data-dialog-action=cancel]').on('click', function () { jQuery.fn.dialog.closeTop(); });
            $form.on('submit', function (e) { e.preventDefault(); $dialog.find('[data-save]').trigger('click'); });
            jQuery.fn.dialog.open({element: $dialog, modal: true, width: 500, height: 'auto', title: $.trim($('label[for=' + $target.attr('id') + ']').text())});
        });
    });
});
</script>
