<?php
namespace Cured\Core\License;

final class Flags {
    private static bool $valid = false;

    public static function init(Manager $manager): void {
        self::$valid = ($manager->get_status() === 'valid');
    }

    public static function is_valid(): bool {
        return self::$valid;
    }
}
