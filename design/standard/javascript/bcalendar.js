(function ($){

	$(document).ready(function () {
	
		//Editing UI
		$('.bc-event-block').each(function () {
			var block = this;
			
			$('.bc-recurtype', block).change(function () {
				var type = $(this).val();
				
				$('.bc-recurblock', block).hide();
				$('.bc-element-range', block).hide();
				
				switch(type)
				{
					case 'ONCE':
						$('.bc-block-single', block).show();
					break;
					case 'WEEK':
						$('.bc-block-weekly', block).show();
						$('.bc-element-range', block).show();
						$('.bc-repeat-type', block).html(' '+blendCalendarWeekString);
					break;
					case 'MONTH':
						$('.bc-block-monthly', block).show();
						$('.bc-element-range', block).show();
						$('.bc-repeat-type', block).html(' '+blendCalendarMonthString);
					break;
					case 'YEAR':
						$('.bc-block-yearly', block).show();
						$('.bc-element-range', block).show();
						$('.bc-repeat-type', block).html(' '+blendCalendarYearString);
					break;
				
				}
			}).triggerHandler( 'change' );
			$('.bc-rangeendtype-null, .bc-rangeendtype-date', block).click(function(event) {
				if( $(event.currentTarget).hasClass('bc-rangeendtype-null') )
				{
					$('.bc-rangeend-date', block ).attr('disabled', 'disabled');
				}
				else
				{
					$('.bc-rangeend-date', block ).removeAttr('disabled');
				}
			});
			$('.bc-rangeendtype-null:checked, .bc-rangeendtype-date:checked', block).triggerHandler('click');

		});
		
		
		//Admin calendar selector
		$('.bc-quickview').each(function () {
			var cal = this,
				dataUrl = $(cal).attr('data-url');
				
			
			this.refreshCalendar=function () {
				var month=$(cal).attr('month'),
					year=$(cal).attr('year'),
					nodeId=$(cal).attr('node-id'),
					contentClassAttributeId=$(cal).attr('attribute-id'),
					url=dataUrl + '/' + contentClassAttributeId + '/' + year + '/' + month + '/(subtree)/' + nodeId;
					
				$('.load', cal).show();
				$('.result', cal).html('');
				
				$.get(url, {}, function (response) {
					$('.result', cal).html(response);
					$('.load', cal).hide();
				
					$('.day-link', cal).click(function () {
						var day = $(this).html();
						$('.calendar-day', cal).hide();
						$('.calendar-day-' + day, cal).show();
						
						$('.day-link', cal).removeClass('selected');
						$(this).addClass('selected');
						
						return false;
					}); 
					$('.calendar-day', cal).hide();
					       
				});
				
			};

			this.nextMonth = function () {
				var month = $(cal).attr('month'),
					year = $(cal).attr('year');
				month++; 
				
				if(month > 12)
				{
					month = 1;
					year++;
				}			
				
				$(cal).attr('month', month);
				$(cal).attr('year', year);
					
				cal.refreshCalendar();				
			};		
			
			this.prevMonth = function () {
				var month = $(cal).attr('month'),
					year = $(cal).attr('year');
				
				month--; 
				
				if (month < 1) {
					month = 12;
					year--;
				}
				
				$(cal).attr('month', month);
				$(cal).attr('year', year);

				cal.refreshCalendar();				
			};

			$('.bc-quickview-next', cal).click(function () {
				cal.nextMonth();
				return false;
			});
			
			$('.bc-quickview-prev', cal).click(function () {
				cal.prevMonth();
				return false;
			});
			
			this.refreshCalendar();
		});
		
	
	});

})(jQuery);
