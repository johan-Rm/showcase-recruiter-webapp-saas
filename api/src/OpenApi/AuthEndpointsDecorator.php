<?php

declare(strict_types=1);

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;

/**
 * Déclare les deux endpoints d'authentification que les bundles laissent hors du contrat.
 *
 * Le login est un `json_login` Symfony : LexikJWT contribue le path à l'OpenAPI en ne
 * déclarant que `token`, tandis que Gesdinet décore le success handler et ajoute
 * `refresh_token` à la réponse au runtime — sans le déclarer. Le refresh, lui, est une route
 * Gesdinet pure : elle n'apparaît nulle part dans l'OpenAPI. Or le front appelle les deux, et
 * en génère ses types : une route absente du contrat est une route qu'on ne peut ni typer ni
 * protéger de la dérive.
 *
 * La priorité négative est indispensable : elle place ce décorateur À L'EXTÉRIEUR de celui de
 * Lexik, qui est justement celui qui ajoute le path du login. Sans elle, on patcherait un path
 * qui n'existe pas encore, et Lexik l'écraserait ensuite.
 */
#[AsDecorator(decorates: 'api_platform.openapi.factory', priority: -10)]
final readonly class AuthEndpointsDecorator implements OpenApiFactoryInterface
{
    private const string LOGIN_PATH = '/v1/auth/login';
    private const string REFRESH_PATH = '/v1/auth/refresh';

    public function __construct(private OpenApiFactoryInterface $decorated)
    {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);

        $this->declareRefreshTokenOnLogin($openApi);
        $this->declareRefreshPath($openApi);

        return $openApi;
    }

    /** Gesdinet ajoute `refresh_token` à la réponse du login sans l'inscrire au contrat. */
    private function declareRefreshTokenOnLogin(OpenApi $openApi): void
    {
        $pathItem = $openApi->getPaths()->getPath(self::LOGIN_PATH);
        $operation = $pathItem?->getPost();

        if (null === $pathItem || null === $operation) {
            return;
        }

        $responses = $operation->getResponses();
        $schema = $responses[200]['content']['application/json']['schema'] ?? null;

        if (!\is_array($schema)) {
            return;
        }

        $schema['properties']['refresh_token'] = self::tokenProperty();
        $schema['required'] = array_values(
            array_unique([...$schema['required'] ?? [], 'refresh_token'])
        );

        $responses[200]['content']['application/json']['schema'] = $schema;

        $openApi->getPaths()->addPath(
            self::LOGIN_PATH,
            $pathItem->withPost($operation->withResponses($responses))
        );
    }

    /** La route de refresh de Gesdinet n'apparaît pas du tout dans l'OpenAPI. */
    private function declareRefreshPath(OpenApi $openApi): void
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'token' => self::tokenProperty(),
                'refresh_token' => self::tokenProperty(),
            ],
            'required' => ['token', 'refresh_token'],
        ];

        $operation = new Operation(
            operationId: 'refresh_token_post',
            tags: ['Auth'],
            summary: 'Renouvelle le JWT à partir du refresh token.',
            requestBody: new RequestBody(
                description: 'Le refresh token obtenu au login.',
                content: new ArrayObject([
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => ['refresh_token' => ['type' => 'string']],
                            'required' => ['refresh_token'],
                        ],
                    ],
                ]),
                required: true,
            ),
            responses: [
                200 => new Response(
                    description: 'JWT renouvelé.',
                    content: new ArrayObject([
                        'application/json' => ['schema' => $schema],
                    ]),
                ),
                401 => new Response(description: 'Refresh token invalide ou expiré.'),
            ],
            security: [],
        );

        $openApi->getPaths()->addPath(self::REFRESH_PATH, new PathItem(post: $operation));
    }

    /** @return array<string, bool|string> */
    private static function tokenProperty(): array
    {
        return ['readOnly' => true, 'type' => 'string', 'nullable' => false];
    }
}
