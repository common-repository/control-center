(function( $ ) {
	'use strict';

	$(function() {
		$( "#date_start" ).datepicker({
			defaultDate: "+1w",
			numberOfMonths: 1,
			onClose: function( selectedDate ) {
				$( "#date_start" ).datepicker( "option", "minDate", selectedDate );
			}
		});
		$( "#date_end" ).datepicker({
			defaultDate: "+1w",
			numberOfMonths: 1,
			onClose: function( selectedDate ) {
				$( "#date_end" ).datepicker( "option", "maxDate", selectedDate );
			}
		});

		$(".datepicker").datepicker();

		$(".repeater").on("change", ".engine", function() {
			var val = $(this).val();
			var group = $(this).parents("tr");
			group.find(".region_google,.region_bing,.region_yahoo").hide();
			group.find(".region_" + $(this).val()).show();
		});

		$('.repeater').repeater({
			// (Optional)
			// "defaultValues" sets the values of added items.  The keys of
			// defaultValues refer to the value of the input's name attribute.
			// If a default value is not specified for an input, then it will
			// have its value cleared.
			defaultValues: {
				'engine': 'google',
				'region_google': 'en-us',
				'region_bing': 'en-us',
				'region_yahoo': 'en-us',
			},
			// (Optional)
			// "show" is called just after an item is added.  The item is hidden
			// at this point.  If a show callback is not given the item will
			// have $(this).show() called on it.
			show: function () {
				$(this).find('.engine').prop("disabled", false);
				$(this).find('.region_google').prop("disabled", false);
				$(this).find('.region_bing').prop("disabled", false);
				$(this).find('.region_yahoo').prop("disabled", false);
				$(this).show();
			},
			// (Optional)
			// "hide" is called when a user clicks on a data-repeater-delete
			// element.  The item is still visible.  "hide" is passed a function
			// as its first argument which will properly remove the item.
			// "hide" allows for a confirmation step, to send a delete request
			// to the server, etc.  If a hide callback is not given the item
			// will be deleted.
			hide: function (deleteElement) {
				if(confirm("Are you sure you want to delete this keyword request?\nDeleting will remove all historical data for this request.")) {
					$(this).hide(deleteElement);
				}
			},
			// (Optional)
			// Removes the delete button from the first list item,
			// defaults to false.
			isFirstItemUndeletable: false
		});
	});

})( jQuery );