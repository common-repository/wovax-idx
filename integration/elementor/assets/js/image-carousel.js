jQuery(window).on('elementor/frontend/init', () => {
    const addHandler = ( $element ) => {
        def = elementorFrontend.elementsHandler.getHandler('image-carousel.default');
        elementorFrontend.elementsHandler.addHandler(def, {
            $element,
        });
    };

    elementorFrontend.hooks.addAction('frontend/element_ready/wovax-idx-image-carousel.default', addHandler);
 } );