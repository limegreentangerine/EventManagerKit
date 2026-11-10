<?php

namespace EventManagerKit\Search;

use Concrete\Core\Search\StickyRequest;
use Concrete\Core\Controller\AbstractController;

/**
 * Sticky-request search controller shared by the four dashboard sections.
 * Subclasses name the sticky key, list/column set/result classes and list URL.
 */
abstract class AbstractSearchController extends AbstractController
{
    private ?StickyRequest $stickyRequest = null;

    private $searchList;

    private $searchResult;

    abstract protected function stickyKey(): string;

    /** @return class-string */
    abstract protected function listClass(): string;

    /** @return class-string */
    abstract protected function columnSetClass(): string;

    /** @return class-string */
    abstract protected function resultClass(): string;

    abstract protected function listUrl(): string;

    /**
     * Apply section-specific filters from the sticky request.
     */
    protected function applyFilters($searchList, array $req): void {}

    protected function getSearchList()
    {
        if ($this->searchList === null) {
            $this->searchList = $this->app->make($this->listClass(), [$this->getStickyRequest()]);
        }

        return $this->searchList;
    }

    public function getStickyRequest()
    {
        if ($this->stickyRequest === null) {
            $this->stickyRequest = new StickyRequest($this->stickyKey());
        }

        return $this->stickyRequest;
    }

    public function getAllowedPaginationSizes()
    {
        return [10, 20, 50, 100];
    }

    public function getDefaultPaginationSize()
    {
        return $this->getAllowedPaginationSizes()[1];
    }

    public function search($reset = false)
    {
        $stickyRequest = $this->getStickyRequest();
        $searchList = $this->getSearchList();

        if ($reset) {
            $stickyRequest->resetSearchRequest();
        }

        $columnSetClass = $this->columnSetClass();
        $columnSet = new $columnSetClass();

        if (!$searchList->getActiveSortColumn()) {
            $sortColumn = $columnSet->getDefaultSortColumn();
            $searchList->sanitizedSortBy($sortColumn->getColumnKey(), $sortColumn->getColumnDefaultSortDirection());
        }

        $req = $stickyRequest->getSearchRequest();

        $q = $req['name'] ?? null;
        if (is_string($q) && $q !== '') {
            $searchList->filterByName($q);
        }
        $this->applyFilters($searchList, $req);

        $size = $req['num_results'] ?? null;
        $paginationSize = ($size && $this->app->make('helper/validation/numbers')->integer($size) && in_array((int) $size, $this->getAllowedPaginationSizes(), true))
            ? (int) $size
            : $this->getDefaultPaginationSize();
        $searchList->setItemsPerPage($paginationSize);

        $resultClass = $this->resultClass();
        $this->searchResult = new $resultClass(
            $columnSet,
            $searchList,
            $this->app->make('url/manager')->resolve([$this->listUrl()]),
        );
    }

    public function getSearchResultObject()
    {
        return $this->searchResult;
    }
}
