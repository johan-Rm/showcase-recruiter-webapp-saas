<?php

declare(strict_types=1);

namespace App\Domain\Jarvi;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Client Jarvi GraphQL pour l'import du vivier showcase-recruiter-webapp-saas. La clé privée reste côté api/ via le
 * client HTTP scopé `jarvi.client`; GraphQL est utilisé pour paginer le projet showcase-recruiter-webapp-saas et charger
 * uniquement les champs professionnels autorisés.
 */
final readonly class JarviClient implements JarviClientInterface
{
    private const int PAGE_SIZE = 500;

    private const string PROFILES_QUERY = <<<'GRAPHQL'
query ShowcaseRecruiterWebappSaasProfiles($projectId: uuid!, $limit: Int!, $offset: Int!) {
  profiles(
    where: { associations: { projectId: { _eq: $projectId } } },
    order_by: { updatedAt: asc },
    limit: $limit,
    offset: $offset
  ) {
    id
    firstName
    lastName
    source
    countResumes
    linkedinId
    headline
    summary
    updatedAt
    lastInteractionDate
    lastInteractionType
    profileUrl
    image { publicUrl }
    locations { source location { locality countryName countryShortName formattedAddress search } }
    positions { id title companyName startDate endDate source linkedinCompanyId linkedinCompanyData { id name } }
    educations { id school { id name } title degreeName fieldOfStudy startDate endDate source }
    locales { language country proficiency source }
    skills { name }
    files { fileId parsedAt shouldBeParsed file { id name mimeType etag } }
    emailAddresses { email source }
    phoneNumbers { number type source whatsappIdentifier }
    historyEntries(
      where: { deletedAt: { _is_null: true }, messageWithConfidentialitySettings: { _is_null: false } },
      order_by: { occurredAt: desc },
      limit: 20
    ) {
      id
      type
      source
      occurredAt
      subjectWithConfidentialitySettings
      messageWithConfidentialitySettings
      isConfidential
      subType { id name }
      sender { id displayName }
      project { id name }
    }
    fieldsValues(where: { deletedAt: { _is_null: true } }) {
      id
      fieldId
      fieldValueId
      value
      field { id name type }
      fieldValue { id name }
      location { locality countryName countryShortName formattedAddress search }
    }
  }
}
GRAPHQL;

    private const string UPDATED_PROFILES_QUERY = <<<'GRAPHQL'
query ShowcaseRecruiterWebappSaasUpdatedProfiles($projectId: uuid!, $limit: Int!, $offset: Int!, $updatedAfter: timestamptz!) {
  profiles(
    where: {
      _and: [
        { associations: { projectId: { _eq: $projectId } } },
        { updatedAt: { _gt: $updatedAfter } }
      ]
    },
    order_by: { updatedAt: asc },
    limit: $limit,
    offset: $offset
  ) {
    id
    firstName
    lastName
    source
    countResumes
    linkedinId
    headline
    summary
    updatedAt
    lastInteractionDate
    lastInteractionType
    profileUrl
    image { publicUrl }
    locations { source location { locality countryName countryShortName formattedAddress search } }
    positions { id title companyName startDate endDate source linkedinCompanyId linkedinCompanyData { id name } }
    educations { id school { id name } title degreeName fieldOfStudy startDate endDate source }
    locales { language country proficiency source }
    skills { name }
    files { fileId parsedAt shouldBeParsed file { id name mimeType etag } }
    emailAddresses { email source }
    phoneNumbers { number type source whatsappIdentifier }
    historyEntries(
      where: { deletedAt: { _is_null: true }, messageWithConfidentialitySettings: { _is_null: false } },
      order_by: { occurredAt: desc },
      limit: 20
    ) {
      id
      type
      source
      occurredAt
      subjectWithConfidentialitySettings
      messageWithConfidentialitySettings
      isConfidential
      subType { id name }
      sender { id displayName }
      project { id name }
    }
    fieldsValues(where: { deletedAt: { _is_null: true } }) {
      id
      fieldId
      fieldValueId
      value
      field { id name type }
      fieldValue { id name }
      location { locality countryName countryShortName formattedAddress search }
    }
  }
}
GRAPHQL;

    private const string PROFILE_QUERY = <<<'GRAPHQL'
query ShowcaseRecruiterWebappSaasProfile($projectId: uuid!, $profileId: uuid!) {
  profiles(
    where: {
      _and: [
        { id: { _eq: $profileId } },
        { associations: { projectId: { _eq: $projectId } } }
      ]
    },
    limit: 1
  ) {
    id
    firstName
    lastName
    source
    countResumes
    linkedinId
    headline
    summary
    updatedAt
    lastInteractionDate
    lastInteractionType
    profileUrl
    image { publicUrl }
    locations { source location { locality countryName countryShortName formattedAddress search } }
    positions { id title companyName startDate endDate source linkedinCompanyId linkedinCompanyData { id name } }
    educations { id school { id name } title degreeName fieldOfStudy startDate endDate source }
    locales { language country proficiency source }
    skills { name }
    files { fileId parsedAt shouldBeParsed file { id name mimeType etag } }
    emailAddresses { email source }
    phoneNumbers { number type source whatsappIdentifier }
    historyEntries(
      where: { deletedAt: { _is_null: true }, messageWithConfidentialitySettings: { _is_null: false } },
      order_by: { occurredAt: desc },
      limit: 20
    ) {
      id
      type
      source
      occurredAt
      subjectWithConfidentialitySettings
      messageWithConfidentialitySettings
      isConfidential
      subType { id name }
      sender { id displayName }
      project { id name }
    }
    fieldsValues(where: { deletedAt: { _is_null: true } }) {
      id
      fieldId
      fieldValueId
      value
      field { id name type }
      fieldValue { id name }
      location { locality countryName countryShortName formattedAddress search }
    }
  }
}
GRAPHQL;

    private const string PERIMETER_IDS_QUERY = <<<'GRAPHQL'
query ShowcaseRecruiterWebappSaasProfileIds($projectId: uuid!, $limit: Int!, $offset: Int!) {
  profiles(
    where: { associations: { projectId: { _eq: $projectId } } },
    order_by: { updatedAt: asc },
    limit: $limit,
    offset: $offset
  ) {
    id
  }
}
GRAPHQL;

    public function __construct(
        private HttpClientInterface $jarviGraphqlClient,
        private string $projectId,
    ) {
    }

    /**
     * Générateur : une page reste vivante le temps d'être consommée, pas tout le périmètre.
     * Conséquence pour l'appelant — l'appel HTTP part à la première itération, pas ici, et une
     * erreur réseau surgit donc au milieu de la boucle de consommation.
     */
    public function fetchProfiles(?DateTimeImmutable $since = null, ?int $maxProfiles = null): iterable
    {
        $yielded = 0;
        $offset = 0;

        do {
            $limit = $this->pageLimit($maxProfiles, $yielded);
            if (0 === $limit) {
                break;
            }

            $variables = [
                'projectId' => $this->projectId,
                'limit' => $limit,
                'offset' => $offset,
            ];
            if (null !== $since) {
                $variables['updatedAfter'] = $since->format(DateTimeInterface::ATOM);
            }

            $rows = $this->graphqlRows(
                null === $since ? self::PROFILES_QUERY : self::UPDATED_PROFILES_QUERY,
                $variables,
                'profiles',
            );

            foreach ($rows as $row) {
                $profile = $this->toProfile($row);
                if (null !== $profile) {
                    ++$yielded;
                    yield $profile;
                }
            }

            $offset += $limit;
        } while (\count($rows) === $limit);
    }

    public function listPerimeterProfileIds(?int $maxProfiles = null): array
    {
        $ids = [];
        $offset = 0;

        do {
            $limit = $this->pageLimit($maxProfiles, \count($ids));
            if (0 === $limit) {
                break;
            }

            $rows = $this->graphqlRows(self::PERIMETER_IDS_QUERY, [
                'projectId' => $this->projectId,
                'limit' => $limit,
                'offset' => $offset,
            ], 'profiles');

            foreach ($rows as $row) {
                $id = $this->asString($row['id'] ?? null);
                if (null !== $id) {
                    $ids[] = $id;
                }
            }

            $offset += $limit;
        } while (\count($rows) === $limit);

        return $ids;
    }

    public function fetchProfile(string $jarviProfileId): ?JarviProfile
    {
        $rows = $this->graphqlRows(self::PROFILE_QUERY, [
            'projectId' => $this->projectId,
            'profileId' => $jarviProfileId,
        ], 'profiles');
        $row = $rows[0] ?? null;

        return null !== $row ? $this->toProfile($row) : null;
    }

    private function pageLimit(?int $maxProfiles, int $alreadyFetched): int
    {
        if (null === $maxProfiles) {
            return self::PAGE_SIZE;
        }

        return max(0, min(self::PAGE_SIZE, $maxProfiles - $alreadyFetched));
    }

    /**
     * @param array<string, mixed> $variables
     *
     * @return list<array<string, mixed>>
     */
    private function graphqlRows(string $query, array $variables, string $key): array
    {
        $payload = $this->jarviGraphqlClient->request('POST', '', [
            'json' => [
                'query' => $query,
                'variables' => $variables,
            ],
        ])->toArray(false);

        if (isset($payload['errors'])) {
            throw new RuntimeException('Erreur GraphQL Jarvi: '.json_encode($payload['errors'], \JSON_THROW_ON_ERROR));
        }

        $rows = $payload['data'][$key] ?? [];
        if (!\is_array($rows)) {
            return [];
        }

        $result = [];
        foreach ($rows as $row) {
            if (\is_array($row)) {
                $result[] = $row;
            }
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $raw
     */
    private function toProfile(array $raw): ?JarviProfile
    {
        $id = $this->asString($raw['id'] ?? null);
        $firstName = $this->asString($raw['firstName'] ?? null);
        $lastName = $this->asString($raw['lastName'] ?? null);
        if (null === $id || null === $firstName || null === $lastName) {
            return null;
        }

        $profileLocation = $this->firstArray($raw['locations'] ?? null);
        $location = \is_array($profileLocation['location'] ?? null) ? $profileLocation['location'] : [];

        return new JarviProfile(
            $id,
            $firstName,
            $lastName,
            $this->asString($raw['headline'] ?? null),
            $this->asString($location['locality'] ?? null),
            $this->asString($location['countryShortName'] ?? null) ?? $this->asString($location['countryName'] ?? null),
            $this->asDate($raw['updatedAt'] ?? null) ?? new DateTimeImmutable(),
            $this->asString($raw['summary'] ?? null),
            $this->photoUrl($raw['image'] ?? null),
            $this->arrayRows($raw['positions'] ?? null),
            $this->arrayRows($raw['educations'] ?? null),
            $this->arrayRows($raw['locales'] ?? null),
            $this->skills($raw['skills'] ?? null),
            $this->resumes($raw['files'] ?? null),
            $this->contacts($raw),
            $this->rawPayload($raw),
            $this->asDate($raw['lastInteractionDate'] ?? null),
            $this->asString($raw['lastInteractionType'] ?? null),
        );
    }

    private function asString(mixed $value): ?string
    {
        return \is_string($value) && '' !== $value ? $value : null;
    }

    private function asDate(mixed $value): ?DateTimeImmutable
    {
        if (!\is_string($value) || '' === $value) {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (Exception) {
            return null;
        }
    }

    /** @return array<string, mixed> */
    private function firstArray(mixed $value): array
    {
        if (!\is_array($value)) {
            return [];
        }

        $first = $value[0] ?? null;

        return \is_array($first) ? $first : [];
    }

    /** @return list<array<string, mixed>> */
    private function arrayRows(mixed $value): array
    {
        if (!\is_array($value)) {
            return [];
        }

        $rows = [];
        foreach ($value as $row) {
            if (\is_array($row)) {
                $rows[] = $this->withoutTypename($row);
            }
        }

        return $rows;
    }

    /**
     * Photo de profil : on ne garde que l'URL publique servie par le storage Jarvi. `imageId` n'est
     * PAS l'identifiant du fichier dans cette URL (deux UUID distincts) : sans `publicUrl`, l'URL
     * est irreconstructible.
     */
    private function photoUrl(mixed $image): ?string
    {
        return \is_array($image) ? $this->asString($image['publicUrl'] ?? null) : null;
    }

    /** @return list<string> */
    private function skills(mixed $value): array
    {
        $skills = [];
        foreach ($this->arrayRows($value) as $row) {
            $name = $this->asString($row['name'] ?? null);
            if (null !== $name) {
                $skills[] = $name;
            }
        }

        return $skills;
    }

    /** @return list<array<string, mixed>> */
    private function resumes(mixed $value): array
    {
        $resumes = [];
        foreach ($this->arrayRows($value) as $row) {
            $file = \is_array($row['file'] ?? null) ? $row['file'] : [];
            $resumes[] = [
                'fileId' => $this->asString($row['fileId'] ?? null) ?? $this->asString($file['id'] ?? null),
                'name' => $this->asString($file['name'] ?? null) ?? $this->asString($row['name'] ?? null),
                'mimeType' => $this->asString($file['mimeType'] ?? null) ?? $this->asString($row['mimeType'] ?? null),
                'etag' => $this->asString($file['etag'] ?? null),
                'parsedAt' => $this->asString($row['parsedAt'] ?? null),
                'shouldBeParsed' => $this->asBool($row['shouldBeParsed'] ?? null),
            ];
        }

        return $resumes;
    }

    /**
     * @param array<string, mixed> $raw
     *
     * @return list<array<string, mixed>>
     */
    private function contacts(array $raw): array
    {
        $contacts = [];
        foreach ($this->arrayRows($raw['emailAddresses'] ?? null) as $row) {
            $email = $this->asString($row['email'] ?? null);
            if (null !== $email) {
                $contacts[] = ['type' => 'email', 'value' => $email, 'source' => $this->asString($row['source'] ?? null)];
            }
        }
        foreach ($this->arrayRows($raw['phoneNumbers'] ?? null) as $row) {
            $number = $this->asString($row['number'] ?? null);
            if (null !== $number) {
                $contacts[] = [
                    'type' => 'phone',
                    'value' => $number,
                    'source' => $this->asString($row['source'] ?? null),
                    // Identifiant WhatsApp brut (JID) tel que Jarvi le fournit : la réplica reste
                    // opaque, la dérivation en numéro joignable vit à l'exposition.
                    'whatsappIdentifier' => $this->asString($row['whatsappIdentifier'] ?? null),
                ];
            }
        }

        return $contacts;
    }

    /**
     * @param array<string, mixed> $raw
     *
     * @return array<string, mixed>
     */
    private function rawPayload(array $raw): array
    {
        return [
            'source' => $this->asString($raw['source'] ?? null),
            'countResumes' => $this->asInt($raw['countResumes'] ?? null),
            'linkedinId' => $this->asInt($raw['linkedinId'] ?? null),
            'profileUrl' => $this->asString($raw['profileUrl'] ?? null),
            'locations' => $this->arrayRows($raw['locations'] ?? null),
            'historyEntries' => $this->arrayRows($raw['historyEntries'] ?? null),
            'fieldsValues' => $this->arrayRows($raw['fieldsValues'] ?? null),
        ];
    }

    private function asInt(mixed $value): ?int
    {
        return \is_int($value) ? $value : null;
    }

    private function asBool(mixed $value): ?bool
    {
        return \is_bool($value) ? $value : null;
    }

    /**
     * @param array<array-key, mixed> $row
     *
     * @return array<array-key, mixed>
     */
    private function withoutTypename(array $row): array
    {
        unset($row['__typename']);

        foreach ($row as $key => $value) {
            if (\is_array($value)) {
                $row[$key] = $this->withoutTypename($value);
            }
        }

        return $row;
    }
}
