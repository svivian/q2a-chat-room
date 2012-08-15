<?php
/*
	Chat Room plugin for Question2Anaswer
	Copyright (c) 2010 Scott Vivian

	Question2Answer 1.3 (c) 2010, Gideon Greenspan
	http://www.question2answer.org/

	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	http://www.gnu.org/licenses/gpl-2.0.html
*/

/*
	Plugin Name: Chat Room
	Plugin URI: https://github.com/svivian/q2a-chat-room
	Plugin Description: A simple chat room functionality for Q2A
	Plugin Version: 1.5
	Plugin Date: 2012-08-15
	Plugin Author: Scott Vivian
	Plugin Author URI: http://pokemondb.net/
	Plugin License: GPLv2
	Plugin Minimum Question2Answer Version: 1.3
*/

if ( !defined('QA_VERSION') )
{
	header('Location: ../../');
	exit;
}


qa_register_plugin_module('page', 'qa-chat.php', 'qa_chat', 'Chat Room');
qa_register_plugin_layer('qa-chat-layer.php', 'Chat Room layer');
