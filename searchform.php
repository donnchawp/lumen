<?php
/**
 * Search Form Template
 *
 * @package Lumen
 */

if (!defined('ABSPATH')) {
    exit;
}

$lumen_search_id = 'search-' . wp_unique_id();
?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url(home_url('/')); ?>">
    <label class="screen-reader-text" for="<?php echo esc_attr($lumen_search_id); ?>">
        <?php esc_html_e('Search for:', 'lumen'); ?>
    </label>
    <input
        type="search"
        id="<?php echo esc_attr($lumen_search_id); ?>"
        class="search-field"
        placeholder="<?php esc_attr_e('Search photos and notes', 'lumen'); ?>"
        value="<?php echo esc_attr(get_search_query(false)); ?>"
        name="s"
    />
    <button type="submit" class="search-submit"><?php esc_html_e('Search', 'lumen'); ?></button>
</form>
