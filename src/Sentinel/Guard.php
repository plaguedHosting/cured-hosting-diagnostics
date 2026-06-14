<?php
namespace Cured\Sentinel;

use Cured\Contracts\Module_Interface;

final class Guard implements Module_Interface {
    private bool $locked = false;

    public function boot(): void {
        // Security hooks will go here later.
    }

    public function requires_license(): bool {
        return false;
    }

    public function set_locked(bool $locked): void {
        $this->locked = $locked;
    }
}
