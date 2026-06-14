<?php
/**
 * Theme Footer Template
 * Minimal footer to complement header.php
 */
?>
    </main><!-- #site-content -->

    <footer id="site-footer" role="contentinfo">
        <div class="site-info">
            &copy; <?php echo date_i18n( _x( 'Y', 'yearly copyright date format', 'textdomain' ) ); ?> <?php bloginfo( 'name' ); ?>
        </div>
    </footer>

    <?php wp_footer(); ?>
</body>
</html>
