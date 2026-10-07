<?php

declare(strict_types=1);

namespace App\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\Input\AcceptInvitationInput;
use App\ApiResource\Input\CandidateOnboardingInput;
use App\ApiResource\Input\ChangePasswordInput;
use App\ApiResource\Input\ConsentInput;
use App\ApiResource\Input\DifferentiatorsInput;
use App\ApiResource\Input\FavoriteInput;
use App\ApiResource\Input\InvitationInput;
use App\ApiResource\Input\LoginInput;
use App\ApiResource\Input\MagicLinkConsumeInput;
use App\ApiResource\Input\MagicLinkRequestInput;
use App\ApiResource\Input\MoneyInput;
use App\ApiResource\Input\OrganizationInput;
use App\ApiResource\Input\PasswordResetConsumeInput;
use App\ApiResource\Input\PasswordResetRequestInput;
use App\ApiResource\Input\PasswordSetupConsumeInput;
use App\ApiResource\Input\PresencePeriodInput;
use App\ApiResource\Input\ProfileNoteInput;
use App\ApiResource\Input\RevealInput;
use App\ApiResource\Input\SavedSearchInput;
use App\ApiResource\Input\VisibilityInput;
use App\ApiResource\Output\ValidationRulesOutput;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadataInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * GET /v1/validation-rules/{form} : expose les contraintes Symfony Validator des DTO d'entrée.
 *
 * @implements ProviderInterface<ValidationRulesOutput>
 */
final readonly class ValidationRulesProvider implements ProviderInterface
{
    /** @var array<string, class-string> */
    private const array FORMS = [
        'acceptInvitation' => AcceptInvitationInput::class,
        'authLogin' => LoginInput::class,
        'candidateOnboarding' => CandidateOnboardingInput::class,
        'changePassword' => ChangePasswordInput::class,
        'consent' => ConsentInput::class,
        'differentiators' => DifferentiatorsInput::class,
        'favorite' => FavoriteInput::class,
        'invitation' => InvitationInput::class,
        'magicLinkConsume' => MagicLinkConsumeInput::class,
        'magicLinkRequest' => MagicLinkRequestInput::class,
        'money' => MoneyInput::class,
        'organization' => OrganizationInput::class,
        'passwordResetConsume' => PasswordResetConsumeInput::class,
        'passwordResetRequest' => PasswordResetRequestInput::class,
        'passwordSetupConsume' => PasswordSetupConsumeInput::class,
        'presencePeriod' => PresencePeriodInput::class,
        'profileNote' => ProfileNoteInput::class,
        'reveal' => RevealInput::class,
        'savedSearch' => SavedSearchInput::class,
        'visibility' => VisibilityInput::class,
    ];

    public function __construct(private ValidatorInterface $validator)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ValidationRulesOutput
    {
        $form = $uriVariables['form'] ?? null;
        if (!\is_string($form) || !isset(self::FORMS[$form])) {
            throw new NotFoundHttpException('Unknown validation form.');
        }

        $inputClass = self::FORMS[$form];
        $metadata = $this->validator->getMetadataFor($inputClass);
        if (!$metadata instanceof ClassMetadataInterface) {
            throw new NotFoundHttpException('Unknown validation form.');
        }

        $fields = [];
        $classRules = [];

        foreach ($metadata->getConstraints() as $constraint) {
            $classRules[] = $this->normalizeConstraint($constraint);
        }

        foreach ($metadata->getConstrainedProperties() as $property) {
            $rules = [];
            foreach ($metadata->getPropertyMetadata($property) as $propertyMetadata) {
                foreach ($propertyMetadata->getConstraints() as $constraint) {
                    $rules[] = $this->normalizeConstraint($constraint);
                }
            }

            if ([] !== $rules) {
                $fields[$property] = $rules;
            }
        }

        return new ValidationRulesOutput($form, $inputClass, $fields, $classRules);
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeConstraint(Constraint $constraint): array
    {
        $rule = [
            'type' => $this->ruleType($constraint),
            'groups' => $this->groups($constraint),
        ];

        $properties = get_object_vars($constraint);
        if (isset($properties['message']) && \is_string($properties['message'])) {
            $rule['message'] = $properties['message'];
        }

        if ($constraint instanceof Assert\Length) {
            $rule['min'] = $constraint->min;
            $rule['max'] = $constraint->max;
            $rule['exactly'] = $constraint->min === $constraint->max ? $constraint->min : null;
        }

        if ($constraint instanceof Assert\GreaterThanOrEqual) {
            $rule['value'] = $constraint->value;
            $rule['propertyPath'] = $constraint->propertyPath;
        }

        if ($constraint instanceof Assert\Choice) {
            $rule['choices'] = $this->resolveChoices($constraint);
            $rule['multiple'] = $constraint->multiple;
            $rule['min'] = $constraint->min;
            $rule['max'] = $constraint->max;
        }

        if ($constraint instanceof Assert\Type) {
            $rule['expectedType'] = $constraint->type;
        }

        if ($constraint instanceof Assert\Url) {
            $rule['requireTld'] = $constraint->requireTld;
        }

        return $rule;
    }

    /**
     * @return list<string>
     */
    private function groups(Constraint $constraint): array
    {
        $groups = $constraint->groups;

        return null === $groups ? [] : array_values($groups);
    }

    private function ruleType(Constraint $constraint): string
    {
        return match (true) {
            $constraint instanceof Assert\NotBlank => 'notBlank',
            $constraint instanceof Assert\NotNull => 'notNull',
            $constraint instanceof Assert\Email => 'email',
            $constraint instanceof Assert\Length => 'length',
            $constraint instanceof Assert\Url => 'url',
            $constraint instanceof Assert\Uuid => 'uuid',
            $constraint instanceof Assert\PositiveOrZero => 'positiveOrZero',
            $constraint instanceof Assert\GreaterThanOrEqual => 'greaterThanOrEqual',
            $constraint instanceof Assert\Choice => 'choice',
            $constraint instanceof Assert\Type => 'type',
            $constraint instanceof Assert\Valid => 'valid',
            default => $constraint::class,
        };
    }

    /**
     * @return list<mixed>|null
     */
    private function resolveChoices(Assert\Choice $constraint): ?array
    {
        if (\is_array($constraint->choices)) {
            return array_values($constraint->choices);
        }

        if (\is_callable($constraint->callback)) {
            $choices = ($constraint->callback)();

            return \is_array($choices) ? array_values($choices) : null;
        }

        return null;
    }
}
