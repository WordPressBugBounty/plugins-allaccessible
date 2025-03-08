(function($) {

	if(!window.allaccessible)
		window.allaccessible = {};

	if(allaccessible.DeactivateFeedbackForm)
		return;

	allaccessible.DeactivateFeedbackForm = function(plugin)
	{
		var self = this;
		var strings = allaccessible_deactivate_feedback_form_strings;

		this.plugin = plugin;

		// Dialog HTML
		var element = $('\
			<div class="allaccessible-deactivate-dialog" data-remodal-id="' + plugin.slug + '">\
				<form role="form" aria-label="' + strings.quick_feedback + '">\
					<input type="hidden" name="plugin"/>\
					<input type="hidden" name="type" value="deactivation"/>\
					<h2>' + strings.quick_feedback + '</h2>\
					<p>\
						' + strings.foreword + '\
					</p>\
					<ul class="allaccessible-deactivate-reasons"></ul>\
					<br>\
					<div class="allaccessible-deactivate-dialog-buttons">\
						<input type="submit" class="button confirm" value="' + strings.skip_and_deactivate + '" aria-label="' + strings.skip_and_deactivate + '"/>\
						<button data-remodal-action="cancel" class="button button-primary">' + strings.cancel + '</button>\
					</div>\
				</form>\
			</div>\
		')[0];
		this.element = element;

		$(element).find("input[name='plugin']").val(JSON.stringify(plugin));

		$(element).on("click", "input[name='reason']", function(event) {
			$(element).find("input[type='submit']").val(
				strings.submit_and_deactivate
			);

			// Hide all comment information
			$(element).find(".allaccessible-deactivate-comment").hide();

			// Show comment information for selected option
			var selectedOption = $(element).find("input[name='reason']:checked").val();
			if (selectedOption && plugin.reasons[selectedOption].comment) {
				var comment = plugin.reasons[selectedOption].comment;
				var commentInput = $(element).find(".allaccessible-deactivate-comment-" + selectedOption);
				commentInput.show();
				commentInput.find(".allaccessible-deactivate-comment-desc").html(comment.text);
				commentInput.find(".allaccessible-deactivate-comment-input").attr("type", comment.type);
			}
		});

		$(element).find("form").on("submit", function(event) {
			self.onSubmit(event);
		});

		// Reasons list
		var ul = $(element).find("ul.allaccessible-deactivate-reasons");
		for(var key in plugin.reasons)
		{
			var li = $("<li><input type='radio' name='reason'/> <span></span></li>");

			$(li).find("input").val(key);
			$(li).find("span").html(plugin.reasons[key].option);

			// Add comment information and input field
			if (plugin.reasons[key].comment) {
				var comment = plugin.reasons[key].comment;
				var commentInput;
				if (comment.type === 'textarea') {
					commentInput = $('<div class="allaccessible-deactivate-comment allaccessible-deactivate-comment-' + key + '"><p class="allaccessible-deactivate-comment-desc"></p><textarea name="comment-'+key+'" class="allaccessible-deactivate-comment-input" aria-label="' + strings.brief_description + '"></textarea></div>');
				} else {
					commentInput = $('<div class="allaccessible-deactivate-comment allaccessible-deactivate-comment-' + key + '"><p class="allaccessible-deactivate-comment-desc"></p><input type="text" name="comment-'+key+'" class="allaccessible-deactivate-comment-input" aria-label="' + strings.brief_description + '" /></div>');
				}
				commentInput.hide();
				commentInput.find(".allaccessible-deactivate-comment-desc").html(comment.text);
				$(li).append(commentInput);
			}

			$(ul).append(li);

			// Hide comment information for all options initially
			if (plugin.reasons[key].comment) {
				$(element).find(".allaccessible-deactivate-comment-" + key).hide();
			}
		}

		// Listen for deactivate
		$("#the-list [data-slug='" + plugin.slug + "'] .deactivate>a").on("click", function(event) {
			self.onDeactivateClicked(event);
		});
	}

	allaccessible.DeactivateFeedbackForm.prototype.onDeactivateClicked = function(event)
	{
		this.deactivateURL = event.target.href;

		event.preventDefault();

		if(!this.dialog)
			this.dialog = $(this.element).remodal();
		this.dialog.open();
	}

	allaccessible.DeactivateFeedbackForm.prototype.onSubmit = function(event)
	{
		var element = this.element;
		var strings = allaccessible_deactivate_feedback_form_strings;
		var self = this;
		var data = $(element).find("form").serialize();

		$(element).find("button, input[type='submit']").prop("disabled", true);

		if($(element).find("input[name='reason']:checked").length)
		{
			$(element).find("input[type='submit']").val(strings.thank_you);
			// Remove console.log for production
			$.ajax({
				type: "POST",
				url: "https://app.allaccessible.org/api/wp-feedback",
				data: data
			}).always(function() {
				window.location.href = self.deactivateURL;
			});

			// Add loading indicator
			var loadingIcon = $('<span class="dashicons dashicons-update-alt" style="animation: rotation 2s infinite linear; margin-left: 5px;"></span>');
			$(element).find("input[type='submit']").after(loadingIcon);
		}
		else
		{
			$(element).find("input[type='submit']").val(strings.please_wait);
			window.location.href = self.deactivateURL;
		}

		event.preventDefault();
		return false;
	}

	$(document).ready(function() {

		for(var i = 0; i < allaccessible_deactivate_feedback_form_plugins.length; i++)
		{
			var plugin = allaccessible_deactivate_feedback_form_plugins[i];
			new allaccessible.DeactivateFeedbackForm(plugin);
		}

	});

})(jQuery);
