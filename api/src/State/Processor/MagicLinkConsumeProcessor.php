<?php

declare(strict_types=1);

namespace App\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Input\MagicLinkConsumeInput;
use App\ApiResource\Output\MagicLinkConsumeOutput;
use App\Domain\Auth\MagicLinkConsumer;
use App\Domain\Auth\MagicLinkNotConsumableException;
use Symfony\Component\HttpKernel\Exception\GoneHttpException;

/**
 * @implements ProcessorInterface<MagicLinkConsumeInput, MagicLinkConsumeOutput>
 */
final readonly class MagicLinkConsumeProcessor implements ProcessorInterface
{
    public function __construct(private MagicLinkConsumer $consumer)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): MagicLinkConsumeOutput
    {
        try {
            $authenticated = $this->consumer->consume($data->token);
        } catch (MagicLinkNotConsumableException $e) {
            throw new GoneHttpException($e->getMessage(), $e);
        }

        return new MagicLinkConsumeOutput(
            $authenticated->token,
            $authenticated->email,
            $authenticated->userId,
        );
    }
}
