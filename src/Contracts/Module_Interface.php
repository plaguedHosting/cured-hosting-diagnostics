<?php
namespace Cured\Contracts;

interface Module_Interface {
    public function boot(): void;
    public function requires_license(): bool;
    public function set_locked(bool $locked): void;
}
