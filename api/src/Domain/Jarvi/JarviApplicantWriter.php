<?php

declare(strict_types=1);

namespace App\Domain\Jarvi;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpClientExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Implémentation HTTP du canal `POST /applicants` sur le client scopé `jarvi.client`
 * (base_uri REST + X-API-KEY, cf. jarvi.yaml). Clé privée côté api/ uniquement.
 *
 * N'écrit QUE des données déjà consenties (identité + LinkedIn + CV). Ne touche pas `/profiles`.
 */
final readonly class JarviApplicantWriter implements JarviApplicantWriterInterface
{
    public function __construct(
        private HttpClientInterface $jarviClient,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function submit(JarviApplicant $applicant): JarviApplicantResult
    {
        try {
            $payload = $this->jarviClient
                ->request('POST', 'applicants', ['json' => $this->body($applicant)])
                ->toArray();
        } catch (HttpClientExceptionInterface $e) {
            // Ne jamais journaliser le body (clé/CV) : seul le message d'erreur transport remonte.
            $this->logger->error('Écriture applicant Jarvi échouée', ['exception' => $e]);

            throw new JarviWriteException('Appel /applicants Jarvi en échec.', 0, $e);
        }

        $profileId = $payload['profileId'] ?? null;
        if (!\is_string($profileId) || '' === $profileId) {
            throw new JarviWriteException('Réponse Jarvi inattendue : profileId manquant.');
        }

        $taskId = \is_string($payload['taskId'] ?? null) ? $payload['taskId'] : null;

        return new JarviApplicantResult($profileId, $taskId, $this->fileErrors($payload));
    }

    /**
     * @return array<string, mixed>
     */
    private function body(JarviApplicant $applicant): array
    {
        $body = [
            'externalId' => $applicant->externalId,
            'firstName' => $applicant->firstName,
            'lastName' => $applicant->lastName,
            'projectId' => $applicant->projectId,
        ];
        foreach ([
            'linkedinUrl' => $applicant->linkedinUrl,
            'emailAddresses' => $applicant->emailAddresses,
            'phoneNumbers' => $applicant->phoneNumbers,
            'headline' => $applicant->headline,
            'location' => $applicant->location,
        ] as $key => $value) {
            if (null !== $value && '' !== $value) {
                $body[$key] = $value;
            }
        }

        if ([] !== $applicant->resumesFiles) {
            $body['resumesFiles'] = array_map(
                static fn (JarviResumeFile $file): array => ['fileName' => $file->fileName, 'data' => $file->data],
                $applicant->resumesFiles,
            );
        }

        return $body;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return list<array<string, mixed>>
     */
    private function fileErrors(array $payload): array
    {
        $errors = [];
        foreach (\is_array($payload['fileErrors'] ?? null) ? $payload['fileErrors'] : [] as $error) {
            if (\is_array($error)) {
                $errors[] = $error;
            }
        }

        return $errors;
    }
}
