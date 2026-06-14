<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'admin_menu', 'chd_register_settings_page' );
add_action( 'admin_init', 'chd_register_settings' );

function chd_register_settings_page() {
    add_options_page(
        'Cured Hosting Diagnostics',
        'Cured Hosting Diagnostics',
        'manage_options',
        'cured-hosting-settings',
        'chd_render_settings_page'
    );
}

function chd_register_settings() {
    register_setting( 'chd_settings_group', 'chd_show_info' );
    register_setting( 'chd_settings_group', 'chd_enable_telemetry' );
    add_settings_section( 'chd_main_section', 'General', null, 'chd_settings' );
    add_settings_field( 'chd_show_info', 'Show info in admin', 'chd_field_show_info', 'chd_settings', 'chd_main_section' );
    add_settings_field( 'chd_enable_telemetry', 'Enable lightweight telemetry', 'chd_field_enable_telemetry', 'chd_settings', 'chd_main_section' );
}

function chd_field_show_info() {
    $v = get_option( 'chd_show_info', '1' );
    printf( '<input type="checkbox" name="chd_show_info" value="1" %s /> Show detailed package info in Tools > Cured Hosting Info', checked( '1', $v, false ) );
}

function chd_field_enable_telemetry() {
    $v = get_option( 'chd_enable_telemetry', '0' );
    printf( '<input type="checkbox" name="chd_enable_telemetry" value="1" %s /> Allow collection of anonymous diagnostic counters (opt-in)', checked( '1', $v, false ) );
}

function chd_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    echo '<div class="wrap chd-wrap">';
    echo '<h1>Cured Hosting Diagnostics</h1>';
    echo '<form method="post" action="options.php">';
    settings_fields( 'chd_settings_group' );
    do_settings_sections( 'chd_settings' );
    submit_button();
    echo '</form>';

    // Display rendered INFO.md as a preview
    $md_path = plugin_dir_path( __FILE__ ) . 'INFO.md';
    $md = @file_get_contents( $md_path );
    if ( $md ) {
        echo '<h2>Package Overview</h2>';
        echo '<div style="background:#fff;padding:18px;border:1px solid #e1e1e1;">' . chd_render_markdown( $md ) . '</div>';
    }

    echo '</div>';
}

function chd_render_markdown( $text ) {
    // Minimal Markdown -> HTML converter for admin preview (supports headers, lists, bold, italics, links, code blocks)
    $html = htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );

    // Code blocks ```
    $html = preg_replace_callback('/```\s*(.*?)\s*```/s', function( $m ) {
        return '<pre><code>' . esc_html( $m[1] ) . '</code></pre>';
    }, $html);

    // Headings
    $html = preg_replace('/^###\s*(.+)$/m', '<h3>$1</h3>', $html);
    $html = preg_replace('/^##\s*(.+)$/m', '<h2>$1</h2>', $html);
    $html = preg_replace('/^#\s*(.+)$/m', '<h1>$1</h1>', $html);

    // Bold **text** and _italic_
    $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html);
    $html = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $html);

    // Links [text](url)
    $html = preg_replace_callback('/\[(.*?)\]\((.*?)\)/', function( $m ) {
        $text = esc_html( $m[1] );
        $url  = esc_url( $m[2] );
        return "<a href=\"$url\" target=\"_blank\" rel=\"noopener noreferrer\">$text</a>";
    }, $html);

    // Unordered lists
    $lines = preg_split('/\r?\n/', $html);
    $out = '';
    $in_list = false;
    foreach ( $lines as $line ) {
        if ( preg_match('/^\s*-\s+(.+)$/', $line, $m) ) {
            if ( ! $in_list ) { $out .= '<ul>'; $in_list = true; }
            $out .= '<li>' . $m[1] . '</li>';
        } else {
            if ( $in_list ) { $out .= '</ul>'; $in_list = false; }
            $out .= '<p>' . $line . '</p>';
        }
    }
    if ( $in_list ) { $out .= '</ul>'; }

    // Allow limited tags
    $allowed = array(
        'h1' => array(), 'h2' => array(), 'h3' => array(), 'p' => array(), 'strong' => array(), 'em' => array(), 'ul' => array(), 'li' => array(), 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ), 'pre' => array(), 'code' => array(), 'div' => array( 'style' => array() )
    );

    return wp_kses( $out, $allowed );
}
