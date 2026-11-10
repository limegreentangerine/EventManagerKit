<?php defined('C5_EXECUTE') or die('Access Denied.');
/** @var \EventManagerKit\Entity\Event $event @var \EventManagerKit\Recurrence\Occurrence $occurrence @var string $locale */

use EventManagerKit\Frontend\EventDisplay;

$description = $event->getContent($locale)?->getDescription();
// Link only when the event has a slug (events saved before slugs existed have none).
$slug = $event->getSlug($locale);
?>
<li class="em-event">
    <h3 class="em-event__name">
        <?php if ($slug) { ?>
            <a href="<?php echo h(URL::to('/events', $slug)); ?>"><?php echo h($event->getName($locale)); ?></a>
        <?php } else { ?>
            <?php echo h($event->getName($locale)); ?>
        <?php } ?>
    </h3>
    <p class="em-event__date"><time datetime="<?php echo h($occurrence->start->format($event->isAllDay() ? 'Y-m-d' : 'Y-m-d\TH:i')); ?>"><?php echo h(EventDisplay::dateRange($event, $locale, $occurrence->start, $occurrence->end)); ?></time></p>
    <?php if ($event->getLocation()) { ?>
        <p class="em-event__location"><?php echo h($event->getLocation()->getFullName($locale)); ?></p>
    <?php } ?>
    <?php if ($event->getType() || count($event->getTags())) { ?>
        <p class="em-event__labels">
            <?php if ($event->getType()) { ?>
                <span class="em-event__type"><?php echo h($event->getType()->getName($locale)); ?></span>
            <?php } ?>
            <?php foreach ($event->getTags() as $tag) { ?>
                <span class="em-event__tag"><?php echo h($tag->getName($locale)); ?></span>
            <?php } ?>
        </p>
    <?php } ?>
    <?php if ($description) { ?>
        <p class="em-event__description"><?php echo nl2br(h($description)); ?></p>
    <?php } ?>
</li>
