<?php

namespace App;

use App\Contracts\SessionInterface;
use App\DTO\SessionConfig;
use App\Exception\SessionException;

class Session implements SessionInterface
{

    public function __construct(
        private readonly SessionConfig $config
    ){}


    public function start(): void
    {
        if ($this->isActive()){
            throw new SessionException("Session has already been started");
        }

        if(headers_sent($fileName, $line)){
            throw new SessionException("Headers have been already sent, file: " . $fileName . " , line: " . $line);
        }

        session_set_cookie_params([
            'secure'   => $this->config->secure,
            'httponly' => $this->config->httponly,
            'samesite' => $this->config->sameSite->value,
        ]);

        if(! empty($this->config->name)){
            session_name($this->config->name);
        }

        session_start();

    }

    public function save(): void
    {
        // TODO: Implement save() method.
    }

    private function isActive() : bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    public function regenerate(): bool
    {
        return session_regenerate_id();
    }

    public function has(string $key) : bool
    {
        return array_key_exists($key, $_SESSION);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->has($key) ? $_SESSION[$key] : $default;
    }

    public function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function flash(string $key, array $messages): void
    {
        $_SESSION[$this->config->flashName][$key] = $messages;
    }

    public function getFlash(string $key): array
    {
        $messages = $_SESSION[$this->config->flashName][$key] ?? [];

        unset($_SESSION[$this->config->flashName][$key]);

        return $messages;
    }
}