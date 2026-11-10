<?php

namespace EventManagerKit\Dashboard;

use EventManagerKit\Entity\Location;
use Doctrine\ORM\EntityManagerInterface;
use EventManagerKit\Translation\LocaleContext;
use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Core\Page\Controller\DashboardPageController;

/**
 * Dashboard CRUD for entities that are just a handle plus a name per locale
 * (event types, tags, locations). Subclasses supply the entity and wording.
 */
abstract class NamedEntityPage extends DashboardPageController
{
    protected $helpers = ['form', 'concrete/ui'];

    protected EntityManagerInterface $em;

    /** @return class-string */
    abstract protected function entityClass(): string;

    /** Dashboard path, e.g. /dashboard/event_manager/event_types */
    abstract protected function basePath(): string;

    /** @return class-string search controller */
    abstract protected function searchControllerClass(): string;

    /** Singular, translated label, e.g. "Event Type" */
    abstract protected function label(): string;

    abstract protected function pluralLabel(): string;

    public function on_start()
    {
        parent::on_start();
        $this->em = $this->app->make(EntityManagerInterface::class);
        $this->set('basePath', $this->basePath());
        $this->set('label', $this->label());
        $this->set('pluralLabel', $this->pluralLabel());
        $this->set('locales', LocaleContext::siteLocales());
        $this->set('defaultLocale', LocaleContext::default());
        $this->set('parents', []);
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

        $search = $this->app->make($this->searchControllerClass());
        $search->search($reset);
        $result = $search->getSearchResultObject();

        $params = $search->getStickyRequest()->getSearchRequest();
        $sizes = $search->getAllowedPaginationSizes();

        $this->set('result', $result);
        $this->set('items', $result->getItems());
        $this->set('pagination', $result->getPaginationHTML());
        $this->set('params', $params);
        $this->set('allowed_num_results', array_combine($sizes, $sizes));
        $this->set('num_results', in_array((int) ($params['num_results'] ?? 0), $sizes, true) ? (int) $params['num_results'] : $search->getDefaultPaginationSize());
    }

    public function add()
    {
        $this->prepareForm();
    }

    public function details($id = false)
    {
        $entity = $id ? $this->em->find($this->entityClass(), (string) $id) : null;
        if (!$entity) {
            return $this->buildRedirect($this->basePath())->send();
        }
        $this->set('entity', $entity);
        $this->prepareForm($entity);
    }

    public function save()
    {
        if (!$this->request->isPost()) {
            return $this->buildRedirect($this->basePath())->send();
        }

        $post = $this->request->request->all();
        $entity = !empty($post['id']) ? $this->em->find($this->entityClass(), (string) $post['id']) : null;
        $names = array_filter(array_map('trim', (array) ($post['name'] ?? [])), 'strlen');
        $default = LocaleContext::default();

        if (!$this->token->validate('submit')) {
            $this->error->add($this->token->getErrorMessage());
        }
        if (!$names) {
            $this->error->add(t('Please enter a name in at least one language.'));
        }
        if (!$entity && !isset($names[$default])) {
            $this->error->add(t('Please enter a name for the default language (%s).', $default));
        }
        if (!empty($post['id']) && !$entity) {
            $this->error->add(t('Unable to find the specified %s.', $this->label()));
        }

        $handle = $entity ? $entity->getHandle() : $this->app->make('helper/text')->handle($names[$default] ?? (string) reset($names));
        if (!$entity && $handle !== '' && $this->em->getRepository($this->entityClass())->findOneBy(['handle' => $handle])) {
            $this->error->add(t('A %s with this name already exists.', $this->label()));
        }

        $parent = $this->resolveParent($post, $entity);

        if (!$this->error->has()) {
            $class = $this->entityClass();
            $entity ??= (new $class())->setHandle($handle);
            $this->applyExtra($entity, $parent);
            foreach (LocaleContext::siteLocales() as $locale) {
                if (isset($names[$locale])) {
                    $entity->setName($locale, $names[$locale]);
                }
            }
            $this->em->persist($entity);
            $this->em->flush();

            $this->flash('success', t('%s saved.', $this->label()));

            return $this->buildRedirect($this->basePath() . '/details/' . $entity->getID())->send();
        }

        $this->set('formContent', $post);
        $this->set('entity', $entity);
        $this->prepareForm($entity ?: null);
    }

    public function delete($id = false)
    {
        $rf = $this->app->make(ResponseFactoryInterface::class);

        if (!$this->token->validate('delete-' . $this->basePath() . '-' . $id)) {
            $this->error->add($this->token->getErrorMessage());
        } elseif ($entity = $this->em->find($this->entityClass(), (string) $id)) {
            $this->flash('success', t('%s deleted.', $this->label()));
            $this->em->remove($entity);
            $this->em->flush();

            return $rf->json(true);
        } else {
            $this->error->add(t('Unable to find the specified %s.', $this->label()));
        }

        return $rf->json($this->error->jsonSerialize());
    }

    /** Hook: fill in the select options etc. a form needs. */
    protected function prepareForm($entity = null): void {}

    /** Hook: read extra posted fields (e.g. a location's parent). */
    protected function resolveParent(array $post, $entity): ?Location
    {
        return null;
    }

    /** Hook: copy extra fields onto the entity before saving. */
    protected function applyExtra($entity, ?Location $parent): void {}
}
