<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Input\PasswordSetupConsumeInput;
use App\ApiResource\Output\PasswordSetupOutput;
use App\Domain\Auth\PasswordSetupConsumer;
use App\Domain\Auth\PasswordSetupTokenNotConsumableException;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;

/**
 * @implements ProcessorInterface<PasswordSetupConsumeInput, PasswordSetupOutput>
 */
final readonly class PasswordSetupConsumeProcessor implements ProcessorInterface
{
    public function __construct(private PasswordSetupConsumer $consumer)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): PasswordSetupOutput
    {
        try {
            $email = $this->consumer->consume($data->token, $data->password);
        } catch (PasswordSetupTokenNotConsumableException $e) {
            throw new GoneHttpException($e->getMessage(), $e);
        }

        return new PasswordSetupOutput(email: $email);
    }
}
