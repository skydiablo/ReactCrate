<?php

namespace SkyDiablo\ReactCrate\ClientSelection;

use SkyDiablo\ReactCrate\ClientInterface;

interface DelegatesToClientInterface
{
    public function getDelegatedClient(): ClientInterface;
}
