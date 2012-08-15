/* Timeago 0.9 */
(function(d){function l(){var a;a=this;a=d(a);if(!a.data("timeago")){a.data("timeago",{datetime:f.datetime(a)});var b=d.trim(a.text());b.length>0&&a.attr("title",b)}a=a.data("timeago");isNaN(a.datetime)||d(this).text(g(a.datetime));return this}function g(a){return f.inWords((new Date).getTime()-a.getTime())}d.timeago=function(a){return a instanceof Date?g(a):typeof a=="string"?g(d.timeago.parse(a)):g(d.timeago.datetime(a))};var f=d.timeago;d.extend(d.timeago,{settings:{refreshMillis:6E4,allowFuture:false,
strings:{prefixAgo:null,prefixFromNow:null,suffixAgo:"ago",suffixFromNow:"from now",seconds:"less than a minute",minute:"about a minute",minutes:"%d minutes",hour:"about an hour",hours:"about %d hours",day:"a day",days:"%d days",month:"about a month",months:"%d months",year:"about a year",years:"%d years",numbers:[]}},inWords:function(a){function b(j,k){return(d.isFunction(j)?j(k):j).replace(/%d/i,c.numbers&&c.numbers[k]||k)}var c=this.settings.strings,m=c.prefixAgo,n=c.suffixAgo;if(this.settings.allowFuture){if(a<
0){m=c.prefixFromNow;n=c.suffixFromNow}a=Math.abs(a)}a=a/1E3;var h=a/60,i=h/60,e=i/24,o=e/365;a=a<45&&b(c.seconds,Math.round(a))||a<90&&b(c.minute,1)||h<45&&b(c.minutes,Math.round(h))||h<90&&b(c.hour,1)||i<24&&b(c.hours,Math.round(i))||i<48&&b(c.day,1)||e<30&&b(c.days,Math.floor(e))||e<60&&b(c.month,1)||e<365&&b(c.months,Math.floor(e/30))||o<2&&b(c.year,1)||b(c.years,Math.floor(o));return d.trim([m,a,n].join(" "))},parse:function(a){a=d.trim(a);a=a.replace(/\.\d\d\d/,"");a=a.replace(/-/,"/").replace(/-/,
"/");a=a.replace(/T/," ").replace(/Z/," UTC");a=a.replace(/([\+-]\d\d)\:?(\d\d)/," $1$2");return new Date(a)},datetime:function(a){a=d(a).get(0).tagName.toLowerCase()=="time"?d(a).attr("datetime"):d(a).attr("title");return f.parse(a)}});d.fn.timeago=function(){var a=this;a.each(l);var b=f.settings;b.refreshMillis>0&&setInterval(function(){a.each(l)},b.refreshMillis);return a};document.createElement("abbr");document.createElement("time")})(jQuery);



var curr_user_idle = false;
var curr_userid = 0;

var Tmpl =
{
	message: function( post )
	{
		var html = '';
		html += '<li id="qa-chat-id-' + post.postid + '" class="qa-chat-item" style="display:none">';
		html += '  <div class="qa-chat-item-meta">';
		html += '    <span class="qa-chat-item-who">';
		html += '      <a class="qa-user-link" href="./user/' + encodeURIComponent(post.username) + '">' + post.username + '</a>';
		html += '    </span><br>';
		html += '    <span class="qa-chat-item-when" data-utc="' + post.posted_utc + '" title="' + post.posted_utc + '">' + post.posted + '</span>';
		html += '  </div>';
		html += '  <div class="qa-chat-item-data">' + post.message + '</div>';
		html += '</li>';

		return html;
	},

	user_list_wrapper: function()
	{
		var html = '<div class="qa-sidebar"><h3>Users online</h3><ul id="qa-chat-user-list"></ul></div>';
		return html;
	},

	user_list: function( users )
	{
		var html = '';

		for ( var i in users )
		{
			var linkhtml = '<a href="./user/' + encodeURIComponent(users[i].username) + '">' + users[i].username + '</a>';
			if ( users[i].idle == 1 )
				html += '<li class="qa-chat-user-item qa-chat-idle">' + linkhtml + ' (idle)</li>';
			else
				html += '<li class="qa-chat-user-item">' + linkhtml + '</li>';

			if ( users[i].userid == curr_userid )
				curr_user_idle = (users[i].idle == 1);
		}

		return html;
	}
}


/* Q2A chat */

$(function(){
	var lastid = 0;
	var $user_list = null;

	// add a message to the list
	function qa_chat_add_message( post )
	{
		var $ex_post = $( '#qa-chat-id-'+post.postid );
		if ( $ex_post.length == 0 )
		{
			var $msg = $( Tmpl.message( post ) );
			$('.qa-chat-item-when', $msg).timeago();
			$('#qa-chat-list').prepend($msg);
			$msg.slideDown('fast');
		}

		if ( $('.qa-chat-item').length > 80 )
			$('.qa-chat-item:nth-child(n+81)').remove();
	}

	function qa_chat_update_users( users )
	{
		if ( !$user_list ) {
			$('.qa-sidepanel').prepend( Tmpl.user_list_wrapper() );
			$user_list = $('#qa-chat-user-list');
		}

		$user_list.html( Tmpl.user_list(users) );
	}

// 	// fetch all new messages
	function qa_chat_fetch_messages()
	{
		$.ajax({
			type: 'post',
			data: { ajax_get_messages: lastid },
			success: function(response) {
// 				console.log(response);

				var lines = response.split("\n");
				if ( lines[0] != 'QA_AJAX_RESPONSE' || lines[1] == 0 )
					return false;

				curr_userid = lines[1];

				var posts = $.parseJSON( lines[2] ).reverse();
				for ( var i in posts ) {
					qa_chat_add_message( posts[i] );
					lastid = posts[i].postid;
				}

				// update active users
				if ( lines[3] ) {
					var users = $.parseJSON( lines[3] );
					qa_chat_update_users( users );
				}
			}
		});

		// if user is inactive, increase timeout
		if ( curr_user_idle )
			setTimeout( function() { qa_chat_fetch_messages(); }, 30000 );
		else
			setTimeout( function() { qa_chat_fetch_messages(); }, 8000 );
	}

	// adding a message to the chat
	$('#qa-chat-form').submit( function() {
		var message = $('#message').val();
		if ( message.length == 0 )
			return false;

		$('#qa-chat-form input').attr({ disabled: 'disabled' });

		$.ajax({
			type: 'post',
			data: { ajax_add_message: message, ajax_add_lastid: '0' },
			success: function(response) {
// 				console.log(response);

				$('#qa-chat-form input').removeAttr('disabled');
				$('#message').val('').focus();

				var lines = response.split("\n");
				if ( lines[0] != 'QA_AJAX_RESPONSE' ) {
					alert("There was a server error, please try again in a few minutes");
					return false;
				}
				if ( lines[1] == 0 ) {
					alert("Error: "+lines[2]);
					return false;
				}

				var post = $.parseJSON( lines[2] );
				qa_chat_add_message( post );
			}
		});

		return false;
	} );

	// page setup
	qa_chat_fetch_messages();

});
