<?php

namespace SkyDiablo\ReactCrate\ClientSelection;

use SkyDiablo\ReactCrate\ClientInterface;

class CustomClientSelector implements ClientSelectorInterface
{
    /**
     * @var callable
     */
    private $resolver;

    public function __construct(callable $resolver)
    {
        $this->resolver = $resolver;
    }

    public function selectClient(array $clients, string $statement, array $arguments = []): ClientInterface
    {
        if ($clients === []) {
            throw new \InvalidArgumentException('No clients available for selection.');
        }

        $selected = ($this->resolver)($clients, $statement, $arguments);

        if (!$selected instanceof ClientInterface) {
            throw new \InvalidArgumentException('Custom selector must return an instance of ClientInterface.');
        }

        if (!in_array($selected, $clients, true)) {
            throw new \InvalidArgumentException('Custom selector must return one of the configured clients.');
        }

        return $selected;
    }
}
