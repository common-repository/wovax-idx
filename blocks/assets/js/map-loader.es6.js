class WovaxIdxMarker {
    constructor(latitude, longitude, color, type) {
        this.visCall    = null;
        this.popCall    = null;
        this.posCall    = null;
        this.unbindCall = null;
        this.type       = '';
        this.color      = '#FF365E';
        this.pop_up     = {
            title: '',
            msg: ''
        };
        this.setType(type);
        this.setColor(color);
        this.setPosistion(latitude, longitude);
    }
    // Hooks for native handling
    onAddPopup(callback) {
        this.popCall = callback;
    }
    onPosistionChange(callback) {
        this.posCall = callback;
    }
    onUnbind(callback) {
        this.unbindCall = callback;
    }
    onVisualChange(callback) {
        this.visCall = callback;
    }
    triggerAddPopup() {
        if(typeof this.popCall === 'function') {
            this.popCall(this.pop_up);
        }
    }
    triggerPosistionChange() {
        if(typeof this.posCall === 'function') {
            this.posCall();
        }
    }
    triggerUnbind() {
        if(typeof this.unbindCall === 'function') {
            this.unbindCall();
        }
    }
    triggeVisualChange() {
        if(typeof this.visCall === 'function') {
            this.visCall();
        }
    }
    getColors() {
        var color_ratio = 0;
        color_ratio += (parseInt(this.color.substr(1, 2), 16) / 255) * .3;
        color_ratio += (parseInt(this.color.substr(3, 2), 16) / 255) * .58;
        color_ratio += (parseInt(this.color.substr(5, 2), 16) / 255) * .12;
        // switch to a darker color if the constrast starts to get to low
        var path_color = color_ratio > .60 ? '#313131' : '#FFF';
        return {
            fill: this.color.toUpperCase(),
            stroke: path_color
        };
    }
    getPrimaryColor() {
        return this.color.toUpperCase();
    }
    // Get Methods
    getAsHtmlImage(height, width) {
        var styles = [
            ['height', height+'px'],
            ['width', width+'px'],
            ['posistion', 'absolute'],
            ['left', '0px'],
            ['top', '0px'],
        ];
        var html = '<img';
        html += ' style="' + styles.map(function(style) { return style[0] + ':' + style[1];}).join('; ') + '"';
        html += ' src="' + this.getBlobUrl() + '"';
        html += '/>';
        return html;
    }
    getBlobUrl() {
        return 'data:image/svg+xml;charset=UTF-8;base64,' + btoa(this.getSvgStr());
    }
    getPopup() {
        return this.pop_up;
    }
    getPosistion() {
        return this.pos;
    }
    getSvgStr() {
        var colors = this.getColors();
        var str = '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">';
        str += '<defs><filter x="-33.3%" y="-25.0%" width="166.7%" height="150.0%" filterUnits="objectBoundingBox" id="a">';
        str += '<feOffset dy="1" in="SourceAlpha" result="shadow"/>';
        str += '<feGaussianBlur stdDeviation=".5" in="shadow" result="shadowBlur"/>';
        str += '<feColorMatrix values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 .3 0" in="shadowBlur" result="shadowMatrix"/>';
        str += '</filter></defs>';
        str += '<path filter="url(#a)" transform="translate(4.5 1)" d="' + this.constructor.pinPath + '" fill="' + colors.fill +'" stroke="'+ colors.stroke +'"/>';
        str += '<path transform="translate(4.5 1)" d="' + this.constructor.pinPath + '" fill="' + colors.fill +'" stroke="'+ colors.stroke +'"/>';
        if(this.constructor.iconPaths.hasOwnProperty(this.type) && this.constructor.iconPaths[this.type].length > 0) {
            str += '<path d="' + this.constructor.iconPaths[this.type] + '" fill="'+ colors.stroke +'"/>';
        }
        str += '</svg>';
        return str;
    }
    getType() {
        return this.type;
    }
    // Set Methods
    setColor(color) {
        var reg    = new RegExp('^#[0-9A-Fa-f]{6}$|^#[0-9A-Fa-f]{3}$');
        this.color = reg.test(color) ? color : "#FF365E";
        this.triggeVisualChange();
    }
    setPopup(title, msg) {
        this.pop_up.title = title;
        this.pop_up.msg = msg;
        this.triggerAddPopup();
    }
    setPosistion(latitude, longitude) {
        this.pos = {
            latitude: Number(latitude),
            longitude: Number(longitude)
        };
        this.triggerPosistionChange();
    }
    setType(type) {
        this.type = this.constructor.iconPaths.hasOwnProperty(type) ? type : '';
        this.triggeVisualChange();
    }
    static get pinPath() {
        return 'M7.5-.5c4.414 0 8 3.443 8 7.701 0 3.439-2.885 8.11-6.788 12.74a1.593 1.593 0 0 1-2.424 0C2.385 15.311-.5 10.64-.5 7.201-.5 2.943 3.086-.5 7.5-.5z';
    }
    static get iconPaths() {
        return {
            airport: 'M12 4a.9.9 0 0 0-.9.9v2.42L7.72 9.43a.47.47 0 0 0 .39.848l2.99-.932v2.412l-.748.559a.38.38 0 0 0 .32.671L12 12.656l1.329.332a.379.379 0 0 0 .319-.671l-.748-.559V9.346l2.99.932a.47.47 0 0 0 .39-.847L12.9 7.32V4.9A.9.9 0 0 0 12 4z',
            atm: 'M7.956 4.5c-.248 0-.45.2-.45.444V9.39c0 .245.202.444.45.444h8.088c.248 0 .45-.199.45-.444V4.944a.447.447 0 0 0-.45-.444H7.956zm.859.889h6.37a.67.67 0 0 0 .41.405V8.54a.67.67 0 0 0-.41.405h-6.37a.67.67 0 0 0-.41-.405V5.794a.67.67 0 0 0 .41-.405zM12 5.833a1.34 1.34 0 0 0-1.348 1.334A1.34 1.34 0 0 0 12 8.5a1.34 1.34 0 0 0 1.348-1.333A1.34 1.34 0 0 0 12 5.833zm-2.247.89a.447.447 0 0 0-.45.444c0 .245.202.444.45.444s.45-.199.45-.444a.447.447 0 0 0-.45-.445zm4.494 0a.447.447 0 0 0-.45.444c0 .245.202.444.45.444s.45-.199.45-.444a.447.447 0 0 0-.45-.445zm-6.291 3.555a.45.45 0 0 0-.395.22.44.44 0 0 0 0 .448.45.45 0 0 0 .395.22h8.088a.45.45 0 0 0 .395-.22.44.44 0 0 0 0-.448.45.45 0 0 0-.395-.22H7.956zm0 1.333a.45.45 0 0 0-.395.22.44.44 0 0 0 0 .449.45.45 0 0 0 .395.22h8.088a.45.45 0 0 0 .395-.22.44.44 0 0 0 0-.449.45.45 0 0 0-.395-.22H7.956z',
            bank: 'M12 4a.51.51 0 0 0-.203.042L7.95 5.714a.74.74 0 0 0-.45.677c0 .41.34.741.758.741H8.5v3.423c0 .27.224.49.5.49s.5-.22.5-.49V7.132h1v3.423c0 .27.224.49.5.49s.5-.22.5-.49V7.132h1v3.423c0 .27.224.49.5.49s.5-.22.5-.49V7.132h1v3.423c0 .27.224.49.5.49s.5-.22.5-.49V7.132h.242a.75.75 0 0 0 .758-.74.74.74 0 0 0-.45-.678l-3.847-1.672A.509.509 0 0 0 12 4zm-4 8.022c-.276 0-.5.22-.5.489 0 .27.224.489.5.489h8c.276 0 .5-.22.5-.489a.495.495 0 0 0-.5-.489H8z',
            bus_station: 'M9.455 4C8.65 4 8 4.645 8 5.44V12.28c0 .398.325.72.727.72a.723.723 0 0 0 .728-.72v-.133c.652.072 1.5.133 2.545.133s1.893-.061 2.545-.133v.133c0 .398.326.72.728.72a.723.723 0 0 0 .727-.72V5.44C16 4.645 15.349 4 14.545 4h-5.09zm1.09.72h2.91c.2 0 .363.161.363.36 0 .199-.163.36-.363.36h-2.91a.362.362 0 0 1-.363-.36c0-.199.163-.36.363-.36zM8.727 6.16h6.546v1.955a.359.359 0 0 1-.298.354c-.46.08-1.451.211-2.975.211s-2.516-.132-2.975-.212a.358.358 0 0 1-.298-.353V6.16zm.728 3.6c.401 0 .727.322.727.72 0 .398-.326.72-.727.72a.723.723 0 0 1-.728-.72c0-.398.326-.72.728-.72zm5.09 0c.402 0 .728.322.728.72 0 .398-.326.72-.728.72a.723.723 0 0 1-.727-.72c0-.398.326-.72.727-.72z',
            cinema: 'M12.346 4c-.764 0-1.384.597-1.384 1.333 0 .737.62 1.334 1.384 1.334.765 0 1.385-.597 1.385-1.334 0-.736-.62-1.333-1.385-1.333zm-3.461.667c-.574 0-1.039.447-1.039 1 0 .552.465 1 1.039 1 .573 0 1.038-.448 1.038-1 0-.553-.465-1-1.038-1zm-.693 2.666A.68.68 0 0 0 7.5 8v3.333c0 .369.31.667.692.667h4.846a.68.68 0 0 0 .693-.667V8a.68.68 0 0 0-.693-.667H8.192zm7.962.334a.356.356 0 0 0-.193.056l-1.538.944v2l1.533.94a.356.356 0 0 0 .197.06.34.34 0 0 0 .347-.334V8a.34.34 0 0 0-.346-.333z',
            college: 'M11.823 5a.314.314 0 0 0-.125.023L7.254 6.8c-.152.06-.254.23-.254.422 0 .192.102.362.254.422l4.444 1.778c.076.03.158.03.234 0l3.957-1.583v2.618c-.22.154-.37.437-.37.765 0 .491.74 1.778.74 1.778S17 11.713 17 11.222c0-.328-.15-.611-.37-.765V7.245a.533.533 0 0 0-.005-.095.523.523 0 0 0-.006-.037c-.035-.172-.151-.304-.297-.334l-4.39-1.756A.315.315 0 0 0 11.823 5zM9 9v1.227s.574.773 2.5.773c1.926 0 2.5-.773 2.5-.773V9l-2.161.78a1.005 1.005 0 0 1-.678 0L9 9z',
            fuel: 'M10.61 5c-.696 0-1.263.538-1.263 1.2v6h-.42a.426.426 0 0 0-.37.198.383.383 0 0 0 0 .404c.077.124.218.2.37.198h5.89a.426.426 0 0 0 .37-.198.383.383 0 0 0 0-.404.426.426 0 0 0-.37-.198h-.42v-.8h.84c.693 0 1.263-.542 1.263-1.2V7.234l-.965-.917a.432.432 0 0 0-.301-.12.421.421 0 0 0-.39.249.386.386 0 0 0 .096.437l.298.283V7.8c0 .22.188.4.42.4v2c0 .226-.182.4-.42.4h-.842V6.2c0-.662-.566-1.2-1.262-1.2H10.61zm0 .8h2.524c.232 0 .42.18.42.4v1.2c0 .22-.188.4-.42.4H10.61a.41.41 0 0 1-.421-.4V6.2c0-.22.188-.4.42-.4z',
            gym: 'M9.747 6a.365.365 0 0 0-.369.361v2.08a.34.34 0 0 0 0 .115v2.082a.353.353 0 0 0 .186.313.391.391 0 0 0 .377 0 .353.353 0 0 0 .186-.313V8.856h3.746v1.782a.353.353 0 0 0 .186.313.391.391 0 0 0 .377 0 .353.353 0 0 0 .186-.313V8.56a.34.34 0 0 0 0-.116V6.36a.348.348 0 0 0-.11-.257.385.385 0 0 0-.27-.104.365.365 0 0 0-.37.361v1.782h-3.745V6.361a.348.348 0 0 0-.11-.257.385.385 0 0 0-.27-.104zm-1.124.713a.365.365 0 0 0-.369.361v1.07H7.88a.379.379 0 0 0-.329.176.342.342 0 0 0 0 .36.379.379 0 0 0 .329.176h.374v1.07a.353.353 0 0 0 .186.313.391.391 0 0 0 .378 0 .353.353 0 0 0 .186-.313V8.56a.34.34 0 0 0 0-.116v-1.37a.348.348 0 0 0-.11-.257.385.385 0 0 0-.27-.104zm6.742 0a.365.365 0 0 0-.369.361V8.44a.34.34 0 0 0 0 .116v1.37a.353.353 0 0 0 .186.313.391.391 0 0 0 .378 0 .353.353 0 0 0 .186-.313v-1.07h.374a.379.379 0 0 0 .329-.176.342.342 0 0 0 0-.36.379.379 0 0 0-.329-.177h-.374V7.074a.348.348 0 0 0-.11-.257.385.385 0 0 0-.27-.104z',
            hospital: 'M11.755 4.079l-3.556 2.51A.483.483 0 0 0 8 6.984v5.069c0 .523.398.947.889.947h6.222c.491 0 .889-.424.889-.947v-5.07a.483.483 0 0 0-.2-.394l-3.555-2.51a.423.423 0 0 0-.49 0zM13.5 9.5h-1v1a.5.5 0 0 1-1 0v-1h-1a.5.5 0 0 1 0-1h1v-1a.5.5 0 0 1 1 0v1h1a.5.5 0 0 1 0 1z',
            hotel: 'M8.429 5c-.395 0-.715.272-.715.608v2.13h.715v-.609c0-.168.16-.304.357-.304h2.5c.197 0 .357.136.357.304v.608h.714V7.13c0-.168.16-.304.357-.304h2.5c.197 0 .357.136.357.304v.608h.715V5.608c0-.336-.32-.608-.715-.608H8.43zM7.352 8.037c-.197.003-.355.141-.352.309v3.346a.298.298 0 0 0 .177.267.412.412 0 0 0 .36 0 .298.298 0 0 0 .177-.267v-.305h8.572v.305a.298.298 0 0 0 .177.267.412.412 0 0 0 .36 0 .298.298 0 0 0 .177-.267V8.346a.283.283 0 0 0-.105-.22.392.392 0 0 0-.258-.089c-.197.003-.354.141-.351.309H7.714a.283.283 0 0 0-.105-.22.392.392 0 0 0-.257-.089z',
            park: 'M12.05 4c-.813 0-1.5.574-1.694 1.35h-.003c-.663 0-1.242.48-1.347 1.12-.06.369.03.743.246 1.038a1.778 1.778 0 0 0-.305.3 1.838 1.838 0 0 0-.363 1.499 1.797 1.797 0 0 0 1.773 1.438h1.256v1.35h-.875a.435.435 0 0 0-.384.223.46.46 0 0 0 0 .453c.08.14.226.225.384.223h1.239a.426.426 0 0 0 .143 0h1.243a.435.435 0 0 0 .384-.223.46.46 0 0 0 0-.453.435.435 0 0 0-.384-.223h-.875v-1.35h1.255c.866 0 1.612-.605 1.773-1.44a1.836 1.836 0 0 0-.363-1.497 1.778 1.778 0 0 0-.304-.3c.217-.295.306-.67.245-1.038-.105-.64-.683-1.12-1.346-1.12h-.003C13.551 4.573 12.865 4 12.05 4z',
            pharmacy: 'M15.298 5.702a2.404 2.404 0 0 0-3.394 0l-3.2 3.2A2.384 2.384 0 0 0 8 10.6c0 .64.25 1.243.703 1.697A2.386 2.386 0 0 0 10.4 13c.641 0 1.244-.25 1.698-.703l3.2-3.2a2.404 2.404 0 0 0 0-3.395zm-.628 2.89L13.51 9.755a.4.4 0 0 1-.566 0l-1.697-1.697a.4.4 0 0 1 0-.566l1.223-1.223a1.595 1.595 0 0 1 1.132-.468c.385 0 .771.138 1.075.414.683.62.647 1.726-.006 2.379z',
            place_of_worship: '',
            pub: 'M11.618 5c-.48 0-.92.194-1.226.503-.08.081-.171.094-.294.033h-.001a1.321 1.321 0 0 0-.584-.136c-.692 0-1.263.542-1.263 1.2 0 .35.165.665.421.885V11.8c0 .663.566 1.2 1.263 1.2h3.369c.697 0 1.263-.537 1.263-1.2v-1.2a.44.44 0 0 0 .15-.029c.8-.07 1.423-.66 1.502-1.419A.383.383 0 0 0 16.25 9c0-.852-.715-1.55-1.601-1.592a1.16 1.16 0 0 0 .338-.808c0-.658-.57-1.2-1.263-1.2a1.321 1.321 0 0 0-.584.136c-.123.06-.215.048-.295-.033A1.73 1.73 0 0 0 11.618 5zm0 .8c.245 0 .46.096.614.252.338.34.88.4 1.297.193a.432.432 0 0 1 .195-.045c.238 0 .42.174.42.4 0 .226-.182.4-.42.4h-.842a.411.411 0 0 0-.421.4c0 1.641.442 1.534.42 2.036a.787.787 0 0 1-.491.696c-.61.237-1.193-.183-1.193-.732 0-.442.421-.4.421-2 0-.22-.188-.4-.42-.4H9.512c-.238 0-.42-.174-.42-.4 0-.226.182-.4.42-.4.071 0 .136.016.195.045.418.207.96.148 1.297-.193a.854.854 0 0 1 .613-.252zm2.948 2.4c.47 0 .842.353.842.8 0 .476-.341.8-.842.8V8.2z',
            railway_station: 'M11.989 3a.303.303 0 0 0-.077.013L8.246 4.124a.354.354 0 0 0-.235.264.399.399 0 0 0 .088.357.31.31 0 0 0 .322.094l3.58-1.084 3.578 1.084a.31.31 0 0 0 .322-.094.399.399 0 0 0 .088-.357.354.354 0 0 0-.235-.264l-3.666-1.11A.302.302 0 0 0 11.989 3zM12 4.852c-2.666 0-3 .868-3 1.481v4.074c0 .56.283 1.041.695 1.293L9.227 13H10l.4-1.111h3.2L14 13h.773l-.468-1.3c.412-.252.695-.733.695-1.293V6.333c0-.613-.333-1.481-3-1.481zm-2 1.481h1.333c.184 0 .334.166.334.37v1.482c0 .205-.15.37-.334.37H10c-.184 0-.333-.165-.333-.37V6.704c0-.205.15-.37.333-.37zm2.667 0H14c.184 0 .333.166.333.37v1.482c0 .205-.149.37-.333.37h-1.333c-.184 0-.334-.165-.334-.37V6.704c0-.205.15-.37.334-.37zm-2.5 3.704c.276 0 .5.249.5.556 0 .306-.224.555-.5.555s-.5-.249-.5-.555c0-.307.224-.556.5-.556zm3.666 0c.276 0 .5.249.5.556 0 .306-.224.555-.5.555s-.5-.249-.5-.555c0-.307.224-.556.5-.556z',
            restaurant: 'M9.422 5c-.236.004-.425.185-.422.405v1.997c0 .658.58 1.199 1.286 1.199v3.994a.395.395 0 0 0 .212.35.456.456 0 0 0 .432 0 .395.395 0 0 0 .213-.35V8.6c.705 0 1.286-.541 1.286-1.199V5.405a.395.395 0 0 0-.213-.35.456.456 0 0 0-.432 0 .395.395 0 0 0-.213.35v1.598c0 .12-.085.2-.214.2-.128 0-.214-.08-.214-.2V5.405a.386.386 0 0 0-.126-.288.445.445 0 0 0-.31-.117c-.236.004-.424.185-.421.405v1.598c0 .12-.086.2-.215.2-.128 0-.214-.08-.214-.2V5.405a.386.386 0 0 0-.126-.288.445.445 0 0 0-.31-.117zm4.078.006c-.118 0-.214.09-.214.2V12.594a.395.395 0 0 0 .212.35.456.456 0 0 0 .432 0 .395.395 0 0 0 .213-.35v-2.397h.428c.237 0 .429-.179.429-.4V8.602c0-.848 0-2.291-1.155-3.468a.448.448 0 0 0-.318-.127H13.5z',
            school: 'M12 4a.466.466 0 0 0-.129.018l-4.05 1.163a.425.425 0 0 0-.302.533c.071.227.32.356.558.288L12 4.876l3.923 1.126a.452.452 0 0 0 .558-.288.426.426 0 0 0-.301-.533l-4.051-1.163A.462.462 0 0 0 12 4zM7.997 7.01c-.259-.011-.478.18-.478.429v3.856c0 .233.196.419.44.428C10.781 11.835 12 13 12 13s1.218-1.165 4.042-1.277c.243-.01.439-.195.439-.428V7.438c0-.248-.22-.44-.478-.428C13.207 7.131 12 8.286 12 8.286S10.793 7.13 7.997 7.01z',
            stadium: 'M11.128 4.006a2.15 2.15 0 0 0-.478.089V5.47c.765-.853 1.98.388 2.7-.607-.826-.124-1.206-.943-2.222-.858zm3.122.755v1.287c.63-.616 1.62.61 2.25-.148-1.035-.143-1.08-1.045-2.25-1.14zm-6.047.177c-.184-.004-.411.054-.703.207v1.228c.63-.899 1.62-.092 2.25-1.228-.777.32-.991-.194-1.547-.207zM12 6.373c-3.104 0-4.5.947-4.5 1.42v4.26c0 .522.404.947.9.947h2.25v-1.612c0-.522.404-.947.9-.947h.9c.496 0 .9.425.9.947V13h2.25c.496 0 .9-.425.9-.947v-4.26c0-.521-1.396-1.42-4.5-1.42zm-.45 1.701v1.612h-.9V8.118l.9-.044zm.9 0l.9.044v1.568h-.9V8.074zm-2.7.192v1.42h-.9V8.46l.9-.193zm4.5 0l.9.193v1.227h-.9v-1.42zm-4.5 2.278v1.51h-.9v-1.42l.9-.09zm4.5 0l.9.09v1.42h-.9v-1.51z',
            supermarket: 'M8.598 5l-.696.005a.4.4 0 1 0 .006.8l.428-.003 1.317 3.161-.48.766c-.334.535.073 1.271.705 1.271H14.3a.4.4 0 1 0 0-.8H9.878c-.045 0-.05-.009-.026-.047l.471-.753h2.986a.8.8 0 0 0 .7-.412l1.44-2.594a.4.4 0 0 0-.35-.594H9.202l-.23-.554A.4.4 0 0 0 8.598 5zm1.303 6.4a.8.8 0 1 0 0 1.6.8.8 0 0 0 0-1.6zm4 0a.8.8 0 1 0 0 1.6.8.8 0 0 0 0-1.6z',
            toilet: '',
        };
    }
}

class WovaxIdxMapLoader {
    constructor() {
        // Effectively make this a singleton class
        const instance = this.constructor.instance;
        if (instance) {
            return instance;
        }
        this.constructor.instance = this;
        this.loaded = [];
        this.queued = [];
        this.init   = {};
    }
    registerMap(map) {
        var self = this;
        // load scripts after styles
        this.loadResources('style', map.getStyles(), this.loaded, this.queued, 0, function() {
            self.loadResources('script', map.getScripts(), self.loaded, self.queued, 0, function() {
                self.runInit(map.getInitFuncs());
                if(typeof map.render === 'function') {
                    map.render();
                }
            });
        });
    }
    runInit(functions) {
        var self = this;
        for (var key in functions) {
            if(
                functions.hasOwnProperty(key) !== true || self.init.hasOwnProperty(key)) {
                continue;
            }
            var func = functions[key];
            if(typeof func === 'function') {
                func();
                self.init[key] = true;
            }
        }
    }
    // These load functions let us asynchronously load map scripts and styles
    // then execute any rendering functions after loaded.
    loadResources(type, resources, loaded, queued, index, callback) {
        var self = this;
        if(index >= resources.length) {
        	if(typeof callback === 'function') {
            	callback();
            }
        	return;
        }
        // Skip to next resource this one has already been loaded.
        if(loaded.indexOf(resources[index]) > -1) {
            this.loadResources(type, resources, loaded, queued, index + 1, callback);
            return;
        }
        // this resource is queue so wait and try again
        if(queued.indexOf(resources[index]) > -1) {
            setTimeout(function() {
                self.loadResources(type, resources, loaded, queued, index, callback);
            }, 100); // wait 100 ms
            return;
        }
        queued.push(resources[index]);
        var dom_type  = type === 'style' ? 'link' : 'script';
        var type_attr = type === 'style' ? 'text/css' : 'text/javascript';
        var resource  = document.createElement(dom_type);
        if(type === 'script') {
            resource.src = resources[index];
        } else {
            resource.rel  = 'stylesheet';
            resource.href = resources[index];
        }
        resource.type   = type_attr;
        var onload = function() {
            loaded.push(resources[index]);
            resource.removeEventListener('load', onload);
            // remove from queued array
            var q_index = queued.indexOf(resources[index]);
            if(q_index > -1) {
                queued.splice(q_index, 1);
            }
            // load next resource.
            self.loadResources(type, resources, loaded, queued, index + 1, callback);
        };
        resource.addEventListener('load', onload);
        document.getElementsByTagName("head")[0].appendChild(resource);
    }
}

class WovaxIdxMap {
    constructor(parent, token, latitude, longitude) {
        this.markers   = [];
        this.styles    = [];
        this.scripts   = [];
        this.init      = {};
        this.token     = token;
        this.parent    = parent;
        this.center    = {
            latitude:  latitude !== undefined ? latitude : 46.7323603,
            longitude: longitude !== undefined ? longitude : -117.0010062
        };
        if(typeof parent === 'string') {
            this.parent = document.getElementById(parent);
        }
        this.render = this.render.bind(this);
    }
    addInitFunc(name, callback) {
        if(typeof callback !== 'function') {
            return;
        }
        this.init[name] = callback;
    }
    addScript(url) {
        this.scripts.push(url);
    }
    addStyle(url) {
        this.styles.push(url);
    }
    addMarker(marker) {
        return;
    }
    getCenter() {
        return this.center;
    }
    getParentElement() {
        return this.parent;
    }
    getInitFuncs() {
        return this.init;
    }
    getScripts() {
        return this.scripts;
    }
    getStyles() {
        return this.styles;
    }
    getToken() {
        return this.token;
    }
    render() {
        return;
    }
    setZoom(val) {
        return;
    }
    setView(val) {
        return;
    }
}

class WovaxIdxGoogleMap extends WovaxIdxMap {
    constructor(parent, token, latitude, longitude, use_iframe) {
        super(parent, token, latitude, longitude);
        if(use_iframe !== true) {
            this.addScript('https://maps.googleapis.com/maps/api/js?key=' + token);
        }
        this.async_markers = null;
        this.map           = null;
        this.rendered      = false;
        this.zoom          = 15;
        this.view          = 'roadmap';
    }
    render() {
        var center = this.getCenter();
        var mapOptions = {
            center: new google.maps.LatLng(center.latitude, center.longitude),
            zoom: this.zoom,
            mapTypeId: this.view
        }
        this.map = new google.maps.Map(this.getParentElement(), mapOptions);
        this.rendered = true;
        if(typeof this.async_markers === 'function') {
            this.async_markers();
            this.async_markers = null;
        }
    }
    addMarker(marker) {
        var self      = this;
        var size      = 35;
        var add_mark  = function() {
            var icon = {
                url: marker.getBlobUrl(),
                anchor: new google.maps.Point(size / 2, size),
                scaledSize: new google.maps.Size(size, size)
            };
            var google_marker = new google.maps.Marker({
                position: new google.maps.LatLng(
                    marker.getPosistion().latitude,
                    marker.getPosistion().longitude
                ),
                icon: icon
            });
            google_marker.setMap(self.map);
            // Add Popup functionality
            var listener = null;
            var addPopup = function(popUp) {
                var html = '';
                if(popUp.title.length > 0) {
                    html += '<b>' + popUp.title + '</b><br>';
                    google_marker.setTitle(popUp.title);
                }
                if(popUp.msg.length > 0) {
                    html += popUp.msg;
                }
                if(html.length > 0) {
                    if(listener !== null) {
                        google.maps.event.removeListener(listener);
                    }
                    var infoPopup = new google.maps.InfoWindow({
                        content: html
                    });
                    google_marker.addListener('click', function() {
                        infoPopup.open(self.map, google_marker);
                    });
                }
            };
            addPopup(marker.getPopup());
            marker.onAddPopup(addPopup);
            marker.onVisualChange(function() {
                icon.url = marker.getBlobUrl();
                google_marker.setIcon(icon);
            });
            marker.onPosistionChange(function() {
                var latlng = new google.maps.LatLng(
                    marker.getPosistion().latitude,
                    marker.getPosistion().longitude
                );
                google_marker.setPosition(latlng);
            });
            marker.onUnbind(function() {
                marker.onPosistionChange(null);
                marker.onUnbind(null);
                marker.onVisualChange(null);
                google_marker.setMap(null);
                google_marker = null;
            });
        };
        if(this.rendered) {
            add_mark();
            return;
        }
        var old_func = this.async_markers;
        this.async_markers = function() {
            add_mark();
            if(typeof old_func === 'function') {
                old_func();
            }
        }
    }
    setZoom(val) {
        if(val > 18) {
            val = 18;
        }
        if(val < 0) {
            val = 0;
        }
        this.zoom = val;
        if(!this.rendered) {
            return;
        }
        this.map.setZoom(val);
    }
    setView(val) {
        if(val != 'roadmap' && val != 'satellite' && val != 'hybrid') {
            return;
        }
        this.view = val;
        if(!this.rendered) {
            return;
        }
        this.map.setMapTypeId(val);
    }
}

class WovaxIdxLeafletMap extends WovaxIdxMap {
    constructor(parent, token, latitude, longitude) {
        super(parent, token, latitude, longitude);
        this.addStyle('https://leafletjs-cdn.s3.amazonaws.com/content/leaflet/master/leaflet.css');
        this.addScript('https://leafletjs-cdn.s3.amazonaws.com/content/leaflet/master/leaflet.js');
        this.async_markers = null;
        this.map           = null;
        this.rendered      = false;
        this.lib           = null;
        this.zoom          = 16;
        this.last_layer    = null;
        this.view          = 'roadmap';
    }
    render() {
        this.lib      = L.noConflict();
        var center    = this.getCenter();
        var parent_el = this.getParentElement();
        var container = this.lib.DomUtil.get(parent_el);
        if(container != null){
            container._leaflet_id = null;
        }
        // Create Map
        this.last_layer = this.getTile();
        var map = this.lib.map(container, {
            center: [center.latitude, center.longitude],
            //layers: this.getTile(),
            zoom: this.zoom,
            scrollWheelZoom: true,
            // what ever mapquest is doing causes it to break without a layer.
            layers: this.lib.tileLayer('')
        });
        // set the map property
        this.map = map;
        // add specfics for a tile provider
        this.addTileProviderSpecifics(map);
        // Add any markers that where added before render
        if(typeof this.async_markers === 'function') {
            this.async_markers();
            this.async_markers = null;
        }
        // done rendering
        this.rendered = true;
        // Check if container height as changed and invalidate the map size
        // ideally could use ResizeObserver but it's not a standard yet and
        // only chrome supports, and not sure if babel can compile either.
        // So this may have some uneeded triggers, but this will detect
        // the inline style change of parent div.
        var observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                map.invalidateSize();
            });
        });
        var config = {
            attributes: true,
            attributeFilter: ['style'],
            childList: false,
            characterData: false
        };
        observer.observe(container, config);
    }
    addMarker(marker) {
        var self = this;
        var size = 35;
        var add_mark  = function() {
            var map_marker = self.lib.marker([
                marker.getPosistion().latitude,
                marker.getPosistion().longitude
            ]);
            var icon = self.lib.icon({
                iconUrl: marker.getBlobUrl(),
                iconRetinaUrl: marker.getBlobUrl(),
                iconSize: [size, size],
                iconAnchor: [size / 2, size],
                tooltipAnchor: [0, -(size/1.5)],
                popupAnchor: [0, -(size/1.5)]
            });
            map_marker.setIcon(icon);
            map_marker.addTo(self.map);
            document.body.addEventListener('touchstart', function(){
                map_marker.unbindTooltip();
            }, false);
            // Add Popup functionality
            var addPopup = function(popUp) {
                var html = '';
                if(popUp.title.length > 0) {
                    html += '<b>' + popUp.title + '</b><br>';
                    map_marker.unbindTooltip();
                    map_marker.bindTooltip(popUp.title);
                }
                if(popUp.msg.length > 0) {
                    html += popUp.msg;
                }
                if(html.length > 0) {
                    map_marker.unbindPopup();
                    map_marker.bindPopup(html);
                }
            };
            addPopup(marker.getPopup());
            marker.onAddPopup(addPopup);
            // on screens with touch screen the tool tip is annoying
            document.body.addEventListener('touchstart', function(){
                map_marker.unbindTooltip();
            }, false);
            // a laptop might have a touch screen and mouse so re-add
            document.body.addEventListener('touchend', function(){
                var popUp = marker.getPopup();
                if(popUp.title.length > 0) {
                    ap_marker.bindTooltip(popUp.title);
                }
            }, false);
            marker.onVisualChange(function() {
                // some reason modifying the orginal icon was not working.
                // Create a new one and set it use that.
                var icon = self.lib.icon({
                    iconUrl: marker.getBlobUrl(),
                    iconRetinaUrl: marker.getBlobUrl(),
                    iconSize: [size, size],
                    iconAnchor: [size / 2, size],
                    tooltipAnchor: [0, -(size/1.5)],
                    popupAnchor: [0, -(size/1.5)]
                });
                map_marker.setIcon(icon);
            });
            marker.onPosistionChange(function() {
                map_marker.setLatLng(new self.lib.LatLng(
                    marker.getPosistion().latitude,
                    marker.getPosistion().longitude
                ));
            });
            marker.onUnbind(function() {
                marker.onPosistionChange(null);
                marker.onUnbind(null);
                marker.onVisualChange(null);
                self.map.removeLayer(map_marker);
            });
        };
        if(this.rendered) {
            add_mark();
            return;
        }
        var old_func = this.async_markers;
        this.async_markers = function() {
            add_mark();
            if(typeof old_func === 'function') {
                old_func();
            }
        }
    }
    getTile() {
        var lib = this.getLib();
        return lib.tileLayer('');
    }
    addTileProviderSpecifics(map) {
        return;
    }
    getLib() {
        return this.lib;
    }
    setZoom(val) {
        if(val > 18) {
            val = 18;
        }
        if(val < 2) {
            val = 2;
        }
        this.zoom = val;
        if(!this.rendered) {
            return;
        }
        this.map.setZoom(val);
    }
    setView(val) {
    }
}

class WovaxIdxLocationIqMap extends WovaxIdxLeafletMap {
    constructor(parent, token, latitude, longitude) {
        super(parent, token, latitude, longitude);
        this.addScript('https://tiles.unwiredmaps.com/js/leaflet-unwired.js');
    }
    getTile() {
        var lib = this.getLib();
        return lib.tileLayer.Unwired({key: this.getToken(), scheme: "streets"});
    }
    addTileProviderSpecifics(map) {
        var lib     = this.getLib();
        var streets = lib.tileLayer.Unwired({key: this.getToken(), scheme: "streets"});
        var earth   = lib.tileLayer.Unwired({key: this.getToken(), scheme: "earth"});
        var hybrid  = lib.tileLayer.Unwired({key: this.getToken(), scheme: "hybrid"});
        map.addLayer(streets);
        //lib.control.scale().addTo(map);
        //lib.control.layers({
        //    "Streets" : streets,
        //    "Earth" :   earth,
        //    "Hybrid" :  hybrid,
        //}, null, {
        //    position: "topright"
        //}).addTo(map);
    }
}

class WovaxIdxMapQuest extends WovaxIdxLeafletMap {
    constructor(parent, token, latitude, longitude) {
        super(parent, token, latitude, longitude);
        this.addStyle('https://api.mqcdn.com/sdk/mapquest-js/v1.3.2/mapquest-core.css');
        this.addScript('https://api.mqcdn.com/sdk/mapquest-js/v1.3.2/mapquest-core.js');
    }
    getTile() {
        var lib          = this.getLib();
        lib.mapquest.key = this.getToken();
        var street       = lib.mapquest.tileLayer('map');
        var hybrid       = lib.mapquest.tileLayer('hybrid');
        var satellite    = lib.mapquest.tileLayer('satellite');
        switch(this.view) {
            case 'satellite':
                return satellite;
            case 'hybrid':
                return hybrid;
            default:
                return street;
        }
    }
    addTileProviderSpecifics(map) {
        var lib          = this.getLib();
        lib.mapquest.key = this.getToken();
		map.addLayer(lib.mapquest.tileLayer('map'));
		map.addControl(this.lib.mapquest.control());
		//map.addLayer(this.getTile());
		//var type = 'hybrid';
		//if(this.view == 'satellite') {
		//	type = 'satellite'
		//}
		//map.addControl(this.lib.mapquest.control({
		//	mapType: type
		//}));
    }
	setView(val) {
        if(val != 'roadmap' && val != 'satellite' && val != 'hybrid') {
            return;
        }
        this.view = val;
        if(!this.rendered) {
            return;
        }
        //this.map.addLayer(this.getTile());
	}
}

class WovaxIdxLocationIqPOI {
    constructor(token) {
        this.token  = token;
        this.places = {};
        this.cache  = {};
        this.time   = 1;
    }
    getPlaces(type, lat, long, radius, callback) {
        var radius = Math.round(radius);
        if(radius < 1) {
            radius = 1;
        }
        var pos_key   = ('la:'+lat+'lo:'+long).replace('-','_');
        var cache     = null;
        var cache_rad = 0;
        if(
            this.cache.hasOwnProperty(pos_key) &&
            this.cache[pos_key].hasOwnProperty(type)
        ) {
            cache_rad = this.cache[pos_key][type].radius;
            cache     = this.cache[pos_key][type].cache;
        }
        // if radius is less than just filter results
        if(cache !== null && radius <= cache_rad ) {
            callback(cache.filter(function(place) {
                return place.distance <= radius;
            }));
            return;
        }
        // Make request since cache is not populated
        var url = 'https://us1.locationiq.com/v1/nearby.php?';
        var params = {
            key: this.token,
            lat: lat,
            lon: long,
            tag: type,
            radius: radius,
            format: 'json'
        };
        url += Object.keys(params).map(function(key) {
   				return key + "=" + encodeURIComponent(params[key]);
        }).join('&');
        var xhr = new XMLHttpRequest();
        xhr.open('GET', url, true);
        var self = this;
        xhr.onload = function(e) {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    var results = JSON.parse(xhr.responseText);
                    if(!self.cache.hasOwnProperty(pos_key)) {
                        self.cache[pos_key] = {};
                    }
                    self.cache[pos_key][type] = {
                        radius: radius,
                        cache: results
                    }
                    callback(results);
                } else {
                    if(cache !== null) {

                        callback(cache);
                    } else {
                        callback([]);
                    }
                }
            }
        };
        // Api is limited to on request a second
        var wait = this.time;
		this.time += 1000;
        setTimeout(function () {
            console.log(url);
            xhr.send(null);
            self.time -=1000;
        }, wait);
    }
}

class WovaxAppleMap extends WovaxIdxMap {
    constructor(parent, token, latitude, longitude) {
        super(parent, token, latitude, longitude);
        this.async_markers = null;
        this.map           = null;
        this.rendered      = false;
        this.zoom          = 16;
        this.view          = 'roadmap';
        this.addScript('https://cdn.apple-mapkit.com/mk/5.x.x/mapkit.js');
        this.addInitFunc('reg-token', function () {
            console.log("Registering Apple Map Kit Key\n");
            mapkit.init({
                authorizationCallback: function(done) {
                    done(token);
                },
                language: "en"
            });
        });
    }
    render() {
        var center    = this.getCenter();
        var newCenter = new mapkit.Coordinate(center.latitude, center.longitude);
        this.map = new mapkit.Map(this.getParentElement());
        this.map.setCenterAnimated(newCenter, true);
        switch(this.view) {
            case 'satellite':
                this.map.mapType = mapkit.Map.MapTypes.Satellite;
                break;
            case 'hybrid':
                this.map.mapType = mapkit.Map.MapTypes.Hybrid;
                break;
            default:
                break;
        }
        this.rendered = true;
        if(typeof this.async_markers === 'function') {
            this.async_markers();
        }
        this.map._impl.zoomLevel = this.zoom;
        this.async_markers = null;
    }
    addMarker(marker) {
        var self        = this;
        var marker_func = function() {
            var pos  = marker.getPosistion();
            var cord = new mapkit.Coordinate(pos.latitude, pos.longitude);
            var anno = new mapkit.MarkerAnnotation(cord, {
                color: marker.getPrimaryColor()
            });
            self.map.showItems([anno]);
        };
        if(this.rendered) {
            marker_func();
            return;
        }
        // Queue Up markers if the map has not been rendered yet
        var old_func = this.async_markers;
        this.async_markers = function() {
            marker_func();
            if(typeof old_func === 'function') {
                old_func();
            }
        }
    }
    setZoom(val) {
        if(val > 18) {
            val = 18;
        }
        if(val < 0) {
            val = 0;
        };
        this.zoom = val;
        if(!this.rendered) {
            return;
        }
        this.map._impl.zoomLevel = val;
    }
    setView(val) {
        if(val != 'roadmap' && val != 'satellite' && val != 'hybrid') {
            return;
        }
        this.view = val;
        if(!this.rendered) {
            return;
        }
        switch(this.view) {
            case 'satellite':
                this.map.mapType = mapkit.Map.MapTypes.Satellite;
                break;
            case 'hybrid':
                this.map.mapType = mapkit.Map.MapTypes.Hybrid;
                break;
            default:
                this.map.mapType = mapkit.Map.MapTypes.Standard;
                break;
        }
    }
}