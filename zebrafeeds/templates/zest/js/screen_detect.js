var wWidth = $(window).width(),
	wHeight = $(window).height(),
	iPhoneW = 320,
	iPhoneH = 480,
	iPadW = 728,
	iPadH = 1024;
if (wWidth <= iPhoneH) {
	// client has an iPhone-sized screen
	$('head').append('<script type="text/javascript" src="newsfeeds/templates/zest/zestmobile.js"></script>');
}