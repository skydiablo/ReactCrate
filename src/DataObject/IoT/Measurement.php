<?php
declare(strict_types=1);

namespace SkyDiablo\ReactCrate\DataObject\IoT;

class Measurement
{

    protected \DateTimeInterface $time;
    protected string $measurement;
    protected array $tags;
    protected array $fields;

    public function __construct(\DateTimeInterface $time = null, string $measurement = '', array $tags = [], array $fields = [])
    {
        $this->time = $time;
        $this->measurement = $measurement;
        $this->tags = $tags;
        $this->fields = $fields;
    }

    public function setTime(\DateTimeInterface $time): static
    {
        $this->time = $time;
        return $this;
    }

    public function setMeasurement(string $measurement): static
    {
        $this->measurement = $measurement;
        return $this;
    }

    public function setFields(array $fields): static
    {
        $this->fields = $fields;
        return $this;
    }

    public function setTags(array $tags): static
    {
        $this->tags = $tags;
        return $this;
    }

    public function getTime(): \DateTimeInterface
    {
        return $this->time;
    }

    public function getMeasurement(): string
    {
        return $this->measurement;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function getFields(): array
    {
        return $this->fields;
    }


}