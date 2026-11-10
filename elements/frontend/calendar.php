<?php defined('C5_EXECUTE') or die('Access Denied.');
/**
 * Inner markup of the calendar block, fetched from the events endpoint.
 * Links are query-string only ("?month=2026-12"), so they resolve against
 * whichever page the block is on; the block's script turns them into fetches.
 *
 * @var \EventManagerKit\Frontend\EventQuery         $query
 * @var list<\EventManagerKit\Recurrence\Occurrence> $occurrences
 * @var bool                                         $hasMore
 * @var array|null                                   $filters
 * @var string                                       $locale
 */

use EventManagerKit\Frontend\EventDisplay;

$link = fn(array $params) => h('?' . http_build_query($params));
$prev = $query->month->modify('-1 month');
$next = $query->month->modify('+1 month');
?>

<nav class="em-events__months" aria-label="<?php echo h(t('Month')); ?>">
    <a href="<?php echo $link($query->toParams($prev)); ?>" rel="prev">&larr; <?php echo h(EventDisplay::monthTitle($prev, $locale)); ?></a>
    <strong aria-current="date"><?php echo h(EventDisplay::monthTitle($query->month, $locale)); ?></strong>
    <a href="<?php echo $link($query->toParams($next)); ?>" rel="next"><?php echo h(EventDisplay::monthTitle($next, $locale)); ?> &rarr;</a>
</nav>

<?php if ($filters) { ?>
    <form class="em-events__filters" method="get" action="">
        <input type="hidden" name="month" value="<?php echo h($query->month->format('Y-m')); ?>">
        <?php foreach ([
            'type' => [t('All event types'), $filters['types'], $query->typeId],
            'location' => [t('All locations'), $filters['locations'], $query->locationId],
            'tag' => [t('All tags'), $filters['tags'], $query->tagId],
        ] as $name => [$all, $options, $selected]) { ?>
            <?php if ($options) { ?>
                <label class="em-events__filter">
                    <span class="visually-hidden sr-only"><?php echo h($all); ?></span>
                    <select name="<?php echo h($name); ?>">
                        <option value=""><?php echo h($all); ?></option>
                        <?php foreach ($options as $id => $label) { ?>
                            <option value="<?php echo h($id); ?>"<?php echo $id === $selected ? ' selected' : ''; ?>><?php echo h($label); ?></option>
                        <?php } ?>
                    </select>
                </label>
            <?php } ?>
        <?php } ?>
    </form>
<?php } ?>

<?php if (empty($occurrences)) { ?>
    <p class="em-events__empty"><?php echo t('There are no events this month.'); ?></p>
<?php } else { ?>
    <ul class="em-events__items">
        <?php foreach ($occurrences as $occurrence) { ?>
            <?php View::element('frontend/event', ['event' => $occurrence->event, 'occurrence' => $occurrence, 'locale' => $locale], 'event_manager_kit'); ?>
        <?php } ?>
    </ul>
<?php } ?>

<?php if ($query->page > 1 || $hasMore) { ?>
    <nav class="em-events__pages" aria-label="<?php echo h(t('Pages')); ?>">
        <?php if ($query->page > 1) { ?>
            <a href="<?php echo $link($query->toParams(null, $query->page - 1)); ?>" rel="prev">&larr; <?php echo t('Previous'); ?></a>
        <?php } ?>
        <?php if ($hasMore) { ?>
            <a href="<?php echo $link($query->toParams(null, $query->page + 1)); ?>" rel="next"><?php echo t('Next'); ?> &rarr;</a>
        <?php } ?>
    </nav>
<?php } ?>
