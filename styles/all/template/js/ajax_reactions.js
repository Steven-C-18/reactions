/**
	* every thing you see is what you see
	* Reactions $extends the phpBB Software package.
	* @copyright (c) 2024, Steve, https://steven-clark.tech/
*/

var $reactions = {};

(($) => {

'use strict';

/**
	* Handler for AJAX errors/ * stolen
*/	
$.extend(phpbb, {
	jqXHR: (jqXHR, textStatus, errorThrown) => {
	    if (typeof console !== 'undefined' && console.log) {
	        console.log('AJAX error. status: ' + textStatus + ', message: ' + errorThrown);
	    }
	    phpbb.clearLoadingTimeout();
		var $dark = $('#darkenwrapper');
	    var responseText, errorText = false;
	    try {//ajax-
	        responseText = JSON.parse(jqXHR.responseText);
	        responseText = responseText.message;
	    } catch (e) {}
	    if (typeof responseText === 'string' && responseText.length) {
	        errorText = responseText;
	    } else if (typeof errorThrown === 'string' && errorThrown.length) {
	        errorText = errorThrown;
	    } else {
	        errorText = $dark.data('ajax-error-text-' + textStatus);
	        if (typeof errorText !== 'string' || !errorText.length) { errorText = $dark.data('ajax-error-text') }
	    }
	    phpbb.alert($dark.data('ajax-error-title'), errorText);
	}
});
//[]
const $time = 3000;
const $fadeTime = 500;
const $ms = 1000;
const $k = 1000;
const $m = 1000000;
var responseData = null, inc = (typeof tprData !== 'undefined') ? parseInt(tprData.perPage) : 0, start = 0;
$.extend($reactions, {
	click: {
		reactions: $('ul[id^=ltpr-]'),
		dropDown: $("div[id^=tpr-dropdown]"),
		smile: $('.button > .fa-smile-o'),
		remove: $('button[id^=delete-button]')
	},
	const: { body: $('#phpbb'), json: "json" },	
	count_type: (valueCount, postId) => {
		var badge = !tprData.badgeEnabled ? "" : "reaction-ear-color",
			count = !tprData.countEnabled ? '' : "<strong class='reaction-ear " + badge + "'>" + $reactions.round_counts(valueCount) + "</strong>";// change tags ''/""
		$("#reaction-types" + postId).append(count).fadeIn($fadeTime);// #id
	},
	change_counts: (res) => {
		$("#reaction-count" + res.POST_ID).text(parseInt(res.REACTIONS));
		// the broke link, use data- and then []		
		$('[data-show-user-reactions="' + res.POSTER_ID + '"]').show();
		$('[data-reactions-user="' + res.POSTER_ID + '"]').text(parseInt(res.USER_TOTAL));
		$('[data-pos-user="' + res.POSTER_ID + '"]').text(res.USER_REACTIONS);
		$('[data-neg-user="' + res.POSTER_ID + '"]').text(res.USER_REACTIONS_NEG);
		$('[data-neu-user="' + res.POSTER_ID + '"]').text(res.USER_REACTIONS_NEU);
	},
	imgSrc: (value, postId) => {
		var imgSrc = $("<img />").attr({
			id: 'rimage' + value.id + postId,
			src: tprData.url + value.src,
			height: tprData.height,
			width: tprData.width,
			class: "reaction_image",
			alt: tprData.alt
		});
		imgSrc.appendTo("#reaction-types" + postId);
	},
	round_counts: (count) => {
		switch (true) {
			case count < $k:
			  return count;
			break;
			case count < $m:
				return count = Math.sign(count) * ((Math.abs(count) / $k).toFixed(1)) + tprData.k;
			break;
			case $count >= $m:
				return count = Math.sign(count) * ((Math.abs(count) / $m).toFixed(1)) + tprData.m;
			break;
			//billion, never.
			default:
				return false;
			break;
		}
	},
	types_data: (jsonData, postId) => {
		$.each(JSON.parse(jsonData.TYPE_DATA), (index, json) => {
			$reactions.count_type(json.count, postId);
			$reactions.imgSrc(json, postId);
		 	if (jsonData.VIEW_URL) {
				var changeClass = jsonData.NEW_TYPE == json.id ? "user_alert" : "user_alert_new";
		 		var url = '<a href="' + jsonData.VIEW_URL + '" class="data-show-reactions ' + changeClass + '" title="' + tprData.urlTitle + '"></a>';
				$("#reaction-types" + postId + " #rimage" + json.id + postId).wrap(url);
			}
		});
	},
	add_reaction: (jsonData) => {
		var postId = jsonData.POST_ID;
		$("#reaction-types" + postId).fadeOut($fadeTime).empty();
		$reactions.types_data(jsonData, postId);
		if (jsonData.REACTION_DELETE) {
			$('#delete-button' + postId).show();
			$('#delete-reaction' + postId).attr('href', jsonData.REACTION_DELETE);
		}
	},
	delete_reaction: (jsonData) => {
		var postId = jsonData.POST_ID;
		if (typeof jsonData.TYPE_DATA === 'undefined') {
			$("#reaction-types" + postId).fadeOut($fadeTime).empty();
		} else {
			$("#reaction-types" + postId).fadeOut($fadeTime).empty();
			$reactions.types_data(jsonData, postId);
		}
		$('#delete-button' + postId).hide();
	},
	reactions_always: () => {
		setTimeout(() => {
			$reactions.click.smile.removeClass('fa-spin icon-red');
			$reactions.click.reactions.parent().css("visibility", "visible");//prop
			$reactions.click.dropDown.click();
		}, $time);
	},
	reactions_before: () => {
		$reactions.click.reactions.parent().css("visibility", "hidden");//prop
		$reactions.click.smile.addClass('fa-spin icon-red');
	},
	reacted_always: () => {
		$("#load-more-reacted i").removeClass('fa-spin fa-spinner icon-red');
	},
	reacted_before: () => {
		$("#load-more-reacted i").addClass('fa-spin fa-spinner icon-red');
	},
	build_reacted: (jsonData, load_more) => {
		$.each(JSON.parse(jsonData), (index, json) => {
			$('#load-reacted-user').append([
				$("<div />", {
					id: 'reacted-user-' + json.user_id
				}).append([
					$("<img />").attr({
						src: (json.avatar != false) ? tprData.avatarUrl + json.avatar : tprData.url + '1f47b.png',
						class: 'reaction-avatar',
						height: tprData.height,
						width: tprData.width,
						alt: ''
					}).css('margin-right', '5px'),
					$("<a />", {
						href: tprData.userUrl + json.user_id,
						text: json.name,
						class: 'username-coloured'
					}).css({'color': '#' + json.color, 'margin-right': '10px;'}),
					$("<span />", { text: ', ' + json.time}),
					$('<hr class="dashed" />')
				])
			]);

			if (json.img != false) {
				var reaction_image = $("<img />").attr({
					src: tprData.url + json.img,
					height: tprData.height,
					width: tprData.width,
					alt: json.title
				}).css('margin-right', '5px');
				if (json.has_id == false) {
					$('#reacted-user-' + json.user_id).prepend(reaction_image);
				} else {
					if (load_more == false) { $('#reaction-type-top').append(reaction_image); }
				}
			}
		});
	},
	get_reactions: (responseData, url, page, get_before, get_always) => {
		//url = inputUrl.split('?')[0];
		url.replace('&amp;', '&');
		try {
			$.ajax({
				url: url,
				method: 'GET',
				dataType: $reactions.const.json,
				beforeSend: get_before,
				data: 'page=' + page,
				success: responseData,
				error: phpbb.jqXHR,
				cache: false
			}).always(get_always);
			//.fail((jqXHR, textStatus, errorThrown) => { error })
		 } catch (e) {
			 //do summit
		 }
	    return false;
	}
});
$("[data-add-reaction]").bind("click", function(e) {
    e.preventDefault();
	var href_data = $(this).data('add-reaction');
	if (typeof href_data === 'undefined') {
		return false;
	}
   	return $reactions.get_reactions(responseData = (res) => {
		if (typeof res.success === 'undefined') {
			return false;
		}
		$reactions.add_reaction(res);
		$reactions.change_counts(res);
    }, $(this).attr('href'), 0, $reactions.reactions_before(), $reactions.reactions_always());
});
$("[data-delete-reaction]").bind("click", function(e) {
    e.preventDefault();
	var href_data = $(this).data('delete-reaction');
	if (typeof href_data === 'undefined') {
		return false;
	}
   	return $reactions.get_reactions(responseData = (res) => {
		if (typeof res.success === 'undefined') {
			return false;
		}
		$reactions.delete_reaction(res);
		$reactions.change_counts(res);
    }, $(this).attr('href'), 0, $reactions.reactions_before(), $reactions.reactions_always());
});
$('div[id^=post-reactions]').on('click', '.data-show-reactions', function(e) {
	e.preventDefault();
 	$('#show-reacted, .darkenwrapper').show();
 	return $reactions.get_reactions(responseData = (res) => {
 		if (typeof res.success === 'undefined') {
			return false;
		}
		$reactions.build_reacted(res.user_data, false);
  	 	$("#load-more-reacted").attr('href', res.url);
  	 	$('#load-more-count').html(res.count);
  	}, $(this).attr('href'), start, $reactions.reacted_before(), $reactions.reacted_always());
});
$("#load-more-reacted").bind("click", function(e) {
    e.preventDefault();
	if ($('div[id^=reacted-user-]').length < $("#load-more-count").html()) {
		return $reactions.get_reactions(responseData = (res) => {
		  	if (typeof res.success === 'undefined') {
				return false;
			}
			$reactions.build_reacted(res.user_data, true);
	  	}, $(this).attr('href'), start += inc, $reactions.reacted_before(), $reactions.reacted_always());
	} else {
	    $("#load-more-reacted").hide();
		$('#load-more-done').show();//add furcated
	}
});
$(".close-more-reacted").click((e) => {
	e.preventDefault();
	$('#show-reacted, .darkenwrapper, #load-more-done').hide();
	$("#load-more-reacted").attr('href', '').show();
	$('#load-reacted-user, #reaction-type-top').empty();
	start = 0;
});
$('#reactions_button_icon').on('keyup blur', function() {
	$('#button_icon').prop('class', 'icon ' + $(this).val());
});
if (typeof tprData !== 'undefined') {
	$(() => {
		if (tprData.viewtopic) {
		    setTimeout(() => { $('button[id^=delete-button], div[id^=tpr-dropdown], div[id^=post-reactions], span > a[id^=tpr-refresh]').hide(); }, tprData.sessionTime * $ms );
		}
		if (tprData.quickReply !== '') {
			var $qrForm = $('form#qr_postform');
			$qrForm.find('input[type=submit][name=post]').click(() => {
			var message = $qrForm.find('#message-box').val().trim().length;
				if (!message) {
					if (typeof phpbb.alert === "function") {
						phpbb.alert(tprData.alertTitle, tprData.alertMsg);
						phpbb.closeDarkenWrapper($time);
					} else {
						alert(tprData.alertTitle, tprData.alertMsg);
					}
					return false;
				} else {
					$qrForm.attr('action', (i, val) => { return val + '&' + tprData.quickReply; });
				}
			});
		}
	});
}
})(jQuery);
