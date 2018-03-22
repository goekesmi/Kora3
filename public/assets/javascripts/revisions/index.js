var Kora = Kora || {};
Kora.Revisions = Kora.Revisions || {};

Kora.Revisions.Index = function() {
	$('.multi-select').chosen({
		width: '100%'
	});

	function initializeOptionDropdowns() {
		$('.option-dropdown-js').chosen({
			disable_search_threshold: 10,
			width: 'auto'
		});
	}

	function initializeToggle() {
		$('.revision-toggle-js').click(function(e) {
			e.preventDefault();
			
			var $this = $(this);
			var $header = $this.parent().parent();
			var $token = $header.parent();
			var $content = $header.next();

			$this.children('.icon').toggleClass('active');
            $token.toggleClass('active');
            if ($token.hasClass('active')) {
                $header.addClass('active');
                $token.animate({
                    height: $token.height() + $content.outerHeight(true) + 'px'
                }, 230);
                $content.effect('slide', {
                    direction: 'up',
                    mode: 'show',
                    duration: 240
                });
            } else {
                $token.animate({
                    height: '58px'
                }, 230, function() {
                    $header.hasClass('active') ? $header.removeClass('active') : null;
                    $content.hasClass('active') ? $content.removeClass('active') : null;
                });
                $content.effect('slide', {
                    direction: 'up',
                    mode: 'hide',
                    duration: 240
                });
            }
		})
	}

    initializeOptionDropdowns();
    initializeToggle();
}

Kora.Revisions.Index();