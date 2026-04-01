<?php

namespace SkyDiablo\ReactCrate\ClientSelection;

use SkyDiablo\ReactCrate\ClientInterface;

interface TracksClientLoadInterface
{
    public function markRequestStart(ClientInterface $client): void;

    public function markRequestEnd(ClientInterface $client): void;
}
