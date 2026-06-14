<?php
namespace Cured\Core\Stub;

use Cured\Contracts\Module_Interface;

final class Module_Stub implements Module_Interface {
    public function boot(): void {}
    public function requires_license(): bool { return false; }
    public function set_locked(bool $locked): void {}

    public function __call(string $name, array $args): mixed {
        return null;
    }
}
