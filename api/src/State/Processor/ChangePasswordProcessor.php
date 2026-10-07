<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Input\ChangePasswordInput;
use App\ApiResource\Output\ChangePasswordOutput;
use App\Domain\Auth\InvalidCurrentPasswordException;
use App\Domain\Auth\PasswordChanger;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * @implements ProcessorInterface<ChangePasswordInput, ChangePasswordOutput>
 */
final readonly class ChangePasswordProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private PasswordChanger $passwordChanger,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): ChangePasswordOutput
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException();
        }

        try {
            $this->passwordChanger->change($user, $data->currentPassword, $data->newPassword);
        } catch (InvalidCurrentPasswordException $e) {
            throw new AccessDeniedHttpException($e->getMessage(), $e);
        }

        return new ChangePasswordOutput();
    }
}
