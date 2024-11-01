jQuery(document).ready(function($) {

	//Listing Card Status Style
	$('select.wovax-idx-listing-card-style').change(function() {
		var overlayClasses = document.getElementById('wovax-idx-image-overlay').classList;
		var originalStyle = overlayClasses[1];
		var newStyle = $('.wovax-idx-listing-card-style').val();
		var status = $('.wovax-idx-listing-image div');
		status.removeClass(originalStyle).addClass(newStyle);
	});
});