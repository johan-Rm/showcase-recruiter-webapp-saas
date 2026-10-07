<?php

declare(strict_types=1);

namespace App\Domain\Jarvi;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Écriture showcase-recruiter-webapp-saas → Jarvi `POST /companies` (tâche 023).
 * Le service est inoffensif tant que `OrganizationJarviCompanySynchronizer` reste désactivé.
 */
final readonly class JarviCompanyWriter implements JarviCompanyWriterInterface
{
    public function __construct(private HttpClientInterface $jarviClient)
    {
    }

    public function upsert(JarviUpsertCompany $company): string
    {
        $body = [
            'name' => $company->name,
        ];
        if (null !== $company->companyId && '' !== $company->companyId) {
            $body['companyId'] = $company->companyId;
        }
        if (null !== $company->linkedinUrl && '' !== $company->linkedinUrl) {
            $body['linkedinUrl'] = $company->linkedinUrl;
        }

        $payload = $this->jarviClient->request('POST', 'companies', ['json' => $body])->toArray();

        $companyId = $payload['companyId'] ?? null;
        if (!\is_string($companyId) || '' === $companyId) {
            throw new JarviWriteException('Réponse Jarvi inattendue : companyId manquant.');
        }

        return $companyId;
    }

    public function attachToProject(string $projectId, string $companyId): void
    {
        $this->jarviClient->request('POST', 'projects', [
            'json' => [
                'projectId' => $projectId,
                'companyId' => $companyId,
            ],
        ])->toArray();
    }
}
