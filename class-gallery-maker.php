<?php
/**
 * Optional gallery maker stub to avoid fatal errors when missing.
 *
 * This file provides a minimal no-op `CHD_Gallery_Maker` class so the
 * plugin can be activated on sites that don't include the full feature.
 */

if ( ! class_exists( 'CHD_Gallery_Maker' ) ) {
    class CHD_Gallery_Maker {
        public function __construct() {
            // Stub constructor – no initialization required.
        }

        public function init() {
            // No-op: placeholder for future gallery functionality.
        }
    }
}
