<?php
namespace Cured\Media;

use Cured\Contracts\Module_Interface;

final class Pipeline implements Module_Interface {
    private bool $locked = false;

    public function boot(): void {
        // Media optimization hooks will go here later.
    }

    public function requires_license(): bool {
        return false;
    }

    public function set_locked(bool $locked): void {
        $this->locked = $locked;
    }
}
