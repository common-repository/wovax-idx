function WovaxGetFieldOptions() {
    var fields = WovaxBlockData.fields;
    var opts = [];
    for(i = 0; i < fields.length; i++) {
        field = fields[i];
        var el = wp.element.createElement('option', {value: field}, field);
        opts.push(el);
    }
    return opts;
}

wp.blocks.registerBlockType('wovax-idx-wordpress/image-gallery', {
    title: 'Photo Gallery',
    icon: WovaxBlockData.icons.gallery,
    description: 'Displays a gallery of listing images.',
    category: 'wovax-idx',
    // All block attributes
    attributes: {
        autoplay: {
            type: 'boolean',
            default: false
        },
        displayNumbers: {
            type: 'boolean',
            default: false
        },
        displayThumbs: {
            type: 'boolean',
            default: true
        },
        draggable: {
            type: 'boolean',
            default: true
        },
        fade: {
            type: 'boolean',
            default: true
        },
        slideTime: {
            type: 'number',
            default: 300
        },
        // Thumb and Main settings
        showMainArrows: {
            type: 'boolean',
            default: false
        },
        showMainDots: {
            type: 'boolean',
            default: false
        },
        showThumbArrows: {
            type: 'boolean',
            default: true
        },
        showThumbDots: {
            type: 'boolean',
            default: false
        }
    },
    edit: function(props) {
        // this kinda gets interesting since we are using a library for the slide
        // https://reactjs.org/docs/integrating-with-other-libraries.html
        // I would like a better preview but because we use slickCSS I will have
        // to figure out how to get that to work with reacts model of doing things
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
                        title: 'Image Gallery Settings',
                    },
                    wp.element.createElement(
                        wp.components.CheckboxControl,
                        {
                            label: 'Autoplay',
                            checked: props.attributes.autoplay === true,
                            onChange: function(val) {
                                props.setAttributes( { autoplay: (val === true) } );
                            }
                        }
                    ),
                    wp.element.createElement(
                        wp.components.CheckboxControl,
                        {
                            label: 'Draggable',
                            checked: props.attributes.draggable === true,
                            onChange: function(val) {
                                props.setAttributes( { draggable: (val === true) } );
                            }
                        }
                    ),
                    wp.element.createElement(
                        wp.components.CheckboxControl,
                        {
                            label: 'Fade',
                            checked: props.attributes.fade === true,
                            onChange: function(val) {
                                props.setAttributes( { fade: (val === true) } );
                            }
                        }
                    ),
                    wp.element.createElement(
                        wp.components.CheckboxControl,
                        {
                            label: 'Numbers',
                            checked: props.attributes.displayNumbers === true,
                            onChange: function(val) {
                                props.setAttributes( { displayNumbers: (val === true) } );
                            }
                        }
                    ),
                    wp.element.createElement(
                        wp.components.CheckboxControl,
                        {
                            label: 'Thumbnails',
                            checked: props.attributes.displayThumbs === true,
                            onChange: function(val) {
                                props.setAttributes( { displayThumbs: (val === true) } );
                            }
                        }
                    ),
                    wp.element.createElement(
                        wp.components.RangeControl,
                        {
                            label: 'Side Time (ms)',
                            min: 50,
                            max: 1000,
                            value: props.attributes.slideTime,
                            onChange: function(value) {
                                props.setAttributes( { slideTime: value } );
                            }
                        }
                    )
                ),
                wp.element.createElement(
                    wp.components.PanelBody,
                    {
                        title: 'Main Image Settings',
                        initialOpen: false
                    },
                    wp.element.createElement(
                        wp.components.CheckboxControl, {
                            label: 'Arrows',
                            checked: props.attributes.showMainArrows === true,
                            onChange: function(val) {
                                props.setAttributes( { showMainArrows: (val === true) } );
                            }
                        }
                    ),
                    wp.element.createElement(
                        wp.components.CheckboxControl, {
                            label: 'Dots',
                            checked: props.attributes.showMainDots === true,
                            onChange: function(val) {
                                props.setAttributes( { showMainDots: (val === true) } );
                            }
                        }
                    )
                ),
                (props.attributes.displayThumbs !== true ? null : wp.element.createElement(
                    wp.components.PanelBody,
                    {
                        title: 'Thumbnail Settings',
                        initialOpen: false
                    },
                    wp.element.createElement(
                        wp.components.CheckboxControl, {
                            label: 'Arrows',
                            checked: props.attributes.showThumbArrows === true,
                            onChange: function(val) {
                                props.setAttributes( { showThumbArrows: (val === true) } );
                            }
                        }
                    ),
                    wp.element.createElement(
                        wp.components.CheckboxControl, {
                            label: 'Dots',
                            checked: props.attributes.showThumbDots === true,
                            onChange: function(val) {
                                props.setAttributes( { showThumbDots: (val === true) } );
                            }
                        }
                    )
                )),
            ),
            // display in editor
            wp.element.createElement(
                'div',
                {
                    style: {
                        height: '500px',
                        width: '100%',
                        backgroundColor: '#3498db',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center'
                    }
                },
                wp.element.createElement(
                    'h3',
                    {
                        style: {
                            color: '#fff'
                        }
                    },
                    'Listing Gallery'
                )
            )
        );
    },
    save: function( props ) {
        return null;
    },
});

wp.blocks.registerBlockType('wovax-idx-wordpress/labeled-field', {
    title: 'Labeled Field',
    icon: WovaxBlockData.icons.field,
    description: 'Displays a customizeable label and the particular listing field data.',
    category: 'wovax-idx',

    // All block attributes
    attributes: {
        label: {
            type: 'string',
            default: 'No Field'
        },
        listingField: {
            type: 'string',
            default: 'none'
        },
        fieldType: {
            type: 'object',
            default: {
                type: 'text',
                link: {
                    label: 'Click Here',
                },
                numeric: {
                    commas: true,
                    decimals: 2
                },
                price: {
                    left: true,
                    symbol: '$'
                },
				boolean: 'boolean'
			}
        }
    },
    edit: function(props) {
        var label     = props.attributes.label;
        var field     = props.attributes.listingField;

        return wp.element.createElement(
            wp.element.Fragment,
            null,
            // Inspector panel
            wp.element.createElement(
                wp.blockEditor.InspectorControls,
                null,
                // Field select panel
                wp.element.createElement(
                    WovaxBlockComponent.FieldSettings,
                    {
                        field: field,
                        fields: WovaxBlockData.fields,
                        fieldType: props.attributes.fieldType,
                        onFieldChange: function(val) {
                            props.setAttributes( { label: '<strong>' + val + '</strong>' } );
                            props.setAttributes( { listingField:  val} );
                        },
                        onTypeChange: function(val) {
                            props.setAttributes( { fieldType: val } );
                        }
                    }
                )
            ),
            // display in editor
            wp.element.createElement(
                'div',
                {
                    style: {
                        display: 'flex',
                        justifyContent:'space-between'
                    }
                },
                wp.element.createElement(
                    wp.blockEditor.RichText,
                    {
                        className: props.className,
                        value: label,
                        onChange: function(val) {
                            props.setAttributes( { label: val } );
                        }
                    }
                ),
                wp.element.createElement(
                    'div',
                    {
                        style: {
                            fontStyle: 'italic',
                            boxSizing: 'inherit',
                            alignItems: 'right'
                        }
                    },
                    'Field Value'
                )
            )
        );
    },
    save: function( props ) {
        return null;
    },
});

wp.blocks.registerBlockType('wovax-idx-wordpress/field-data', {
    title: 'Field Data',
    icon: WovaxBlockData.icons.paragraph,
    description: 'Display the value for the chosen field.',
    category: 'wovax-idx',

    // All block attributes
    attributes: {
        listingField: {
            type: 'string',
            default: 'none'
        },
        backgroundColor: {
            type: 'string',
            default: ''
        },
        fieldType: {
            type: 'object',
            default: {
                type: 'text',
                link: {
                    label: 'Click Here',
                },
                numeric: {
                    commas: true,
                    decimals: 2
                },
                price: {
                    left: true,
                    symbol: '$'
                },
				boolean: 'boolean'
			}
        },
        textAlign: {
            type: 'string',
            default: ''
        },
        textColor: {
            type: 'string',
            default: ''
        },
        textSize: {
            type: 'string',
            default: ''
        },
        textStyle: {
            type: 'number',
            default: 0
        }
    },
    edit: function(props) {
        var bgColor   = props.attributes.backgroundColor;
        var field     = props.attributes.listingField;
        var textAlign = props.attributes.textAlign;
        var textColor = props.attributes.textColor;
        var textSize  = props.attributes.textSize;
        var textStyle = props.attributes.textStyle;
        function getDecorationString(val) {
            if((val & 0xC) === 0) {
                return null;
            }
            var decs = [];
            if((val & 4) > 0) {
                decs.push('underline');
            }
            if((val & 8) > 0) {
                decs.push('line-through');
            }
            return decs.join(' ');
        }
        return wp.element.createElement(
            wp.element.Fragment,
            null,
            // Inspector panel
            wp.element.createElement(
                wp.blockEditor.InspectorControls,
                null,
                wp.element.createElement(
                    WovaxBlockComponent.FieldSettings,
                    {
                        field: field,
                        fields: WovaxBlockData.fields,
                        fieldType: props.attributes.fieldType,
                        onFieldChange: function(val) {
                            props.setAttributes( { listingField:  val} );
                        },
                        onTypeChange: function(val) {
                            props.setAttributes( { fieldType: val } );
                        }
                    }
                ),
                wp.element.createElement(
                    wp.components.PanelBody,
                    {
                        title: 'Text Settings',
                        initialOpen: false
                    },
                    wp.element.createElement(
                        wp.components.FontSizePicker,
                        {
                            fallbackFontSize: 16,
                            value: Number(textSize),
                            withSlider: true,
                            fontSizes: [
                                {
                                    name: 'Small',
                                    size: 13,
                                    slug: 'wx-small-13'
                                },
                                {
                                    name: 'Normal',
                                    size: '',
                                    slug: ''
                                },
                                {
                                    name: 'Medium',
                                    size: 20,
                                    slug: 'wx-med-20'
                                },
                                {
                                    name: 'Large',
                                    size: 36,
                                    slug: 'wx-lg-36'
                                },
                                {
                                    name: 'Huge',
                                    size: 48,
                                    slug: 'wx-hg-48'
                                },
                            ],
                            onChange: function(value) {
                                if (typeof value !== 'number') {
                                    value = '';
                                } else {
                                    value = value.toString();
                                }
                                // reset makes value undefined.
                                props.setAttributes( { textSize: value } );
                            }
                        }
                    )
                ),
                wp.element.createElement(
                    wp.blockEditor.PanelColorSettings,
                    {
                        title: 'Color Settings',
                        initialOpen: false,
                        colorSettings: [
                            {
                                label: 'Background Color',
                                value: props.backgroundColor,
                                onChange: function(color) {
                                    if (typeof color !== 'string') {
                                        color = '';
                                    }
                                    props.setAttributes( { backgroundColor: color } );
                                }
                            },
                            {
                                label: 'Text Color',
                                value: props.textColor,
                                onChange: function(color) {
                                    if (typeof color !== 'string') {
                                        color = '';
                                    }
                                    props.setAttributes( { textColor: color } );
                                }
                            }
                        ]
                    }
                )
            ),
            // Create Formatting bar
            props.isSelected && wp.element.createElement(
                wp.blockEditor.BlockControls,
                null,
                wp.element.createElement(
                    wp.components.Toolbar,
                    {
                        controls: [
                            {
                                icon: 'editor-alignleft',
                                title: 'Align field value left',
                                isActive: textAlign === 'left',
                                onClick: function() {
                                    var val = textAlign === 'left' ? '' : 'left';
                                    props.setAttributes( { textAlign: val } );
                                }
                            },
                            {
                                icon: 'editor-aligncenter',
                                title: 'Center field value',
                                isActive: textAlign === 'center',
                                onClick: function() {
                                    var val = textAlign === 'center' ? '' : 'center';
                                    props.setAttributes( { textAlign: val } );
                                }
                            },
                            {
                                icon: 'editor-alignright',
                                title: 'Align field value right' ,
                                isActive: textAlign === 'right',
                                onClick: function() {
                                    var val = textAlign === 'right' ? '' : 'right';
                                    props.setAttributes( { textAlign: val } );
                                }
                            }
                        ]
                    }
                ),
                wp.element.createElement(
                    wp.components.Toolbar,
                    {
                        // just for reference
                        // isCollapsed: true,
                        // icon: 'editor-bold',
                        controls: [
                            {
                                icon: 'editor-bold',
                                title: 'Bold field value',
                                isActive: (textStyle & 0x1) > 0,
                                onClick: function( e ) {
                                    var val = textStyle ^ 0x1;
                                    props.setAttributes( { textStyle: val } );
                                }
                            },
                            {
                                icon: 'editor-italic',
                                title: 'Italicize field value',
                                isActive: (textStyle & 0x2) > 0,
                                onClick: function( e ) {
                                    var val = textStyle ^ 0x2;
                                    props.setAttributes( { textStyle: val } );
                                }
                            },
                            {
                                icon: 'editor-underline',
                                title: 'Underline field value',
                                isActive: (textStyle & 0x4) > 0,
                                onClick: function( e ) {
                                    var val = textStyle ^ 0x4;
                                    props.setAttributes( { textStyle: val } );
                                }
                            },
                            {
                                icon: 'editor-strikethrough',
                                title: 'Strike Through field value',
                                isActive: (textStyle & 0x8) > 0,
                                onClick: function( e ) {
                                    var val = textStyle ^ 0x8;
                                    props.setAttributes( { textStyle: val } );
                                }
                            }
                        ]
                    }
                )
            ),
            // display in editor
            wp.element.createElement(
                'div',
                {
                    style: {
                        backgroundColor: bgColor.length > 0 ? bgColor : null,
                        boxSizing: 'inherit',
                        color: textColor.length > 0 ? textColor : null,
                        fontSize: Number(textSize) > 0 ? textSize + 'px' : null,
                        fontStyle: (textStyle & 0x2) > 0 ? 'italic' : null,
                        fontWeight: (textStyle & 0x1) > 0 ? 'bold' : null,
                        textAlign: textAlign.length > 0 ? textAlign : null,
                        textDecoration: getDecorationString(textStyle),

                    }
                },
                field === 'none' ? 'Please select a field' : 'Field Value'
            )
        );
    },
    save: function( props ) {
        return null;
    },
});

wp.blocks.registerBlockType('wovax-idx-wordpress/favorite', {
    title: 'Favorite Button',
    icon: WovaxBlockData.icons.favorite,
    description: 'Lets users favorite listings.',
    category: 'wovax-idx',
    edit: function(props) {
        return wp.element.createElement(WovaxBlockComponent.Favorites);
    },
    save: function( props ) {
        return null;
    },
});
