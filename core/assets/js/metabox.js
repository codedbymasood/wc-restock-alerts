(function($){
  $(document).ready(function() {
    
    // Media uploader
    $(document).on('click', '.upload-media-button', function(e) {
			e.preventDefault();
			const button = $(this);
			const input = button.siblings('input[type=hidden]');
			const preview = button.siblings('.media-preview');
			
			const mediaUploader = wp.media({
					title: 'Select Media',
					button: { text: "Select" },
					multiple: false
			});
			
			mediaUploader.on('select', function() {
				const attachment = mediaUploader.state().get('selection').first().toJSON();
				input.val(attachment.id);
				if (attachment.type === 'image') {
					preview.html(`<img src="${attachment.url}" />`);
				} else {
					preview.html(`<p>${attachment.filename}</p>`);
				}
			});
			
			mediaUploader.open();
    });
    
    $(document).on('click', '.remove-media-button', function(e) {
			e.preventDefault();
			$(this).siblings('input[type=hidden]').val('');
			$(this).siblings('.media-preview').html('');
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

