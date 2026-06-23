<?php

namespace SkyDiablo\ReactCrate\ClientSelection;

use SkyDiablo\ReactCrate\ClientInterface;

class FailoverClientSelector implements ClientSelectorInterface
{
    public function __construct(
        private readonly ClientSelectorInterface $selector,
        private readonly ?int $maxTries = null,
    ) {
        if ($maxTries !== null && $maxTries < 1) {
            throw new \InvalidArgumentException('maxTries must be at least 1.');
        }
    }

    public function selectClient(array $clients, string $statement, array $arguments = []): ClientInterface
    {
        $selected = $this->selector->selectClient($clients, $statement, $arguments);

        return new FailoverClient(
            $selected,
            $clients,
            $this->selector,
            $statement,
            $arguments,
            $this->maxTries,
        );
    }
}
