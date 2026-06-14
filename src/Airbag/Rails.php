<?php
namespace Cured\Airbag;

use Cured\Contracts\Module_Interface;

final class Rails implements Module_Interface {
    private bool $locked = false;

    public function boot(): void {
        // Guard rails for dangerous operations will go here later.
    }

    public function requires_license(): bool {
        return false;
    }

    public function set_locked(bool $locked): void {
        $this->locked = $locked;
    }
}
