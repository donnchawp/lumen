/**
 * Client-side registration for lumen/photo-grid.
 *
 * Without this the block editor knows nothing about the block, and every
 * template that uses it — index, archive and search — shows it as "Your site
 * doesn't include support for the lumen/photo-grid block". The markup does
 * survive a Site Editor save in that state (core parses an unregistered block
 * into core/missing, whose save output is the original delimiter verbatim), so
 * this is about the editing experience rather than about losing the template.
 *
 * There is no build step and none is wanted, so this is plain ES5 against the
 * wp.* globals rather than JSX against imports. block.json names the handle
 * functions.php registers; nothing here parses or transpiles.
 *
 * Only edit and save are declared. The rest of the block type — title, icon,
 * category, the notesHeading attribute and its default — comes from block.json
 * by way of the server-side definitions core bootstraps into the editor, so
 * block.json stays the single source of truth it already was for render.php.
 */
(function (blocks, element, blockEditor, components, i18n) {
    var el = element.createElement;
    var __ = i18n.__;

    blocks.registerBlockType('lumen/photo-grid', {
        /*
         * A static placeholder rather than ServerSideRender, which the spec had
         * pencilled in. render.php partitions the posts the surrounding query
         * has already run: it calls have_posts() against the ambient main query
         * and renders nothing when that is empty. ServerSideRender renders
         * through /wp/v2/block-renderer/, whose main query is the REST request
         * itself and holds no posts, so the preview would be core's "Block
         * rendered as empty." on every template. A placeholder that says what
         * the block does is worth more than a preview that is always blank.
         */
        edit: function (props) {
            // apiVersion 3: the wrapper element has to carry the props the
            // editor supplies, or the block cannot be selected on the canvas.
            var blockProps = blockEditor.useBlockProps();

            return el(
                element.Fragment,
                null,
                el(
                    blockEditor.InspectorControls,
                    null,
                    el(
                        components.PanelBody,
                        { title: __('Notes list', 'lumen') },
                        el(components.TextControl, {
                            label: __('Notes heading', 'lumen'),
                            help: __('Shown above the posts on this page that have no photograph.', 'lumen'),
                            value: props.attributes.notesHeading,
                            onChange: function (value) {
                                props.setAttributes({ notesHeading: value });
                            }
                        })
                    )
                ),
                el(
                    'div',
                    blockProps,
                    el(components.Placeholder, {
                        icon: 'grid-view',
                        label: __('Photo Grid', 'lumen'),
                        instructions: __(
                            'The posts on this page, split in two: those with a photograph become grid cards, the rest are listed under the notes heading. Visible on the site, not here.',
                            'lumen'
                        )
                    })
                )
            );
        },

        // Dynamic block: render.php produces the markup, so nothing is written
        // into the template but the delimiter.
        save: function () {
            return null;
        }
    });
})(window.wp.blocks, window.wp.element, window.wp.blockEditor, window.wp.components, window.wp.i18n);
