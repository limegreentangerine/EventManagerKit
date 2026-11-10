<?php defined('C5_EXECUTE') or die('Access Denied.');

if (in_array($controller->getTask(), ['add', 'details', 'save'])) {
    View::element('dashboard/events/details', [
        'token' => $token,
        'form' => $form,
        'entity' => $entity ?? null,
        'formContent' => $formContent ?? null,
        'basePath' => $basePath,
        'locales' => $locales,
        'defaultLocale' => $defaultLocale,
        'typeOptions' => $typeOptions,
        'locationOptions' => $locationOptions,
        'tagOptions' => $tagOptions,
        'view' => $view,
    ], 'event_manager_kit');
} else {
    View::element('dashboard/events/all', [
        'form' => $form,
        'params' => $params,
        'items' => $items,
        'result' => $result,
        'pagination' => $pagination,
        'basePath' => $basePath,
        'typeOptions' => $typeOptions,
        'locationOptions' => $locationOptions,
    ], 'event_manager_kit');
}
