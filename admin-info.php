<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_menu', 'chd_register_info_page' );

function chd_register_info_page() {
    add_submenu_page(
        'tools.php',
        'Cured Hosting Diagnostics Info',
        'Cured Hosting Info',
        'manage_options',
        'cured-hosting-info',
        'chd_render_info_page'
    );
}

function chd_render_info_page() {
    echo '<div class="wrap"><h1>Cured Hosting Diagnostics — Info</h1>';
    $md_path = plugin_dir_path( __FILE__ ) . 'INFO.md';
    $md = @file_get_contents( $md_path );
    if ( $md === false ) {
        echo '<p><em>INFO.md not found.</em></p></div>';
        return;
    }

    $lines = preg_split("/\r?\n/", $md);
    $html = '';
    $in_list = false;
    foreach ( $lines as $line ) {
        $trim = ltrim( $line );
        if ( $trim === '' ) {
            if ( $in_list ) { $html .= "</ul>"; $in_list = false; }
            $html .= "\n";
            continue;
        }
        if ( strpos( $trim, '# ' ) === 0 ) {
            if ( $in_list ) { $html .= "</ul>"; $in_list = false; }
            $title = esc_html( substr( $trim, 2 ) );
            $html .= "<h2>" . $title . "</h2>\n";
            continue;
        }
        if ( strpos( $trim, '- **' ) === 0 ) {
            // Bold key format: - **Key**: description
            if ( $in_list ) { $html .= "</ul>"; $in_list = false; }
            if ( preg_match('/^- \*\*(.+?)\*\*:\s*(.*)$/', $trim, $m) ) {
                $k = esc_html( $m[1] );
                $v = esc_html( $m[2] );
                $html .= "<p><strong>" . $k . ":</strong> " . $v . "</p>\n";
                continue;
            }
        }
        if ( strpos( $trim, '- ' ) === 0 ) {
            if ( ! $in_list ) { $html .= "<ul>"; $in_list = true; }
            $item = esc_html( substr( $trim, 2 ) );
            $html .= "<li>" . $item . "</li>\n";
            continue;
        }
        // fallback paragraph
        if ( $in_list ) { $html .= "</ul>"; $in_list = false; }
        $html .= "<p>" . esc_html( $trim ) . "</p>\n";
    }
    if ( $in_list ) { $html .= "</ul>"; }

    echo '<div style="background:#fff;padding:18px;border:1px solid #e1e1e1;">' . $html . '</div>';
    echo '</div>';
}
