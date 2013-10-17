(function($) {

	$.entwine('ss.timednotices', function($){
		
		var noticeHTML,
			stashedNotices = null;

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
				var container = this;
				if(stashedNotices){
					container.html(stashedNotices).show();
					$(window).trigger('resize');
				}

				$.getJSON('timednotice/notices', function(data){
					if(data.length){
						container.html('');
						$(data).each(function(){
							container.append($("<p />")
								.addClass('message')
								.addClass(this.MessageType)
								.html(this.Message)
								.html(this.Message)
							);
						});
						container.show();
						$(window).trigger('resize');		
						stashedNotices = container.html();
					}
				});
			}
		});
	});

}(jQuery));
