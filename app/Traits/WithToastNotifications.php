<?php

namespace App\Traits;

trait WithToastNotifications
{
    /**
     * Show a success toast notification
     */
    public function success(string $message): void
    {
        $this->dispatch('toast', ['type' => 'success', 'message' => $message]);
    }

    /**
     * Show an error toast notification
     */
    public function error(string $message): void
    {
        $this->dispatch('toast', ['type' => 'error', 'message' => $message]);
    }

    /**
     * Show a warning toast notification
     */
    public function warning(string $message): void
    {
        $this->dispatch('toast', ['type' => 'warning', 'message' => $message]);
    }

    /**
     * Show an info toast notification
     */
    public function info(string $message): void
    {
        $this->dispatch('toast', ['type' => 'info', 'message' => $message]);
    }

    /**
     * Show a toast notification
     */
    public function toast(string $type, string $message): void
    {
        $this->dispatch('toast', ['type' => $type, 'message' => $message]);
    }
}
