/*
    A simple jQuery modal (http://github.com/kylefox/jquery-modal)
    Version 0.8.0
*/

(function (factory) {
  // Making your jQuery plugin work better with npm tools
  // http://blog.npmjs.org/post/112712169830/making-your-jquery-plugin-work-better-with-npm
  if(typeof module === "object" && typeof module.exports === "object") {
    factory(require("jquery"), window, document);
  }
  else {
    factory(jQuery, window, document);
  }
}(function($, window, document, undefined) {

  var wxmodals = [],
      getCurrent = function() {
        return wxmodals.length ? wxmodals[wxmodals.length - 1] : null;
      },
      selectCurrent = function() {
        var i,
            selected = false;
        for (i=wxmodals.length-1; i>=0; i--) {
          if (wxmodals[i].$blocker) {
            wxmodals[i].$blocker.toggleClass('current',!selected).toggleClass('behind',selected);
            selected = true;
          }
        }
      };

  $.wxmodal = function(el, options) {
    var remove, target;
    this.$body = $('body');
    this.options = $.extend({}, $.wxmodal.defaults, options);
    this.options.doFade = !isNaN(parseInt(this.options.fadeDuration, 10));
    this.$blocker = null;
    if (this.options.closeExisting)
      while ($.wxmodal.isActive())
        $.wxmodal.close(); // Close any open modals.
    wxmodals.push(this);
    if (el.is('a')) {
      target = el.attr('href');
      //Select element by id from href
      if (/^#/.test(target)) {
        this.$elm = $(target);
        if (this.$elm.length !== 1) return null;
        this.$body.append(this.$elm);
        this.open();
      //AJAX
      } else {
        this.$elm = $('<div>');
        this.$body.append(this.$elm);
        remove = function(event, modal) { modal.elm.remove(); };
        this.showSpinner();
        el.trigger($.wxmodal.AJAX_SEND);
        $.get(target).done(function(html) {
          if (!$.wxmodal.isActive()) return;
          el.trigger($.wxmodal.AJAX_SUCCESS);
          var current = getCurrent();
          current.$elm.empty().append(html).on($.wxmodal.CLOSE, remove);
          current.hideSpinner();
          current.open();
          el.trigger($.wxmodal.AJAX_COMPLETE);
        }).fail(function() {
          el.trigger($.wxmodal.AJAX_FAIL);
          var current = getCurrent();
          current.hideSpinner();
          wxmodals.pop(); // remove expected modal from the list
          el.trigger($.wxmodal.AJAX_COMPLETE);
        });
      }
    } else {
      this.$elm = el;
      this.$body.append(this.$elm);
      this.open();
    }
  };

  $.wxmodal.prototype = {
    constructor: $.wxmodal,

    open: function() {
      var m = this;
      this.block();
      if(this.options.doFade) {
        setTimeout(function() {
          m.show();
        }, this.options.fadeDuration * this.options.fadeDelay);
      } else {
        this.show();
      }
      $(document).off('keydown.wxmodal').on('keydown.wxmodal', function(event) {
        var current = getCurrent();
        if (event.which == 27 && current.options.escapeClose) current.close();
      });
      if (this.options.clickClose)
        this.$blocker.click(function(e) {
          if (e.target==this)
            $.wxmodal.close();
        });
    },

    close: function() {
      wxmodals.pop();
      this.unblock();
      this.hide();
      if (!$.wxmodal.isActive())
        $(document).off('keydown.wxmodal');
    },

    block: function() {
      this.$elm.trigger($.wxmodal.BEFORE_BLOCK, [this._ctx()]);
      this.$body.css('overflow','hidden');
      this.$blocker = $('<div class="jquery-modal blocker current"></div>').appendTo(this.$body);
      selectCurrent();
      if(this.options.doFade) {
        this.$blocker.css('opacity',0).animate({opacity: 1}, this.options.fadeDuration);
      }
      this.$elm.trigger($.wxmodal.BLOCK, [this._ctx()]);
    },

    unblock: function(now) {
      if (!now && this.options.doFade)
        this.$blocker.fadeOut(this.options.fadeDuration, this.unblock.bind(this,true));
      else {
        this.$blocker.children().appendTo(this.$body);
        this.$blocker.remove();
        this.$blocker = null;
        selectCurrent();
        if (!$.wxmodal.isActive())
          this.$body.css('overflow','');
      }
    },

    show: function() {
      this.$elm.trigger($.wxmodal.BEFORE_OPEN, [this._ctx()]);
      if (this.options.showClose) {
        this.closeButton = $('<a href="#close-wxmodal" rel="wxmodal:close" class="close-wxmodal ' + this.options.closeClass + '">' + this.options.closeText + '</a>');
        this.$elm.append(this.closeButton);
      }
      this.$elm.addClass(this.options.modalClass).appendTo(this.$blocker);
      if(this.options.doFade) {
        this.$elm.css('opacity',0).show().animate({opacity: 1}, this.options.fadeDuration);
      } else {
        this.$elm.show();
      }
      this.$elm.trigger($.wxmodal.OPEN, [this._ctx()]);
    },

    hide: function() {
      this.$elm.trigger($.wxmodal.BEFORE_CLOSE, [this._ctx()]);
      if (this.closeButton) this.closeButton.remove();
      var _this = this;
      if(this.options.doFade) {
        this.$elm.fadeOut(this.options.fadeDuration, function () {
          _this.$elm.trigger($.wxmodal.AFTER_CLOSE, [_this._ctx()]);
        });
      } else {
        this.$elm.hide(0, function () {
          _this.$elm.trigger($.wxmodal.AFTER_CLOSE, [_this._ctx()]);
        });
      }
      this.$elm.trigger($.wxmodal.CLOSE, [this._ctx()]);
    },

    showSpinner: function() {
      if (!this.options.showSpinner) return;
      this.spinner = this.spinner || $('<div class="' + this.options.wxmodalClass + '-spinner"></div>')
        .append(this.options.spinnerHtml);
      this.$body.append(this.spinner);
      this.spinner.show();
    },

    hideSpinner: function() {
      if (this.spinner) this.spinner.remove();
    },

    //Return context for custom events
    _ctx: function() {
      return { elm: this.$elm, $blocker: this.$blocker, options: this.options };
    }
  };

  $.wxmodal.close = function(event) {
    if (!$.wxmodal.isActive()) return;
    if (event) event.preventDefault();
    var current = getCurrent();
    current.close();
    return current.$elm;
  };

  // Returns if there currently is an active modal
  $.wxmodal.isActive = function () {
    return wxmodals.length > 0;
  }

  $.wxmodal.getCurrent = getCurrent;

  $.wxmodal.defaults = {
    closeExisting: true,
    escapeClose: true,
    clickClose: true,
    closeText: 'Close',
    closeClass: '',
    modalClass: "wxmodal",
    spinnerHtml: null,
    showSpinner: true,
    showClose: true,
    fadeDuration: null,   // Number of milliseconds the fade animation takes.
    fadeDelay: 1.0        // Point during the overlay's fade-in that the modal begins to fade in (.5 = 50%, 1.5 = 150%, etc.)
  };

  // Event constants
  $.wxmodal.BEFORE_BLOCK = 'wxmodal:before-block';
  $.wxmodal.BLOCK = 'wxmodal:block';
  $.wxmodal.BEFORE_OPEN = 'wxmodal:before-open';
  $.wxmodal.OPEN = 'wxmodal:open';
  $.wxmodal.BEFORE_CLOSE = 'wxmodal:before-close';
  $.wxmodal.CLOSE = 'wxmodal:close';
  $.wxmodal.AFTER_CLOSE = 'wxmodal:after-close';
  $.wxmodal.AJAX_SEND = 'wxmodal:ajax:send';
  $.wxmodal.AJAX_SUCCESS = 'wxmodal:ajax:success';
  $.wxmodal.AJAX_FAIL = 'wxmodal:ajax:fail';
  $.wxmodal.AJAX_COMPLETE = 'wxmodal:ajax:complete';

  $.fn.wxmodal = function(options){
    if (this.length === 1) {
      new $.wxmodal(this, options);
    }
    return this;
  };

  // Automatically bind links with rel="wxmodal:close" to, well, close the modal.
  $(document).on('click.wxmodal', 'a[rel="wxmodal:close"]', $.wxmodal.close);
  $(document).on('click.wxmodal', 'a[rel="wxmodal:open"]', function(event) {
    event.preventDefault();
    $(this).wxmodal();
  });
}));
