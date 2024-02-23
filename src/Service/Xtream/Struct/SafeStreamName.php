<?php

namespace App\Service\Xtream\Struct;

class SafeStreamName
{
    private ?string $name;
    private ?string $firstLetter;

    public function __construct(string $name, string $firstLetter)
    {
        $this->name = $name;
        $this->firstLetter = $firstLetter;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getFirstLetter(): ?string
    {
        return $this->firstLetter;
    }

    public function setFirstLetter(?string $firstLetter): void
    {
        $this->firstLetter = $firstLetter;
    }


}