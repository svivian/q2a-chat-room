<?php
/*
	Question2Answer Chat Room plugin
	License: http://www.gnu.org/licenses/gpl.html
*/

class qa_html_theme_layer extends qa_html_theme_base
{
	private $optcss = 'chat_hide_css';

	function head_custom()
	{
		parent::head_custom();

		if (!in_array($this->template, ['plugin', 'custom']) || $this->request != 'chat') {
			return;
		}

		$hidecss = qa_opt($this->optcss) === '1';

		if (!$hidecss) {
			$chatCSS = file_get_contents(QA_HTML_THEME_LAYER_DIRECTORY.'chat.css');
			$this->output_raw("<style>{$chatCSS}</style>\n");
		}
	}

}
