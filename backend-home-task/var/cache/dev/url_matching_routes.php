<?php

/**
 * This file has been auto-generated
 * by the Symfony Routing Component.
 */

return [
    false, // $matchHost
    [ // $staticRoutes
        '/' => [[['_route' => 'home', '_controller' => 'App\\Controller\\HomeController::index'], null, ['GET' => 0], null, false, false, null]],
        '/api/repositories' => [[['_route' => 'list_repositories', '_controller' => 'App\\Controller\\RepositoryController::listRepositories'], null, ['GET' => 0], null, false, false, null]],
        '/api/uploads' => [
            [['_route' => 'upload_files', '_controller' => 'App\\Controller\\UploadController::upload'], null, ['POST' => 0], null, false, false, null],
            [['_route' => 'upload_list', '_controller' => 'App\\Controller\\UploadController::list'], null, ['GET' => 0], null, false, false, null],
        ],
    ],
    [ // $regexpList
        0 => '{^(?'
                .'|/_error/(\\d+)(?:\\.([^/]++))?(*:35)'
                .'|/api/(?'
                    .'|repository/([^/]++)/scans(*:75)'
                    .'|scan/([^/]++)(*:95)'
                    .'|uploads/([^/]++)(*:118)'
                .')'
            .')/?$}sDu',
    ],
    [ // $dynamicRoutes
        35 => [[['_route' => '_preview_error', '_controller' => 'error_controller::preview', '_format' => 'html'], ['code', '_format'], null, null, false, true, null]],
        75 => [[['_route' => 'repository_scans', '_controller' => 'App\\Controller\\RepositoryController::repositoryScans'], ['id'], ['GET' => 0], null, false, false, null]],
        95 => [[['_route' => 'scan_details', '_controller' => 'App\\Controller\\ScanController::scanDetails'], ['scanId'], ['GET' => 0], null, false, true, null]],
        118 => [
            [['_route' => 'upload_status', '_controller' => 'App\\Controller\\UploadController::status'], ['id'], ['GET' => 0], null, false, true, null],
            [null, null, null, null, false, false, 0],
        ],
    ],
    null, // $checkCondition
];
