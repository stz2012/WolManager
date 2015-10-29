// JQuery Mobileの初期設定
$(document).bind('mobileinit', function(){
	$.mobile.ajaxEnabled = false;
	$.mobile.hashListeningEnabled = false;
	$.mobile.page.prototype.options.addBackBtn = false;
	$.mobile.page.prototype.options.keepNative = '.data-role-none, .data-role-none *';
});
