<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Input\PasswordResetRequestInput;
use App\ApiResource\Output\PasswordResetOutput;
use App\Domain\Auth\PasswordResetIssuer;

/**
 * @implements ProcessorInterface<PasswordResetRequestInput, PasswordResetOutput>
 */
final readonly class PasswordResetRequestProcessor implements ProcessorInterface
{
    public function __construct(private PasswordResetIssuer $issuer)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): PasswordResetOutput
    {
        $this->issuer->request($data->email);

        return new PasswordResetOutput();
    }
}
