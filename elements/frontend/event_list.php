<?php defined('C5_EXECUTE') or die('Access Denied.');
/** @var list<\EventManagerKit\Recurrence\Occurrence> $occurrences @var string $locale */
?>
<?php if (empty($occurrences)) { ?>
    <p class="em-events__empty"><?php echo t('There are no upcoming events.'); ?></p>
<?php } else { ?>
    <ul class="em-events__items">
        <?php foreach ($occurrences as $occurrence) { ?>
            <?php View::element('frontend/event', ['event' => $occurrence->event, 'occurrence' => $occurrence, 'locale' => $locale], 'event_manager_kit'); ?>
        <?php } ?>
    </ul>
<?php } ?>
