<?php

declare(strict_types=1);

namespace App\Support;

use Inertia\Inertia;

/**
 * Toast helper class for creating flash notifications.
 *
 * Provides a clean, fluent API for creating toast notifications that
 * mirrors the Sonner/Shadcn UI toast signature on the frontend.
 *
 * @example
 * ```php
 * // Simple success toast
 * Toast::success('Feature created successfully!')->flash();
 *
 * // With description
 * Toast::error('Failed to process')
 *     ->description('Please check your input and try again')
 *     ->flash();
 *
 * // With custom duration
 * Toast::info('Processing in background')
 *     ->duration(6000)
 *     ->flash();
 * ```
 */
final class Toast
{
    private string $type;

    private string $message;

    private ?string $description = null;

    private ?int $duration = null;

    private function __construct(string $type, string $message)
    {
        $this->type = $type;
        $this->message = $message;
    }

    /**
     * Create a success toast.
     */
    public static function success(string $message): self
    {
        return new self('success', $message);
    }

    /**
     * Create an error toast.
     */
    public static function error(string $message): self
    {
        return new self('error', $message);
    }

    /**
     * Create an info toast.
     */
    public static function info(string $message): self
    {
        return new self('info', $message);
    }

    /**
     * Create a warning toast.
     */
    public static function warning(string $message): self
    {
        return new self('warning', $message);
    }

    /**
     * Set the toast description (subtitle).
     */
    public function description(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Set the toast duration in milliseconds.
     */
    public function duration(int $milliseconds): self
    {
        $this->duration = $milliseconds;

        return $this;
    }

    /**
     * Flash the toast to the session for Inertia.
     */
    public function flash(): void
    {
        $toastData = [
            'type' => $this->type,
            'message' => $this->message,
        ];

        if ($this->description !== null) {
            $toastData['description'] = $this->description;
        }

        if ($this->duration !== null) {
            $toastData['duration'] = $this->duration;
        }

        // Get existing Inertia flash data
        $flashData = Inertia::getFlashed();

        // Get existing toasts from flash data or initialize empty array
        $existingToasts = $flashData['toasts'] ?? [];

        // Add new toast
        $existingToasts[] = $toastData;

        // Update toasts in flash data
        $flashData['toasts'] = $existingToasts;

        // Flash to session using Inertia's session key
        // This ensures Inertia handles clearing history properly
        Inertia::flash($flashData);
    }

    /**
     * Convert toast to array (for testing).
     */
    public function toArray(): array
    {
        $data = [
            'type' => $this->type,
            'message' => $this->message,
        ];

        if ($this->description !== null) {
            $data['description'] = $this->description;
        }

        if ($this->duration !== null) {
            $data['duration'] = $this->duration;
        }

        return $data;
    }
}
