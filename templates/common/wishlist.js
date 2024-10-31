function wishlistpagecontent(result) {
				var content = '<ul>';
				var index;
				var a = result['invoice_line_items'];
				for (index = 0; index < a.length; ++index) {
					var itemthumb = a[index]['image_links'][0]['photo_hash'];
					if (wishlistthumbnailsize == 200)
						var thumbbase = '_large_thumbnail_';
					else
						var thumbbase = '_public_thumbnail_';

					content += '<li class="id-' + a[index].inventory_item_id + '">';
					var url = '';
					if (permalinks == true) {
						if (seoURLs == true ) {                           
							seourl = a[index].name;
							seourl = seourl.replace(/[^A-Za-z0-9\s-._\/]/g,"");
							seourl = seourl.replace(/[\/._]+/g," ");
							seourl = seourl.replace(/[\s-]+/g," ");
							seourl = seourl.trim();
							seourl = seourl.substr(0, 100);
							seourl = seourl.trim();
							seourl = seourl.replace(/\s/g,"-");
							seourl = seourl.toLowerCase();
							url = galleryURL + 'item/' + seourl + '-' + a[index].inventory_item_id + '/';
						}
						else
							url = galleryURL + 'item/' + a[index].inventory_item_id + '/';
					}
					else {
						url = galleryURL + '&rweitem=' + a[index].inventory_item_id;
					}

					content += '<a href="' + url + '">';

					if (itemthumb)
						content += '<img src="https://images.rwelephant.com/' + rweID + thumbbase + itemthumb + '"/>';

					content += '<span class="item-name"';

					if (!itemthumb) content += ' style="padding-left: ' + wishlistthumbnailsize + 'px;"';

					content += '>'+a[index].name+'</span></a>';
					if (showprice) {
						content += '<span class="item-price">$' + a[index].unit_price + '</span>';
					}
					if (showquantity) {
						content += '<input name="' + a[index].inventory_item_id +'" class="item qty" type="text" value="' + a[index].quantity + '"/>';
					}
					else {
						content += '<button class="remove-from-wishlist" value="'+a[index].inventory_item_id+'">X</button>';
					}
					content += '</li>';
				}
				content += '</ul>';
				content += '<input id="update-view-wishlist" type="submit" style="visibility: hidden;" value="Update" />';
				content += '<button type="button" onclick="location.href=\''+galleryURL+'\'" class="continue-shopping">'+wishlistcontinuebuttontext+'</button>';
				content += '<button type="button" class="submit-wishlist">'+wishlistsubmitbuttontext+'</button>';

				return content;
}
function viewDropdownWishlist (result, cartid) {
			var content = '';
			if (result.wishlist_id !== cartid) {
					setCookie("cartid",result.wishlist_id,1);
			}
			if(typeof result.invoice_line_items === 'undefined'){
				content = 'Your wishlist is empty.';
			}
			else {
				if (!result.invoice_line_items.length){
					content = 'Your wishlist is empty.';
				}
				else {
					content += '<ul>';
					var index;
					var value = result['invoice_line_items'];
					for (index = 0; index < value.length; ++index) {
						content += '<li>';
						if (showquantity) {
							content += '<input name="' + value[index].inventory_item_id +'" class="item qty" type="text" value="' + value[index].quantity + '"/> ';
						}						
						else {
							content += '<button class="x-from-wishlist" value="'+value[index].inventory_item_id+'">X</button>';
						}

						content += '<span>'+value[index].name+'</span>';
						if (showprice) {
							content += '<span class="price">$' + value[index].unit_price+'</span>';
						}
						content += '</li>';
					}
					content += '<ul>';
					content += '<input id="update-wishlist" type="submit" style="visibility: hidden;" value="Update" />';
					content += '<button type="button" class="submit-wishlist">Submit Wishlist</button>';
					// $("#wishlist-result").empty();
				}
			}
			return content;
}

function wishlist_empty() {
		return '<p>Your wishlist is empty</p><button onclick="location.href=\''+galleryURL+'\'" type="button" class="continue-shopping">'+wishlistcontinuebuttontext+'</button>';
}


jQuery(function ($) {
   $(document).ready(function() {
	if( $('.view-wishlist-page').length ) // view wishlist page
	{
		var content = '';
		var rwe_sid = getCookie('rwe_sid');
		var cartid = getCookie('cartid');
		$.getJSON(rweURL,{sid: rwe_sid, action:'list_wishlist_items', wishlist_id: cartid}, function(result){
			if (result.wishlist_id !== cartid) {
					setCookie("cartid",result.wishlist_id,1);
			}
			if(typeof result.invoice_line_items === 'undefined'){
				$("#wishlist-page-contents").html(wishlist_empty());
			}
			else {
				if (!result.invoice_line_items.length){
					$("#wishlist-page-contents").html(wishlist_empty());
				}
				else {
					content = wishlistpagecontent(result);
					$("#wishlist-page-contents").html(content);
				}
			}
		});
	}
	$(".view-wishlist-page").on("click", ".submit-wishlist", function() {
		var loc = window.location.href;
		newloc = updateQueryStringParameter(loc, 'rwewishlist', 1);
		window.location.href = newloc;
	});
	$('.view-wishlist-page').on('click', '.remove-from-wishlist', function (e){
		e.preventDefault();
		// remove by adding qty 0 to wishlist
		var rwe_sid = getCookie('rwe_sid');
		var cartid = getCookie('cartid');
		var item = $(this).val();
		$.getJSON(rweURL,{sid: rwe_sid, action:'add_item_to_wishlist', wishlist_id: cartid, inventory_item_id: item, quantity: '0'}, function(result){
			if (result.response_status == 'Error') {
				$("#wishlist-page-contents").html(result.response_message);
			}
			else if (result.inventory_item_id == item) {
				// just remove the item.
				$("#wishlist-page-contents li.id-"+item).remove();
				if ($("#wishlist-page-contents ul").is(':empty')){
					$("#wishlist-page-contents").html(wishlist_empty());
				}
				// get list and update counter
				$.getJSON(rweURL,{sid: rwe_sid, action:'list_wishlist_items', wishlist_id: cartid}, function(result){
					if (result.invoice_line_items.length){
						var itemcount = result.invoice_line_items.length;
						if ($(".item-count").length)
							$(".item-count").html('('+itemcount+')');
						else 
							$('.view-wishlist').append(' <span class="item-count">('+itemcount+')</span>');
					}
					else {
						$(".item-count").remove();
					}
				});
			}
			else {
				$("#wishlist-page-contents").html('Unknown error occured.');
			}
		});
	});
	$("#wishlistpageform").submit(function(e) {
		e.preventDefault();
		var rwe_sid = getCookie('rwe_sid');
		var cartid = getCookie('cartid');
		var itemArray = [];
		x=$("#wishlistpageform").serializeArray();
		$.each(x, function(i, field){
			if (isPositiveInteger(field.value)) {
				itemArray.push({
					inventory_item_id: field.name,
					quantity: field.value
				});
			}
		});
		var dataString = '&sid='+rwe_sid+'&action=add_item_to_wishlist&wishlist_id='+cartid+'&items='+encodeURIComponent(JSON.stringify(itemArray));
		$.getJSON(rweURL,dataString, function(result){
			$.getJSON(rweURL,{sid: rwe_sid, action:'list_wishlist_items', wishlist_id: cartid}, function(result){
				var content = '';
				if(typeof result.invoice_line_items === 'undefined'){
					$("#wishlist-page-contents").html(wishlist_empty());
				}
				else {
					if (!result.invoice_line_items.length){
						$("#wishlist-page-contents").html(wishlist_empty());
						$(".item-count").remove();
					}
					else {
						content = wishlistpagecontent(result);
						$("#wishlist-page-contents").html(content);
						// update counter
						var itemcount = result.invoice_line_items.length;
						if ($(".item-count").length)
							$(".item-count").html('('+itemcount+')');
						else 
							$('.view-wishlist').append(' <span class="item-count">('+itemcount+')</span>');
					}
				}
			});
		});
	});
	// disable submit when changing data.
	$("#wishlistpageform").on("change keyup paste click", "input.qty", function() {
		$("#update-view-wishlist").css("visibility", "visible");
		$(".submit-wishlist").attr("disabled", "disabled").css({ opacity: 0.5 });
	});
	if( $('.view-wishlist').length ) // we have a view wishlist button
	{
		var rwe_sid = getCookie('rwe_sid');
		var cartid = getCookie('cartid');
		$.getJSON(rweURL,{sid: rwe_sid, action:'list_wishlist_items', wishlist_id: cartid}, function(result){
			if (result.wishlist_id !== cartid) {
					setCookie("cartid",result.wishlist_id,1);
			}
			if (result.invoice_line_items.length){
				var itemcount = result.invoice_line_items.length;
				$('.view-wishlist').append(' <span class="item-count">('+itemcount+')</span>');
				if( $('.add-to-wishlist').length ) // we have an add to wishlist button
				{
					var this_item = $('.add-to-wishlist').attr("value");
					$.each(result.invoice_line_items, function(index, value) {
						if (value.inventory_item_id == this_item) {
							$(".add-to-wishlist").attr("disabled", "disabled");
							$(".add-to-wishlist").html("Already on wishlist");
							$("#wishlist-result").html('Added to wishlist.');
						}
					});
				}
			}
		});
	}
	$(".add-to-wishlist").click(function() {
		var rwe_sid = getCookie('rwe_sid');
		var cartid = getCookie('cartid');
		var item = $(this).val();
		$.getJSON(rweURL,{sid: rwe_sid, action:'add_item_to_wishlist', wishlist_id: cartid, inventory_item_id: item, quantity: '+1'}, function(result){
			if (result.response_status == 'Error') {
				$("#wishlist-result").html(result.response_message);
			}
			else if (result.inventory_item_id == item) {
				// returned same item, add confirmed
				$(".add-to-wishlist").attr("disabled", "disabled");
				$(".add-to-wishlist").html("Already on wishist");
				$("#wishlist-result").html('Added to wishlist.');
				// check wishlist ID, if different update cookie
				if (result.wishlist_id !== cartid) {
						setCookie("cartid",result.wishlist_id,1);
						cartid = result.wishlist_id;
				}
				// get new wishlist count and update wishlist button
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
							if( $('.add-to-wishlist').length ) // we have an add to wishlist button
							{
								var this_item = $('.add-to-wishlist').attr("value");
								$.each(result.invoice_line_items, function(index, value) {
									if (value.inventory_item_id == this_item) {
										$(".add-to-wishlist").attr("disabled", "disabled");
										$(".add-to-wishlist").html("Already on wishist");
										$("#wishlist-result").html('Added to wishlist.');
									}
								});
							}
						}
					});
				}
			}
			else {
				$("#wishlist-result").html('Unknown error occured.');
			}
			$("#wishlist").slideUp();
		});
	});
	$(".view-wishlist").click(function() {
	  if (viewwishlistpage==true) {
		var loc = window.location.href;
		newloc = updateQueryStringParameter(loc, 'rweviewwishlist', 1);
		window.location.href = newloc;
	  }
	  else {
	    if ($("#wishlist").is(":visible")) {
		$("#wishlist").slideUp();
	    }
	    else {
		var rwe_sid = getCookie('rwe_sid');
		var cartid = getCookie('cartid');
		$.getJSON(rweURL,{sid: rwe_sid, action:'list_wishlist_items', wishlist_id: cartid}, function(result){
			var content = '';
			if (result.wishlist_id !== cartid) {
					setCookie("cartid",result.wishlist_id,1);
			}
			if(typeof result.invoice_line_items === 'undefined'){
				$("#wishlist-contents").html('Your wishlist is empty.');
				$("#wishlist").slideDown();
			}
			else {
				if (!result.invoice_line_items.length){
					$("#wishlist-contents").html('Your wishlist is empty.');
					$("#wishlist").slideDown();
				}
				else {
					content += '<ul>';
					$.each(result.invoice_line_items, function(index, value) {
						content += '<li>';
						if (showquantity) {
							content += '<input name="' + value.inventory_item_id +'" class="item qty" type="text" value="' + value.quantity + '"/> ';
						}
						else {
							content += '<button class="x-from-wishlist" value="'+value.inventory_item_id+'">X</button>';
						}
						content += '<span>'+value.name+'</span>';
						if (showprice) {
							content += '<span class="price">$' + value.unit_price+'</span>';
						}
						content += '</li>';
					});
					content += '<ul>';
					content += '<input id="update-wishlist" type="submit" style="visibility: hidden;" value="Update" />';
					content += '<button type="button" class="submit-wishlist">Submit Wishlist</button>';
					$("#wishlist-contents").html(content);
					$("#wishlist").slideDown();
					$("#wishlist-result").empty();
				}
			}
		});	
	    }
	  }
	});
	$("#wishlist").on("click", ".clear-whishlist", function() {
		var rwe_sid = getCookie('rwe_sid');
		var cartid = getCookie('cartid');
		$.getJSON(rweURL,{sid: rwe_sid, action:'clear_wishlist', wishlist_id: cartid}, function(result){
			if (result.response_status == 'Error') {
				$("#wishlist-contents").html(result.response_message);
			}
			else {
				$("#wishlist-contents").html('Wishlist cleared.');
			}
		});
	});
	// disable submit when changing data.
	$("#wishlist").on("change keyup paste click", "input.qty", function() {
		$("#update-wishlist").css("visibility", "visible");
		$(".submit-wishlist").attr("disabled", "disabled").css({ opacity: 0.5 });
	});
	// submit whishlist
	$("#wishlist").on("click", ".submit-wishlist", function() {
		var loc = window.location.href;
		newloc = updateQueryStringParameter(loc, 'rwewishlist', 1);
		window.location.href = newloc;
	});
	// remove item from wishlist
	$("#wishlist").on("click", ".x-from-wishlist", function(e) {
		e.preventDefault();
		// remove by adding qty 0 to wishlist
		var rwe_sid = getCookie('rwe_sid');
		var cartid = getCookie('cartid');
		var item = $(this).val();
		$.getJSON(rweURL,{sid: rwe_sid, action:'add_item_to_wishlist', wishlist_id: cartid, inventory_item_id: item, quantity: '0'}, function(result){
			if (result.response_status == 'Error') {
				$("#wishlist-contents").html(result.response_message);
			}
			else if (result.inventory_item_id == item) {
				$.getJSON(rweURL,{sid: rwe_sid, action:'list_wishlist_items', wishlist_id: cartid}, function(result2){
					var content = viewDropdownWishlist(result2, cartid);
					$("#wishlist-contents").html(content);
					if (result2.invoice_line_items.length){
						var itemcount = result2.invoice_line_items.length;
						if ($(".item-count").length)
							$(".item-count").html('('+itemcount+')');
						else 
							$('.view-wishlist').append(' <span class="item-count">('+itemcount+')</span>');
					}
					else {
						$(".item-count").remove();
					}
					if( $('.add-to-wishlist').length ) // we have add to wishlist button
					{
						var this_item = $('.add-to-wishlist').attr("value");
						var this_item_exists = 1;
					}
					$.each(result2.invoice_line_items, function(index, value) {
					    if ( this_item_exists && value.inventory_item_id == this_item) {
							this_item_exists +=1;
					    }
					});
					if ( this_item_exists == 1) {
						$(".add-to-wishlist").removeAttr("disabled");
						$(".add-to-wishlist").html("Add to wishist");
						$("#wishlist-result").html('');
					}
				});
			}
			else {
				$("#wishlist-contents").html('Unknown error occured.');
			}
		});
	});
	// update wishlist
	$("#wishlist").submit(function(e) {
		e.preventDefault();
		var rwe_sid = getCookie('rwe_sid');
		var cartid = getCookie('cartid');
		var itemArray = [];
		x=$("#wishlist").serializeArray();
		$.each(x, function(i, field){
			if (isPositiveInteger(field.value)) {
				itemArray.push({
					inventory_item_id: field.name,
					quantity: field.value
				});
			}
		});
		var dataString = '&sid='+rwe_sid+'&action=add_item_to_wishlist&wishlist_id='+cartid+'&items='+encodeURIComponent(JSON.stringify(itemArray));
		$.getJSON(rweURL,dataString, function(result){

			// same as view wishlist

			$.getJSON(rweURL,{sid: rwe_sid, action:'list_wishlist_items', wishlist_id: cartid}, function(result){
				var content = '';
				if(typeof result.invoice_line_items === 'undefined'){
					$("#wishlist-contents").html('Your wishlist is empty.');
					$(".item-count").remove();
					if( $('.add-to-wishlist').length ) // we have add to wishlist button
					{
						$(".add-to-wishlist").removeAttr("disabled");
						$(".add-to-wishlist").html("Add to wishist");
						$("#wishlist-result").html('');
					}
				}
				else {
					if (!result.invoice_line_items.length){
						$("#wishlist-contents").html('Your wishlist is empty.');
						$(".item-count").remove();
						if( $('.add-to-wishlist').length ) // we have add to wishlist button
						{
							$(".add-to-wishlist").removeAttr("disabled");
							$(".add-to-wishlist").html("Add to wishist");
							$("#wishlist-result").html('');
						}

					}
					else {
						// update counter
						var itemcount = result.invoice_line_items.length;
						if ($(".item-count").length)
							$(".item-count").html('('+itemcount+')');
						else 
							$('.view-wishlist').append(' <span class="item-count">('+itemcount+')</span>');

						if( $('.add-to-wishlist').length ) // we have add to wishlist button
						{
							var this_item = $('.add-to-wishlist').attr("value");
							var this_item_exists = 1;
						}
						content += '<ul>';
						$.each(result.invoice_line_items, function(index, value) {
						    content += '<li><input name="' + value.inventory_item_id +'" class="item qty" type="text" value="' + value.quantity + '"/> ';
						    content += '<span>'+value.name+'</span>';
						    if ( this_item_exists && value.inventory_item_id == this_item) {
								this_item_exists +=1;
						    }
						    if (showprice) {
						        content += ' $' + value.unit_price;
						    }
						    content += '</li>';
						});
						content += '</ul><input id="update-wishlist" type="submit" style="visibility: hidden;" value="Update" />';
						content += '<button type="button" class="submit-wishlist">Submit Wishlist</button>';
						content += 'Wishlist updated.';
						$("#wishlist-contents").html(content);
						if ( this_item_exists == 1) {
							$(".add-to-wishlist").removeAttr("disabled");
							$(".add-to-wishlist").html("Add to wishist");
							$("#wishlist-result").html('');
						}
					}
				}
			});
	
			// end same as view

		});

	});
   });
});

function updateQueryStringParameter(uri, key, value) {
  var re = new RegExp("([?|&])" + key + "=.*?(&|$)", "i");
  separator = uri.indexOf('?') !== -1 ? "&" : "?";
  if (uri.match(re)) {
    return uri.replace(re, '$1' + key + "=" + value + '$2');
  }
  else {
    return uri + separator + key + "=" + value;
  }
}
function isPositiveInteger(n) {
    return 0 === n % (!isNaN(parseFloat(n)) && 0 <= ~~n);
}
function setCookie(cname,cvalue,exdays)
{
var d = new Date();
d.setTime(d.getTime()+(exdays*24*60*60*1000));
var expires = "expires="+d.toGMTString();
document.cookie = cname + "=" + cvalue + "; " + expires + "; path=/";
}
function getCookie(cname)
{
var name = cname + "=";
var ca = document.cookie.split(';');
for(var i=0; i<ca.length; i++) 
  {
  var c = ca[i].trim();
  if (c.indexOf(name)==0) return c.substring(name.length,c.length);
}
return "";
}
