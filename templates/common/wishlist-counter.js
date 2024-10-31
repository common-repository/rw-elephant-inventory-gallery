jQuery(function ($) {
   $(document).ready(function() {
	var rwe_sid = getCookie('rwe_sid');
	var cartid = getCookie('cartid');
	// get wishlist count and update wishlist button
	if( $('.view-wishlist').length ) // we have a view wishlist button
	{
		$.getJSON(rweURL,{sid: rwe_sid, action:'list_wishlist_items', wishlist_id: cartid}, function(result){
			if (result.wishlist_id !== cartid) {
					setCookie("cartid",result.wishlist_id,1);
			}
			if (result.invoice_line_items.length){
				var itemcount = result.invoice_line_items.length;
				if ($(".item-count").length)
					$(".item-count").html('('+itemcount+')');
				else 
					$('.view-wishlist').append(' <span class="item-count">('+itemcount+')</span>');
			}
		});
	}
   });
});