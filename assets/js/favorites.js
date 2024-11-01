var WovaxIdxFavs = {
    ajaxUrl:  '',
    svg: {
        version: '1.1',
        viewBox: '-1 -1 102 90',
        height: 26,
        preserveAspectRatio: 'xMidYMid meet',
        xmlns: 'http://www.w3.org/2000/svg',
        path: {
            'stroke-width': 5,
            d: 'M50 86.8C35 75 2.5 58 2.5 28 2.5 13 13 2.5 28 2.5S50 18 50 18 57 2.5 72 2.5 97.5 13 97.5 28C97.5 58 65 75 50 86.8z'
        }
    },
    addEvent: function(e) {
        e.stopPropagation();
        e.preventDefault();
        var id      = this.getAttribute('data-idx-id');
        var feed    = this.getAttribute('data-idx-feed');
        WovaxIdxFavs.ajaxRequest('save_favorite', feed, id);
        this.setAttribute('data-idx-fav', 'yes');
        this.addEventListener('click',  WovaxIdxFavs.removeEvent);
        this.removeEventListener('click', WovaxIdxFavs.addEvent);
    },
    ajaxRequest(action, feed, id) {
        var request = jQuery.ajax({
            url: WovaxIdxFavs.ajaxUrl,
            method: "POST",
            dataType: "json",
            data: {
                feed_id: feed,
                mls: id,
                action: action
            }
        });
        request.done(function(response) {
            if(response.success) {
                return;
            }
            if(response.reason === 'no_login' && typeof WovaxIdxUserModal.showModal !== "undefined") {
                console.log(response);
                WovaxIdxUserModal.showModal(false, response.msg);
                return;
            }
            alert(response.msg);
        });
        request.fail(function(jqXHR, textStatus) {
            alert('Something happened try again. ('+textStatus+')');
        });
    },
    init: function() {
        var favs     = document.getElementsByClassName('wovax-idx-heart');
        var addItems = function(el) {
            if(
                el.tagName !== 'DIV' ||
                !el.hasAttribute('data-idx-id') ||
                !el.hasAttribute('data-idx-fav') ||
                !el.hasAttribute('data-idx-feed')
            ) {
                return;
            }
            var svg   = WovaxIdxFavs.getSvgNode();
            var click = WovaxIdxFavs.addEvent;
            if(el.getAttribute('data-idx-fav') === 'yes') {
                click = WovaxIdxFavs.removeEvent;
            }
            el.addEventListener('click',  click);
            el.appendChild(svg);
        };
        for(var i = 0; i < favs.length; i++) {
            addItems(favs[i]);
        }
    },
    getSvgNode: function() {
        var build = function(tag, obj) {
            var node = document.createElementNS("http://www.w3.org/2000/svg", tag);
            Object.keys(obj).map(function(key) {
                var item = obj[key];
                if(typeof item === 'object') {
                    node.appendChild(build(key, item));
                } else {
                    node.setAttribute(key, item);
                }
            });
            return node;
        };
        return build('svg', WovaxIdxFavs.svg)
    },
    removeEvent: function(e) {
        e.stopPropagation();
        e.preventDefault();
        var id      = this.getAttribute('data-idx-id');
        var feed    = this.getAttribute('data-idx-feed');
        WovaxIdxFavs.ajaxRequest('delete_favorite', feed, id);
        this.setAttribute('data-idx-fav', 'no');
        this.addEventListener('click', WovaxIdxFavs.addEvent);
        this.removeEventListener('click',  WovaxIdxFavs.removeEvent);
    }
};
WovaxIdxFavs.init();