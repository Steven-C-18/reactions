/**
	* every thing you see is what you see
	* Reactions $extends the phpBB Software package.
	* @copyright (c) 2024, Steve, https://steven-clark.tech/
*/

var $reactions = {};
(($) => { 'use strict';

/**
	* Handler for AJAX errors/ * stolen, updated slightly...
	* used for console errors only! no UI interaction
*/
$.extend(phpbb, {
	jqXHR: (jqXHR, textStatus, errorThrown) => {
	    var responseText, errorText = false;
	    try {
	        responseText = JSON.parse(jqXHR.responseText);
	        responseText = responseText.message;
	    } catch (e) {}
	    if (typeof responseText === 'string' && responseText.length > 0) {
	        errorText = responseText;
	    } else if (typeof errorThrown === 'string' && errorThrown.length > 0) {
	        errorText = errorThrown;
	    }
	    if (typeof console !== 'undefined' && console.log) {
	        console.log('Reaction AJAX error. status: ' + textStatus + ', message: ' + errorText);
		}
	}
});
const $time = 3000;
const $fadeTime = 500;
const $ms = 1000;
const $k = 1000;
const $m = 1000000;
$.extend($reactions, {
	click: { dropDown: $("div[id^=reaction-dropdown-]"), react: $('[data-show-reaction-types="true"]'), remove: $('button[id^=delete-reaction-button]'), types: $('[data-show-reaction-types="ul"]') },
	count: (valueCount, postId) => {
		var badge = rData.badgeEnabled === false ? '' : "reaction-ear-color",
			count = !rData.countEnabled ? '' : "<strong class='reaction-ear " + badge + "'>" + $reactions.round(valueCount) + "</strong>";
		$("#reaction-types" + postId).append(count).fadeIn($fadeTime);
	},
	changeCountViewtopic: (res) => {
		$("#reaction-count" + res.POST_ID).text(parseInt(res.REACTIONS));
		$('[data-show-user-reactions="' + res.POSTER_ID + '"]').show();
		$('[data-reactions-user="' + res.POSTER_ID + '"]').text(parseInt(res.USER_TOTAL));
	},
	imgSrc: (value, postId) => {
		var imgSrc = $("<img />").attr({
			id: 'rimage' + value.id + postId,
			src: rData.url + value.src,
			height: rData.height,
			width: rData.width,
			class: "reaction-image",
			alt: rData.alt
		});
		imgSrc.appendTo("#reaction-types" + postId);
	},
	workITout: (count, amount, amountLang) => {
		//Did i ever tell you how the constipated maths magician worked it out?
		return count = Math.sign(count) * ((Math.abs(count) / amount).toFixed(1)) + amountLang;//with a pencil
	},
	round: (count) => {
		switch (true) {
			case count < $k:
			  return count;
			break;
			case count < $m:
				return count = $reactions.workITout(count, $k, rData.k);
			break;
			case $count >= $m:
				return count = $reactions.workITout(count, $m, rData.m);
			break;
			default://billion, never.
				return false;
			break;
		}
	},
	types: (jsonData, postId) => {
		$.each(JSON.parse(jsonData.TYPE_DATA), (index, json) => {
			$reactions.count(json.count, postId);
			$reactions.imgSrc(json, postId);
		 	if (jsonData.VIEW_URL) {
				var changeClass = jsonData.NEW_TYPE == json.id ? "reaction-user-alert" : "reaction-user-alert-new";
		 		var url = '<a href="' + jsonData.VIEW_URL + '" data-reaction-img-src="' +  rData.url + json.src + '" class="data-show-reactions ' + changeClass + '" title="' + rData.urlTitle + '"></a>';
				$("#reaction-types" + postId + " #rimage" + json.id + postId).wrap(url);
			}
		});
	},
	postReaction: (jsonData) => {
		var postId = jsonData.POST_ID;
		$("#reaction-types" + postId).fadeOut($fadeTime).empty();
		$reactions.types(jsonData, postId);
		if (jsonData.REACTION_DELETE) {
			$('#delete-reaction-button' + postId).show();
			$('#delete-reaction' + postId).attr('href', jsonData.REACTION_DELETE);
		}
	},
	del: (jsonData) => {
		var postId = jsonData.POST_ID;
		if (typeof jsonData.TYPE_DATA === 'undefined') {
			$("#reaction-types" + postId).fadeOut($fadeTime).empty();
		} else {
			$("#reaction-types" + postId).fadeOut($fadeTime).empty();
			$reactions.types(jsonData, postId);
		}
		$('#delete-reaction-button' + postId).hide();
	},
	before: () => {
		$reactions.click.types.parent().css("visibility", "hidden");
		$reactions.click.react.addClass('fa-spin');
	},	
	always: () => {
		setTimeout(() => {
			$reactions.click.react.removeClass('fa-spin');
			$reactions.click.types.parent().css("visibility", "visible");
			$reactions.click.dropDown.click();
		}, $time);
	},
	build: function(jsonData, load_more, auth, url) {
		if (url !== '' && !load_more) {
			$('#reaction-type-top').append($("<img />").attr({
				src: url,
				height: rData.height,
				width: rData.width,
				alt: ''
			}));
		}
		$.each(JSON.parse(jsonData), (index, json) => {
			$('#load-reacted-user').append([
				$("<div />", {
					id: 'reacted-user-' + json.user_id
				}).append([			
					$("<img />").attr({
						src: (json.avatar != false) ? rData.avatarUrl + json.avatar : rData.noAvatar,
						class: 'reaction-avatar',
						height: rData.height,
						width: rData.width,
						alt: ''
					}).css('margin-right', '5px'),
					$("<a />", {
						href: rData.userUrl + json.user_id,
						text: json.name,
						class: 'username-coloured'
					}).css({'color': '#' + json.color, 'margin-right': '10px;'}),
					$("<span />", { text: ', ' + json.time}),				
					$('<hr class="dashed" />')
				])
			]);
			if (json.img != false) {
				var reaction_images = $("<img />").attr({
					src: rData.url + json.img,
					height: rData.height,
					width: rData.width,
					alt: json.title
				}).css('margin-right', '5px');
				if (json.has_id == false) {
					$('#reacted-user-' + json.user_id).prepend(reaction_images);
				}
			}			
			if (auth) {
				$('#reacted-user-' + json.user_id).prepend($("<a />", {
					href: rData.delUrl + json.post_id + '/' + json.user_id + rData.hash,
					html: '<i class="icon fa-times-circle icon-red fa-fw" aria-hidden="true" style="margin-right: 5px;font-size: 18px !important"></i>',
				}).attr('data-delete-user-reaction', json.user_id));
			}	
		});
	},
	get: (data, url, page, before, always) => {
		try {
			$.ajax({
				url: url.replace('&amp;', '&'),
				method: 'GET',
				dataType: "json",
				beforeSend: before,
				data: 'page=' + page,
				success: data,
				error: phpbb.jqXHR,
				cache: false
			}).always(always);
		} catch (e) { return false }
	}
});
var data = null;
$("[data-add-reaction]").bind("click", function(e) {
    e.preventDefault();
	if (typeof $(this).data('add-reaction') === 'undefined') { return false }
   	return $reactions.get(data = (res) => {
		if (typeof res.success === 'undefined') { return false }
		$reactions.postReaction(res);
		$reactions.changeCountViewtopic(res);
    }, $(this).attr('href'), 0, $reactions.before(), $reactions.always());
});
$("[data-delete-reaction]").bind("click", function(e) {
    e.preventDefault();
	if (typeof $(this).data('delete-reaction') === 'undefined') { return false }
   	return $reactions.get(data = (res) => {
		if (typeof res.success === 'undefined') { return false }
		$reactions.del(res);
		$reactions.changeCountViewtopic(res);
    }, $(this).attr('href'), 0, $reactions.before(), $reactions.always());
});
var inc = (typeof rData !== 'undefined') ? parseInt(rData.perPage) : 10, start = 0;
$('div[id^=post-reactions]').on('click', '.data-show-reactions', function(e) {
	e.preventDefault();
 	$('#show-reacted, .darkenwrapper').show();
 	return $reactions.get(data = (res) => {
 		if (typeof res.success === 'undefined') { return false }
		$reactions.build(res.user_data, false, res.auth, $(this).attr("data-reaction-img-src"));
  	 	$("#load-more-reacted").attr('href', res.url);
  	 	$('#load-more-count').html(res.count);
  	}, $(this).attr('href'), start, '', '');
});
$("#load-more-reacted").bind("click", function(e) {
    e.preventDefault();
	if ($('div[id^=reacted-user-]').length < $("#load-more-count").html()) {
		return $reactions.get(data = (res) => {
		  	if (typeof res.success === 'undefined') { return false }
			$reactions.build(res.user_data, true, res.auth, $(this).attr("data-reaction-img-src"));
	  	}, $(this).attr('href'), start += inc, '', '');
	} else {
	    $("#load-more-reacted").hide();
		$('#load-more-done').show();
	}
});
$('[data-reaction-type-top-hide="true"]').click((e) => {
	e.preventDefault();
	$('#reaction-type-top').empty();
});
$(".close-more-reacted, #darkenwrapper").click((e) => {
	e.preventDefault();
	$('#show-reacted, #load-more-done, .darkenwrapper').hide();
	$("#load-more-reacted").attr('href', '').show();
	$('#load-reacted-user, #reaction-type-top').empty();
	start = 0;
});
$('#reactions_button_icon').on('keyup blur', function() {
	$('#button_icon, #button_icon_top').prop('class', 'icon ' + $(this).val());
});
if (typeof rData !== 'undefined') {
	$(() => {
		if (rData.viewtopic) { setTimeout(() => { $('div[id^=reaction-dropdown-], #show-reacted, #load-more-done, .darkenwrapper').hide() }, rData.sessionTime * $ms ) }
		if (rData.quickReply !== '') {
			$('form#qr_postform').find('input[type=submit][name=post]').click(() => {
				$('form#qr_postform').attr('action', (i, val) => { return val + '&' + rData.quickReply; });
			});
		}
	});
}
})(jQuery);
