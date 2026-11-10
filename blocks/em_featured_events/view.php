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

<section<?php echo !empty($title) ? ' aria-labelledby="em-title-' . (int) $bID . '"' : ''; ?> class="em-events em-events--featured"
    data-em-endpoint="<?php echo h($endpoint); ?>"
    data-em-view="featured"
    data-em-locale="<?php echo h($locale); ?>"
    data-em-limit="<?php echo (int) $numEvents; ?>"
    data-em-updated="<?php echo h(t('Events updated.')); ?>"
    data-em-error="<?php echo h(t('Events could not be loaded. Please try again later.')); ?>">
    <?php if (!empty($title)) { ?>
        <h2 class="em-events__title" id="em-title-<?php echo (int) $bID; ?>"><?php echo h($title); ?></h2>
    <?php } ?>

    <div class="em-events__content">
        <p class="em-events__loading"><?php echo t('Loading featured events…'); ?></p>
        <noscript><p><?php echo t('Please enable JavaScript to see events.'); ?></p></noscript>
    </div>
    <p class="em-events__status visually-hidden sr-only" role="status"></p>
</section>
