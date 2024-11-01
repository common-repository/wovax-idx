class WovaxIdxReactMap extends wp.element.Component {
    constructor(props) {
        super(props);
        this.mapRef = React.createRef();
        this.map = null;
    }
    addMap() {
        this.map = document.createElement('div');
        this.map.style.width = '100%';
        this.map.style.height = this.props.height + 'px';
        this.map.style.marginTop = '1em';
        this.map.style.marginBottom = '1em';
        this.map.style.zIndex = 0;
        this.map.innerHTML = 'Loading Map...';
        this.mapRef.current.appendChild(this.map);
        var mapType = null;
        switch(this.props.type) {
            case 'google':
                mapType = WovaxIdxGoogleMap;
                break;
            case 'location_iq':
                mapType = WovaxIdxLocationIqMap;
                break;
            case 'map_quest':
                mapType = WovaxIdxMapQuest;
                break;
            default:
                return;
        }
        var props  = this.props;
        var newMap = new mapType(
            this.map,
            props.token,
            props.lat,
            props.long
        );
        props.onMountGetMap(newMap);
    }
    componentDidMount() {
        if(this.mapPropsGood()) {
            this.addMap();
        }
    }
    componentDidUpdate(prev) {
        var cur = this.props;
        if(this.map !== null && prev.height !== cur.height) {
            this.map.style.height = cur.height + 'px';
        }
        // load new map type
        if(prev.type !== cur.type) {
            if(this.map !== null) {
                this.map.remove();
            }
            if(this.mapPropsGood()) {
                this.addMap();
            }
        }
    }
    noDisplay(props) {
        var styles = {
            height: this.props.height + 'px',
            width: '100%',
            backgroundColor: props.backGroundColor,
            display: 'flex',
            flexWrap: 'wrap',
            alignItems: 'center',
            justifyContent: 'center',
            textAlign: 'center'
        };
        var sub_message = '';
        if(!this.isTokenSet()) {
           sub_message = props.noTokenMsg;
        }
        if(!this.isTypeGood()) {
            sub_message = 'Please select a provider for this block.';
        }
        var subMsgElements = null;
        if(sub_message.length > 0) {
            subMsgElements = <wp.element.Fragment>
            <br></br>
            <h2 style={{color: '#FFF'}}>{sub_message}</h2>
            </wp.element.Fragment>;
        }
        return <div>
            <div style={styles}>
                <p>
                    <h3 style={{color: '#FFF'}}>{props.title}</h3>
                    {subMsgElements}
                </p>
            </div>
            {this.props.children}
        </div>;
    }
    render() {
        if(this.mapPropsGood()) {
            return <div ref={this.mapRef}></div>;
        }
        return this.noDisplay(this.props);
    }
    mapPropsGood() {
        var good = this.isTypeGood();
        good = good && this.isTokenSet();
        return good;
    }
    isTypeGood() {
        var types = ['google','location_iq','map_quest'];
        if(!types.includes(this.props.type)) {
            return false;
        }
        return true;
    }
    isTokenSet() {
        if(this.props.token.length < 1) {
            return false;
        }
        return true;
    }
}
WovaxIdxReactMap.defaultProps = {
    backGroundColor: '#6EE28A',
    height: '500px',
    lat: 46.7323603,
    long: -117.0010062,
    noTokenMsg: 'Set an API key on the Wovax IDX Initial Setup page.',
    onMountGetMap: function() { return; },
    title: 'Listing Map',
    token: '',
    type: 'none'
};

class WovaxIdxReactDropDown extends wp.element.Component {
    constructor(props) {
        super(props);
        this.ref = React.createRef();
    }
    render() {
        var self = this;
        return <wp.element.Fragment>
                <label style={{marginBottom: '6.5px'}}>{this.props.title}</label>
                <form>
                    <select
                        ref={this.ref}
                        value={this.props.value}
                        style={{
                            boxSizing: 'border-box',
                            marginBottom: '6.5px'
                        }}
                        onChange={function (e) {
                            self.props.onChange(self.ref.current.value);
                            e.preventDefault();
                        }}
                    >
                    <option disabled={true} selected={true} value='none'>{this.props.selectMsg}</option>
                    {
                        this.props.options.map(function(opt) {
                            return <option value={opt[0]}>{opt[1]}</option>
                        })
                    }
                    </select>
                </form>
        </wp.element.Fragment>;
    }
}
WovaxIdxReactDropDown.defaultProps = {
    options: [],
    selectMsg: 'Select an Option',
    title: '',
    value: 'none',
    onChange: function(val) { return; }
};

class WovaxIdxMapStore {
    constructor() {
        this.map = null;
        this.markers = [];
    }
    setMap(map) {
        this.map = map;
        var loader = new WovaxIdxMapLoader();
        loader.registerMap(map);
        var markers = this.markers;
        for(var i = 0; i < markers.length; i++) {
            markers[i].triggerUnbind();
            map.addMarker(markers[i]);
        }
    }
    addMarker(marker) {
        if(this.map !== null) {
            this.map.addMarker(marker);
        }
        this.markers.push(marker);
    }
    getMarkers(type) {
        if(typeof type !== 'string') {
            return this.markers;
        }
        var markers_of_type = [];
        for(var i = 0; i < this.markers.length; i++) {
            var marker = this.markers[i];
            if(marker.getType() === type) {
                markers_of_type.push(marker);
            }
        }
        return markers_of_type;
    }
    removeMarkerType(type) {
        if(typeof type !== 'string') {
            return;
        }
        var left_over = [];
        this.markers.map(function(marker) {
            if(marker.getType() === type) {
                marker.triggerUnbind();
            } else {
                left_over.push(marker);
            }
        });
        this.markers = left_over;
    }
}

class WovaxIdxPoiBlock extends wp.element.Component {
    constructor(props) {
        super(props);
        this.mapStore = new WovaxIdxMapStore();
    }
    loadMarkerType(type) {
        var props  = this.props;
        var api    = props.api;
        var pos    = props.pos;
        var radius = 1609.344 * props.radius; // convert to meters.
        var store  = this.mapStore;
        var color  = '';
        if (typeof api.getPlaces !== 'function') {
            return;
        }
        if(props.pinColors.hasOwnProperty(type)) {
            color = props.pinColors[type];
        }
        api.getPlaces(type, ...pos, radius, function(places) {
            store.removeMarkerType(type);
            places.map(function(place) {
                var m = new WovaxIdxMarker(place.lat, place.lon, color, type);
                if(place.hasOwnProperty('name')) {
                    m.setPopup(place.name, '');
                }
                store.addMarker(m);
            });
        });
    }
    initMarkers() {
        var self   = this;
        var props  = this.props;
        var colors = props.pinColors;
        var places = props.places;
        // setup listing marker
        this.mapStore.removeMarkerType('');
        this.mapStore.addMarker(new WovaxIdxMarker(...props.pos, colors.__listing));
        // load all other markers
        places.map(function(place) {
            self.loadMarkerType(place);
        });
    };
    componentDidUpdate(prev) {
        var cur   = this.props;
        var store = this.mapStore;
        // update colors
        Object.keys(cur.pinColors).map(function(type) {
            if(
                !prev.pinColors.hasOwnProperty(type) ||
                prev.pinColors[type] !== cur.pinColors[type]
            ) {
                var color = cur.pinColors[type];
                if(type === '__listing') {
                    type = '';
                }
                store.getMarkers(type).map(function(marker) {
                    marker.setColor(color);
                });
            }
        })
        // Remove unchecked markers
        prev.places.filter(function(place) {
            return !cur.places.includes(place);
        }).map(function(unchecked) {
            store.removeMarkerType(unchecked);
        });
        // add new markers
        var self = this;
        cur.places.filter(function(place) {
            return !prev.places.includes(place);
        }).map(function(added) {
            self.loadMarkerType(added);
        });
        // Reload all the markers if the radius changes.
        if(Number(prev.radius) !== Number(cur.radius)) {
            this.initMarkers();
        }
    }
    render() {
        var place_types  =  [
            ['__listing', 'Listing Pin'],
            ['airport', 'Airports'],
            ['atm', 'ATMs'],
            ['bank', 'Banks'],
            ['bus_station', 'Bus Stations'],
            ['cinema',  'Movie Theatres'],
            ['college', 'Colleges'],
            ['fuel', 'Gas Stations'],
            ['gym', 'Gyms'],
            ['hospital', 'Hospitals'],
            ['hotel', 'Hotels'],
            ['park', 'Parks'],
            ['pharmacy', 'Pharmacies'],
            //['place_of_worship', 'Places of Worship'],
            ['pub', 'Bars/Pubs'],
            ['railway_station', 'Rail Stations'],
            ['restaurant', 'Restaurants'],
            ['school', 'Schools'],
            ['stadium', 'Stadiums'],
            ['supermarket', 'Supermarkets'],
            //['toilet',  'Restrooms'],
        ];
        var self       = this;
        var props      = this.props;
        var store      = this.mapStore;
        var colors     = props.pinColors;
        var component  = wp.components;
        var editor     = wp.blockEditor;
        var element    = wp.element;
        return <element.Fragment>
            <editor.InspectorControls>
            <component.PanelBody title='Map Settings'>
                    <component.RangeControl
                        label='Height'
                        initialPosition={500}
                        min={250}
                        max={1500}
                        value={props.height}
                        onChange={function (value) {
                            props.onChangeHeight(value);
                        }}
                    ></component.RangeControl>
                    <component.TextControl
                        label='Search Radius in Miles (Max 18.5)'
                        value={props.radius}
                        onChange={ function (value) {
                            var reg = new RegExp('^[0-9]*[.]?[0-9]*$');
                            value = value.replace(new RegExp('^0+'), '');
                            if(value.length < 1) {
                                value = '0';
                            }
                            if(value.charAt(0) === '.') {
                                value = '0' + value;
                            }
                            if(!reg.test(value)) {
                                return;
                            }
                            if(value > 18.5) {
                                return;
                            }
                            props.onChangeRadius(value);
                        }}
                    ></component.TextControl>
                </component.PanelBody>
                <component.PanelBody title='Choose Places' initialOpen={false}>
                { place_types.map( function(place) {
                    if(place[0] === '__listing') {
                        return null;
                    }
                    return <component.CheckboxControl
                        label={place[1]}
                        checked={props.places.includes(place[0])}
                        onChange={ function() {
                            var type   = place[0];
                            var sel    = props.places.slice();
                            var index  = sel.indexOf(type);
                            if(index > -1) {
                                sel.splice(index, 1);
                            } else {
                                sel.push(type);
                            }
                            props.onChangePlaces(sel);
                        }}
                    ></component.CheckboxControl>;
                })}
                </component.PanelBody>
                <editor.PanelColorSettings
                    title='Pin Colors'
                    initialOpen={false}
                    colorSettings={[
                        ...place_types.filter(function (type) {
                            if(type[0] === '__listing') {
                                return true;
                            }
                            return props.places.includes(type[0]);
                        }).map(function(type) {
                            return {
                                label: type[1],
                                value: colors.hasOwnProperty(type[0]) ? colors[type[0]] : '',
                                onChange: function (color) {
                                    // if cleared this component does not give a string
                                    if (typeof color !== 'string') {
                                        color = '';
                                    }
                                    var types = Object.keys(colors);
                                    var copy = {};
                                    types.map(function(type_with_color) {
                                        copy[type_with_color] = colors[type_with_color];
                                    });
                                    copy[type[0]] = color;
                                    props.onChangePinColors(copy);
                                }
                            }
                        })
                    ]}
                ></editor.PanelColorSettings>
            </editor.InspectorControls>
            <WovaxIdxReactMap
                title='Points of Interest'
                height={props.height}
                token={WovaxBlockData.mapTokens.location_iq}
                lat={props.pos[0]}
                long={props.pos[1]}
                type='location_iq'
                noTokenMsg='Set a Location IQ key on the Wovax IDX Initial Setup page.'
                onMountGetMap={function(map) {
                    store.setMap(map);
                    self.initMarkers();
                }}
            ></WovaxIdxReactMap>
        </element.Fragment>;
    }
}
WovaxIdxPoiBlock.defaultProps = {
    api: null,
    height: 500,
    pinColors: {
        __listing: ''
    },
    pos: [46.7323603, -117.0010062],
    places: ['college', 'school'],
    radius: 1.5,
    onChangeHeight: function(val) { return; },
    onChangePinColors: function(val) { return; },
    onChangePlaces: function(val) { return; },
    onChangeRadius: function(val) { return; }
};

function wovax_idx_add_poi_block() {
    var pos        = [37.773972, -122.431297]; // San Francisco
    //var pos        = [46.7323603, -117.0010062]; // Moscow office
    var poiApi     = new WovaxIdxLocationIqPOI(WovaxBlockData.mapTokens.location_iq);
    var block      = {
        title: 'Points of Interest',
        icon: WovaxBlockData.icons.poi,
        description: 'Displays various points of interest near the property such as restaurants, and schools ect...',
        icon: WovaxBlockData.icons.poi,
        category: 'wovax-idx',
        attributes: {
            height: {
                type: 'number',
                default: 500
            },
            searchRadiusMiles: {
                type: 'string',
                default: 1.5
            },
            pinColors: {
                type: 'object',
                default: {
                    __listing: '',
                    college: '',
                    school: ''
                }
            },
            places: {
                type: 'array',
                default: ['college', 'school']
            }
        },
        save: function() {
            return null;
        }
    };
    block.edit = function(props) {
        var height          = props.attributes.height;
        var listingPinColor = props.attributes.listingPinColor;
        var searchRadius    = props.attributes.searchRadiusMiles;
        var tagColors       = props.attributes.tagColors;
        var init_markers    = function(radius) {
            // setup listing marker
            store.removeMarkerType('');
            store.addMarker(new WovaxIdxMarker(...pos, listingPinColor));
            // load all other markers
            props.attributes.tags.map(function(tag) {
                var color = '';
                if(tagColors.hasOwnProperty(tag)) {
                    color = tagColors[tag];
                }
                loadPoiMarkers(radius, tag, color);
            });
        };
        return <WovaxIdxPoiBlock
            api={poiApi}
            pos={pos}
            height={props.attributes.height}
            pinColors={props.attributes.pinColors}
            places={props.attributes.places}
            radius={props.attributes.searchRadiusMiles}
            onChangeHeight={function(value) {
                props.setAttributes( { height: value } );
            }}
            onChangePinColors={function(value) {
                props.setAttributes( { pinColors: value } );
            }}
            onChangePlaces={function(value) {
                props.setAttributes( { places: value } );
            }}
            onChangeRadius={function(value) {
                props.setAttributes( { searchRadiusMiles: value } );
            }}
        ></WovaxIdxPoiBlock>;
    }
    wp.blocks.registerBlockType('wovax-idx-wordpress/points-of-interest', block);
};
wovax_idx_add_poi_block();

class WovaxIdxMapBlock extends wp.element.Component {
    constructor(props) {
        super(props);
        this.mapStore = new WovaxIdxMapStore();
    }
    componentDidUpdate(prev) {
        // Adjust colors
        if(prev.pinColor !== this.props.pinColor) {
            var newColor = this.props.pinColor;
            var markers  = this.mapStore.getMarkers('');
            markers.map(function(marker) {
                marker.setColor(newColor);
            });
        }
    }
    render() {
        var component = wp.components;
        var editor    = wp.blockEditor;
        var element   = wp.element;
        var pos       = [46.7323603, -117.0010062]; // Moscow office
        var height    = this.props.height;
        var mapType   = this.props.type;
        var color     = this.props.pinColor;
        var store     = this.mapStore;
        var self      = this;
        return  <element.Fragment>
            <editor.InspectorControls>
                <component.PanelBody title='Map Settings'>
                <WovaxIdxReactDropDown
                    title='Provider'
                    selectMsg=' -- select a map provider -- '
                    value={mapType}
                    onChange={function (type) {
                        self.props.onChangeType(type);
                    }}
                    options={[
                        ['google', 'Google Maps'],
                        ['location_iq', 'Location IQ'],
                        ['map_quest', 'MapQuest']
                    ]}
                ></WovaxIdxReactDropDown>
                <component.RangeControl
                        label='Height'
                        initialPosition={500}
                        min={250}
                        max={1500}
                        value={height}
                        onChange={function (value) {
                            self.props.onChangeHeight(value);
                        }}
                ></component.RangeControl>
                </component.PanelBody>
                <editor.PanelColorSettings
                    title='Pin Colors'
                    initialOpen={false}
                    colorSettings={[
                        {
                            label: 'Listing Pin',
                            value: color,
                            onChange: function (value) {
                                self.props.onChangePinColor(value);
                            }
                        }
                    ]}
                ></editor.PanelColorSettings>
            </editor.InspectorControls>
            <WovaxIdxReactMap
                height={height}
                token={WovaxBlockData.mapTokens[mapType]}
                lat={pos[0]}
                long={pos[1]}
                type={mapType}
                onMountGetMap={function(map) {
                    store.removeMarkerType('');
                    store.setMap(map);
                    store.addMarker(new WovaxIdxMarker(...pos, color));
                }}
            ></WovaxIdxReactMap>
        </element.Fragment>;
    }
}
WovaxIdxMapBlock.defaultProps = {
    height: 500,
    pinColor: '',
    type: 'none',
    onChangeHeight: function(val) { return; },
    onChangePinColor: function(val) { return; },
    onChangeType: function(val) { return; }
};
wp.blocks.registerBlockType('wovax-idx-wordpress/listing-map', {
    title: 'Listing Map',
    icon: WovaxBlockData.icons.map,
    description: 'Show the listing on a map.',
    category: 'wovax-idx',
    attributes: {
        height: {
            type: 'number',
            default: 500
        },
        mapType: {
            type: 'string',
            default: 'none'
        },
        listingPinColor: {
            type: 'string',
            default: ''
        },
    },
    save: function() {
        return null;
    },
    edit: function (props) {
        return <WovaxIdxMapBlock
            height={props.attributes.height}
            pinColor={props.attributes.listingPinColor}
            type={props.attributes.mapType}
            token={WovaxBlockData.mapTokens[props.attributes.mapType]}
            onChangeHeight={function (value) {
                props.setAttributes( { height: value } );
            }}
            onChangePinColor={function (value) {
                props.setAttributes( { listingPinColor: value } );
            }}
            onChangeType={function (value) {
                props.setAttributes( { mapType: value } );
            }}
        ></WovaxIdxMapBlock>;
    }
});