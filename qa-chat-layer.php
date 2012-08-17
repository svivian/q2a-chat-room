<?php
/*
	Question2Answer Chat Room plugin, v1.5
	License: http://www.gnu.org/licenses/gpl.html
*/

class qa_html_theme_layer extends qa_html_theme_base
{
	private $cssopt = 'chat_room_css';

	function head_custom()
	{
		parent::head_custom();

		if ( $this->template != 'plugin' || $this->request != 'chat' )
			return;

		$hidecss = qa_opt($this->cssopt) === '1';

		if ( !$hidecss )
		{
			$chat_css = '
				<style>
				#qa-chat-form { text-align: center; }
				.qa-chat-post { width: 600px; }
				#qa-chat-list, .qa-chat-item, #qa-chat-user-list, .qa-chat-user-item { display: block; list-style: none; margin: 0; padding: 0; }
				#qa-chat-list { width: 728px; margin: 1em auto; }
				.qa-chat-item { overflow: hidden; padding: 4px 0; border-top: 1px solid #eee; }
				.qa-chat-item:last-child { border-bottom: 1px solid #eee; }
				.qa-chat-item-meta { float: left; width: 140px; padding-right: 20px; font-size: 11px; color: #999; text-align: right; }
				.qa-chat-item-data { float: left; width: 568px; }
				/*#qa-chat-user-list { width: 204px; margin-top: 5px; }*/
				.qa-chat-user-item { padding: 2px 4px; }
				.qa-chat-user-item:hover { background: rgba(255,255,255,0.4); }
				.qa-chat-idle, .qa-chat-idle > a { color: #aaa; }

				.qa-chat-kick { float: right; cursor: pointer; width: 8px; height: 8px; border-radius: 16px; background: #999; margin-top: 5px; }
				.qa-chat-kick:hover { background: #f00; }

				.qa-chat-service { background: #fffacd; }
				/*.qa-chat-service .qa-chat-item-data { color: #a44; }*/
				</style>';

			$this->output_raw( $chat_css );
		}
	}

}
