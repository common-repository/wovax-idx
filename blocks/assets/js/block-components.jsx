var WovaxBlockComponent = {};

WovaxBlockComponent.Dropdown = class extends wp.element.Component {
    constructor(props) {
        super(props);
        this.ref     = React.createRef();
        this.itemRef = React.createRef();
        this.state = {
            focusedItem: null,
            focusMain:   false,
        };
        this.setItemPos = false;
    }
    componentDidUpdate() {
        var btn = ReactDOM.findDOMNode(this.ref.current);
        if(this.state.focusMain) {
            btn.focus();
        }
        if(this.itemRef.current === null) {
            this.setItemPos = false;
            return;
        }
        if(!this.setItemPos) {
            var sel = ReactDOM.findDOMNode(this.itemRef.current);
            sel.scrollIntoView(true);
            sel.focus();
            this.setItemPos = true;
        }
    }
    render() {
        var self        = this;
        var props       = this.props;
        // options need both title and value
        var options = props.options.filter(function(opt) {
            return (opt.length > 1);
        });
        var renderToggle = function(info) {
            var isOpen   = info.isOpen;
            var onToggle = info.onToggle;
            var msg      = props.selectMsg;
            // change msg to match current value.
            for(var i = 0; i < options.length; i++) {
                var opt = options[i];
                if(opt[0] === props.value) {
                    msg = opt[1];
                }
            }
            var css_class = 'wovax-component-dropdown';
            if(isOpen) {
                css_class += ' wovax-component-dropdown-open';
            }
            return <button
                ref={self.ref}
                class={css_class}
                onClick={onToggle}
                onKeyDown={function(e) {
                        if(isOpen || e.keyCode !== 40) {
                            return;
                        }
                        e.preventDefault();
                        e.stopPropagation();
                        onToggle();
                    }
                }
                onBlur={function() {
                    self.setState({ focusMain: false });
                }}
            >
                <span class='wovax-component-dropdown-label'>{msg}</span>
            </button>;
        };
        var renderDropDown = function(info) {
            var onClose = info.onClose;
            var items = options.map(function(opt) {
                var label      = opt[1];
                var value      = opt[0];
                var isSelected = props.value === value;
                var isFocused  = self.state.focusedItem === value;
                var ref        = isSelected ? self.itemRef : null;
                return <wp.components.Button
                    ref={ref}
                    icon={ isSelected ? "saved" : <svg height={20} width={20}/>}
                    onClick={ function(e) {
                        e.stopPropagation();
                        self.setState({ focusMain: true });
                        props.onChange(value);
                        onClose();
                    }}
                    onFocus={function() {
                        self.setState({focusedItem: value});
                    }}
                    class="components-button components-icon-button components-dropdown-menu__menu-item"
                    aria-checked={ isSelected }
                    role="menuitem"
                    style={
                        {
                            color: '#000',
                            border: isFocused ? '1px dotted' : null,
                            boxShadow: isFocused ? '0px 0px 0px #000' : null,
                            width: '100%',
                            marginBottom: '2px'
                        }
                    }
                >
                    <span style={{width: '100%', textAlign: 'left'}}>{label}</span>
                </wp.components.Button>;
            });
            return <wp.components.NavigableMenu
                style={{
                    padding: '10px'
                }}
            >
                {items}
            </wp.components.NavigableMenu>;
        };
        return <wp.components.BaseControl label={props.title}>
            <wp.components.Dropdown
                renderToggle={renderToggle}
                renderContent={renderDropDown}
            />
        </wp.components.BaseControl>;
    }
};
WovaxBlockComponent.Dropdown.defaultProps = {
    options: [],
    selectMsg: 'Select an Option',
    title: '',
    value: 'none',
    onChange: function(val) { return; }
};

WovaxBlockComponent.FieldSettings = class extends wp.element.Component {
    render() {
        function cloneFieldType(type) {
            var cpy              = {};
            cpy.link             = {};
            cpy.numeric          = {};
            cpy.price            = {};
            cpy.type             = 'text';
            cpy.boolean          = {};
            cpy.link.label       = 'Click Here';
            cpy.numeric.commas   = true;
            cpy.numeric.decimals = 2;
            cpy.price.left       = true;
            cpy.price.symbol     = '$';
            if(type.hasOwnProperty('type')) {
                var typ_str = type.type;
                if(
                    typ_str === 'numeric' ||
                    typ_str === 'link' ||
                    typ_str === 'price' ||
                    typ_str === 'boolean'
                ) {
                    cpy.type = typ_str;
                }
            }
            if(
                type.hasOwnProperty('link') &&
                type.link.hasOwnProperty('label') &&
                typeof type.link.label === 'string'
            ) {
                cpy.link.label = type.link.label;
            }
            if(type.hasOwnProperty('numeric')) {
                if(
                    type.numeric.hasOwnProperty('commas') &&
                    typeof type.numeric.commas === 'boolean'
                ) {
                    cpy.numeric.commas = type.numeric.commas;
                }
                if(
                    type.numeric.hasOwnProperty('decimals') &&
                    typeof type.numeric.decimals === 'number'
                ) {
                    cpy.numeric.decimals = type.numeric.decimals;
                }
            }
            if(type.hasOwnProperty('price')) {
                if(
                    type.price.hasOwnProperty('left') &&
                    typeof type.price.left === 'boolean'
                ) {
                    cpy.price.left = type.price.left;
                }
                if(
                    type.hasOwnProperty('price') &&
                    type.price.hasOwnProperty('symbol') &&
                    typeof type.price.symbol === 'string'
                ) {
                    cpy.price.symbol = type.price.symbol;
                }
            }
            return cpy;
        }
        var props     = this.props;
        var fields    = props.fields.map(function(field) { return [field, field]; });
        var fieldType = cloneFieldType(props.fieldType);
        return <wp.components.PanelBody title='Field Settings'>
            <WovaxBlockComponent.Dropdown
                title='Field'
                value={props.field}
                selectMsg=' -- Select a Field -- '
                options={fields}
                onChange={function(field) {
                    var cpy = cloneFieldType(props.fieldType);
                    switch(field) {
                        case 'Price':
                            cpy.type = 'price';
                            break;
                        case 'Acres':
                        case 'Bathrooms':
                        case 'Bedrooms':
                        case 'Lot Size':
                        case 'Square Footage':
                            cpy.type = 'numeric';
                            break;
                        case 'Virtual Tour':
                            cpy.type = 'link';
                            break;
                        default:
                            cpy.type = 'text';
                            break;
                    }
                    props.onFieldChange(field);
                    if(cpy.type !== props.fieldType.type) {
                        props.onTypeChange(cpy);
                    }
                }}
            />
            <WovaxBlockComponent.Dropdown
                title='Field Type'
                value={fieldType.type}
                selectMsg=' -- Select a Type -- '
                options={[
                    ['link', 'Link'],
                    ['numeric', 'Numeric'],
                    ['price', 'Price'],
                    ['text', 'Text'],
                    ['boolean', 'Boolean']
                ]}
                onChange={function(val) {
                    var cpy = cloneFieldType(props.fieldType);
                    cpy.type = val;
                    props.onTypeChange(cpy);
                }}
            />
            {
                (fieldType.type === 'numeric' || fieldType.type === 'price' ) &&
                <wp.components.TextControl
                    style={{width: '100%'}}
                    label='Decimals'
                    value={fieldType.numeric.decimals}
                    onChange={function(value) {
                        var reg = new RegExp('^[0-9]+$');
                        value = value.replace(new RegExp('^0+'), '');
                        if(value.length < 1) {
                            value = '0';
                        }
                        if(!reg.test(value)) {
                            return;
                        }
                        var cpy = cloneFieldType(props.fieldType);
                        cpy.numeric.decimals = Number(value);
                        props.onTypeChange(cpy);
                    }}
                />
            }
            {
                (fieldType.type === 'numeric' || fieldType.type === 'price' ) &&
                <wp.components.CheckboxControl
                    label='Display Commas'
                    checked={fieldType.numeric.commas}
                    onChange={function(val) {
                        var cpy = cloneFieldType(props.fieldType);
                        cpy.numeric.commas = val;
                        props.onTypeChange(cpy);
                    }}
                />
            }
            {
                (fieldType.type === 'price' ) &&
                <wp.components.RadioControl
                    label='Currency Position'
                    selected={fieldType.price.left ? 'left' : 'right'}
                    options={[
                        { label: 'Left', value: 'left' },
                        { label: 'Right', value: 'right' },
                    ]}
                    onChange={function(value) {
                        var cpy = cloneFieldType(props.fieldType);
                        cpy.price.left = (value == 'left') ? true : false;
                        props.onTypeChange(cpy);
                    }}
                />
            }
            {
                ( fieldType.type === 'price' ) &&
                <wp.components.TextControl
                    style={{width: '100%'}}
                    label='Currency Symbol'
                    value={fieldType.price.symbol}
                    onChange={function(value) {
                        var cpy = cloneFieldType(props.fieldType);
                        cpy.price.symbol = value;
                        props.onTypeChange(cpy);
                    }}
                />
            }
            {(props.type === 'link') && <wp.components.TextControl
                style={{width: '100%'}}
                label='Link Display Label'
                value={fieldType.link.label}
                onChange={function(val) {
                    var cpy = cloneFieldType(props.fieldType);
                    cpy.link.label = val;
                    props.onTypeChange(cpy);
                }}
            />}
        </wp.components.PanelBody>;
    }
};
WovaxBlockComponent.FieldSettings.defaultProps = {
    field: null,
    fields: [],
    fieldType: {
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
        }
    },
    onFieldChange: function(val) { return; },
    onTypeChange: function(val) { return; }
};

WovaxBlockComponent.Favorites = class extends wp.element.Component {
    constructor(props) {
        super(props);
        this.state = { clicked: false };
    }
    render() {
        var self = this;
        return <div
            style={{
                display: 'flex',
                justifyContent: 'center'
            }}
            >
            <div
                class="wovax-idx-heart"
                data-idx-fav={this.state.clicked ? 'yes' : 'no'}
                onClick={function() {
                    self.setState({ clicked: !self.state.clicked });
                }}
            >
                <svg
                    height="26"
                    viewBox="-1 -1 102 90"
                    preserveAspectRatio="xMidYMid meet"
                >
                    <path
                        strokeWidth="5"
                        d="M50 86.8C35 75 2.5 58 2.5 28 2.5 13 13 2.5 28 2.5S50 18 50 18 57 2.5 72 2.5 97.5 13 97.5 28C97.5 58 65 75 50 86.8z"
                    />
                </svg>
            </div>
        </div>;
    }
};