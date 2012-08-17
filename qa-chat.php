<?php
/*
	Question2Answer Chat Room plugin, v1.5
	License: http://www.gnu.org/licenses/gpl.html
*/

// define( 'QA_CHAT_')

class qa_chat
{
	private $directory;
	private $urltoroot;
	private $user;

	// private $tbl_posts = 'chat_posts';
	// private $tbl_users = 'chat_users';
	// private $tbl_kicks = 'chat_kicks';
	private $optactive = 'chat_active';

	public function load_module($directory, $urltoroot)
	{
		$this->directory = $directory;
		$this->urltoroot = $urltoroot;
	}

	public function suggest_requests() // for display in admin interface
	{
		return array(
			array(
				'title' => 'Chat Room',
				'request' => 'chat',
				'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
			),
		);
	}

	public function match_request( $request )
	{
		return $request == 'chat';
	}

	function init_queries( $tableslc )
	{
		$tbl1 = qa_db_add_table_prefix('chat_posts');
		$tbl2 = qa_db_add_table_prefix('chat_users');

		if ( in_array($tbl1, $tableslc) && in_array($tbl2, $tableslc) )
		{
			qa_opt( $this->optactive, '1' );
			return null;
		}

		return array(
			'CREATE TABLE IF NOT EXISTS ^chat_posts (
			  `postid` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `userid` int(10) unsigned NOT NULL,
			  `posted` datetime NOT NULL,
			  `message` varchar(800) NOT NULL,
			  PRIMARY KEY (`postid`),
			  KEY `posted` (`posted`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8',

			'CREATE TABLE IF NOT EXISTS ^chat_users (
			  `userid` int(10) unsigned NOT NULL,
			  `lastposted` datetime NOT NULL,
			  `lastpolled` datetime NOT NULL,
			  `banneduntil` datetime DEFAULT NULL,
			  PRIMARY KEY (`userid`),
			  KEY `active` (`lastpolled`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8',

			'CREATE TABLE IF NOT EXISTS ^chat_kicks (
			  `userid` int(10) unsigned NOT NULL,
			  `kickedby` int(10) unsigned NOT NULL,
			  `when` datetime NOT NULL,
			  PRIMARY KEY (`userid`,`kickedby`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8',
		);

	}

	public function process_request( $request )
	{
		// set up user
		$this->user = array(
			'id' => qa_get_logged_in_userid(),
			'handle' => qa_get_logged_in_handle(),
			'flags' => qa_get_logged_in_flags(),
			'level' => qa_get_logged_in_level(),
		);

		// TODO: select from qa_chat_users
		$sql = 'SELECT (banneduntil-NOW() > 0) AS banned FROM ^chat_users WHERE userid=#';
		$result = qa_db_query_sub( $sql, $this->user['id'] );
		$row = qa_db_read_one_assoc($result, true);
		$this->user['banned'] = @$row['banned'];

		// create dates for database
		$now = time();
		$posted = gmdate( 'Y-m-d H:i:s', $now );
		$posted_utc = gmdate( 'Y-m-d\TH:i:s\Z', $now );

		// AJAX: someone posted a message
		$message = qa_post_text('ajax_add_message');
		if ( $message !== null )
		{
			if ( !$this->user_perms_post() )
			{
				echo "QA_AJAX_RESPONSE\n0\nYou are not allowed to post currently, sorry.";
				return;
			}

			$data = array(
				'userid' => $this->user['id'],
				'username' => $this->user['handle'],
				'posted' => $posted,
				'posted_utc' => $posted_utc,
				'message' => $message
			);

			// save to database
			$data['postid'] = $this->post_message( $data );
			$this->update_activity(true);

			$data['username'] = qa_html( $data['username'] );
			$data['message'] = $this->format_message( $data['message'] );

			header('Content-Type: text/plain; charset=utf-8');
			echo "QA_AJAX_RESPONSE\n" . $this->user['id'] . "\n" . json_encode($data);
			return;
		}

		// AJAX: polling check; $lastid=0 on initial page load
		$lastid = qa_post_text('ajax_get_messages');
		if ( $lastid !== null )
		{
			if ( !$this->user_perms_view() )
			{
				echo "QA_AJAX_RESPONSE\n0\nYou don't appear to be logged in. Please reload the page.";
				return;
			}

			$this->update_activity( $lastid==0 );
			$messages = $this->get_messages( $lastid );
			$users = $this->users_online();

			header('Content-Type: text/plain; charset=utf-8');
			echo "QA_AJAX_RESPONSE\n" . $this->user['id'] . "\n" . json_encode($messages) . "\n" . json_encode($users);
			return;
		}

		// AJAX: request to kick user
		$kickuserid = qa_post_text('ajax_kick_user');
		if ( $kickuserid !== null )
		{
			// currently only mods/admins can kick users
			if ( $this->user['level'] < QA_USER_LEVEL_MODERATOR )
			{
				echo "QA_AJAX_RESPONSE\n0\nYou are not allowed to do that currently, sorry.";
				return;
			}

			$sql = 'UPDATE ^chat_users SET banneduntil=DATE_ADD(NOW(), INTERVAL 10 MINUTE) WHERE userid=#';
			qa_db_query_sub( $sql, $kickuserid );

			$message = array(
				'userid' => '0',
				'posted' => $posted,
				'message' => 'User #' . $kickuserid . ' has been kicked for 10 minutes',
			);
			$this->post_message( $message );

			header('Content-Type: text/plain; charset=utf-8');
			echo "QA_AJAX_RESPONSE\n" . $this->user['id'] . "\nGave em a right kickin!";
			return;
		}



		// regular page request
		$qa_content=qa_content_prepare();
		$qa_content['title']='Chat Room';

		if ( $this->user_perms_post() )
		{
			$qa_content['custom_form'] =
				'<form method="post" id="qa-chat-form">' .
				'	<input id="message" class="qa-chat-post" type="text" name="ajax_add_message" autocomplete="off" maxlength="800">' .
				'	<input type="submit" value="Post">' .
				'</form>' .
				'<ul id="qa-chat-list"></ul>' .
				'<script type="text/javascript" src="'.$this->urltoroot.'qa-chat.js?=v1.5"></script>';
		}
		else if ( $this->user_perms_view() )
		{
			$qa_content['error'] = qa_insert_login_links( 'Sorry, you are currently unable to post in chat. If you are new, you must confirm your email address.', $request );
		}
		else
		{
			$qa_content['error'] = qa_insert_login_links( 'Please ^1log in^2 or ^3register^4 to use the chat room.', $request );
		}

		return $qa_content;
	}



	// fetch all messages after given id
	private function get_messages( $lastid )
	{
		$sql =
			'SELECT p.postid, p.userid, u.handle AS username, p.message AS message, ' .
			'p.posted, DATE_FORMAT(p.posted, "%Y-%m-%dT%H:%i:%sZ") AS posted_utc ' .
			'FROM ^chat_posts p LEFT JOIN ^users u ON u.userid=p.userid ' .
			'WHERE p.postid > # ORDER BY p.posted DESC LIMIT 80';
		$result = qa_db_query_sub( $sql, $lastid );

		$messages = qa_db_read_all_assoc($result);

		foreach ( $messages as &$m )
		{
			// if ( $m['userid'] == 0 )
				// $m['username'] = 'HITMONCHAN';
			$m['message'] = $this->format_message( $m['message'] );
			$m['username'] = qa_html( $m['username'] );
		}

		return $messages;
	}

	// save message to database
	private function post_message( $data )
	{
		$sql = 'INSERT INTO ^chat_posts (postid, userid, posted, message) VALUES (0, #, $, $)';
		qa_db_query_sub( $sql, $data['userid'], $data['posted'], $data['message'] );
		return qa_db_last_insert_id();
	}

	// update user activity
	private function update_activity( $posted=false )
	{
		if ( $posted )
			$sql = 'INSERT INTO ^chat_users (userid, lastposted, lastpolled) VALUES (#, NOW(), NOW()) ON DUPLICATE KEY UPDATE lastposted=NOW(), lastpolled=NOW()';
		else
			$sql = 'INSERT INTO ^chat_users (userid, lastpolled) VALUES (#, NOW()) ON DUPLICATE KEY UPDATE lastpolled=NOW()';

		qa_db_query_sub( $sql, $this->user['id'] );
	}

	// get recently active users
	private function users_online()
	{
		$sql =
			'SELECT u.userid, u.handle AS username, u.level, (c.lastposted < DATE_SUB(NOW(), INTERVAL 8 MINUTE)) AS idle, (c.banneduntil > NOW()) AS banned
			 FROM ^users u, ^chat_users c
			 WHERE u.userid=c.userid AND c.lastpolled > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
			 ORDER BY u.handle';
		$result = qa_db_query_sub( $sql );

		$users = qa_db_read_all_assoc($result);

		foreach ( $users as &$u )
		{
			$u['username'] = qa_html( $u['username'] );
			$u['mod'] = $u['level'] >= QA_USER_LEVEL_MODERATOR ? '1' : '0';
		}

		return $users;
	}

	// check user permissions for viewing page
	private function user_perms_view()
	{
		return $this->user['id'] > 0;
	}

	// check user permissions for posting messages
	private function user_perms_post()
	{
		return qa_user_permit_error() === false && !$this->user['banned'] && (
			($this->user['level'] >= QA_USER_LEVEL_EXPERT) ||
			($this->user['flags'] & QA_USER_FLAGS_EMAIL_CONFIRMED)
		);
	}

	// format message
	private function format_message( $msg )
	{
		$msg = qa_html( $msg );
		// TODO: censor swearing based on admin options

		return qa_html_convert_urls($msg);
	}

}
