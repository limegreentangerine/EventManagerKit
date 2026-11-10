<?php defined('C5_EXECUTE') or die('Access Denied.');

if (in_array($controller->getTask(), ['add', 'details', 'save'])) {
    View::element('dashboard/named/details', [
        'token' => $token,
        'form' => $form,
        'controller' => $controller,
        'entity' => $entity ?? null,
        'formContent' => $formContent ?? null,
        'basePath' => $basePath,
        'label' => $label,
        'locales' => $locales,
        'defaultLocale' => $defaultLocale,
        'parents' => $parents,
        'place' => $place ?? null,
    ], 'event_manager_kit');
} else {
    View::element('dashboard/named/all', [
        'params' => $params,
        'items' => $items,
        'result' => $result,
        'pagination' => $pagination,
        'basePath' => $basePath,
        'label' => $label,
        'pluralLabel' => $pluralLabel,
    ], 'event_manager_kit');
}
