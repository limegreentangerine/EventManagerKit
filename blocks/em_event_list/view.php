<?php defined('C5_EXECUTE') or die('Access Denied.');
/**
 * Shell only: this page may be cached, so the events are fetched from the
 * endpoint by js/events.js and never baked into the page HTML.
 *
 * @var string $endpoint
 * @var string $locale
 * @var int    $numEvents
 * @var string $title
 */
?>

<section class="em-events em-events--list"
    data-em-endpoint="<?php echo h($endpoint); ?>"
    data-em-view="list"
    data-em-locale="<?php echo h($locale); ?>"
    data-em-limit="<?php echo (int) $numEvents; ?>"
    data-em-error="<?php echo h(t('Events could not be loaded. Please try again later.')); ?>">
    <?php if (!empty($title)) { ?>
        <h2 class="em-events__title"><?php echo h($title); ?></h2>
    <?php } ?>

    <div class="em-events__content" aria-live="polite">
        <p class="em-events__loading"><?php echo t('Loading events…'); ?></p>
        <noscript><p><?php echo t('Please enable JavaScript to see events.'); ?></p></noscript>
    </div>
</section>
