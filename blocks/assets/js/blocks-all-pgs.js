// Blocks here will be useable on any page not just the details page ect...

wp.blocks.registerBlockType('wovax-idx-wordpress/time-stamp', {
    title: 'Update Timestamp',
    icon: WovaxBlockData.icons.timestamp,
    description: 'Displays the last update timestamp for a feed.',
    category: 'wovax-idx',
    attributes: {
        feedID: {
            type: 'string',
            default: '0'
        },
    },
    edit: function(props) {
        var feedID  = props.attributes.feedID;
        var feeds   = WovaxBlockData.feeds;
        var curFeed = feeds.hasOwnProperty(feedID) ? feeds[feedID] : feeds[Object.keys(feeds)];
        var options = [];
        Object.keys(feeds).forEach(function(feedIdKey) {
            var feed = feeds[feedIdKey];
            options.push([feed['id'].toString(), feed['name']]);
        });
        return wp.element.createElement(
            wp.element.Fragment,
            null,
            // Inspector panel
            wp.element.createElement(
                wp.blockEditor.InspectorControls,
                null,
                wp.element.createElement(
                    wp.components.PanelBody,
                    {
                        title: 'Update Timestamp Settings',
                    },
                    wp.element.createElement(
                        WovaxBlockComponent.Dropdown,
                        {
                            title: "Choose Feed",
                            selectMsg: ' -- Select a Feed -- ',
                            value: feedID,
                            options: options,
                            onChange: function(val) {
                                props.setAttributes( { feedID:  val} );
                            }
                        }
                    )
                )
            ),
            wp.element.createElement(
                'div',
                null,
                curFeed['update']
            )
        );
    },
    save: function( props ) {
        return null;
    },
});