var WovaxBlockData = {
    fields: [
        'Acres', 'Bathrooms', 'Bedrooms', 'City', 'Description',
        'MLS Number', 'Price', 'Property Type', 'State', 'Status',
        'Street Address', 'Square Footage', 'Lot Size', 'Zip Code'
    ],
    // Add Icon SVGs to data the data container
    icons: {
        favorite: wp.element.createElement(
            'svg',
            {
                width: 20,
                viewBox: '-1 -1 102 90',
                preserveAspectRatio: 'xMidYMid meet',
            },
            wp.element.createElement(
                'path',
                {
                    fill: '#FF365E',
                    stroke: '#FF365E',
                    strokeWidth: 5,
                    d: 'M50 86.8C35 75 2.5 58 2.5 28 2.5 13 13 2.5 28 2.5S50 18 50 18 57 2.5 72 2.5 97.5 13 97.5 28C97.5 58 65 75 50 86.8z'
                }
            )
        ),
        field: wp.element.createElement(
            'svg',
            {
                width: 23,
                height: 14
            },
            wp.element.createElement(
                'g',
                {
                    fill: 'none',
                    fillRule: 'evenodd'
                },
                wp.element.createElement(
                    'path',
                    {
                        d: 'M0-6h24v24H0z'
                    }
                ),
                wp.element.createElement(
                    'text',
                    {
                        fontFamily: 'HelveticaNeue, Helvetica Neue',
                        fontSize: 18,
                        fill: '#FF365E',
                        transform: 'translate(0 -6)'
                    },
                    wp.element.createElement(
                        'tspan',
                        {
                            x: '1.335',
                            y: '18.5'
                        },
                        'Aa'
                    )
                )
            )
        ),
        gallery: wp.element.createElement(
            'svg',
            {
                width: 20,
                height: 20
            },
            wp.element.createElement(
                'g',
                {
                    fill: 'none',
                    fillRule: 'evenodd'
                },
                wp.element.createElement(
                    'path',
                    {
                        d: 'M-2-2h24v24H-2z'
                    }
                ),
                wp.element.createElement(
                    'path',
                    {
                        fill: '#FF365E',
                        fillRule: 'nonzero',
                        d: 'M18 2v12H6V2h12zm0-2H6L4 2v12l2 2h12l2-2V2l-2-2z'
                    }
                ),
                wp.element.createElement(
                    'path',
                    {
                        fill: '#FF365E',
                        d: 'M10 10l1 2 3-3 3 4H7z'
                    }
                ),
                wp.element.createElement(
                    'path',
                    {
                        fill: '#FF365E',
                        d: 'M0 4v14l2 2h14v-2H2V4z'
                    }
                )
            )
        ),
        map: wp.element.createElement(
            'svg',
            {
                width: 20,
                height: 20
            },
            wp.element.createElement(
                'path',
                {
                    fill: '#FF365E',
                    d: 'M10.189 1.003C6.048.899 2.606 3.878 2.502 7.655c-.107 3.876 4.452 9.714 6.342 11.962.417.493 1.216.513 1.66.041 2.01-2.15 6.886-7.754 6.994-11.63.104-3.776-3.168-6.922-7.31-7.025zM9.9 11.45a3.5 3.326 0 1 1 .183-6.648A3.5 3.326 0 0 1 9.9 11.45z'
                }
            )
        ),
        paragraph: wp.element.createElement(
            'svg',
            {
                width: 20,
                height: 20
            },
            wp.element.createElement(
                'g',
                {
                    fill: 'none',
                    fillRule: 'evenodd'
                },
                wp.element.createElement(
                    'path',
                    {
                        d: 'M-4-3h24v24H-4z'
                    }
                ),
                wp.element.createElement(
                    'path',
                    {
                        fill: '#FF365E',
                        fillRule: 'nonzero',
                        d: 'M7 2v7H5.5C3.6 9 2 7.4 2 5.5S3.6 2 5.5 2H7zm8-2H5.5C2.5 0 0 2.5 0 5.5S2.5 11 5.5 11H7v7h2V2h2v16h2V2h2V0z'
                    }
                )
            )
        ),
        poi: wp.element.createElement(
            'svg',
            {
                width: 20,
                height: 20
            },
            wp.element.createElement(
                'path',
                {
                    fill: '#FF365E',
                    d: 'M15.5 8C13.015 8 11 9.934 11 12.32c0 2.449 2.831 6.062 4.002 7.451a.656.656 0 0 0 .996 0c1.17-1.39 4.002-5.002 4.002-7.45C20 9.934 17.985 8 15.5 8zm0 6.6a2.1 2.1 0 1 1 0-4.2 2.1 2.1 0 0 1 0 4.2zM4.5 0C2.015 0 0 1.934 0 4.32c0 2.449 2.831 6.062 4.002 7.451a.656.656 0 0 0 .996 0C6.168 10.381 9 6.77 9 4.321 9 1.934 6.985 0 4.5 0zm0 6.6a2.1 2.1 0 1 1 0-4.2 2.1 2.1 0 0 1 0 4.2z'
                }
            )
        ),
        timestamp: wp.element.createElement(
            'svg',
            {
                width: 24,
                height: 24
            },
            wp.element.createElement(
                'path',
                {
                    fill: '#FF365E',
                    d: 'M12,2 C6.477,2 2,6.477 2,12 C2,17.523 6.477,22 12,22 C17.523,22 22,17.523 22,12 C22,6.477 17.523,2 12,2 Z M14.586,16 L11.293,12.707 C11.105,12.519 11,12.265 11,12 L11,7 C11,6.448 11.448,6 12,6 L12,6 C12.552,6 13,6.448 13,7 L13,11.586 L16,14.586 C16.39,14.976 16.39,15.61 16,16 L16,16 C15.61,16.39 14.976,16.39 14.586,16 Z'
                }
            )
        )
    },
    // populate with global tokens
    mapTokens: {
        google: '',
        location_iq: '',
        map_quest: '',
        none: ''
    }
};