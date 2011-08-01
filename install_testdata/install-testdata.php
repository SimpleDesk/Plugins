<?php
##############################################################################
#                 Simple Desk Project - www.simpledesk.net                   #
##############################################################################
#               An advanced help desk modifcation built on SMF               #
##############################################################################
#                                                                            #
#         * Copyright 2010 - SimpleDesk.net                                  #
#                                                                            #
#   This file and its contents are subject to the license                    #
#   included with this distribution, license.txt, which                      #
#   states that this software is New BSD Licensed.                           #
#   Any questions, please contact SimpleDesk.net                             #
#                                                                            #
##############################################################################
# SimpleDesk Version: 2.0 Anatidae                                           #
# File Info: install-testdata.php / 2.0 Anatidae                             #
##############################################################################
// Version: 2.0 Anatidae; SimpleDesk installation test-data

/**
 *	This script allows for large volumes of ticket data to be generated quickly for testing SimpleDesk.
 *
 *	This file is not part of standard distributions of SimpleDesk; it is maintained internally by the dev team for use
 *	with testing features, and is not generally available.
 *
 *	It is meant to be executed through adding to the root directory of the forum, where SSI.php is, and simple call by URL.
 *
 *	@package installer
 *	@since 1.0
 */

/**
 *	Before attempting to execute, this file attempts to load SSI.php to enable access to the database functions.
*/

if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
	require_once(dirname(__FILE__) . '/SSI.php');
elseif (!defined('SMF')) // If we are outside SMF and can't find SSI.php, then throw an error
	die('<b>Error:</b> Cannot install - please verify you put this file in the same place as SMF\'s SSI.php.');

if (!defined('TICKET_STATUS_NEW')) // SD isn't loaded and active
	die('<b>SimpleDesk needs to be installed and active before running this script.</b>');
elseif (!in_array('install_testdata', $context['shd_plugins']))
	die('<b>This plugin needs to be activated in the admin panel.</b>');
elseif (!$user_info['is_admin'])
	die('This can only be accessed by an administrator account.');

$db_show_debug = false;
shd_load_language('SDPluginInstallTestdata');

/**
 *	This also requires a subsidiary file to be able to use the post-creation functions.
 *
 *	@see shd_create_ticket_post()
 *	@see shd_members_allowed_to()
*/
require_once($sourcedir . '/sd_source/Subs-SimpleDeskPost.php');

$lorem = new LoremIpsumGenerator;

if (SMF != 'SSI')
	fatal_error('This script can only be run via direct link, it cannot be embedded into the forum or helpdesk itself.', false);

$context['shd_delete_rules'] = array('actionlog', 'relationships', 'roles');
$context['page_title_html_safe'] = $txt['shdp_install_testdata_title'];
template_header();

// Get departments
$depts = array();
$query = $smcFunc['db_query']('', '
	SELECT id_dept, dept_name
	FROM {db_prefix}helpdesk_depts
	ORDER BY dept_order');
while ($row = $smcFunc['db_fetch_assoc']($query))
	$depts[$row['id_dept']] = $row['dept_name'];
$smcFunc['db_free_result']($query);

$do_form = empty($_REQUEST['go']);

if (!$do_form)
{
	$errors = array();

	if ($_REQUEST['go'] == 'yeah')
	{
		// Check the list for normal test-data
		$num_tickets = !empty($_POST['tickets']) ? (int) $_POST['tickets'] : 0;
		if ($num_tickets == 0 || $num_tickets > 100000)
			$errors[] = $txt['shdp_install_testdata_invalidtickets'];

		$pc_closed = !empty($_POST['closed_chance']) ? (int) $_POST['closed_chance'] : 0;
		if ($pc_closed > 100)
			$errors[] = $txt['shdp_install_testdata_invalidclosed'];

		$pc_assigned = !empty($_POST['assigned_chance']) ? (int) $_POST['assigned_chance'] : 0;
		if ($pc_assigned > 100)
			$errors[] = $txt['shdp_install_testdata_invalidassigned'];

		$staff_member = !empty($_POST['staff_member']) ? (int) $_POST['staff_member'] : 0;
		if ($staff_member == 0)
			$errors[] = $txt['shdp_install_testdata_invalidstaff'];

		$user_member = !empty($_POST['user_member']) ? (int) $_POST['user_member'] : 0;

		$dest_dept = !empty($_POST['dept']) ? (int) $_POST['dept'] : 0;
		if (!isset($depts[$dest_dept]))
			$errors[] = $txt['shd_install_testdata_invalid_dept'];

	}
	elseif ($_REQUEST['go'] == 'yeah-for-delete')
	{
		checkSession();
		validateSession();
		$errors_clearance = array();
		$selected = 0;
		foreach ($context['shd_delete_rules'] as $test)
			if (!empty($_POST['purge_' . $test]))
				$selected++;

		if ($selected == 0)
			$errors_clearance[] = $txt['shdp_install_testdata_nothingselected'];
	}
}

$staff = shd_members_allowed_to('shd_staff', 0);
$names = array(
	0 => $txt['shdp_install_testdata_guestuser'],
);

$query = $smcFunc['db_query']('', '
	SELECT id_member, real_name
	FROM {db_prefix}members
	ORDER BY real_name
', array());

while ($row = $smcFunc['db_fetch_assoc']($query))
	$names[$row['id_member']] = $row['real_name'];

$nonstaff = array_diff(array_keys($names), $staff);

if (!empty($errors) || !empty($errors_clearance))
	$do_form = true;

if ($do_form)
{
	echo '
				<div class="tborder">
					<div class="cat_bar">
						<h3 class="catbg">
							<img src="', $settings['default_images_url'], '/simpledesk/ticket.png" alt="x" /> ', $txt['shdp_install_testdata_title'], '
						</h3>
					</div>
					<div class="windowbg">
						<span class="topslice"><span></span></span>
						<div class="content">';

	if (!empty($errors))
	{
		// crap, that means we actually did the form submission but it was borked somehow :(
		echo '
						<div class="errorbox" id="errors"><strong>', $txt['shdp_install_testdata_errors'], ':</strong>
						<ul class="error">';
		foreach ($errors as $msg)
			echo '
							<li>', $msg, '</li>';

		echo '
						</ul>
						</div>';
	}

	echo '
						<form id="creator" name="creator" action="', $boardurl, '/install-testdata.php" method="post">
							<input type="hidden" name="go" value="yeah" />
							<dl>
								<dt><strong>', $txt['shdp_install_testdata_numtickets'], ':</strong></dt>
								<dd><input type="text" size="5" value="100" name="tickets" class="input_text" /></dd>
								<dt><strong>', $txt['shdp_install_testdata_pcresolved'], ':</strong></dt>
								<dd><input type="text" size="5" value="30" name="closed_chance" class="input_text" /></dd>
								<dt><strong>', $txt['shdp_install_testdata_pcassigned'], ':</strong></dt>
								<dd><input type="text" size="5" value="10" name="assigned_chance" class="input_text" /></dd>									
								<dt><strong>', $txt['shdp_install_testdata_staff'], ':</strong></dt>
								<dd>
									<select name="staff_member">';
	foreach ($staff as $member)
		echo '
										<option value="', $member, '">', $smcFunc['htmlspecialchars']($names[$member]), '</option>';

		echo '
									</select>
								</dd>
								<dt><strong>', $txt['shdp_install_testdata_nonstaff'], ':</strong></dt>
								<dd>
									<select name="user_member">';
	foreach ($nonstaff as $member)
		echo '
										<option value="', $member, '">', $smcFunc['htmlspecialchars']($names[$member]), '</option>';

	echo '
									</select>
								</dd>
								<dt><strong>', $txt['shdp_install_testdata_dept'], ':</strong></dt>
								<dd>
									<select name="dept">';
	foreach ($depts as $id => $dept)
		echo '
										<option value="', $id, '">', $dept, '</option>';

	echo '
									</select>
								</dd>
							</dl>
							<input type="submit" value="', $txt['shdp_install_testdata_create'], '" class="button_submit" />
						</form>
						</div>
						<span class="botslice"><span></span></span>
					</div>
				</div>';

	// Now we do the form for content delete.
	echo '
				<br />
				<div class="tborder">
					<div class="cat_bar">
						<h3 class="catbg">
							<img src="', $settings['default_images_url'], '/simpledesk/delete.png" alt="x" /> ', $txt['shdp_install_testdata_del_title'], '
						</h3>
					</div>
					<div class="windowbg">
						<span class="topslice"><span></span></span>
						<div class="content">';

	if (!empty($errors_clearance))
	{
		// crap, that means we actually did the form submission but it was borked somehow :(
		echo '
							<div class="errorbox" id="errors"><strong>', $txt['shdp_install_testdata_errors'], ':</strong>
							<ul class="error">';
		foreach ($errors_clearance as $msg)
			echo '
								<li>', $msg, '</li>';

		echo '
							</ul>
							</div>';
	}

	echo '
							<form id="creator" name="creator" action="', $boardurl, '/install-testdata.php" method="post">
								<dl>';
	foreach ($context['shd_delete_rules'] as $delete_opt)
		echo '
									<dt><strong>', $txt['shdp_install_testdata_purge_' . $delete_opt], ':</strong></dt>
									<dd><input type="checkbox" class="input_check" name="purge_', $delete_opt, '" /></dd>';
	
	echo '
								</dl>
								<input type="submit" value="', $txt['shdp_install_testdata_del_title'], '" class="button_submit" onclick="return confirm(' . JavaScriptEscape($txt['shdp_install_testdata_clear_sure']). ');" />
								<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
								<input type="hidden" name="go" value="yeah-for-delete" />
							</form>
						</div>
						<span class="botslice"><span></span></span>
					</div>
				</div>';
}
elseif (!empty($_REQUEST['go']) && $_REQUEST['go'] == 'yeah')
{
	echo '
				<div class="tborder">
					<div class="cat_bar">
						<h3 class="catbg">
							<img src="', $settings['default_images_url'], '/simpledesk/ticket.png" alt="x" /> ', $txt['shdp_install_testdata_title'], '
						</h3>
					</div>
					<div class="windowbg">
						<span class="topslice"><span></span></span>
						<div class="content">';

	// Right, let's be naughty.
	@set_time_limit(0);
	ob_end_flush();
	ob_end_flush();

	for ($i = 1; $i <= $num_tickets; $i++)
	{
		// Some fun facts and figures of the ticket
		$assigned = $pc_assigned > mt_rand(1,100) ? $staff_member : 0;
		$closed = ($pc_closed > mt_rand(1,100));
		$length = 100 + mt_rand(-50, 50) + mt_rand(-25, 25); // range of 25 to 175 words

		$num_replies = 0;
		$replies = rand(0, 6) + rand(-1, 3) + rand(-1, 5);
		$replies = max(0, $replies); // make it at least zero

		// Set up the ticket details
		$posterOptions = array(
			'id' => $user_member,
			'name' => $names[$user_member],
			'email' => 'user@example.com',
		);

		$msgOptions = array(
			'body' => $lorem->getText($length, true),
			'id' => 0,
			'smileys_enabled' => true,
		);

		$ticketOptions = array(
			'id' => 0,
			'mark_as_read' => true,
			'subject' => 'Ticket ' . $i,
			'private' => false,
			'status' => $closed ? TICKET_STATUS_CLOSED : TICKET_STATUS_NEW,
			'urgency' => TICKET_URGENCY_LOW,
			'assigned' => $assigned,
			'dept' => $dest_dept,
		);

		shd_create_ticket_post($msgOptions, $ticketOptions, $posterOptions);

		if ($replies > 0)
		{
			$staff_pc_reply = 65; // % chance a reply will be staff replying
			for ($j = 0; $j < $replies; $j++)
			{
				$reply_length = 75 + mt_rand(-25, 10) + mt_rand(-25, 10);
				// Setting up for a reply
				$msgOptions = array(
					'body' => $lorem->getText($reply_length, true),
					'id' => 0,
					'smileys_enabled' => true,
				);

				if ($staff_pc_reply <= rand(1,100))
				{
					$posterOptions = array(
						'id' => $staff_member,
						'name' => $names[$staff_member],
						'email' => 'user@example.com',
					);
					$ticketOptions['status'] = TICKET_STATUS_PENDING_USER;
				}
				else
				{
					$posterOptions = array(
						'id' => $user_member,
						'name' => $names[$user_member],
						'email' => 'user@example.com',
					);
					$ticketOptions['status'] = TICKET_STATUS_PENDING_STAFF;
				}

				if ($closed) // override!!
					$ticketOptions['status'] = TICKET_STATUS_CLOSED;

				shd_create_ticket_post($msgOptions, $ticketOptions, $posterOptions);
			}
		}

		$smcFunc['db_query']('', '
			UPDATE {db_prefix}helpdesk_tickets
			SET last_updated = {int:time}
			WHERE id_ticket = {int:ticket}',
			array(
				'ticket' => $ticketOptions['id'],
				'time' => time(),
			)
		);

		echo sprintf($txt['shdp_install_testdata_added'], $i, $replies), ' <br />';
		flush();
	}

	echo '
						</div>
						<span class="botslice"><span></span></span>
					</div>
				</div>';
}
elseif (!empty($_REQUEST['go']) && $_REQUEST['go'] == 'yeah-for-delete')
{
	echo '
				<div class="tborder">
					<div class="cat_bar">
						<h3 class="catbg">
							<img src="', $settings['default_images_url'], '/simpledesk/delete.png" alt="x" /> ', $txt['shdp_install_testdata_del_title'], '
						</h3>
					</div>
					<div class="windowbg">
						<span class="topslice"><span></span></span>
						<div class="content">';

	// Purging the action log
	if (!empty($_POST['purge_actionlog']))
	{
		$smcFunc['db_query']('', 'TRUNCATE {db_prefix}helpdesk_log_action');
		echo $txt['shdp_install_testdata_purge_actionlog'], ' - <strong>', $txt['shdp_install_testdata_completed_purge'], '</strong><br />';
		flush();
	}

	// Purging relationships
	if (!empty($_POST['purge_relationships']))
	{
		$smcFunc['db_query']('', 'TRUNCATE {db_prefix}helpdesk_relationships');
		echo $txt['shdp_install_testdata_purge_relationships'], ' - <strong>', $txt['shdp_install_testdata_completed_purge'], '</strong><br />';
		flush();
	}

	// Purging roles
	if (!empty($_POST['purge_roles']))
	{
		$smcFunc['db_query']('', 'TRUNCATE {db_prefix}helpdesk_roles');
		$smcFunc['db_query']('', 'TRUNCATE {db_prefix}helpdesk_role_groups');
		$smcFunc['db_query']('', 'TRUNCATE {db_prefix}helpdesk_role_permissions');
		$smcFunc['db_query']('', 'TRUNCATE {db_prefix}helpdesk_dept_roles');
		echo $txt['shdp_install_testdata_purge_roles'], ' - <strong>', $txt['shdp_install_testdata_completed_purge'], '</strong><br />';
		flush();
	}

	echo '
						</div>
						<span class="botslice"><span></span></span>
					</div>
				</div>';
}

template_footer();

/**
 *	Copyright (c) 2009, Mathew Tinsley (tinsley@tinsology.net)
 *	All rights reserved.
 *
 *	Redistribution and use in source and binary forms, with or without
 *	modification, are permitted provided that the following conditions are met:
 *		* Redistributions of source code must retain the above copyright
 *		  notice, this list of conditions and the following disclaimer.
 *		* Redistributions in binary form must reproduce the above copyright
 *		  notice, this list of conditions and the following disclaimer in the
 *		  documentation and/or other materials provided with the distribution.
 *		* Neither the name of the organization nor the
 *		  names of its contributors may be used to endorse or promote products
 *		  derived from this software without specific prior written permission.
 *
 *	THIS SOFTWARE IS PROVIDED BY MATHEW TINSLEY ''AS IS'' AND ANY
 *	EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 *	WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 *	DISCLAIMED. IN NO EVENT SHALL <copyright holder> BE LIABLE FOR ANY
 *	DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 *	(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 *	LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 *	ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 *	(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 *	SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 *
 *	Changes to this are made by SimpleDesk, and this resultant class is similarly BSD licensed.
 *	-- Arantor, SimpleDesk Lead Developer.
 *
 *	Changed:
 *	- Made PHP 4 compatible (for SMF 2.0 compatibility reasons)
 *	- Added smileys
 *	- Reformatted to comply with SMF coding guidelines
 *	- Removed plain and HTML modes, text mode edited
 *	- Made it capitalise sentences nicely.
 *
 *	@package installer
 *	@since 1.0
*/

class LoremIpsumGenerator {
	var $words, $wordsPerParagraph, $wordsPerSentence, $bbcode, $smileys;

	function __construct($wordsPer = 100)
	{
		$this->wordsPerParagraph = $wordsPer;
		$this->wordsPerSentence = 24.460;
		$this->words = array
		(
			'lorem',
			'ipsum',
			'dolor',
			'sit',
			'amet',
			'consectetur',
			'adipiscing',
			'elit',
			'curabitur',
			'vel',
			'hendrerit',
			'libero',
			'eleifend',
			'blandit',
			'nunc',
			'ornare',
			'odio',
			'ut',
			'orci',
			'gravida',
			'imperdiet',
			'nullam',
			'purus',
			'lacinia',
			'a',
			'pretium',
			'quis',
			'congue',
			'praesent',
			'sagittis',
			'laoreet',
			'auctor',
			'mauris',
			'non',
			'velit',
			'eros',
			'dictum',
			'proin',
			'accumsan',
			'sapien',
			'nec',
			'massa',
			'volutpat',
			'venenatis',
			'sed',
			'eu',
			'molestie',
			'lacus',
			'quisque',
			'porttitor',
			'ligula',
			'dui',
			'mollis',
			'tempus',
			'at',
			'magna',
			'vestibulum',
			'turpis',
			'ac',
			'diam',
			'tincidunt',
			'id',
			'condimentum',
			'enim',
			'sodales',
			'in',
			'hac',
			'habitasse',
			'platea',
			'dictumst',
			'aenean',
			'neque',
			'fusce',
			'augue',
			'leo',
			'eget',
			'semper',
			'mattis',
			'tortor',
			'scelerisque',
			'nulla',
			'interdum',
			'tellus',
			'malesuada',
			'rhoncus',
			'porta',
			'sem',
			'aliquet',
			'et',
			'nam',
			'suspendisse',
			'potenti',
			'vivamus',
			'luctus',
			'fringilla',
			'erat',
			'donec',
			'justo',
			'vehicula',
			'ultricies',
			'varius',
			'ante',
			'primis',
			'faucibus',
			'ultrices',
			'posuere',
			'cubilia',
			'curae',
			'etiam',
			'cursus',
			'aliquam',
			'quam',
			'dapibus',
			'nisl',
			'feugiat',
			'egestas',
			'class',
			'aptent',
			'taciti',
			'sociosqu',
			'ad',
			'litora',
			'torquent',
			'per',
			'conubia',
			'nostra',
			'inceptos',
			'himenaeos',
			'phasellus',
			'nibh',
			'pulvinar',
			'vitae',
			'urna',
			'iaculis',
			'lobortis',
			'nisi',
			'viverra',
			'arcu',
			'morbi',
			'pellentesque',
			'metus',
			'commodo',
			'ut',
			'facilisis',
			'felis',
			'tristique',
			'ullamcorper',
			'placerat',
			'aenean',
			'convallis',
			'sollicitudin',
			'integer',
			'rutrum',
			'duis',
			'est',
			'etiam',
			'bibendum',
			'donec',
			'pharetra',
			'vulputate',
			'maecenas',
			'mi',
			'fermentum',
			'consequat',
			'suscipit',
			'aliquam',
			'habitant',
			'senectus',
			'netus',
			'fames',
			'quisque',
			'euismod',
			'curabitur',
			'lectus',
			'elementum',
			'tempor',
			'risus',
			'cras',
		);

		$this->bbcode = array(
			'b',
			'i',
			'u',
			's',
		);

		$this->smileys = array(
			':)',
			';)',
			':D',
			';D',
			'>:(',
			':(',
			':o',
			'8)',
			'???',
			'::)',
			':P',
			':-[',
			':-X',
			':-\\',
			':-*',
			':\'(',
		);
	}

	function getWords(&$arr, $count, $loremipsum)
	{
		$i = 0;
		if($loremipsum)
		{
			$i = 2;
			$arr[0] = 'lorem';
			$arr[1] = 'ipsum';
		}

		for($i; $i < $count; $i++)
		{
			$index = array_rand($this->words);
			$word = $this->words[$index];

			if($i > 0 && $arr[$i - 1] == $word)
				$i--;
			else
				$arr[$i] = $word;
		}
	}

	function getPlain($count, $loremipsum, $returnStr = true)
	{
		$words = array();
		$this->getWords($words, $count, $loremipsum);

		$delta = $count;
		$curr = 0;
		$sentences = array();
		while($delta > 0)
		{
			$senSize = $this->gaussianSentence();
			if(($delta - $senSize) < 4)
				$senSize = $delta;

			$delta -= $senSize;

			$sentence = array();
			for($i = $curr; $i < ($curr + $senSize); $i++)
				$sentence[] = $words[$i];

			$this->punctuate($sentence);
			$curr = $curr + $senSize;
			$sentences[] = $sentence;
		}

		if($returnStr)
		{
			$output = '';
			foreach($sentences as $s)
				foreach($s as $w)
					$output .= $w . ' ';

			return $output;
		}
		else
			return $sentences;
	}

	function getText($count, $loremipsum = true)
	{
		if ($count <= 0)
			return '';

		$sentences = $this->getPlain($count, $loremipsum, false);
		$paragraphs = $this->getParagraphArr($sentences);

		$paragraphStr = array();
		foreach($paragraphs as $p)
			$paragraphStr[] = $this->paragraphToString($p);

		return implode("\n\n", $paragraphStr);
	}

	function getParagraphArr($sentences)
	{
		$wordsPer = $this->wordsPerParagraph;
		$sentenceAvg = $this->wordsPerSentence;
		$total = count($sentences);

		$paragraphs = array();
		$pCount = 0;
		$currCount = 0;
		$curr = array();

		for($i = 0; $i < $total; $i++)
		{
			$s = $sentences[$i];
			$currCount += count($s);
			$curr[] = $s;
			if($currCount >= ($wordsPer - round($sentenceAvg / 2.00)) || $i == $total - 1)
			{
				$currCount = 0;
				$paragraphs[] = $curr;
				$curr = array();
				//print_r($paragraphs);
			}
			//print_r($paragraphs);
		}

		return $paragraphs;
	}

	function paragraphToString($paragraph, $htmlCleanCode = false)
	{
		$paragraphStr = '';
		foreach($paragraph as $sentence)
		{
			foreach($sentence as $word)
				$paragraphStr .= $word . ' ';

			if($htmlCleanCode)
				$paragraphStr .= "\n";
		}
		return $paragraphStr;
	}

	/*
	* Inserts commas and periods in the given
	* word array.
	*/
	function punctuate(& $sentence)
	{
		$count = count($sentence);
		$sentence[$count - 1] .= '.';
		$sentence[0] = ucfirst($sentence[0]);

		if($count < 4)
			return $sentence;

		$commas = $this->numberOfCommas($count);

		for($i = 1; $i <= $commas; $i++)
		{
			$index = (int) round($i * $count / ($commas + 1));

			if($index < ($count - 1) && $index > 0)
				$sentence[$index] .= ',';
		}

		// Add smileys to the end of the sentence :D
		$n = 3;
		while ($n >= rand(1, 15))
		{
			$n--;
			$sentence[$count - 1] .= ' ' . $this->smileys[rand(0, count($this->smileys)-1)];
		}
	}

	/*
	* Determines the number of commas for a
	* sentence of the given length. Average and
	* standard deviation are determined superficially
	*/
	function numberOfCommas($len)
	{
		$avg = (float) log($len, 6);
		$stdDev = (float) $avg / 6.000;

		return (int) round($this->gauss_ms($avg, $stdDev));
	}

	/*
	* Returns a number on a gaussian distribution
	* based on the average word length of an english
	* sentence.
	* Statistics Source:
	*	http://hearle.nahoo.net/Academic/Maths/Sentence.html
	*	Average: 24.46
	*	Standard Deviation: 5.08
	*/
	function gaussianSentence()
	{
		$avg = (float) 24.460;
		$stdDev = (float) 5.080;

		return (int) round($this->gauss_ms($avg, $stdDev));
	}

	/*
	* The following three functions are used to
	* compute numbers with a guassian distrobution
	* Source:
	* 	http://us.php.net/manual/en/function.rand.php#53784
	*/
	function gauss()
	{   // N(0,1)
		// returns random number with normal distribution:
		//   mean=0
		//   std dev=1

		// auxilary vars
		$x=$this->random_0_1();
		$y=$this->random_0_1();

		// two independent variables with normal distribution N(0,1)
		$u=sqrt(-2*log($x))*cos(2*pi()*$y);
		$v=sqrt(-2*log($x))*sin(2*pi()*$y);

		// i will return only one, couse only one needed
		return $u;
	}

	function gauss_ms($m=0.0,$s=1.0)
	{
		return $this->gauss()*$s+$m;
	}

	function random_0_1()
	{
		return (float)rand()/(float)getrandmax();
	}

}
?>