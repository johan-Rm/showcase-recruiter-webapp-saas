<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Input\MagicLinkRequestInput;
use App\ApiResource\Output\MagicLinkRequestOutput;
use App\Domain\Auth\MagicLinkIssuer;
use App\Enum\MagicLinkAudience;

/**
 * @implements ProcessorInterface<MagicLinkRequestInput, MagicLinkRequestOutput>
 */
final readonly class MagicLinkRequestProcessor implements ProcessorInterface
{
    public function __construct(private MagicLinkIssuer $issuer)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): MagicLinkRequestOutput
    {
        $this->issuer->request($data->email, MagicLinkAudience::tryFrom($data->space ?? ''));

        return new MagicLinkRequestOutput();
    }
}
