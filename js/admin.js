jQuery(document).ready(function() {
	jQuery( '#bulk-entry-toolbar-add-posts' ).click(function( eventobj ){
		var card_content = '';
		var type = jQuery( '#bulk-entry-add-post-type' ).val();
		var count = jQuery( '#bulk-entry-add-post-count' ).val();
		var status = jQuery( '#bulk-entry-add-post-status' ).val();

		jQuery.ajax({
			url: ajaxurl,
			type: "post",
			data: {
				action: 'bulk_entry_new_card',
				bulk_entry_posttype: type,
				bulk_entry_postcount: count,
				bulk_entry_poststatus: status
			},
			success: function( data ){
				data = jQuery.parseJSON( data );
				var cards = jQuery( data.content );
				var height = cards.height();
				height = Math.max(height,30);
				cards.css('opacity','0').css('margin-top',"-"+height+"px");
				jQuery( '#bulk-entry-canvas' ).prepend( cards );
				tinyMCE_bulk_entry_init( data );
				cards.animate({
					opacity: 1,
					"margin-top":"0"
				}, 200, function() {
					//
				});
			},
			error: function(){
				alert("fail :-(");
			}
		});
	});

	jQuery( document).on( 'click', '.bulk-entry-card-delete', function ( e ) {
		var formobj = jQuery( this ).closest( '.bulk-entry-block' );
		var height = formobj.height();
		height = Math.max(height,30);
		formobj.css( 'position', 'relative' ).animate({
			opacity: 0,
			left: '+500px',
			"margin-bottom":"-"+height+"px"
		}, 200, function() {
			formobj.remove();
		});
		return false;
	});

	jQuery( document ).on( 'submit' , '.bulk-entry-canvas form', function ( e ) {
		// submit AJAX request for stuff
		var formobj = jQuery(this);
		var type = formobj.find( 'input[name="bulk_entry_posttype"]').val();
		var status = formobj.find( 'input[name="bulk_entry_poststatus"]').val();
		var content = formobj.find( '.wp-editor-area:first-child').val();
		var title = formobj.find(".bulk-entry-card--title:first-child").val();
		jQuery.ajax({
			url: ajaxurl,
			type: "post",
			data: {
				action: 'bulk_entry_submit_post',
				bulk_entry_posttype: type,
				bulk_entry_poststatus: status,
				bulk_entry_postcontent: content,
				bulk_entry_posttitle: title
			},
			success: function( data ){
				data = jQuery.parseJSON( data );
				var cards = jQuery( data.content );
				var height = cards.height();
				height = Math.max( height, 30 );
				cards.css( 'opacity', '0' ).css( 'margin-top', "-" + height + "px" );
				jQuery( '#bulk-entry-canvas' ).prepend( cards );
				cards.animate({
					opacity: 1,
					"margin-top": "0"
				}, 200, function() {
					//
				});
				formobj.css( 'position', 'relative' ).css('margin-top',height+'px').animate({
					opacity: 0,
					left: '+500px',
					"margin-top": 0,
					"margin-bottom":"-"+formobj.height()+"px"
				}, 200, function() {
					formobj.remove();
				});
				//tinyMCE_bulk_entry_init( data );
			},
			error: function(){
				alert("fail :-(");
			}
		});
		return false;
	})
});


function tinyMCE_bulk_entry_init( response ) {
	var init, ed, qt, first_init, DOM, el, i;

	if ( typeof(tinymce) == 'object' ) {

		var editor;
		for ( e in tinyMCEPreInit.mceInit ) {
			editor = e;
			break;
		}
		for ( i in response.editor_ids ) {
			var ed_id = response.editor_ids[i];
			tinyMCEPreInit.mceInit[ed_id] = tinyMCEPreInit.mceInit[editor];
			tinyMCEPreInit.mceInit[ed_id]['elements'] = ed_id;
			tinyMCEPreInit.mceInit[ed_id]['body_class'] = ed_id;
			tinyMCEPreInit.mceInit[ed_id]['succesful'] =  false;
		}

		for ( ed in tinyMCEPreInit.mceInit ) {
			// check if there is an adjacent span with the class mceEditor
			if ( ! jQuery('#'+ed).next().hasClass('mceEditor') ) {
				init = tinyMCEPreInit.mceInit[ed];
				try {
					tinymce.init(init);
				} catch(e){
					console.log('fail');
				}
			}
		}

	}
}




