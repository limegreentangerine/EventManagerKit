<?php defined('C5_EXECUTE') or die('Access Denied.');
/** @var array $events @var string $locale */
?>
<?php if (empty($events)) { ?>
    <p class="em-events__empty"><?php echo t('There are no upcoming events.'); ?></p>
<?php } else { ?>
    <ul class="em-events__items">
        <?php foreach ($events as $event) { ?>
            <?php View::element('frontend/event', ['event' => $event, 'locale' => $locale], 'event_manager_kit'); ?>
        <?php } ?>
    </ul>
<?php } ?>
