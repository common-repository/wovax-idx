jQuery.noConflict();
(function($) {

    $('input.number').keyup(function(event){
        // skip for arrow keys
        if(event.which >= 37 && event.which <= 40){
            event.preventDefault();
        }
        var $this = $(this);
        var num = $this.val().replace(/,/gi, "").split("").reverse().join("");
        var num2 = RemoveRogueChar(num.replace(/(.{3})/g,"$1,").split("").reverse().join(""));
        // the following line has been simplified. Revision history contains original.
        $this.val(num2);
    });

    function RemoveRogueChar(convertString){
        if(convertString.substring(0,1) == ","){
            return convertString.substring(1, convertString.length)
        }
        return convertString;
    }

    //float labels
    $(document).ready(function(){

        var floatlabels = new FloatLabels( '#wovax-idx-form-1, #wovax-idx-form-2, #wovax-idx-search-form, #wovax-idx-sort, #wovax-idx-saved-searches', {
        // options go here,
        customEvent  : null,
        customLabel  : null,
        exclude      : '.no-label',
        inputRegex   : /email|number|password|search|tel|text|url/,
        prefix       : 'fl-',
        prioritize   : 'label',
        requiredClass: 'required',
        style        : 2,
        transform    : 'input, select, textarea',
        });
    //input number formatter

    }); /* end ready function */

    $(document).ready(function() {
        $('.wovax-idx-saved-search-select').change(function() {
            let url = $('.wovax-idx-saved-search-select').val();
            if(url === "" || url === null) {
                console.log("Invalid search url selected");
            } else {
                window.open(url, "_self");
            }
            
        });

        $('.wovax-idx-save-search-button').click(function() {
            let url = window.location.href;
            let name = prompt("Give your search a name:", "");
            if(name === null || name === "") {
                console.log("Search save cancelled");
            } else {
                let data = {
                    action: 'wovax_idx_save_search',
                    data: {
                        name: name,
                        url: url
                    }
                }
                $.ajax({
                    url: wovaxIdx.ajaxurl,
                    type: 'POST',
                    data: data,
                    success: function(response) {
                        console.log(response);
                        location.reload();
                    }
                });
            }
        });
    });

})(jQuery);
