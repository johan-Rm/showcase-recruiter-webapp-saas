<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Input\CandidateOnboardingInput;
use App\ApiResource\Output\MeOutput;
use App\Domain\Candidate\CandidateOnboarder;
use App\Domain\Candidate\InvalidResumeException;
use App\Domain\Candidate\ResumeValidator;
use App\Domain\Organization\EmailAlreadyRegisteredException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * POST /v1/candidates/onboarding : inscription candidat (public). Crée compte + projection ; la
 * remontée Jarvi `/applicants` part ensuite en asynchrone depuis le domaine (tâche 089) —
 * l'inscription n'attend jamais Jarvi. Renvoie le contexte « Me » du nouveau compte.
 * Email déjà inscrit → 409 ; CV non conforme → 422.
 *
 * @implements ProcessorInterface<CandidateOnboardingInput, MeOutput>
 */
final readonly class CandidateOnboardingProcessor implements ProcessorInterface
{
    public function __construct(
        private CandidateOnboarder $onboarder,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): MeOutput
    {
        // CV validé au plus tôt (avant toute création) : un fichier non conforme = 422, rien n'est créé.
        try {
            $resume = ResumeValidator::validate($data->cvFileName, $data->cvContent);
        } catch (InvalidResumeException $e) {
            throw new UnprocessableEntityHttpException($e->getMessage(), $e);
        }

        try {
            $candidate = $this->onboarder->onboard(
                $data->email,
                $data->password,
                $data->givenName,
                $data->familyName,
                $data->linkedinUrl,
                $data->country,
                $data->city,
                $resume['fileName'] ?? null,
                $resume['content'] ?? null,
            );
        } catch (EmailAlreadyRegisteredException $e) {
            throw new ConflictHttpException($e->getMessage(), $e);
        }

        $person = $candidate->getPerson();

        return new MeOutput(
            $person->getUuid()->toString(),
            $data->email,
            $person->givenName,
            $person->familyName,
            ['ROLE_CANDIDATE', 'ROLE_USER'],
            candidate: true,
            recruiter: false,
            candidateId: $candidate->getUuid()->toString(),
            // La projection porte toujours son conteneur de réplica (PENDING à l'inscription).
            candidateSyncStatus: $candidate->getJarvi()->syncStatus->value,
        );
    }
}
