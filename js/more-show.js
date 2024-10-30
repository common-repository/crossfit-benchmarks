jQuery(document).ready(function() {
  jQuery('div.demo-show> div').hide();
  jQuery('div.demo-show> h3').click(function() {
	jQuery(this).next('div').slideToggle('fast')
	.siblings('div:visible').slideUp('fast');
  });
});
