<?php

namespace Concrete\Package\EventManagerKit\Controller\SinglePage\Dashboard\EventManager;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use EventManagerKit\Translation\LocaleContext;
use Concrete\Core\Http\ResponseFactoryInterface;
use EventManagerKit\Slug\{SlugGenerator, SlugService};
use Concrete\Core\Page\Controller\DashboardPageController;
use EventManagerKit\Entity\{Event, EventTag, EventType, Location};
use Concrete\Package\EventManagerKit\Controller\Search\Events as SearchController;

class Events extends DashboardPageController
{
    private const BASE = '/dashboard/event_manager/events';

    protected $helpers = ['form', 'concrete/ui'];

    protected EntityManagerInterface $em;

    private function prepareForm(): void
    {
        $this->set('typeOptions', ['' => t('(none)')] + $this->options(EventType::class));
        $this->set('locationOptions', ['' => t('(none)')] + $this->options(Location::class, true));
        $this->set('tagOptions', $this->options(EventTag::class));
    }

    /**
     * @return array<string,string> id => label, in the displayed locale
     */
    private function options(string $class, bool $full = false): array
    {
        $options = [];
        foreach ($this->em->getRepository($class)->findAll() as $entity) {
            $options[$entity->getID()] = $full ? $entity->getFullName() : $entity->getName();
        }
        asort($options);

        return $options;
    }

    private function lookup(string $class, mixed $id, string $what): ?object
    {
        $id = (string) $id;
        if ($id === '') {
            return null;
        }
        $entity = $this->em->find($class, $id);
        if (!$entity) {
            $this->error->add(t('Unable to find the selected %s.', $what));
        }

        return $entity;
    }

    /**
     * Dates arrive from <input type="datetime-local"> / <input type="date">.
     */
    private function parseDate(string $value, bool $allDay): ?DateTimeImmutable
    {
        foreach ($allDay ? ['Y-m-d', 'Y-m-d\TH:i'] : ['Y-m-d\TH:i', 'Y-m-d\TH:i:s'] as $format) {
            $date = DateTimeImmutable::createFromFormat('!' . $format, $value);
            if ($date && $date->format($format) === $value) {
                return $allDay ? $date->setTime(0, 0) : $date;
            }
        }

        return null;
    }

    public function on_start()
    {
        parent::on_start();
        $this->em = $this->app->make(EntityManagerInterface::class);
        $this->set('basePath', self::BASE);
        $this->set('locales', LocaleContext::siteLocales());
        $this->set('defaultLocale', LocaleContext::default());
    }

    public function view()
    {
        $reset = false;
        if ($this->request->isPost()) {
            if (!$this->token->validate('em-search')) {
                $this->error->add($this->token->getErrorMessage());
            } else {
                $reset = true;
            }
        }

        $search = $this->app->make(SearchController::class);
        $search->search($reset);
        $result = $search->getSearchResultObject();
        $sizes = $search->getAllowedPaginationSizes();
        $params = $search->getStickyRequest()->getSearchRequest();

        $this->set('result', $result);
        $this->set('items', $result->getItems());
        $this->set('pagination', $result->getPaginationHTML());
        $this->set('params', $params);
        $this->set('allowed_num_results', array_combine($sizes, $sizes));
        $this->set('typeOptions', $this->options(EventType::class));
        $this->set('locationOptions', $this->options(Location::class, true));
    }

    public function add()
    {
        $this->prepareForm();
    }

    public function details($id = false)
    {
        $event = $id ? $this->em->find(Event::class, (string) $id) : null;
        if (!$event) {
            return $this->buildRedirect(self::BASE)->send();
        }
        $this->set('entity', $event);
        $this->prepareForm();
    }

    public function save()
    {
        if (!$this->request->isPost()) {
            return $this->buildRedirect(self::BASE)->send();
        }

        $post = $this->request->request->all();
        $event = !empty($post['id']) ? $this->em->find(Event::class, (string) $post['id']) : null;
        $default = LocaleContext::default();

        if (!$this->token->validate('submit')) {
            $this->error->add($this->token->getErrorMessage());
        }
        if (!empty($post['id']) && !$event) {
            $this->error->add(t('Unable to find the specified Event.'));
        }

        $names = array_filter(array_map('trim', (array) ($post['name'] ?? [])), 'strlen');
        if (!isset($names[$default])) {
            $this->error->add(t('Please enter an event name for the default language (%s).', $default));
        }

        // Optional per-language slugs; blank means "generate from the name".
        $slugs = SlugService::forEntityManager($this->em);
        $chosenSlugs = [];
        foreach ((array) ($post['slug'] ?? []) as $locale => $slug) {
            $slug = strtolower(trim((string) $slug));
            if ($slug === '' || !isset($names[$locale])) {
                continue;
            }
            if (!SlugGenerator::isValid($slug)) {
                $this->error->add(t('The URL slug for %s may only contain lowercase letters, numbers and single hyphens.', $locale));
            } elseif ($slugs->isTaken($locale, $slug, $event)) {
                $this->error->add(t('The URL slug "%s" is already used by another event in %s.', $slug, $locale));
            } else {
                $chosenSlugs[$locale] = $slug;
            }
        }

        $allDay = !empty($post['allDay']);
        $start = $this->parseDate((string) ($post['startDate'] ?? ''), $allDay);
        $end = $this->parseDate((string) ($post['endDate'] ?? ''), $allDay);
        if ($end && $allDay) {
            // All-day events run to the end of their last day.
            $end = $end->setTime(23, 59, 59);
        }
        if (!$start) {
            $this->error->add(t('Please enter a valid start date.'));
        }
        if (!$end) {
            $this->error->add(t('Please enter a valid end date.'));
        }
        if ($start && $end && $end <= $start) {
            $this->error->add(t('The end date must be after the start date.'));
        }

        $location = $this->lookup(Location::class, $post['location'] ?? '', t('location'));
        $type = $this->lookup(EventType::class, $post['type'] ?? '', t('event type'));
        $tags = [];
        foreach ((array) ($post['tags'] ?? []) as $tagId) {
            if ($tag = $this->lookup(EventTag::class, $tagId, t('tag'))) {
                $tags[] = $tag;
            }
        }

        if (!$this->error->has()) {
            $event ??= new Event();
            $event->setDates($start, $end)->setAllDay($allDay)->setPublished(!empty($post['published']))->setLocation($location)->setType($type);

            $descriptions = (array) ($post['description'] ?? []);
            foreach (LocaleContext::siteLocales() as $locale) {
                if (isset($names[$locale])) {
                    $event->setContent($locale, $names[$locale], trim((string) ($descriptions[$locale] ?? '')) ?: null, $chosenSlugs[$locale] ?? null);
                } elseif ($content = $event->getContents()->filter(fn($c) => $c->getLocale() === $locale)->first()) {
                    // Name cleared for a language: drop that translation (the fallback covers it).
                    $event->removeContent($content);
                }
            }

            $slugs->fill($event);

            foreach ($event->getTags()->toArray() as $existing) {
                $event->removeTag($existing);
            }
            foreach ($tags as $tag) {
                $event->addTag($tag);
            }

            $this->em->persist($event);
            $this->em->flush();

            $this->flash('success', t('Event saved.'));

            return $this->buildRedirect(self::BASE . '/details/' . $event->getID())->send();
        }

        $this->set('formContent', $post);
        $this->set('entity', $event);
        $this->prepareForm();
    }

    public function delete($id = false)
    {
        $rf = $this->app->make(ResponseFactoryInterface::class);

        if (!$this->token->validate('delete-' . self::BASE . '-' . $id)) {
            $this->error->add($this->token->getErrorMessage());
        } elseif ($event = $this->em->find(Event::class, (string) $id)) {
            $this->flash('success', t('Event deleted.'));
            $this->em->remove($event);
            $this->em->flush();

            return $rf->json(true);
        } else {
            $this->error->add(t('Unable to find the specified Event.'));
        }

        return $rf->json($this->error->jsonSerialize());
    }
}
