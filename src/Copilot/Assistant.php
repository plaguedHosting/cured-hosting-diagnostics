<?php
namespace Cured\Copilot;

use Cured\Contracts\Module_Interface;

final class Assistant implements Module_Interface {
    private bool $locked = false;

    public function boot(): void {
        // Developer helper utilities will go here later.
    }

    public function requires_license(): bool {
        return false;
    }

    public function set_locked(bool $locked): void {
        $this->locked = $locked;
    }
}
