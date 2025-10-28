(function($){
  'use strict';

  const PluginDeactivationFeedback = {
                    
    // Properties
    deactivateLink: '',
    pluginSlug: '',
    modal: null,
    form: null,
    
    // Initialize
    init: function() {
      this.cacheElements();
      this.bindEvents();
    },
    
    // Cache DOM elements
    cacheElements: function() {
      this.modal = $('#plugin-deactivation-modal');
      this.form = $('#plugin-deactivation-form');
      this.cancelBtn = $('#cancel-deactivation');
      this.skipBtn = $('#skip-deactivation');
      this.reasonInputs = $('input[name="reason"]');
      this.detailInputs = $('.reason-detail');
    },
    
    // Bind all events
    bindEvents: function() {
      const self = this;
      
      // Intercept deactivation link
      $('#the-list').on('click', '[data-slug] .deactivate a', function(e) {
        e.preventDefault();
        const slug = $(this).closest('[data-slug]').data('slug');

        self.form.find('[name="plugin"]').val(slug);

        if ( stobokit && stobokit.plugins && stobokit.plugins.includes( slug ) ) {
          self.onDeactivateClick(e, this);
        }
        
      });
      
      // Radio button change
      this.reasonInputs.on('change', function() {
          self.onReasonChange(this);
      });
      
      // Cancel button
      this.cancelBtn.on('click', function(e) {
          self.onCancelClick(e);
      });
      
      // Skip button
      this.skipBtn.on('click', function(e) {
          self.onSkipClick(e);
      });
      
      // Form submit
      this.form.on('submit', function(e) {
          self.onFormSubmit(e);
      });
      
      // Close on overlay click
      $('.plugin-deactivation-overlay').on('click', function(e) {
          self.onCancelClick(e);
      });
      
      // ESC key to close
      $(document).on('keyup', function(e) {
        if (e.key === 'Escape' || e.keyCode === 27) {
          self.closeModal();
        }
      });
    },
    
    // Event: Deactivate link clicked
    onDeactivateClick: function(e, element) {
      e.preventDefault();
      this.deactivateLink = $(element).attr('href');
      this.showModal();
    },
    
    // Event: Reason changed
    onReasonChange: function(element) {
      this.detailInputs.addClass('hidden');
      $(element).closest('li').find('.reason-detail').removeClass('hidden');
    },
    
    // Event: Cancel clicked
    onCancelClick: function(e) {
      e.preventDefault();
      this.closeModal();
    },
    
    // Event: Skip clicked
    onSkipClick: function(e) {
        e.preventDefault();
        this.deactivatePlugin();
    },
    
    // Event: Form submitted
    onFormSubmit: function(e) {
      e.preventDefault();
      
      var reason = this.reasonInputs.filter(':checked').val();
      
      if (!reason) {
        this.showError('Please select a reason');
        return;
      }
      
      var detail = this.reasonInputs.filter(':checked')
        .closest('li')
        .find('.reason-detail')
        .val();

      const plugin = this.form.find('[name="plugin"]').val();
      
      this.submitFeedback(reason, detail, plugin);
    },
    
    // Show modal
    showModal: function() {
      this.modal.fadeIn(200);
      this.resetForm();
    },
    
    // Close modal
    closeModal: function() {
      this.modal.fadeOut(200);
      this.resetForm();
    },
    
    // Reset form
    resetForm: function() {
      this.form[0].reset();
      this.detailInputs.addClass('hidden');
    },
    
    // Show error message
    showError: function(message) {
        alert(message);
    },
    
    // Submit feedback via AJAX
    submitFeedback: function(reason, detail, plugin) {
      var self = this;      
      
      $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
          action: 'stobokit_plugin_deactivation_feedback',
          reason,
          detail,
          plugin,
          nonce: stobokit.nonce
        },
        beforeSend: function() {
          self.form.find('button').prop('disabled', true);
        },
        success: function(response) {
          
          if (response.success) {
            self.deactivatePlugin();
          } else {
            setTimeout(function() {
              self.deactivatePlugin();
            }, 1000);
          }
        },
        error: function() {
          setTimeout(function() {
            self.deactivatePlugin();
          }, 1000);
        }
      });
    },
    
    // Proceed with deactivation
    deactivatePlugin: function() {
      if (this.deactivateLink) {
        window.location.href = this.deactivateLink;
      }
    }
};

// Initialize on document ready
$(document).ready(function() {
  PluginDeactivationFeedback.init();
});
  
})(jQuery);