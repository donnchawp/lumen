<?php
/**
 * Comments Template
 *
 * @package Lumen
 */

if (!defined('ABSPATH')) {
    exit;
}

// Do not leak comments on a post whose password has not been supplied.
if (post_password_required()) {
    return;
}
?>

<div id="comments" class="comments-area">

    <?php if (have_comments()) : ?>
        <h2 class="comments-title">
            <?php
            $lumen_comment_count = get_comments_number();

            printf(
                esc_html(
                    /* translators: %s: Number of comments. */
                    _n('%s comment', '%s comments', (int) $lumen_comment_count, 'lumen')
                ),
                esc_html(number_format_i18n($lumen_comment_count))
            );
            ?>
        </h2>

        <ol class="comment-list">
            <?php
            wp_list_comments(array(
                'style'       => 'ol',
                'short_ping'  => true,
                'avatar_size' => 48,
            ));
            ?>
        </ol>

        <?php
        the_comments_navigation(array(
            'prev_text' => esc_html__('← Older comments', 'lumen'),
            'next_text' => esc_html__('Newer comments →', 'lumen'),
        ));
        ?>
    <?php endif; ?>

    <?php if (!comments_open() && get_comments_number() && post_type_supports(get_post_type(), 'comments')) : ?>
        <p class="no-comments"><?php esc_html_e('Comments are closed.', 'lumen'); ?></p>
    <?php endif; ?>

    <?php
    // The reply title defaults to an h3, which skips a level when the post has
    // no comments and so no h2 "N comments" heading precedes it.
    comment_form(array(
        'title_reply_before' => '<h2 id="reply-title" class="comment-reply-title">',
        'title_reply_after'  => '</h2>',
    ));
    ?>

</div>
