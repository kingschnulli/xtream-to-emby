<?php

namespace App\Service\Xtream\Struct;

class XtreamUser
{
    private ?string $username;

    private ?string $password;

    private ?string $host;

    public function __construct(?string $username, ?string $password, ?string $host)
    {
        $this->username = $username;
        $this->password = $password;
        $this->host = $host;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): void
    {
        $this->username = $username;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): void
    {
        $this->password = $password;
    }

    public function getHost(): ?string
    {
        return $this->host;
    }

    public function setHost(?string $host): void
    {
        $this->host = $host;
    }

}