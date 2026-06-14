<?php
namespace Cured\Cache;

use Cured\Contracts\Module_Interface;

final class Drain implements Module_Interface {
    private bool $locked = false;

    public function boot(): void {
        // Cache flush/drain helpers will go here later.
    }

    public function requires_license(): bool {
        return false;
    }

    public function set_locked(bool $locked): void {
        $this->locked = $locked;
    }
}
