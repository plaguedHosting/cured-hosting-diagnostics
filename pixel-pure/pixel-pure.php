<?php
// Sanctum Guard: Prevent direct asset scanning
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Cure 02: Pixel Pure Media Optimizer (Standard + Pro Core)
 * Core Pillar: RESILIENCE, MAXIMUM SPEED & ZERO TELEMETRY
 */
final class PlagueDr_Pixel_Pure {

    private static $instance = null;
    private $is_pro = false;
    private $compression_quality = 82;
    private $max_width = 1920;
    private $max_height = 1080;
    private $watermark_text = '';
    private $watermark_image = '';
    private $watermark_opacity = 60;

    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->audit_pro_clearance();

        // FIX 1: Hook into metadata generation, not just the upload.
        // This ensures we catch the original AND all the thumbnails WP creates.
        add_filter( 'wp_generate_attachment_metadata', array( $this, 'process_all_image_sizes' ), 10, 2 );
        add_filter( 'wp_editor_set_quality', array( $this, 'enforce_engine_quality' ) );

        if ( $this->is_pro ) {
            add_action( 'wp_ajax_pd_bulk_optimize', array( $this, 'execute_bulk_crucible_ajax' ) );
        }
    }

    private function audit_pro_clearance() {
        $token = get_option( 'pd_license_token' );
        if ( $token && md5( $token ) === md5( 'plaguedr_pro_elite' ) ) {
            $this->is_pro = true;
            $this->compression_quality = (int) get_option( 'pd_pixel_pure_quality', 82 );
            $this->max_width = (int) get_option( 'pd_pixel_pure_max_width', 1920 );
            $this->max_height = (int) get_option( 'pd_pixel_pure_max_height', 1080 );
            $this->watermark_text = trim( get_option( 'pd_pixel_pure_watermark_text', '' ) );
            $this->watermark_image = trim( get_option( 'pd_pixel_pure_watermark_image', '' ) );
            $this->watermark_opacity = (int) get_option( 'pd_pixel_pure_watermark_opacity', 60 );
        }
    }

    public function enforce_engine_quality( $quality ) {
        return $this->compression_quality;
    }

    /**
     * Loops through the main file AND all generated thumbnail sizes
     */
    public function process_all_image_sizes( $metadata, $attachment_id ) {
        $file_path = get_attached_file( $attachment_id );
        $mime_type = get_post_mime_type( $attachment_id );

        // Process the main original file
        $this->process_single_file( $file_path, $mime_type );

        // Process all generated thumbnail sizes (medium, large, etc.)
        if ( isset( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
            $path_parts = pathinfo( $file_path );
            $dir_path = $path_parts['dirname'];

            foreach ( $metadata['sizes'] as $size => $size_info ) {
                $thumb_path = trailingslashit( $dir_path ) . $size_info['file'];
                // Only process if it's a JPEG or PNG
                if ( in_array( $size_info['mime-type'], array( 'image/jpeg', 'image/png' ) ) ) {
                    $this->process_single_file( $thumb_path, $size_info['mime-type'] );
                }
            }
        }

        return $metadata;
    }

    /**
     * Helper to run optimization and WebP generation on a single file
     */
    private function process_single_file( $file_path, $mime_type ) {
        $this->optimize_image_file( $file_path, $mime_type );

        if ( $this->is_pro ) {
            $this->generate_webp_variant( $file_path, $mime_type );
            $this->resize_image_if_needed( $file_path, $mime_type );
            $this->apply_watermark( $file_path, $mime_type );
        }
    }

    /**
     * Heavy-Duty local resource optimizer using standard host PHP libraries
     */
    private function optimize_image_file( $file_path, $mime_type ) {
        if ( ! file_exists( $file_path ) ) {
            return false;
        }

        // FIX 2: Memory limit protection for massive raw images
        if ( filesize( $file_path ) > 3 * 1024 * 1024 ) { // If file is larger than 3MB
            wp_raise_memory_limit( 'image' );
        }

        if ( $mime_type === 'image/jpeg' && function_exists( 'imagecreatefromjpeg' ) ) {
            $image = imagecreatefromjpeg( $file_path );
            if ( $image ) {
                imagejpeg( $image, $file_path, $this->compression_quality );
                imagedestroy( $image );
            }
        } elseif ( $mime_type === 'image/png' && function_exists( 'imagecreatefrompng' ) ) {
            $image = imagecreatefrompng( $file_path );
            if ( $image ) {
                $png_quality = max( 0, min( 9, round( ( 100 - $this->compression_quality ) / 10 ) ) );
                imagealphablending( $image, false );
                imagesavealpha( $image, true );
                imagepng( $image, $file_path, $png_quality );
                imagedestroy( $image );
            }
        }
    }

    /**
     * PRO FEATURE: Automated WebP Transformation Matrix
     */
    private function generate_webp_variant( $file_path, $mime_type ) {
        if ( ! function_exists( 'imagewebp' ) ) {
            return false;
        }

        $image = null;
        if ( $mime_type === 'image/jpeg' ) {
            $image = @imagecreatefromjpeg( $file_path ); // Suppress warnings on corrupted jpegs
        } elseif ( $mime_type === 'image/png' ) {
            $image = @imagecreatefrompng( $file_path );
            if ( $image ) {
                imagealphablending( $image, false );
                imagesavealpha( $image, true );
            }
        }

        if ( $image ) {
            $webp_path = preg_replace( '/\.(jpg|jpeg|png)$/i', '.webp', $file_path );
            imagewebp( $image, $webp_path, $this->compression_quality );
            imagedestroy( $image );
        }
    }

    private function resize_image_if_needed( $file_path, $mime_type ) {
        if ( ! file_exists( $file_path ) || empty( $this->max_width ) || empty( $this->max_height ) ) {
            return false;
        }

        list( $orig_width, $orig_height ) = @getimagesize( $file_path );
        if ( ! $orig_width || ! $orig_height ) {
            return false;
        }

        if ( $orig_width <= $this->max_width && $orig_height <= $this->max_height ) {
            return false;
        }

        $ratio = min( $this->max_width / $orig_width, $this->max_height / $orig_height );
        $new_width = max( 1, intval( $orig_width * $ratio ) );
        $new_height = max( 1, intval( $orig_height * $ratio ) );

        $src_image = null;
        if ( $mime_type === 'image/jpeg' ) {
            $src_image = imagecreatefromjpeg( $file_path );
        } elseif ( $mime_type === 'image/png' ) {
            $src_image = imagecreatefrompng( $file_path );
        }

        if ( ! $src_image ) {
            return false;
        }

        $dst_image = imagecreatetruecolor( $new_width, $new_height );

        if ( $mime_type === 'image/png' ) {
            imagealphablending( $dst_image, false );
            imagesavealpha( $dst_image, true );
        }

        imagecopyresampled( $dst_image, $src_image, 0, 0, 0, 0, $new_width, $new_height, $orig_width, $orig_height );

        if ( $mime_type === 'image/jpeg' ) {
            imagejpeg( $dst_image, $file_path, $this->compression_quality );
        } elseif ( $mime_type === 'image/png' ) {
            $png_quality = max( 0, min( 9, round( ( 100 - $this->compression_quality ) / 10 ) ) );
            imagepng( $dst_image, $file_path, $png_quality );
        }

        imagedestroy( $src_image );
        imagedestroy( $dst_image );

        return true;
    }

    private function apply_watermark( $file_path, $mime_type ) {
        if ( empty( $this->watermark_text ) && empty( $this->watermark_image ) ) {
            return false;
        }

        if ( ! file_exists( $file_path ) ) {
            return false;
        }

        $image = null;
        if ( $mime_type === 'image/jpeg' ) {
            $image = imagecreatefromjpeg( $file_path );
        } elseif ( $mime_type === 'image/png' ) {
            $image = imagecreatefrompng( $file_path );
        }

        if ( ! $image ) {
            return false;
        }

        $width = imagesx( $image );
        $height = imagesy( $image );

        if ( ! empty( $this->watermark_image ) && file_exists( $this->watermark_image ) ) {
            $this->overlay_watermark_image( $image, $width, $height );
        } elseif ( ! empty( $this->watermark_text ) ) {
            $this->overlay_watermark_text( $image, $width, $height );
        }

        if ( $mime_type === 'image/jpeg' ) {
            imagejpeg( $image, $file_path, $this->compression_quality );
        } elseif ( $mime_type === 'image/png' ) {
            imagealphablending( $image, false );
            imagesavealpha( $image, true );
            $png_quality = max( 0, min( 9, round( ( 100 - $this->compression_quality ) / 10 ) ) );
            imagepng( $image, $file_path, $png_quality );
        }

        imagedestroy( $image );
        return true;
    }

    private function overlay_watermark_text( $image, $width, $height ) {
        $font_size = max( 10, round( min( $width, $height ) / 24 ) );
        $angle = 0;
        $color = imagecolorallocatealpha( $image, 255, 255, 255, 127 - round( $this->watermark_opacity / 100 * 127 ) );
        $padding = 10;

        $font_file = function_exists( 'imagettfbbox' ) ? dirname( __FILE__ ) . '/fonts/arial.ttf' : false;
        $text = $this->watermark_text;

        if ( $font_file && file_exists( $font_file ) ) {
            $bbox = imagettfbbox( $font_size, $angle, $font_file, $text );
            $text_width = abs( $bbox[2] - $bbox[0] );
            $text_height = abs( $bbox[5] - $bbox[1] );
            $x = $width - $text_width - $padding;
            $y = $height - $padding;
            imagettftext( $image, $font_size, $angle, $x, $y, $color, $font_file, $text );
        } else {
            $text_width = imagefontwidth( 4 ) * strlen( $text );
            $text_height = imagefontheight( 4 );
            $x = $width - $text_width - $padding;
            $y = $height - $text_height - $padding;
            imagestring( $image, 4, $x, $y, $text, $color );
        }
    }

    private function overlay_watermark_image( $image, $width, $height ) {
        if ( ! file_exists( $this->watermark_image ) ) {
            return false;
        }

        $wm_size = getimagesize( $this->watermark_image );
        if ( ! $wm_size ) {
            return false;
        }

        $wm_mime = $wm_size['mime'];
        $watermark = null;

        if ( $wm_mime === 'image/png' ) {
            $watermark = imagecreatefrompng( $this->watermark_image );
        } elseif ( $wm_mime === 'image/jpeg' ) {
            $watermark = imagecreatefromjpeg( $this->watermark_image );
        }

        if ( ! $watermark ) {
            return false;
        }

        $wm_width = imagesx( $watermark );
        $wm_height = imagesy( $watermark );
        $scale = min( $width * 0.25 / $wm_width, $height * 0.25 / $wm_height, 1 );
        $target_w = intval( $wm_width * $scale );
        $target_h = intval( $wm_height * $scale );

        $dst = imagecreatetruecolor( $target_w, $target_h );
        imagealphablending( $dst, false );
        imagesavealpha( $dst, true );
        imagecopyresampled( $dst, $watermark, 0, 0, 0, 0, $target_w, $target_h, $wm_width, $wm_height );

        $dest_x = $width - $target_w - 10;
        $dest_y = $height - $target_h - 10;

        imagecopy( $image, $dst, $dest_x, $dest_y, 0, 0, $target_w, $target_h );
        imagedestroy( $watermark );
        imagedestroy( $dst );
        return true;
    }

    /**
     * PRO FEATURE: Background Legacy Ingestion Loop
     */
    public function execute_bulk_crucible_ajax() {
        // FIX 3: Introduce batching/pagination to prevent server 500 timeouts
        $offset = isset( $_POST['offset'] ) ? intval( $_POST['offset'] ) : 0;
        $batch_size = 3; // Process 3 attachments (and all their thumbnails) per request

        $attachments = get_posts( array(
            'post_type'      => 'attachment',
            'post_mime_type' => array( 'image/jpeg', 'image/png' ),
            'posts_per_page' => $batch_size,
            'offset'         => $offset,
            'post_status'    => 'inherit'
        ));

        // If no attachments are found, the process is complete
        if ( empty( $attachments ) ) {
            wp_send_json( array( 
                'success' => true, 
                'done'    => true, 
                'message' => 'All legacy image assets fully optimized and WebP layers generated.' 
            ) );
        }

        $count = 0;
        foreach ( $attachments as $attachment ) {
            // Retrieve metadata to ensure we compress the legacy thumbnails too
            $metadata = wp_get_attachment_metadata( $attachment->ID );
            if ( $metadata ) {
                $this->process_all_image_sizes( $metadata, $attachment->ID );
            } else {
                // Fallback for images without metadata
                $file_path = get_attached_file( $attachment->ID );
                $mime_type = get_post_mime_type( $attachment->ID );
                if ( $file_path && file_exists( $file_path ) ) {
                    $this->process_single_file( $file_path, $mime_type );
                }
            }
            $count++;
        }

        // Return a response prompting the frontend to send the next batch
        wp_send_json( array( 
            'success'     => true, 
            'done'        => false, 
            'next_offset' => $offset + $batch_size, 
            'message'     => 'Batch processed. Moving to offset ' . ($offset + $batch_size) . '...' 
        ) );
    }
}

/**
 * Initialize Engine Module
 */
function plaguedr_pixel_pure_init() {
    return PlagueDr_Pixel_Pure::instance();
}
plaguedr_pixel_pure_init();