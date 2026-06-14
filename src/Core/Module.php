<?php
namespace Cured\Core;

use Cured\Contracts\Module_Interface;

final class Module implements Module_Interface {
    public function boot(): void {}
    public function requires_license(): bool { return false; }
    public function set_locked(bool $locked): void {}
}
