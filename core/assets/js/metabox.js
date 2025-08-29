(function($){
  $(document).ready(function() {
		$(document).on('click', '.upload-media-button', function(e) {
			e.preventDefault();
			
			const button = $(this);
			const input = button.siblings('input[type=hidden]');
			const preview = button.siblings('.media-preview');
			const isMultiple = button.data('multiple') === true || button.data('multiple') === 'true';
			let fileType = button.data('file-type') || 'all';
			
			// Parse file type if it's JSON string (array)
			if (typeof fileType === 'string' && fileType.startsWith('[')) {
				try {
					fileType = JSON.parse(fileType);
				} catch(e) {
					fileType = 'all';
				}
			}
			
			// Configure media uploader options
			const mediaOptions = {
				title: 'Select Media',
				button: { text: "Select" },
				multiple: isMultiple
			};
			
			// Set file type restrictions
			if (fileType !== 'all') {
				if (Array.isArray(fileType)) {
					// Handle multiple file types
					const allowedTypes = [];
					fileType.forEach(type => {
						switch (type) {
							case 'image':
								allowedTypes.push('image');
								break;
							case 'video':
								allowedTypes.push('video');
								break;
							case 'audio':
								allowedTypes.push('audio');
								break;
							case 'document':
							case 'application':
								allowedTypes.push('application');
								break;
						}
					});
					
					if (allowedTypes.length > 0) {
							mediaOptions.library = { type: allowedTypes };
					}
				} else {
					// Handle single file type
					switch (fileType) {
						case 'image':
							mediaOptions.library = { type: 'image' };
							break;
						case 'video':
							mediaOptions.library = { type: 'video' };
							break;
						case 'audio':
							mediaOptions.library = { type: 'audio' };
							break;
						case 'document':
						case 'application':
							mediaOptions.library = { type: 'application' };
							break;
					}
				}
			}
			
			// Create a unique media frame to avoid conflicts
			const frameId = 'media_frame_' + Math.random().toString(36).substr(2, 9);
			const mediaUploader = wp.media(mediaOptions);
			
			// Set pre-selected items when opening the media library
			mediaUploader.on('open', function() {
				const selection = mediaUploader.state().get('selection');
				selection.reset(); // Clear any previous selections
					
				// Small delay to ensure the media library is fully loaded
				setTimeout(function() {
					if (isMultiple) {
						// For multiple selection, get all existing IDs
						const existingIds = input.val() ? input.val().split(',').filter(id => id && id.trim()) : [];
						existingIds.forEach(function(id) {
							if (id && id.trim()) {
								const attachment = wp.media.attachment(parseInt(id.trim()));
								if (attachment) {
									attachment.fetch().then(function() {
										selection.add(attachment);
									});
								}
							}
						});
					} else {
						// For single selection, get the current value
						const currentId = input.val();
						if (currentId && currentId.trim()) {
							const attachment = wp.media.attachment(parseInt(currentId.trim()));
							if (attachment) {
								attachment.fetch().then(function() {
									selection.add(attachment);
								});
							}
						}
					}
				}, 100);
			});
			
			mediaUploader.on('select', function() {
				if (isMultiple) {
					handleMultipleMediaSelection(mediaUploader, input, preview);
				} else {
					handleSingleMediaSelection(mediaUploader, input, preview);
				}
			});
			
			mediaUploader.open();
		});
	
		// Handle single media selection
		function handleSingleMediaSelection(mediaUploader, input, preview) {
			const attachment = mediaUploader.state().get('selection').first().toJSON();
			input.val(attachment.id);
			
			let mediaHtml = '';
			mediaHtml += `<div class="media-item" data-id="${attachment.id}">`;
			if (attachment.type === 'image') {
					mediaHtml += `<img src="${attachment.url}" style="max-width: 150px; height: auto;" />`;
			} else if (attachment.type === 'video') {
					mediaHtml += `<video width="150" controls><source src="${attachment.url}" type="${attachment.mime}"></video>`;
			} else {
					mediaHtml += `<p>${attachment.filename}</p>`;
			}
			mediaHtml += `</div>`;

			preview.html(mediaHtml);
		}
	
		// Handle multiple media selection
		function handleMultipleMediaSelection(mediaUploader, input, preview) {
			const selections = mediaUploader.state().get('selection');
			const selectedIds = [];
			
			// Get all selected items (including previously selected ones)
			selections.each(function(attachment) {
				const attachmentData = attachment.toJSON();
				selectedIds.push(attachmentData.id);
			});
			
			// Remove duplicates and update input
			const uniqueIds = [...new Set(selectedIds)];
			input.val(uniqueIds.join(','));
			
			// Update preview
			updateMultipleMediaPreview(uniqueIds, preview);
		}
	
		// Update multiple media preview
		function updateMultipleMediaPreview(mediaIds, preview) {
			if (!mediaIds.length) {
				preview.html('');
				return;
			}
			
			let galleryHtml = '<div class="media-gallery">';
			
			// Get attachment data for each ID
			const promises = mediaIds.map(id => {
				return new Promise((resolve) => {
					if (!id) {
						resolve(null);
						return;
					}
					
					const attachment = wp.media.attachment(id);
					attachment.fetch().then(() => {
						resolve(attachment.toJSON());
					}).catch(() => {
						resolve(null);
					});
				});
			});
			
			Promise.all(promises).then(attachments => {
				attachments.forEach(attachment => {
					if (!attachment) return;
					
					galleryHtml += `<div class="media-item" data-id="${attachment.id}">`;
					galleryHtml += '<span class="remove-single-media"><span class="dashicons dashicons-no-alt"></span></span>';
					
					if (attachment.type === 'image') {
							galleryHtml += `<img src="${attachment.url}" style="max-width: 100px; height: auto;" />`;
					} else if (attachment.type === 'video') {
							galleryHtml += `<video width="100" controls><source src="${attachment.url}" type="${attachment.mime}"></video>`;
					} else {
							galleryHtml += `<p class="file-name">${attachment.filename}</p>`;
					}
					
					galleryHtml += '</div>';
				});
				
				galleryHtml += '</div>';
				preview.html(galleryHtml);
			});
		}
	
		// Remove all media
		$(document).on('click', '.remove-media-button', function(e) {
				e.preventDefault();
				$(this).siblings('input[type=hidden]').val('');
				$(this).siblings('.media-preview').html('');
		});
		
		// Remove single media from multiple selection
		$(document).on('click', '.remove-single-media', function(e) {
			e.preventDefault();
			
			const mediaItem = $(this).closest('.media-item');
			const mediaId = mediaItem.data('id');
			const input = mediaItem.closest('.field-content').find('input[type=hidden]');
			
			// Remove from input value
			let currentIds = input.val() ? input.val().split(',').filter(id => id) : [];
			currentIds = currentIds.filter(id => id != mediaId);
			input.val(currentIds.join(','));
			
			// Remove from DOM
			mediaItem.remove();
			
			// If no more items, clear the gallery
			if (currentIds.length === 0) {
				input.siblings('.media-preview').html('');
			}
		});
    
    // Repeater
    $(document).on('click', '.add-repeater-item', function() {
			const container = $(this).siblings('.repeater-container');
			let template = container.find('.repeater-template').html();
			const index = container.find('.repeater-item').length;
			
			template = template.replace(/\{INDEX_DISPLAY\}/g, index+1);
			container.append(template);
			checkConditions();
    });
    
    $(document).on('click', '.remove-item', function() {
			$(this).closest('.repeater-item').remove();
			checkConditions();
    });
    
    // Conditional fields
    function checkConditions() {
			if ( $('[data-condition]').length ) {
				$('[data-condition]').each(function() {
					const element = $(this);
					const conditions = JSON.parse(element.attr('data-condition'));
					let show = true;
					
					if (Array.isArray(conditions)) {
						// Multiple conditions with AND/OR logic
						const relation = conditions.relation || 'AND';
						const results = [];
						
						conditions.conditions.forEach(function(condition) {
							results.push(checkSingleCondition(condition));
						});
						
						if (relation === 'OR') {
							show = results.some(function(result) { return result; });
						} else {
							show = results.every(function(result) { return result; });
						}
					} else {
						// Single condition
						show = checkSingleCondition(conditions);
					}
					
					if (show) {
						element.removeClass('hidden');
					} else {
						element.addClass('hidden');
					}
				});
			}
    }
    
    function checkSingleCondition(condition) {
			const field = $("[name*=\"[" + condition.field + "]\"]");
			if (!field.length) return false;
			
			const fieldValue = '';
			if (field.is(':checkbox')) {
				fieldValue = field.is(':checked') ? '1' : '0';
			} else if (field.is(':radio')) {
				fieldValue = field.filter(':checked').val() || '';
			} else {
				fieldValue = field.val();
			}
			
			const conditionValue = condition.value;
			const operator = condition.operator || "=";
			
			switch (operator) {
				case '=':
					return fieldValue == conditionValue;
				case '!=':
					return fieldValue != conditionValue;
				case 'in':
					return Array.isArray(conditionValue) && conditionValue.includes(fieldValue);
				case 'not_in':
					return Array.isArray(conditionValue) && !conditionValue.includes(fieldValue);
				default:
					return fieldValue == conditionValue;
			}
    }
    
    // Trigger condition check on field changes
    $(document).on('change', 'input, select, textarea', function() {
      checkConditions();
    });
    
    // Initial condition check
    checkConditions();
  });
})(jQuery);

