(function($) {

	$.entwine('ss.timednotices', function($){
		
		var noticeHTML,
			container,
			timeout
		;
		
		var updateNotices = function () {
			$.getJSON('timednotice/notices', function(data){
				if(data.length) {
					container.html('');
					$(data).each(function(){
						var entry = $("<div />")
							.addClass('message')
							.addClass(this.MessageType)
							.attr('data-id', this.ID)
							.html(this.Message)
						
						var snoozer = $('<div>Snooze for <a href="#" rel="15">15 mins</a>, <a href="#" rel="60">1 hour</a>, <a href="#" rel="1440">1 day</a></div>')
							.addClass('notice-snoozer')
						
						entry.append(snoozer);
						container.append(entry);
					
					});
					container.show();
					$(window).trigger('resize');		
				}
				
				timeout = setTimeout(updateNotices, 30000);
			});
		}

		$('.cms-content-header').entwine({
			onmatch: function(){
				this.prepend($("<div />")
					.attr('id', 'timed-notices')
					.hide()
				);
			}
		});

		$('#timed-notices').entwine({
			onmatch: function(){
				container = this;
				
				if (timeout) {
					clearTimeout(timeout);
				}
				updateNotices();
			}
		});
		
		$('#timed-notices .notice-snoozer a').entwine({
			onclick: function (e) {
				e.preventDefault();
				var notice = $(this.closest('.message'));
				
				$.post('timednotice/snooze', {ID: notice.attr('data-id'), plus: $(this).attr('rel')}, function(data) {
					notice.remove();
				})
				return false;
			}
		})
	});

}(jQuery));
