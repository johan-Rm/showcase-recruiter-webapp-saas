<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Output\CollectionOutput;
use App\ApiResource\Output\SuggestionOutput;
use App\Domain\Organization\CurrentRecruiterResolver;
use App\Domain\Search\SuggestionFinder;
use App\Entity\Recruiter;
use App\Entity\User;
use App\Enum\SuggestionKind;
use App\Enum\SuggestionType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * GET /v1/suggestions?type=JOB|SKILL&q=… — ce qu'on propose pendant la frappe.
 *
 * Réservé aux recruteurs d'une organisation validée, comme la recherche : les propositions décrivent
 * le vivier (ce qu'on y trouve, et en quelle quantité), elles ne sont pas publiques.
 *
 * Un terme trop court ne propose RIEN plutôt que de servir les termes les plus fréquents du vivier :
 * sur une lettre, la liste ne renseigne pas, elle occupe l'écran.
 *
 * @implements ProviderInterface<CollectionOutput<SuggestionOutput>>
 */
final readonly class SuggestionProvider implements ProviderInterface
{
    /** En deçà, on ne propose pas : une lettre ne dit rien de ce qu'on cherche. */
    private const int MIN_TERM_LENGTH = 2;

    private const int DEFAULT_LIMIT = 10;

    public function __construct(
        private SuggestionFinder $suggestions,
        private Security $security,
        private RequestStack $requestStack,
        private CurrentRecruiterResolver $currentRecruiterResolver,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CollectionOutput
    {
        $user = $this->security->getUser();
        $recruiter = $user instanceof User
            ? $this->currentRecruiterResolver->resolveFirstActive($user)
            : null;
        if (!$recruiter instanceof Recruiter || $recruiter->organization->isRejected()) {
            throw new AccessDeniedHttpException();
        }

        $request = $this->requestStack->getCurrentRequest() ?? new Request();
        $term = trim($request->query->get('q', ''));
        $type = SuggestionType::tryFrom($request->query->get('type', '')) ?? SuggestionType::SKILL;
        $limit = $request->query->getInt('limit', self::DEFAULT_LIMIT);

        if (mb_strlen($term) < self::MIN_TERM_LENGTH) {
            return new CollectionOutput([], 0, 1, 0);
        }

        // La liste servie est HOMOGÈNE : chaque passage du finder ne joue que si le précédent n'a
        // rien ramené, donc sa nature vaut pour toute la liste. Elle est portée par chaque
        // proposition quand même — un client qui en garde une isolée doit savoir ce qu'elle promet.
        if (SuggestionType::JOB === $type) {
            $jobs = $this->suggestions->jobs($term, $limit);
            $items = self::wrap($jobs['values'], $jobs['kind']);
        } else {
            $items = self::wrap($this->suggestions->skills($term, $limit), SuggestionKind::LITERAL);
        }

        return new CollectionOutput($items, \count($items), 1, \count($items));
    }

    /**
     * @param list<string> $values
     *
     * @return list<SuggestionOutput>
     */
    private static function wrap(array $values, SuggestionKind $kind): array
    {
        return array_map(
            static fn (string $value): SuggestionOutput => new SuggestionOutput($value, $kind),
            $values,
        );
    }
}
