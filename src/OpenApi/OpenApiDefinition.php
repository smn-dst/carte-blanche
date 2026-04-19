<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'Carte Blanche API',
    version: '1.0.0',
    description: 'API REST de la plateforme d\'enchères de restaurants Carte Blanche.',
    contact: new OA\Contact(email: 'contact@carteblanche.fr')
)]
#[OA\SecurityScheme(
    securityScheme: 'cookieAuth',
    type: 'apiKey',
    in: 'cookie',
    name: 'PHPSESSID',
    description: 'Session Symfony — connexion via /login'
)]
class OpenApiDefinition
{
}
