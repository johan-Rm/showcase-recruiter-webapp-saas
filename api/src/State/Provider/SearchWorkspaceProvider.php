<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Output\SearchWorkspaceOutput;
use App\Domain\Organization\CurrentRecruiterResolver;
use App\Entity\Recruiter;
use App\Entity\User;
use App\State\SearchWorkspaceBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * GET /v1/searches/workspace : relais vers le constructeur du plan de travail.
 *
 * Le périmètre est celui de la collection — mes recherches et celles que l'équipe a partagées —,
 * restreint aux **ouvertes**. Aucun paramètre : le plan de travail est celui du porteur du jeton, il
 * ne se demande pas pour quelqu'un d'autre.
 *
 * @implements ProviderInterface<SearchWorkspaceOutput>
 */
final readonly class SearchWorkspaceProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private CurrentRecruiterResolver $currentRecruiterResolver,
        private SearchWorkspaceBuilder $builder,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): SearchWorkspaceOutput
    {
        return $this->builder->build($this->currentRecruiter());
    }

    private function currentRecruiter(): Recruiter
    {
        $user = $this->security->getUser();
        $recruiter = $user instanceof User
            ? $this->currentRecruiterResolver->resolveFirstActive($user)
            : null;

        // Une organisation rejetée n'a pas de plan de travail : même refus que la collection.
        if (!$recruiter instanceof Recruiter || $recruiter->organization->isRejected()) {
            throw new AccessDeniedHttpException();
        }

        return $recruiter;
    }
}
