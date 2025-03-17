( function( $ ) {
	'use strict';

	$(document).ready(function() {
		if ( !mlHelper.userID ) {
			$('#medlink-form').remove();
		}
		
		$(document).on('change', '#acf-page_hero_image', function(event) {
			let self = $(this);
			let parent = self.closest('.acf-field');
			let description = parent.find('.description');
			let thumbnail = parent.find('.show-if-value img');

			if ( !validateHeroImage(event) ) {
				self.val('');
				description.addClass('error');
				thumbnail.attr('src', '');
			} else {
				description.removeClass('error');
				thumbnail.attr('src', URL.createObjectURL(event.target.files[0]));
			}
		});

		$(document).on('submit', '#medlink-form', function(event) {
			event.preventDefault();
			let form = $(this);
			
			let heroSecrion = $('#page_hero_image'); 
			if ( heroSecrion.find('.show-if-value img').attr('src').length == 0 ) {
				heroSecrion.find('.description').addClass('error');
				return false;
			}
						
			form.find('.acf-input input[type=text], .acf-field-textarea textarea').each( function() {
				let maxlength = $(this).attr('maxlength');
				if ( typeof maxlength === 'undefined' ) return;
				
				let textField = $(this);
				let description = textField.closest('.acf-input').find('.description');

				if ( textField.val().length > maxlength ) {
					description.addClass('error');
					return false;
				} else {
					description.removeClass('error');					
				}
			});
			
			let submitButton = form.find('[type=submit]');

			var formData = new FormData(this);
			formData.append( 'action', 'save_form_data' );
			formData.append( 'nonce', mlHelper.mlSecurity );
			formData.append( 'user_id', mlHelper.userID );
			formData.append( 'img_thumbnail', $('.acf-field-page-hero-image .show-if-value img').attr('src') );
			
			$.ajax({
				type: 'POST',
				url: mlHelper.ajaxUrl,
				processData: false,
				contentType: false,
				data: formData,
				beforeSend : function() {
					submitButton.addClass('wait');
					form.addClass('readonly');
				},
				success: function( result ){
					if ( result === false ) {
						$('.save-result.error').show();
						$('.save-result.success').hide();
						return false;
					} 

					let fields = JSON.parse(result);
					console.log( fields );
					$('.medlink-call-to-action').text( fields['page_call_to_action'] );
					$('.medlink-intro').text( fields['page_intro_text'] );
					
					if ( fields['page_hero_image'].length ) {
						$('.medlink-hero-image').replaceWith( fields['page_hero_image'] );
					}
					
					$('.save-result.error').hide();
					$('.save-result.success').show();
					submitButton.removeClass('wait');
					form.removeClass('readonly');
					return false;
				},
				error: function (request, status, error) {
					console.log(request, status, error);
					submitButton.removeClass('wait');
					form.removeClass('readonly');
					return false;
				}
			});
			
			return false;
		});
	});

	function validateHeroImage(event) {
		let file = event.target.files[0];
		if ( !file ) return false;
		
		let extention = /(?:\.([^.]+))?$/.exec( file['name'] )[1];

		if ( !mlHelper.heroImage.mimeTypes.includes(extention) ) return false;
	
		let fileSize = file['size'];
		if ( fileSize < mlHelper.heroImage.minSize || fileSize > mlHelper.heroImage.maxSize ) 
			return false; 
		
		return true;
	}
} )( jQuery );