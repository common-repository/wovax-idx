jQuery( document ).ready(function() {
	var wovax_idx_loading_feed_td			    = jQuery(".wovax-idx-table-loading td");
	var wovax_idx_loading_feed_details_td	= jQuery(".wovax-idx-table-loading-feed-details td");
	var status_ajax 				              = true;
	var wovax_idx_board_up 			          = jQuery("#wovax-idx-feeds-board-search");
	var wovax_idx_board_down   	         	= jQuery("#wovax-idx-feeds-board-search-tfoot");
	var wovax_idx_feed_up 	        			= jQuery("#wovax-idx-feeds-feed-search");
	var wovax_idx_feed_down 			        = jQuery("#wovax-idx-feeds-feed-search-tfoot");
	var wovax_idx_environment_up 	       	= jQuery("#wovax-idx-feeds-environment-search");
	var wovax_idx_environment_down 	      = jQuery("#wovax-idx-feeds-environment-search-tfoot");
	var wovax_idx_class_up 			          = jQuery("#wovax-idx-feeds-class-search");
	var wovax_idx_class_down   		        = jQuery("#wovax-idx-feeds-class-search-tfoot");
	var wovax_idx_resource_up 		       	= jQuery("#wovax-idx-feeds-resource-search");
	var wovax_idx_resource_down 	       	= jQuery("#wovax-idx-feeds-resource-search-tfoot");
	var wovax_idx_status_up 		        	= jQuery("#wovax-idx-feeds-status-search");
	var wovax_idx_status_down          		= jQuery("#wovax-idx-feeds-status-search-tfoot");
	var wovax_idx_updated_up 		        	= jQuery("#wovax-idx-feeds-updated-search");
	var wovax_idx_updated_down 	        	= jQuery("#wovax-idx-feeds-updated-search-tfoot");
	var wovax_idx_search_input 	        	= jQuery('#wovax-idx-feeds-search-input');
	var wovax_idx_search_submit 		      = jQuery("#wovax-idx-feeds-search-submit");
	var wovax_idx_filter_select 		      = jQuery('#filter-by-wovax-idx-feeds');
	var wovax_idx_filter_query_submit     = jQuery("#wovax-idx-feeds-query-submit");
	var wovax_idx_feed_paginated 		      = jQuery("#wovax-idx-feeds-pagination");
	var wovax_idx_feed_table 				      = jQuery("#wovax-idx-feeds-table");
  //WOVAX_SEETINGS
	var url 						                  = object_name.ajax_url;
	var wovax_idx_content_page			      = jQuery("#content_page");
	var wovax_idx_wovax_id_search_post	  = jQuery("#wovax-idx-settings-search-results-page");
	var wovax_idx_btn_create_search 	    = jQuery("#wovax-idx-settings-search-results-button");
	var wovax_idx_wovax_id_list_post	    = jQuery("#wovax-idx-settings-listing-details-page");
	var wovax_idx_btn_create_list   	    = jQuery("#wovax-idx-settings-listing-details-button");
	var wovax_idx_message				          = jQuery("#wovax_idx_message");
	var wovax_idx_message_listing	       	= jQuery("#wovax_idx_message_listing");
	var wovax_idx_message_no_result     	= jQuery("#wovax_idx_message_no_result");
	var wovax_idx_loading_class		        = jQuery(".wovax-idx-table-loading");
	//var wovax_idx_loading_feed_details	  = jQuery("#wovax-idx-sortable-layout");
	var wovax_idx_page_all 								= jQuery("#wovax-idx-pagechecklist-all");
	var wovax_idx_page_default 						= jQuery("#wovax-idx-pagechecklist-default");
	var wovax_idx_page_search             = jQuery("#wovax-idx-pagechecklist-search");
	var wovax_idx_page_container 				  = jQuery("#wovax-idx-sortable-layout");

	var wovax_idx_trash_button			      = jQuery("#wovax-idx-shortcode-trash");
	//WOVAX SHORTCODE
	var wovax_idx_shortcode_title 	     	= jQuery("#wovax-idx-shortcode-title");
	var wovax_idx_shortcode_name 		      = jQuery("#wovax-idx-shortcode-name");
	var wovax_idx_shortcode_type 		      = jQuery("#wovax-idx-shortcode-type");
	var wovax_idx_shortcode_author 	      = jQuery("#wovax-idx-shortcode-author");
	var wovax_idx_shortcode_created 		  = jQuery("#wovax-idx-shortcode-created");
	var wovax_idx_shortcode_title_foot    = jQuery("#wovax-idx-shortcode-title-foot");
	var wovax_idx_shortcode_name_foot 	  = jQuery("#wovax-idx-shortcode-name-foot");
	var wovax_idx_shortcode_type_foot    	= jQuery("#wovax-idx-shortcode-type-foot");
	var wovax_idx_shortcode_author_foot   = jQuery("#wovax-idx-shortcode-author-foot");
	var wovax_idx_shortcode_created_foot  = jQuery("#wovax-idx-shortcode-created-foot");
	//WOVAX_USERS
	var wovax_idx_user_activity_username_search 		   = jQuery("#wovax-idx-user-username-search");
	var wovax_idx_user_activity_fullname_search 		   = jQuery("#wovax-idx-user-fullname-search");
	var wovax_idx_user_activity_phone_search 		       = jQuery("#wovax-idx-user-phone-search");
	var wovax_idx_user_activity_email_search 		       = jQuery("#wovax-idx-user-email-search");
	var wovax_idx_user_activity_favorites_search 	     = jQuery("#wovax-idx-user-favorites-search");
	var wovax_idx_user_activity_username_search_tfoot  = jQuery("#wovax-idx-user-username-search-tfoot");
	var wovax_idx_user_activity_fullname_search_tfoot  = jQuery("#wovax-idx-user-fullname-search-tfoot");
	var wovax_idx_user_activity_phone_search_tfoot     = jQuery("#wovax-idx-user-phone-search-tfoot");
	var wovax_idx_user_activity_email_search_tfoot     = jQuery("#wovax-idx-user-email-search-tfoot");
	var wovax_idx_user_activity_favorites_search_tfoot = jQuery("#wovax-idx-user-favorites-search-tfoot");
	var wovax_idx_user_input 	        							 	 = jQuery('#wovax-idx-user-search-input');
	var wovax_idx_user_submit 		      						 	 = jQuery("#wovax-idx-user-search-submit");
	var wovax_idx_user_paginated 		                   = jQuery("#wovax-idx-users-pagination");
	var wovax_idx_user_table 				                   = jQuery("#wovax-idx-users-table");

	//LOADIGN LOOP
	function wovax_idx_loading_table(num,element){ setTimeout(function(){
		if(num==6)
			{num=1;}
		var text="";
		for(var i=0;i<num;i++)
			{text+=".";}
		element.text("Loading "+text);
		num++;
		wovax_idx_loading_table(num,element);}, 500
		);
	}

	function wovax_idx_ajax(paged,orderby,search,ordername,filter){
		if(!status_ajax){return;}
		wovax_idx_remove_list_table_feed();
		wovax_idx_loading_class.show();
		status_ajax = false;
		var url = object_name.ajax_url;
		var attr = { paged : paged, orderby : orderby, s : search, ordername : ordername,  filter : filter }
		var request = jQuery.ajax({ url: url, method: "POST", dataType: "json", data: { attr : attr , action : 'get_wovax_idx_get_result_api' } });
		request.done(function( response ) {
			wovax_idx_loading_class.hide();
			jQuery(response.table_html).appendTo(wovax_idx_feed_table);
			jQuery(response.paginate_html).appendTo(wovax_idx_feed_paginated);
			status_ajax=true;
		});
		request.fail(function( jqXHR, textStatus ) {
			wovax_idx_loading_class.hide();
		  // alert( "Request failed: " + textStatus );
			status_ajax=true;
		});
	}

	//EVENT CLICK SELECT FILTER
	wovax_idx_filter_query_submit.on("click",function(){ var sc = wovax_idx_search_input.val(); var ft = wovax_idx_filter_select.val();wovax_idx_ajax(1,'ASC',sc,'feed_id',ft); });

	//EVENT CLICK SEARCH ======>
	//feeds view
	wovax_idx_search_submit.on("click",function(){
		var ft = wovax_idx_filter_select.val();
		var sc = wovax_idx_search_input.val();
		wovax_idx_ajax(1,'ASC',sc,'feed_id',ft);
	});
	//users view
	wovax_idx_user_submit.on("click",function(){
		var sc = wovax_idx_user_input.val();
		wovax_idx_ajax_user_activity_sort_table(1,'ASC',sc,'nickname');
	});

	//EVENT KEYPRESS INPUT SEARCH ======>
	//feeds view
	wovax_idx_search_input.on("keypress",function(e){ 
		if(e.which == 13) { 
			var ft = wovax_idx_filter_select.val(); 
			var sc = wovax_idx_search_input.val(); 
			wovax_idx_ajax(1,'ASC',sc,'feed_id',ft); 
		} 
	});
	//users view
	wovax_idx_user_input.on("keypress",function(e){ 
		if(e.which == 13) { 
			var sc = wovax_idx_user_input.val(); 
			wovax_idx_ajax_user_activity_sort_table(1,'ASC',sc,'nickname'); 
		} 
	});

	// EVENT CLICK BOARD SORT
	wovax_idx_class_up.on("click",function(){ wovax_ixd_sort('class', 'class_visible_name'); });
	wovax_idx_class_down.on("click",function(){ wovax_ixd_sort('class', 'class_visible_name'); });
	wovax_idx_board_up.on("click",function(){ wovax_ixd_sort('board', 'board_acronym'); });
	wovax_idx_board_down.on("click",function(){ wovax_ixd_sort('board', 'board_acronym' ); });
	wovax_idx_feed_up.on("click",function(){ wovax_ixd_sort('feed', 'feed_description'); });
	wovax_idx_feed_down.on("click",function(){ wovax_ixd_sort('feed', 'feed_description'); });
	wovax_idx_environment_up.on("click",function(){ wovax_ixd_sort('environment', 'environment'); });
	wovax_idx_environment_down.on("click",function(){ wovax_ixd_sort('environment', 'environment'); });
	wovax_idx_resource_up.on("click",function(){ wovax_ixd_sort('resource', 'resource'); });
	wovax_idx_resource_down.on("click",function(){ wovax_ixd_sort('resource', 'resource'); });
	wovax_idx_status_up.on("click",function(){ wovax_ixd_sort('status', 'status'); });
	wovax_idx_status_down.on("click",function(){ wovax_ixd_sort('status', 'status'); });
	wovax_idx_updated_up.on("click",function(){ wovax_ixd_sort('updated', 'updated'); });
	wovax_idx_updated_down.on("click",function(){ wovax_ixd_sort('updated', 'updated'); });

	function wovax_ixd_sort(id, ordername){
		var search_val     = wovax_idx_search_input.val();
		var ft             = wovax_idx_filter_select.val();
		var th_qty         = 7 - ( jQuery('th.hidden').length / 2 );
		var class_asc_desc = jQuery( "#"+id ).hasClass( "desc" );
		if (class_asc_desc){ jQuery( "#wovax-idx-feed-colspan-qty-loading" ).attr("colspan",th_qty); jQuery( "#"+id  ).addClass( "asc" ).removeClass( "desc" ); wovax_idx_ajax(1,'DESC', search_val, ordername, ft);
		}else{ jQuery( "#wovax-idx-feed-colspan-qty-loading" ).attr("colspan",th_qty); jQuery( "#"+id  ).addClass( "desc" ).removeClass( "asc" ); wovax_idx_ajax(1,'ASC', search_val, ordername, ft);}
	}
	//EVENT NEXT ======>
	//feeds view
	wovax_idx_feed_paginated.on("click", "#wovax-idx-feeds-next-search", function(){
		var ft        = wovax_idx_filter_select.val();
		var search 	  =	wovax_idx_search_input.val();
		var total_pag = jQuery('#wovax-idx-feeds-pag-pages').text();
		var page_base = jQuery('#wovax-idx-current-feeds-page-selector').val();
		if( page_base < total_pag ){ var page_find = parseInt(page_base) + 1; wovax_idx_ajax( page_find, 'ASC', search, 'feed_id', ft ); }
	});
	//users view
	wovax_idx_user_paginated.on("click", "#wovax-idx-users-next-search", function(){
		var search 	  =	wovax_idx_user_input.val();
		var total_pag = jQuery('#wovax-idx-users-pag-pages').text();
		var page_base = jQuery('#wovax-idx-current-users-page-selector').val();
		if( page_base < total_pag ){ var page_find = parseInt(page_base) + 1; wovax_idx_ajax_user_activity_sort_table( page_find, 'ASC', search, 'nickname' ); }
	});

	//EVENT PEV ======>
	//feeds view
	wovax_idx_feed_paginated.on("click", "#wovax-idx-feeds-prev-search", function(){
		var ft        = wovax_idx_filter_select.val();
		var search 	  = wovax_idx_search_input.val();
		var page_base = jQuery('#wovax-idx-current-feeds-page-selector').val();
		if(page_base!='1'){ var page_find = parseInt(page_base) - 1; wovax_idx_ajax( page_find, 'ASC', search , 'feed_id', ft ); }
	});
	//users view
	wovax_idx_user_paginated.on("click", "#wovax-idx-users-prev-search", function(){
		var search 	  = wovax_idx_user_input.val();
		var page_base = jQuery('#wovax-idx-current-users-page-selector').val();
		if(page_base!='1'){ var page_find = parseInt(page_base) - 1; wovax_idx_ajax_user_activity_sort_table( page_find, 'ASC', search , 'nickname' ); }
	});

	//EVENT LAST SEARCH ======>
	//feeds view
	wovax_idx_feed_paginated.on("click", "#wovax-idx-feeds-last-search", function(){
		var ft        = wovax_idx_filter_select.val();
		var search    = wovax_idx_search_input.val();
		var total_pag = jQuery('#wovax-idx-feeds-pag-pages').text();
		wovax_idx_ajax( total_pag, 'ASC', search , 'feed_id', ft );
	});
	//users view
	wovax_idx_user_paginated.on("click", "#wovax-idx-users-last-search", function(){
		var search    = wovax_idx_user_input.val();
		var total_pag = jQuery('#wovax-idx-users-pag-pages').text();
		wovax_idx_ajax_user_activity_sort_table( total_pag, 'ASC', search , 'nickname' );
	});

	//EVENT FIRST SEARCH ======>
	//feeds view
	wovax_idx_feed_paginated.on("click", "#wovax-idx-feeds-first-search", function(){
		var ft = wovax_idx_filter_select.val();
		var search = wovax_idx_search_input.val();
		wovax_idx_ajax( 1, 'ASC', search , 'feed_id', ft );
	});
	//users view
	wovax_idx_user_paginated.on("click", "#wovax-idx-users-last-search", function(){
		var search = wovax_idx_user_input.val();
		wovax_idx_ajax_user_activity_sort_table( 1, 'ASC', search , 'nickname' );
	});

	//EVENT TRASH
	jQuery("#the-list").on("click", "#wovax-idx-shortcode-trash", function(){
		var id = jQuery("#wovax-idx-shortcode").val();
		console.log('--->'+id);
		wovax_idx_shortcode_ajax('trash', id);
	});

	//FUNCTION REMOVE LIST TABLE ======>
	//feeds view
	function wovax_idx_remove_list_table_feed(){
		jQuery("#the-list tr").each(function(){ if(!jQuery(this).hasClass('wovax-idx-table-loading')){ jQuery(this).remove(); } }); wovax_idx_feed_paginated.children().remove();
	}
	//users view
	function wovax_idx_remove_list_table_user(){
		jQuery("#the-list-users tr").each(function(){ if(!jQuery(this).hasClass('wovax-idx-table-loading')){ jQuery(this).remove(); } }); wovax_idx_user_paginated.children().remove();
	}


  wovax_idx_btn_create_search.on("click",function(){
  	if(!wovax_idx_wovax_id_search_post.val()){

   		// wovax_idx_message_no_result.hide();
      var request = jQuery.ajax({ url: url, type: 'POST', data:{async:true,action:'create_page_post'} });
      request.done(function(data){
	      var data = JSON.parse(data);
	      wovax_idx_wovax_id_search_post.val(data.id_post);
	      wovax_idx_message.show();
      });
   	}
  });

 	wovax_idx_btn_create_list.on("click",function(){
   	if(!wovax_idx_wovax_id_list_post.val()){

   		// wovax_idx_message_no_result.hide();
     	var request = jQuery.ajax({ url: url, type: 'POST', data:{async:true,action:'create_listing_page_post'} });
     	request.done(function(data){
      	var data = JSON.parse(data);
       	wovax_idx_wovax_id_list_post.val(data.id_post);
       	wovax_idx_message_listing.show();
      });
   	}
  });

  // WOVAX_IDX_FEED_DETAILS
	function wovax_idx_feed_details_ajax(idfeed){
		if(!status_ajax){return;}
		status_ajax = false;
		//wovax_idx_loading_feed_details.show();
		var url 	= object_name.ajax_url;
		var attr 	= { idfeed : idfeed }
		var request = jQuery.ajax({ url: url,
									method: "POST",
									dataType: "json",
									data: { attr : attr ,
											action : 'get_wovax_idx_get_result_api_feed_details' }
								  });
		request.done(function(response){
			//wovax_idx_loading_feed_details.hide();
			jQuery( response.all ).appendTo( jQuery(wovax_idx_page_all) );
			jQuery( response.default ).appendTo( jQuery(wovax_idx_page_default) );
			jQuery( response.container ).appendTo( jQuery(wovax_idx_page_container) );
			status_ajax=true;
		});
		request.fail(function(jqXHR, textStatus){
			//wovax_idx_loading_feed_details.hide();
			status_ajax=true;
		});
	}

	jQuery( "input#quick-search-posttype-page" ).on("change paste keyup", function() {
    var input_value = jQuery(this).val();
    if( input_value.length >= 3 ){
    	var idfeed = jQuery(this).parent().parent().find('input[name="wovax_idx_id_for_search"]').val();
    	status_ajax = false;
			var url 	= object_name.ajax_url;
			var attr 	= { idfeed : idfeed, value : input_value }
			var request = jQuery.ajax({ url: url,
										method: "POST",
										dataType: "json",
										data: { attr : attr ,
												action : 'get_wovax_idx_get_result_api_feed_for_search_content' }
									  });
			request.done(function(response){

				jQuery( wovax_idx_page_search ).empty();
				var array = Object.keys(response.search).map(function (key) {
				  return { [key]: response.search[key] };
				});

				jQuery( array ).each(function(){
					var object_key   = Object.keys(this);
					var object_value = Object.values(this);

					if( jQuery(wovax_idx_page_search).find('input[name="wovax_idx_id_field_' + object_key[0] + '"]').length == 0){
						jQuery(wovax_idx_page_search).prepend(object_value[0]);
					}
				})

				status_ajax=true;
			});
			request.fail(function(jqXHR, textStatus){
				//wovax_idx_loading_feed_details.hide();
				status_ajax=true;
			});

    }else{

    	jQuery( wovax_idx_page_search ).empty();

    }
  });

  // SEETING
  var tab =jQuery("#wovax_idx_feed_tab");
  var wrf =jQuery("#wovax-idx-settings-users-registration-force");
	var wrfc=jQuery("#wovax-idx-settings-users-registration-force-count");
	wrf.change(function(){cwrfc(jQuery(this));});
	function cwrfc(wrf){if(wrf.attr("checked")=='checked'){ wrfc.removeAttr("readonly"); }else{ wrfc.attr("readonly", true); wrfc.val("10");}}

  // INIT IDX LIST
  if(object_name.type_page=='wovax_idx_feeds_list'){ wovax_idx_loading_table(1,wovax_idx_loading_feed_td); wovax_idx_ajax(object_name.paged,object_name.orderby,object_name.s,object_name.ordername,object_name.filter); }

  if(object_name.type_page=='wovax_idx_user_activity'){ wovax_idx_loading_table(1,wovax_idx_loading_feed_td); wovax_idx_ajax_user_activity_sort_table(object_name.paged,object_name.orderby,object_name.s,object_name.ordername); }
  // INIT DETAILS

  if(object_name.type_page=='wovax_idx_feeds_detail' &&  tab.val()=='fields' ){ wovax_idx_loading_table(1,wovax_idx_loading_feed_details_td); wovax_idx_feed_details_ajax(object_name.idfeed); wovax_idx_sortable_layout_feed_details(); }
  if(object_name.type_page=='wovax_idx_feeds_detail' &&  tab.val()=='layout' ){ wovax_ixd_sortable_layout();}

  // INIT SEETINGS
  if(object_name.type_page=='wovax_idx_settings'){ cwrfc(wrf); }

	// JQUERY-UI-SORTABLE
	var the_list_filter=jQuery( "#the-list-shortcode-filters-details" );
	the_list_filter.sortable({
		update: function( event, ui ){
			wovax_idx_filters_ajax(the_list_filter.sortable( "toArray" ));
		}
	});

	var the_list_rule_shortcode=jQuery( "#the-list-shortcode-rules-details" );
	the_list_rule_shortcode.sortable({
		update: function( event, ui ){
			wovax_idx_shortcode_rules_ajax(the_list_rule_shortcode.sortable( "toArray" ));
		}
	});

	var the_list_rule_feed=jQuery( "#the-list-feed-rules-details" );
	the_list_rule_feed.sortable({
		update: function( event, ui ){
			wovax_idx_feed_rules_ajax(the_list_rule_feed.sortable( "toArray" ));
		}
	});

	// AJAX SORTABLE SAVE DATA
	function wovax_idx_filters_ajax(array_orden){

		if(!status_ajax){return;}
		status_ajax = false;
		var url 	= object_name.ajax_url;
		var id_shortcode = the_list_filter.attr('data-id-shortcode');
		var order_json = JSON.stringify(array_orden);
		var request = jQuery.ajax({ url: url,
									method: "POST",
									dataType: "application/json",
									data: { id_shortcode : id_shortcode ,
											order_json : order_json ,
											option : 'shortcode_filter' ,
											action : 'set_order_section' }
								 });
		request.done(function( response ) {
			status_ajax=true;
		});
		request.fail(function( jqXHR, textStatus ) {
			status_ajax=true;
		});
	}

	function wovax_idx_shortcode_rules_ajax(array_orden){
		if(!status_ajax){return;}
		status_ajax = false;
		var url 	= object_name.ajax_url;
		var id_shortcode = the_list_rule_shortcode.attr('data-id-shortcode');
		var order_json = JSON.stringify(array_orden);
		var request = jQuery.ajax({ url: url,
									method: "POST",
									dataType: "application/json",
									data: { id_shortcode : id_shortcode ,
											order_json : order_json ,
											option : 'shortcode_rule' ,
											action : 'set_order_section' }
								 });
		request.done(function( response ) {
			status_ajax=true;
		});
		request.fail(function( jqXHR, textStatus ) {
			status_ajax=true;
		});
	}

	function wovax_idx_feed_rules_ajax(array_orden){
		if(!status_ajax){return;}
		status_ajax = false;
		var url 	= object_name.ajax_url;
		var id_feed = the_list_rule_feed.attr('data-id-feed');
		var order_json = JSON.stringify(array_orden);
		var request = jQuery.ajax({ url: url,
									method: "POST",
									dataType: "application/json",
									data: { id_feed : id_feed ,
											order_json : order_json ,
											option : 'feed_rule' ,
											action : 'set_order_section' }
								 });
		request.done(function( response ) {
			status_ajax=true;
		});
		request.fail(function( jqXHR, textStatus ) {
			status_ajax=true;
		});
	}

	function wovax_idx_ajax_user_activity_sort_table(paged,orderby,search,ordername){
		if(!status_ajax){return;}
		wovax_idx_remove_list_table_user();
		wovax_idx_loading_class.show();
		status_ajax = false;
		var url 	= object_name.ajax_url;
		var attr 	= { paged : paged, orderby : orderby, ordername : ordername, search: search}
		var request = jQuery.ajax({ url: url, method: "POST", dataType: "json", data: { attr : attr , action : 'get_wovax_idx_sort_user_activity' } });
		request.done(function( response ) {
			wovax_idx_loading_class.hide();
			jQuery(response.table_html).appendTo(wovax_idx_user_table);
			jQuery(response.paginate_html).appendTo(wovax_idx_user_paginated);
			status_ajax=true;
		});
		request.fail(function( jqXHR, textStatus ) {
			wovax_idx_loading_class.hide();
			status_ajax=true;
		});
	}

	function wovax_idx_sort_user_activity(id, ordername){
		var search_val     = wovax_idx_user_input.val();
		var th_qty         = 5 - ( jQuery('th.hidden').length / 2 );
		var class_asc_desc = jQuery( "#"+id ).hasClass( "desc" );
		if (class_asc_desc){ jQuery( "#wovax-idx-user-colspan-qty-loading" ).attr("colspan",th_qty); jQuery( "#"+id  ).addClass( "asc" ).removeClass( "desc" ); wovax_idx_ajax_user_activity_sort_table(1,'DESC', search_val, ordername);
		}else{ jQuery( "#wovax-idx-user-colspan-qty-loading" ).attr("colspan",th_qty); jQuery( "#"+id  ).addClass( "desc" ).removeClass( "asc" ); wovax_idx_ajax_user_activity_sort_table(1,'ASC', search_val, ordername);}
	}

	wovax_idx_user_activity_username_search.on("click",function(){ wovax_idx_sort_user_activity( 'wovax-idx-user-username-search', 'nickname' ); });
	wovax_idx_user_activity_fullname_search.on("click",function(){ wovax_idx_sort_user_activity( 'wovax-idx-user-fullname-search', 'fullname' ); });
	wovax_idx_user_activity_phone_search.on("click",function(){ wovax_idx_sort_user_activity( 'wovax-idx-user-phone-search', 'phone' ); });
	wovax_idx_user_activity_email_search.on("click",function(){ wovax_idx_sort_user_activity( 'wovax-idx-user-email-search', 'email' ); });
	wovax_idx_user_activity_favorites_search.on("click",function(){ wovax_idx_sort_user_activity( 'wovax-idx-user-favorites-search', 'favorites' ); });
	wovax_idx_user_activity_username_search_tfoot.on("click",function(){ wovax_idx_sort_user_activity( 'wovax-idx-user-username-search-tfoot', 'nickname' ); });
	wovax_idx_user_activity_fullname_search_tfoot.on("click",function(){ wovax_idx_sort_user_activity( 'wovax-idx-user-fullname-search-tfoot', 'fullname' ); });
	wovax_idx_user_activity_phone_search_tfoot.on("click",function(){ wovax_idx_sort_user_activity( 'wovax-idx-user-phone-search-tfoot', 'phone' ); });
	wovax_idx_user_activity_email_search_tfoot.on("click",function(){ wovax_idx_sort_user_activity( 'wovax-idx-user-email-search-tfoot', 'email' ); });
	wovax_idx_user_activity_favorites_search_tfoot.on("click",function(){ wovax_idx_sort_user_activity( 'wovax-idx-user-favorites-search-tfoot', 'favorites' ); });

  function wovax_idx_ajax_shortcode_sort_table(orderby,search,ordername,filter,section){
		if(!status_ajax){return;}
		status_ajax = false;
		var url 	= object_name.ajax_url;
		var attr 	= { orderby : orderby, ordername : ordername, search: search, filter: filter, section: section}
		var request = jQuery.ajax({ url: url, method: "POST", dataType: "json", data: { attr : attr , action : 'get_wovax_idx_sort_shortcode' } });
		request.done(function( response ) {
			jQuery("#the-list-shortcode tr").each(function(){  jQuery(this).remove(); });
			wovax_idx_shortcode_table = jQuery("#the-list-shortcode");
			jQuery(response.table).appendTo(wovax_idx_shortcode_table);
			status_ajax=true;
		});
		request.fail(function( jqXHR, textStatus ) {
			status_ajax=true;
		});
	}

	function wovax_ixd_sort_shortcode(id, ordername){
		var button_delete = jQuery("#delete_all").val();
		var section = ( (button_delete == 'Empty Trash') ? 'trash' : 'published' );
		var s = jQuery("#wovax-idx-shortcodes-search-input").val();
		var f = jQuery("#filter-by-wovax-idx-shortcode-type").val();
		var class_asc_desc = jQuery( "#"+id ).hasClass( "desc" );
		if (class_asc_desc){
			jQuery( "#"+id  ).addClass( "asc" ).removeClass( "desc" );
			wovax_idx_ajax_shortcode_sort_table('DESC', s, ordername, f, section);
		}else{
			jQuery( "#"+id  ).addClass( "desc" ).removeClass( "asc" );
			wovax_idx_ajax_shortcode_sort_table('ASC', s, ordername, f, section);
		}
	}

	wovax_idx_shortcode_title.on("click",function(){ wovax_ixd_sort_shortcode( 'wovax-idx-shortcode-title', 'title' ); });
	wovax_idx_shortcode_name.on("click",function(){ wovax_ixd_sort_shortcode( 'wovax-idx-shortcode-name', 'id' ); });
	wovax_idx_shortcode_type.on("click",function(){ wovax_ixd_sort_shortcode( 'wovax-idx-shortcode-type', 'type' ); });
	wovax_idx_shortcode_author.on("click",function(){ wovax_ixd_sort_shortcode( 'wovax-idx-shortcode-author', 'author' ); });
	wovax_idx_shortcode_created.on("click",function(){ wovax_ixd_sort_shortcode( 'wovax-idx-shortcode-created', 'date' ); });
	wovax_idx_shortcode_title_foot.on("click",function(){ wovax_ixd_sort_shortcode( 'wovax-idx-shortcode-title-foot', 'title' ); });
	wovax_idx_shortcode_name_foot.on("click",function(){ wovax_ixd_sort_shortcode( 'wovax-idx-shortcode-name-foot', 'id' ); });
	wovax_idx_shortcode_type_foot.on("click",function(){ wovax_ixd_sort_shortcode( 'wovax-idx-shortcode-type-foot', 'type' ); });
	wovax_idx_shortcode_author_foot.on("click",function(){ wovax_ixd_sort_shortcode( 'wovax-idx-shortcode-author-foot', 'author' ); });
	wovax_idx_shortcode_created_foot.on("click",function(){ wovax_ixd_sort_shortcode( 'wovax-idx-shortcode-created-foot', 'date' ); });

	//TAB FEEDS
	var data_list_results = jQuery("#list_wovax_set_datas").children();
	jQuery('#search-text-feeds-results').on("keyup",function(){
	  var wovax_idx_name  = jQuery(this).val().toUpperCase();
		if (wovax_idx_name.length >= 3 ){
			jQuery("#list_wovax_set_datas").empty();
			data_list_results.each(function(i, v){
				var text_value = jQuery(v).find('label').text().trim().toUpperCase();
				if(text_value.indexOf(wovax_idx_name) > -1){
					jQuery("#list_wovax_set_datas").append(v);
				}
			});
		}else{
			jQuery("#list_wovax_set_datas").empty();
			data_list_results.each(function(i, v){
				 jQuery("#list_wovax_set_datas").append(v);
			});
		}
  });

  //Autocomplete for page and post selection on the send pushnotifications page
  jQuery('#wovax-idx-settings-search-results-page, #wovax-idx-settings-listing-details-page').autocomplete({
    source: function( request, response) {
      data = {
        action: 'wovax_idx_settings_autocomplete',
        data: request
      }
      jQuery.ajax({
        type: "POST",
        url: ajaxurl,
        data: data,
        success: response,
        dataType: 'json',
        minLength: 2,
        delay: 100
            });
    },
    minLength: 2,
  });

});

function wovax_ixd_sortable_layout(){
	jQuery( "#sortable1, #sortable2" ).sortable({
	  connectWith: ".wovax-idx-settings-sortable-layout",
    update: function(event, ui) {
     	container = jQuery(ui.item[0]).parent()[0].id;
     	if(container == "sortable1"){
     		jQuery(ui.item[0]).find(".available-sortable").val("0");
     	}else{
     		jQuery(ui.item[0]).find(".available-sortable").val("1");
     	}
    },
    stop: function(event, ui) {
     	list = jQuery("#sortable1").find(".order-index");
      list.each(function(index, elem){
       	jQuery(elem).val(index);
      });
      list = jQuery("#sortable2").find(".order-index");
      list.each(function(index, elem){
       	jQuery(elem).val(index);
      });
    }
	}).disableSelection();
}

function wovax_idx_sortable_layout_feed_details(){
	jQuery( "#wovax-idx-sortable-layout" ).sortable({
	  connectWith: ".menu-layout",
    stop: function(event, ui) {
     	list = jQuery("#wovax-idx-sortable-layout").find(".order-index");
      list.each(function(index, elem){
       	jQuery(elem).val(index);
      });
    }
	}).disableSelection();
}

function wovax_idx_select_all_fields(){
  var div_content = jQuery('div#posttype-page').find('div.show');

  if ( jQuery( div_content ).hasClass( "ischecked" ) ){

  	jQuery(div_content).find('input[type="checkbox"]').each(function(){
	    jQuery( this ) .prop('checked', false);
	  })
	  jQuery(div_content).removeClass( "ischecked" );

  }else {

	  jQuery(div_content).find('input[type="checkbox"]').each(function(){
	    jQuery( this ) .prop('checked', true);
	  })
	  jQuery(div_content).addClass("ischecked");

  }

}

function wovax_idx_select_all_styling(){
  var div_content = jQuery('div#stylingtype-page').find('div.show');

  if ( jQuery( div_content ).hasClass( "ischecked" ) ){

  	jQuery(div_content).find('input[type="checkbox"]').each(function(){
	    jQuery( this ) .prop('checked', false);
	  })
	  jQuery(div_content).removeClass( "ischecked" );

  }else {

	  jQuery(div_content).find('input[type="checkbox"]').each(function(){
	    jQuery( this ) .prop('checked', true);
	  })
	  jQuery(div_content).addClass("ischecked");

  }

}

function wovax_idx_get_content(req){
  jQuery(req).parent().toggleClass('open');
  var content = jQuery(req).parent().find('div.accordion-section-content');

  jQuery(content).toggleClass('show');
}

function wovax_idx_change_content(req){
  jQuery(req).toggleClass('special');
  var sibling_selector = jQuery(req).parent().parent().parent().siblings();

  jQuery(sibling_selector).toggleClass('show');
}

function wovax_idx_clean_input(req){

  var selector = jQuery(req).parent().parent().parent().remove();
  jQuery('ul#wovax-idx-sortable-layout').sortable('refresh');
  list = jQuery('ul#wovax-idx-sortable-layout').find(".order-index");
  list.each(function(index, elem){
    jQuery(elem).val(index);
  });
}

function wovax_idx_enable_content(req){

	//Get element data type and set it to a varible
  var content_div = jQuery(req).attr('data-type');

  //Add class "tabs" (display) and remove it from every sibling
  var li_parent = jQuery(req).parent().addClass('tabs');
  var siblings = jQuery(li_parent).siblings();

  jQuery( siblings ).each(function() {
    jQuery( this ).removeClass( "tabs" );
  });

  //Condition for every div container
  if(content_div == 'page-all'){

  	//Get div and add "show" class
  	//Remove "show" class from all other container in same div
    var page_all = jQuery('div#page-all');
    jQuery(page_all).addClass('show');

    var page_all_sibling = jQuery(page_all).siblings();

    jQuery( page_all_sibling ).each(function() {
      jQuery( this ).removeClass( "show" );
    });

  }else if(content_div == 'tabs-panel-posttype-page-search'){

  	//Get div and add "show" class
  	//Remove "show" class from all other container in same div
    var search = jQuery('div#tabs-panel-posttype-page-search');
    jQuery(search).addClass('show');

    var search_sibling = jQuery(search).siblings();

    jQuery( search_sibling ).each(function() {
      jQuery( this ).removeClass( "show" );
    });

  }else if(content_div == 'tabs-panel-posttype-page-most-recent'){

  	//Get div and add "show" class
  	//Remove "show" class from all other container in same div
    var con_default = jQuery('div#tabs-panel-posttype-page-most-recent');
    jQuery(con_default).addClass('show');

    var default_sibling = jQuery(con_default).siblings();

    jQuery( default_sibling ).each(function() {
      jQuery( this ).removeClass( "show" );
    });
  }else if(content_div == 'tabs-panel-styling-page-search'){

  	//Get div and add "show" class
  	//Remove "show" class from all other container in same div
    var style_search = jQuery('div#tabs-panel-styling-page-search');
    jQuery(style_search).addClass('show');

    var default_sibling = jQuery(style_search).siblings();

    jQuery( default_sibling ).each(function() {
      jQuery( this ).removeClass( "show" );
    });
  }else if(content_div == 'tabs-panel-styling-page-all-tools'){

  	//Get div and add "show" class
  	//Remove "show" class from all other container in same div
    var style_all = jQuery('div#tabs-panel-styling-page-all-tools');
    jQuery(style_all).addClass('show');

    var default_sibling = jQuery(style_all).siblings();

    jQuery( default_sibling ).each(function() {
      jQuery( this ).removeClass( "show" );
    });
  }
}
jQuery(document).ready(function($) {
	$(document).on('click', '.wovax-idx-styling-default-image-button', function(event) {
		event.preventDefault();
		var correct = $(this).parent().children('.wovax-idx-settings-styling-default-image');
		var image = wp.media({
				title: 'Upload IDX Logo',
				// mutiple: true if you want to upload multiple files at once
				multiple: false
		}).open()
		.on('select', function(e){
				// This will return the selected image from the Media Uploader, the result is an object
				var uploaded_image = image.state().get('selection').first();
				// Output to the console uploaded_image
				console.log(uploaded_image);
				// We convert uploaded_image to a JSON object to make accessing it easier
				var image_url = uploaded_image.toJSON().url;
				// Let's assign the url value to the input field
				$(correct).val(image_url);
		});
	});
	
	$(function() {
		$('.wovax-idx-color-field').wpColorPicker();
	});

	$(document).on('change', '#shortcode-filter-type', function(){
		value = $(this).val();
		switch (value) {
			case 'range':
				$('tr.wovax-range').show('slow');
				$('tr.wovax-range-interval').show('slow');
				$('tr.wovax-filter-label').show('slow');
				$('tr.wovax-filter-placeholder').show('slow');
				break;
			case 'preset_range':
				$('tr.wovax-range').show('slow');
				$('tr.wovax-range-interval').hide('slow');
				$('tr.wovax-filter-label').hide('slow');
				$('tr.wovax-filter-placeholder').hide('slow');
				$('tr.wovax-preset-value').hide('slow');
				break;
			case 'preset_value':
				$('tr.wovax-preset-value').show('slow');
				$('tr.wovax-filter-label').hide('slow');
				$('tr.wovax-filter-placeholder').hide('slow');
				$('tr.wovax-range').hide('slow');
				$('tr.wovax-range-interval').hide('slow');
				break;
			default:
				$('tr.wovax-range').hide('slow');
				$('tr.wovax-range-interval').hide('slow');
				$('tr.wovax-preset-value').hide('slow');
				$('tr.wovax-filter-label').show('slow');
				$('tr.wovax-filter-placeholder').show('slow');
				break;
		}
	});
});

//Submit form when clicking on "#save_menu_header"
jQuery( "input#save_menu_header" ).click(function( event ) {
  jQuery("#wovax_idx_feeds_layout_form_container").submit();
});


