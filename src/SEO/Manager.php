<?php
namespace Cured\SEO;

use Cured\Contracts\Module_Interface;

final class Manager implements Module_Interface {
    private bool $locked = false;

    public function boot(): void {
        // SEO defaults and schema helpers will go here later.
    }

    public function requires_license(): bool {
        return false;
    }

    public function set_locked(bool $locked): void {
        $this->locked = $locked;
    }
}
