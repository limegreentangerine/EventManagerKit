<?php defined('C5_EXECUTE') or die('Access Denied.');
/** @var \EventManagerKit\Entity\Event $event @var string $locale @var bool $isDraft */

use EventManagerKit\Frontend\EventDisplay;

$description = $event->getContent($locale)?->getDescription();
?>

<article class="em-event-detail">
    <?php if ($isDraft) { ?>
        <p class="em-event-detail__draft"><strong><?php echo t('Draft'); ?></strong> &ndash; <?php echo t('only administrators can see this event.'); ?></p>
    <?php } ?>

    <h1 class="em-event-detail__name"><?php echo h($event->getName($locale)); ?></h1>

    <dl class="em-event-detail__facts">
        <dt><?php echo t('When'); ?></dt>
        <dd><?php echo h(EventDisplay::dateRange($event, $locale)); ?></dd>

        <?php if ($event->getLocation()) { ?>
            <dt><?php echo t('Where'); ?></dt>
            <dd><?php echo h($event->getLocation()->getFullName($locale)); ?></dd>
        <?php } ?>

        <?php if ($event->getType()) { ?>
            <dt><?php echo t('Type'); ?></dt>
            <dd><?php echo h($event->getType()->getName($locale)); ?></dd>
        <?php } ?>

        <?php if (count($event->getTags())) { ?>
            <dt><?php echo t('Tags'); ?></dt>
            <dd>
                <?php foreach ($event->getTags() as $tag) { ?>
                    <span class="em-event__tag"><?php echo h($tag->getName($locale)); ?></span>
                <?php } ?>
            </dd>
        <?php } ?>
    </dl>

    <?php if ($description) { ?>
        <div class="em-event-detail__description"><?php echo nl2br(h($description)); ?></div>
    <?php } ?>
</article>
