<?php


return [
    'update.ctas' => [
        'path' => '/update-hubspot-ctas',
        'access' => 'public',
        'target' => \T3G\Hubspot\Controller\CtaApiController::class . '::updateCtaAction',
    ],
];

