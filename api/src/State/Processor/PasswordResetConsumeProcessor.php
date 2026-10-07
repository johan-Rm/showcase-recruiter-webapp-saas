<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Input\PasswordResetConsumeInput;
use App\ApiResource\Output\PasswordResetOutput;
use App\Domain\Auth\PasswordResetConsumer;
use App\Domain\Auth\PasswordResetTokenNotConsumableException;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;

/**
 * @implements ProcessorInterface<PasswordResetConsumeInput, PasswordResetOutput>
 */
final readonly class PasswordResetConsumeProcessor implements ProcessorInterface
{
    public function __construct(private PasswordResetConsumer $consumer)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): PasswordResetOutput
    {
        try {
            $this->consumer->consume($data->token, $data->newPassword);
        } catch (PasswordResetTokenNotConsumableException $e) {
            throw new GoneHttpException($e->getMessage(), $e);
        }

        return new PasswordResetOutput();
    }
}
