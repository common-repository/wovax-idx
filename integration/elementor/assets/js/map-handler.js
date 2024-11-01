jQuery( window ).on( 'elementor/frontend/init', () => {
    const addHandler = ( $element ) => {
		var map_id = $element.find('.wovax-idx-map-widget')[0].id;
		if(typeof window[map_id + '_func'] === 'function') {
			window[map_id + '_func']();
         }
    };
    elementorFrontend.hooks.addAction( 'frontend/element_ready/wovax_idx_elementor_maps.default', addHandler );
});