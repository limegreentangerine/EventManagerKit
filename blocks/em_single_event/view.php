<?php defined('C5_EXECUTE') or die('Access Denied.');
/**
 * Shell only: this page may be cached, so the event is fetched from the
 * endpoint by js/events.js and never baked into the page HTML.
 *
 * @var string $endpoint
 * @var string $locale
 * @var string $eventID
 * @var string $title
 */
?>

<section<?php echo !empty($title) ? ' aria-labelledby="em-title-' . (int) $bID . '"' : ''; ?> class="em-events em-events--single"
    data-em-endpoint="<?php echo h($endpoint); ?>"
    data-em-view="event"
    data-em-locale="<?php echo h($locale); ?>"
    data-em-event="<?php echo h($eventID); ?>"
    data-em-updated="<?php echo h(t('Event updated.')); ?>"
    data-em-error="<?php echo h(t('The event could not be loaded. Please try again later.')); ?>">
    <?php if (!empty($title)) { ?>
        <h2 class="em-events__title" id="em-title-<?php echo (int) $bID; ?>"><?php echo h($title); ?></h2>
    <?php } ?>

    <div class="em-events__content">
        <p class="em-events__loading"><?php echo t('Loading event…'); ?></p>
        <noscript><p><?php echo t('Please enable JavaScript to see this event.'); ?></p></noscript>
    </div>
    <p class="em-events__status visually-hidden sr-only" role="status"></p>
</section>
