<?php defined('C5_EXECUTE') or die('Access Denied.');
/** @var \EventManagerKit\Entity\Event $event @var string $locale @var bool $isDraft */

use Concrete\Core\Support\Facade\Url;
use EventManagerKit\Time\SiteTimezone;
use EventManagerKit\Frontend\{EventDisplay, IcsEndpoint, LocationMap};

$zone = SiteTimezone::current();

$description = $event->getContent($locale)?->getDescription();

// schema.org Event; all-day events use date-only values.
$dateFormat = $event->isAllDay() ? 'Y-m-d' : DATE_ATOM;
$schema = [
    '@context' => 'https://schema.org',
    '@type' => 'Event',
    'name' => $event->getName($locale),
    'startDate' => SiteTimezone::localise($event->getStartDate(), $zone)->format($dateFormat),
    'endDate' => SiteTimezone::localise($event->getEndDate(), $zone)->format($dateFormat),
    'eventStatus' => 'https://schema.org/EventScheduled',
    'inLanguage' => $locale,
];
if ($event->getSlug($locale)) {
    $schema['url'] = (string) Url::to('/events', $event->getSlug($locale));
}
if ($description) {
    $schema['description'] = trim(strip_tags($description));
}
$place = $event->getLocation();
if ($place) {
    $locations = [];
    if ($place->getAddress() || $place->hasCoordinates() || !$place->getOnlineUrl()) {
        $physical = ['@type' => 'Place', 'name' => $place->getFullName($locale)];
        if ($place->getAddress()) {
            $physical['address'] = $place->getAddress();
        }
        if ($place->hasCoordinates()) {
            $physical['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => $place->getLatitude(), 'longitude' => $place->getLongitude()];
        }
        $locations[] = $physical;
    }
    if ($place->getOnlineUrl()) {
        $locations[] = ['@type' => 'VirtualLocation', 'url' => $place->getOnlineUrl()];
    }
    $schema['location'] = count($locations) === 1 ? $locations[0] : $locations;
    $schema['eventAttendanceMode'] = 'https://schema.org/' . (count($locations) > 1 ? 'MixedEventAttendanceMode' : ($locations[0]['@type'] === 'VirtualLocation' ? 'OnlineEventAttendanceMode' : 'OfflineEventAttendanceMode'));
}
if (count($event->getTags())) {
    $schema['keywords'] = implode(', ', array_map(fn($tag) => $tag->getName($locale), $event->getTags()->toArray()));
}

$file = fn(?int $id) => $id ? \Concrete\Core\File\File::getByID($id) : null;
$image = $file($event->getImageID());
$video = $file($event->getVideoID());
$poster = $file($event->getVideoPosterID());
$linkHref = $event->getLinkHref();
if ($image) {
    $schema['image'] = $image->getURL();
}
?>
<?php if (!$isDraft) { ?>
    <script type="application/ld+json"><?php echo json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP); ?></script>
<?php } ?>

<article class="em-event-detail">
    <?php if ($isDraft) { ?>
        <p class="em-event-detail__draft"><strong><?php echo t('Draft'); ?></strong> &ndash; <?php echo t('only administrators can see this event.'); ?></p>
    <?php } ?>

    <h1 class="em-event-detail__name"><?php echo h($event->getName($locale)); ?></h1>

    <?php if ($video) { ?>
        <video class="em-event-detail__video" controls preload="metadata"<?php echo ($poster || $image) ? ' poster="' . h(($poster ?: $image)->getURL()) . '"' : ''; ?> src="<?php echo h($video->getURL()); ?>"></video>
    <?php } elseif ($image) { ?>
        <img class="em-event-detail__image" src="<?php echo h($image->getURL()); ?>" alt="<?php echo h($event->getName($locale)); ?>">
    <?php } ?>

    <dl class="em-event-detail__facts">
        <dt><?php echo t('When'); ?></dt>
        <dd><?php echo h(EventDisplay::dateRange($event, $locale)); ?></dd>

        <?php if ($event->getLocation()) { ?>
            <dt><?php echo t('Where'); ?></dt>
            <dd>
                <?php echo h($place->getFullName($locale)); ?>
                <?php if ($place->getAddress()) { ?><br><span class="em-event-detail__address"><?php echo h($place->getAddress()); ?></span><?php } ?>
                <?php if ($place->getOnlineUrl()) { ?><br><a href="<?php echo h($place->getOnlineUrl()); ?>" rel="noopener"><?php echo t('Join online'); ?></a><?php } ?>
                <?php if (empty($mapboxConfig) && ($mapLink = LocationMap::externalUrl($place))) { ?>
                    <br><a href="<?php echo h($mapLink); ?>" target="_blank" rel="noopener"><?php echo t('View on map'); ?></a>
                <?php } ?>
            </dd>
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

    <?php if (!empty($mapboxConfig)) { ?>
        <mapbox-component class="em-event-detail__map" style="display:block;height:320px" data-config="<?php echo h((string) json_encode($mapboxConfig)); ?>"></mapbox-component>
    <?php } ?>

    <?php if ($event->isPublished() && $event->getSlug($locale)) {
        $calendarLinks = EventDisplay::calendarLinks($event, $locale, IcsEndpoint::eventUrl($event, $locale), $zone); ?>
        <p class="em-event-detail__calendar">
            <?php echo t('Add to calendar'); ?>:
            <a href="<?php echo h((string) Url::to('/ajax/events-manager/ics', $event->getSlug($locale))); ?>"><?php echo t('iCal / Apple'); ?></a> |
            <a href="<?php echo h($calendarLinks['google']); ?>" target="_blank" rel="noopener"><?php echo t('Google'); ?></a> |
            <a href="<?php echo h($calendarLinks['outlook']); ?>" target="_blank" rel="noopener"><?php echo t('Outlook'); ?></a>
        </p>
    <?php } ?>

    <?php if ($linkHref) { ?>
        <p class="em-event-detail__link"><a class="btn" href="<?php echo h($linkHref); ?>"<?php echo $event->getLinkType() === 'url' ? ' rel="noopener"' : ''; ?>><?php echo $event->getLinkType() === 'email' ? t('Email us') : ($event->getLinkType() === 'tel' ? t('Call us') : t('More information / tickets')); ?></a></p>
    <?php } ?>
</article>
