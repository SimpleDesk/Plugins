<?php
###############################################################
#         Simple Desk Project - www.simpledesk.net            #
###############################################################
#       An advanced help desk modifcation built on SMF        #
###############################################################
#                                                             #
#         * Copyright 2010 - SimpleDesk.net                   #
#                                                             #
#   This file and its contents are subject to the license     #
#   included with this distribution, license.txt, which       #
#   states that this software is New BSD Licensed.            #
#   Any questions, please contact SimpleDesk.net              #
#                                                             #
###############################################################
# SimpleDesk Version: 2.0 Anatidae                            #
# File Info: SDPluginReportToHelpdeskMain.php / 2.0 Anatidae  #
###############################################################

/**
 *	This file handles the main work of handling reporting to the helpdesk instead of the original functions.
 *
 *	@package source
 *	@since 2.0
 */

if (!defined('SMF'))
	die('Hacking attempt...');

function shd_report_to_helpdesk()
{
	global $txt, $topic, $sourcedir, $modSettings, $user_info, $context, $smcFunc;

	$context['robot_no_index'] = true;

	// You can't use this if it's off or you are not allowed to do it.
	isAllowedTo('report_any');

	// If they're posting, it should be processed by ReportToModerator2.
	if ((isset($_POST[$context['session_var']]) || isset($_POST['submit'])) && empty($context['post_errors']))
		shd_report_to_helpdesk2();

	// We need a message ID to check!
	if (empty($_REQUEST['msg']) && empty($_REQUEST['mid']))
		fatal_lang_error('no_access', false);

	// For compatibility, accept mid, but we should be using msg. (not the flavor kind!)
	$_REQUEST['msg'] = empty($_REQUEST['msg']) ? (int) $_REQUEST['mid'] : (int) $_REQUEST['msg'];

	// Check the message's ID - don't want anyone reporting a post they can't even see!
	$result = $smcFunc['db_query']('', '
		SELECT m.id_msg, m.id_member, t.id_member_started
		FROM {db_prefix}messages AS m
			INNER JOIN {db_prefix}topics AS t ON (t.id_topic = {int:current_topic})
		WHERE m.id_msg = {int:id_msg}
			AND m.id_topic = {int:current_topic}
		LIMIT 1',
		array(
			'current_topic' => $topic,
			'id_msg' => $_REQUEST['msg'],
		)
	);
	if ($smcFunc['db_num_rows']($result) == 0)
		fatal_lang_error('no_board', false);
	list ($_REQUEST['msg'], $member, $starter) = $smcFunc['db_fetch_row']($result);
	$smcFunc['db_free_result']($result);

	// Do we need to show the visual verification image?
	$context['require_verification'] = $user_info['is_guest'] && !empty($modSettings['guests_report_require_captcha']);
	if ($context['require_verification'])
	{
		require_once($sourcedir . '/Subs-Editor.php');
		$verificationOptions = array(
			'id' => 'report',
		);
		$context['require_verification'] = create_control_verification($verificationOptions);
		$context['visual_verification_id'] = $verificationOptions['id'];
	}

	// Show the inputs for the comment, etc.
	loadLanguage('Post');
	shd_load_language('SDPluginReportToHelpdesk');
	loadTemplate('SendTopic');

	$context['comment_body'] = !isset($_POST['comment']) ? '' : trim($_POST['comment']);
	$context['email_address'] = !isset($_POST['email']) ? '' : trim($_POST['email']);

	// This is here so that the user could, in theory, be redirected back to the topic.
	$context['start'] = $_REQUEST['start'];
	$context['message_id'] = $_REQUEST['msg'];

	$context['page_title'] = $txt['report_to_mod'];
	$context['sub_template'] = 'report';
}

function shd_report_to_helpdesk2()
{
	global $txt, $scripturl, $topic, $board, $user_info, $modSettings, $sourcedir, $language, $context, $smcFunc;

	// You must have the proper permissions!
	isAllowedTo('report_any');

	// Make sure they aren't spamming.
	spamProtection('reporttm');

	require_once($sourcedir . '/Subs-Post.php');

	// No errors, yet.
	$post_errors = array();

	// Check their session.
	if (checkSession('post', '', false) != '')
		$post_errors[] = 'session_timeout';

	// Make sure we have a comment and it's clean.
	if (!isset($_POST['comment']) || $smcFunc['htmltrim']($_POST['comment']) === '')
		$post_errors[] = 'no_comment';

	$poster_comment = $smcFunc['htmlspecialchars'](!empty($_POST['comment']) ? $_POST['comment'] : '', ENT_QUOTES);

	// Guests need to provide their address!
	if ($user_info['is_guest'])
	{
		$_POST['email'] = !isset($_POST['email']) ? '' : trim($_POST['email']);
		if ($_POST['email'] === '')
			$post_errors[] = 'no_email';
		elseif (preg_match('~^[0-9A-Za-z=_+\-/][0-9A-Za-z=_\'+\-/\.]*@[\w\-]+(\.[\w\-]+)*(\.[\w]{2,6})$~', $_POST['email']) == 0)
			$post_errors[] = 'bad_email';

		isBannedEmail($_POST['email'], 'cannot_post', sprintf($txt['you_are_post_banned'], $txt['guest_title']));

		$user_info['email'] = htmlspecialchars($_POST['email']);
	}

	// Could they get the right verification code?
	if ($user_info['is_guest'] && !empty($modSettings['guests_report_require_captcha']))
	{
		require_once($sourcedir . '/Subs-Editor.php');
		$verificationOptions = array(
			'id' => 'report',
		);
		$context['require_verification'] = create_control_verification($verificationOptions, true);
		if (is_array($context['require_verification']))
			$post_errors = array_merge($post_errors, $context['require_verification']);
	}

	// Any errors?
	if (!empty($post_errors))
	{
		loadLanguage('Errors');

		$context['post_errors'] = array();
		foreach ($post_errors as $post_error)
			$context['post_errors'][] = $txt['error_' . $post_error];

		return shd_report_to_helpdesk();
	}

	// Get the basic topic information, and make sure they can see it.
	$_POST['msg'] = (int) $_POST['msg'];

	$request = $smcFunc['db_query']('', '
		SELECT m.id_topic, m.id_board, m.subject, m.body, m.id_member AS id_poster, m.poster_name, IFNULL(mem.real_name, {string:guest}) AS real_name
		FROM {db_prefix}messages AS m
			LEFT JOIN {db_prefix}members AS mem ON (m.id_member = mem.id_member)
		WHERE m.id_msg = {int:id_msg}
			AND m.id_topic = {int:current_topic}
		LIMIT 1',
		array(
			'current_topic' => $topic,
			'id_msg' => $_POST['msg'],
			'guest' => $txt['guest_title'],
		)
	);
	if ($smcFunc['db_num_rows']($request) == 0)
		fatal_lang_error('no_board', false);
	$message = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);

	$poster_name = un_htmlspecialchars($message['real_name']) . ($message['real_name'] != $message['poster_name'] ? ' (' . $message['poster_name'] . ')' : '');
	$reporterName = un_htmlspecialchars($user_info['name']) . ($user_info['name'] != $user_info['username'] && $user_info['username'] != '' ? ' (' . $user_info['username'] . ')' : '');
	$subject = un_htmlspecialchars($message['subject']);

	// OK, so we're about to make this one. Get everything we need, including forum's default language version of our nice strings.
	shd_load_language('SDPluginReportToHelpdesk', $language);
	require_once($sourcedir . '/sd_source/Subs-SimpleDeskPost.php');

	$replacements = array(
		'{subject}' => $subject,
		'{author}' => $poster_name,
		'{reporter}' => $reporterName,
		'{comment}' => $poster_comment,
	);
	$body = str_replace(array_keys($replacements), array_values($replacements), $txt['reported_body']);
	preparsecode($body);
	$body = str_replace('{body}', $message['body'], $body);

	$msgOptions = array(
		'id' => 0,
		'body' => $body,
		'smileys_enabled' => false,
	);
	$ticketOptions = array(
		'id' => 0,
		'mark_as_read' => true,
		'subject' => $smcFunc['htmltrim']($smcFunc['htmlspecialchars']($txt['reported_post'] . ': ' . $subject)),
		'private' => false,
		'status' => TICKET_STATUS_NEW,
		'urgency' => TICKET_URGENCY_LOW,
		'assigned' => 0,
		'dept' => $modSettings['report_posts_dept'],
	);
	$posterOptions = array(
		'id' => $user_info['id'],
		'name' => $user_info['name'],
		'email' => $user_info['email'],
		'ip' => $user_info['ip'],
	);

	shd_create_ticket_post($msgOptions, $ticketOptions, $posterOptions);
	shd_clear_active_tickets();

	// Update our nice ticket store with the ticket id
	$context['ticket_id'] = $ticketOptions['id'];
	$context['ticket_form']['ticket'] = $ticketOptions['id'];

	shd_log_action(
		'newticket',
		array(
			'ticket' => $context['ticket_id'],
			'subject' => $ticketOptions['subject'],
		)
	);

	// Handle notifications
	require_once($sourcedir . '/sd_source/SimpleDesk-Notifications.php');
	shd_notifications_notify_newticket($msgOptions, $ticketOptions, $posterOptions);

	// Back to the post we reported!
	redirectexit('reportsent;topic=' . $topic . '.msg' . $_POST['msg'] . '#msg' . $_POST['msg']);
}

function shd_report_to_helpdesk_options($return_config)
{
	global $context, $modSettings, $txt, $sourcedir, $smcFunc;

	$dept_list = array(
		0 => $txt['report_normally'],
	);
	$query = $smcFunc['db_query']('', '
		SELECT id_dept, dept_name
		FROM {db_prefix}helpdesk_depts
		ORDER BY dept_order');
	while ($row = $smcFunc['db_fetch_assoc']($query))
		$dept_list[$row['id_dept']] = $row['dept_name'];
	$smcFunc['db_free_result']($query);

	$config_vars = array(
		array('select', 'report_posts_dept', $dept_list),
		'',
		array('select', 'report_pms_dept', $dept_list),
	);
	$context['settings_title'] = $txt['shdp_report_to_helpdesk'];
	$context['settings_icon'] = 'warning.png';

	return $config_vars;
}

function shd_report_to_helpdesk_adminmenu(&$admin_areas)
{
	global $context, $modSettings, $txt;

	// Enabled?
	if (!in_array('report_to_helpdesk', $context['shd_plugins']))
		return;

	$admin_areas['helpdesk_info']['areas']['helpdesk_options']['subsections']['report_to_helpdesk'] = array($txt['shdp_report_to_helpdesk']);
}

function shd_report_to_helpdesk_hdadminopts()
{
	global $context, $modSettings, $txt;

	// Enabled?
	if (!in_array('report_to_helpdesk', $context['shd_plugins']))
		return;

	$context[$context['admin_menu_name']]['tab_data']['tabs']['report_to_helpdesk'] = array(
		'description' => $txt['shdp_report_to_helpdesk_desc'],
		'function' => 'shd_report_to_helpdesk_options',
	);
}

function shd_report_pm()
{
	// This is one ugly function. We have to reproduce a decent chunk of PersonalMessage.php to make this work, because you can't hook into the PM system otherwise.
	global $txt, $scripturl, $sourcedir, $context, $user_info, $user_settings, $language, $smcFunc, $modSettings;

	// No guests!
	is_not_guest();

	// You're not supposed to be here at all, if you can't even read PMs.
	isAllowedTo('pm_read');

	// Things we need, to make us strong.
	require_once($sourcedir . '/Subs-Post.php');
	require_once($sourcedir . '/PersonalMessage.php');
	shd_load_language('SDPluginReportToHelpdesk', $language);
	require_once($sourcedir . '/sd_source/Subs-SimpleDeskPost.php');

	loadLanguage('PersonalMessage');

	if (WIRELESS && WIRELESS_PROTOCOL == 'wap')
		fatal_lang_error('wireless_error_notyet', false);
	elseif (WIRELESS)
		$context['sub_template'] = WIRELESS_PROTOCOL . '_pm';
	else
		loadTemplate('PersonalMessage');

	// Load up the members maximum message capacity.
	if ($user_info['is_admin'])
		$context['message_limit'] = 0;
	elseif (($context['message_limit'] = cache_get_data('msgLimit:' . $user_info['id'], 360)) === null)
	{
		// !!! Why do we do this?  It seems like if they have any limit we should use it.
		$request = $smcFunc['db_query']('', '
			SELECT MAX(max_messages) AS top_limit, MIN(max_messages) AS bottom_limit
			FROM {db_prefix}membergroups
			WHERE id_group IN ({array_int:users_groups})',
			array(
				'users_groups' => $user_info['groups'],
			)
		);
		list ($maxMessage, $minMessage) = $smcFunc['db_fetch_row']($request);
		$smcFunc['db_free_result']($request);

		$context['message_limit'] = $minMessage == 0 ? 0 : $maxMessage;

		// Save us doing it again!
		cache_put_data('msgLimit:' . $user_info['id'], $context['message_limit'], 360);
	}

	// Prepare the context for the capacity bar.
	if (!empty($context['message_limit']))
	{
		$bar = ($user_info['messages'] * 100) / $context['message_limit'];

		$context['limit_bar'] = array(
			'messages' => $user_info['messages'],
			'allowed' => $context['message_limit'],
			'percent' => $bar,
			'bar' => min(100, (int) $bar),
			'text' => sprintf($txt['pm_currently_using'], $user_info['messages'], round($bar, 1)),
		);
	}

	// a previous message was sent successfully? show a small indication.
	if (isset($_GET['done']) && ($_GET['done'] == 'sent'))
		$context['pm_sent'] = true;

	// Now we have the labels, and assuming we have unsorted mail, apply our rules!
	if ($user_settings['new_pm'])
	{
		$context['labels'] = $user_settings['message_labels'] == '' ? array() : explode(',', $user_settings['message_labels']);
		foreach ($context['labels'] as $id_label => $label_name)
			$context['labels'][(int) $id_label] = array(
				'id' => $id_label,
				'name' => trim($label_name),
				'messages' => 0,
				'unread_messages' => 0,
			);
		$context['labels'][-1] = array(
			'id' => -1,
			'name' => $txt['pm_msg_label_inbox'],
			'messages' => 0,
			'unread_messages' => 0,
		);

		ApplyRules();
		updateMemberData($user_info['id'], array('new_pm' => 0));
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}pm_recipients
			SET is_new = {int:not_new}
			WHERE id_member = {int:current_member}',
			array(
				'current_member' => $user_info['id'],
				'not_new' => 0,
			)
		);
	}

	// Load the label data.
	if ($user_settings['new_pm'] || ($context['labels'] = cache_get_data('labelCounts:' . $user_info['id'], 720)) === null)
	{
		$context['labels'] = $user_settings['message_labels'] == '' ? array() : explode(',', $user_settings['message_labels']);
		foreach ($context['labels'] as $id_label => $label_name)
			$context['labels'][(int) $id_label] = array(
				'id' => $id_label,
				'name' => trim($label_name),
				'messages' => 0,
				'unread_messages' => 0,
			);
		$context['labels'][-1] = array(
			'id' => -1,
			'name' => $txt['pm_msg_label_inbox'],
			'messages' => 0,
			'unread_messages' => 0,
		);

		// Looks like we need to reseek!
		$result = $smcFunc['db_query']('', '
			SELECT labels, is_read, COUNT(*) AS num
			FROM {db_prefix}pm_recipients
			WHERE id_member = {int:current_member}
				AND deleted = {int:not_deleted}
			GROUP BY labels, is_read',
			array(
				'current_member' => $user_info['id'],
				'not_deleted' => 0,
			)
		);
		while ($row = $smcFunc['db_fetch_assoc']($result))
		{
			$this_labels = explode(',', $row['labels']);
			foreach ($this_labels as $this_label)
			{
				$context['labels'][(int) $this_label]['messages'] += $row['num'];
				if (!($row['is_read'] & 1))
					$context['labels'][(int) $this_label]['unread_messages'] += $row['num'];
			}
		}
		$smcFunc['db_free_result']($result);

		// Store it please!
		cache_put_data('labelCounts:' . $user_info['id'], $context['labels'], 720);
	}

	// This determines if we have more labels than just the standard inbox.
	$context['currently_using_labels'] = count($context['labels']) > 1 ? 1 : 0;

	// Some stuff for the labels...
	$context['current_label_id'] = isset($_REQUEST['l']) && isset($context['labels'][(int) $_REQUEST['l']]) ? (int) $_REQUEST['l'] : -1;
	$context['current_label'] = &$context['labels'][(int) $context['current_label_id']]['name'];
	$context['folder'] = !isset($_REQUEST['f']) || $_REQUEST['f'] != 'sent' ? 'inbox' : 'sent';

	// This is convenient.  Do you know how annoying it is to do this every time?!
	$context['current_label_redirect'] = 'action=pm;f=' . $context['folder'] . (isset($_GET['start']) ? ';start=' . $_GET['start'] : '') . (isset($_REQUEST['l']) ? ';l=' . $_REQUEST['l'] : '');
	$context['can_issue_warning'] = in_array('w', $context['admin_features']) && allowedTo('issue_warning') && $modSettings['warning_settings'][0] == 1;

	// Build the linktree for all the actions...
	$context['linktree'][] = array(
		'url' => $scripturl . '?action=pm',
		'name' => $txt['personal_messages']
	);

	// Preferences...
	$context['display_mode'] = WIRELESS ? 0 : $user_settings['pm_prefs'] & 3;

	// NOW FINALLY, we do something actually pertinent to reporting, that isn't just scaffolding from PersonalMessage.php.
	messageIndexBar('report');

	// Check that this feature is even enabled!
	if (empty($modSettings['enableReportPM']) || empty($_REQUEST['pmsg']))
		fatal_lang_error('no_access', false);

	$pmsg = (int) $_REQUEST['pmsg'];

	if (!isAccessiblePM($pmsg, 'inbox'))
		fatal_lang_error('no_access', false);

	$context['pm_id'] = $pmsg;
	$context['page_title'] = $txt['pm_report_title'];

	// If we're here, just send the user to the template, with a few useful context bits.
	if (!isset($_POST['report']))
	{
		$context['sub_template'] = 'report_message';

		$context['admins'] = array();
		$context['admin_count'] = 1; // To stop the form displaying a list of admins. It's going to the helpdesk instead, remember?
	}
	// Otherwise, let's get down to the sending stuff.
	else
	{
		// Check the session before proceeding any further!
		checkSession('post');

		// First, pull out the message contents, and verify it actually went to them!
		$request = $smcFunc['db_query']('', '
			SELECT pm.subject, pm.body, pm.msgtime, pm.id_member_from, IFNULL(m.real_name, pm.from_name) AS sender_name
			FROM {db_prefix}personal_messages AS pm
				INNER JOIN {db_prefix}pm_recipients AS pmr ON (pmr.id_pm = pm.id_pm)
				LEFT JOIN {db_prefix}members AS m ON (m.id_member = pm.id_member_from)
			WHERE pm.id_pm = {int:id_pm}
				AND pmr.id_member = {int:current_member}
				AND pmr.deleted = {int:not_deleted}
			LIMIT 1',
			array(
				'current_member' => $user_info['id'],
				'id_pm' => $context['pm_id'],
				'not_deleted' => 0,
			)
		);
		// Can only be a hacker here!
		if ($smcFunc['db_num_rows']($request) == 0)
			fatal_lang_error('no_access', false);
		list ($subject, $body, $time, $memberFromID, $memberFromName) = $smcFunc['db_fetch_row']($request);
		$smcFunc['db_free_result']($request);

		// Remove the line breaks...
		$body = preg_replace('~<br ?/?' . '>~i', "\n", $body);

		// Get any other recipients of the email.
		$request = $smcFunc['db_query']('', '
			SELECT mem_to.id_member AS id_member_to, mem_to.real_name AS to_name, pmr.bcc
			FROM {db_prefix}pm_recipients AS pmr
				LEFT JOIN {db_prefix}members AS mem_to ON (mem_to.id_member = pmr.id_member)
			WHERE pmr.id_pm = {int:id_pm}
				AND pmr.id_member != {int:current_member}',
			array(
				'current_member' => $user_info['id'],
				'id_pm' => $context['pm_id'],
			)
		);
		$recipients = array();
		$hidden_recipients = 0;
		while ($row = $smcFunc['db_fetch_assoc']($request))
		{
			// If it's hidden still don't reveal their names - privacy after all ;)
			if ($row['bcc'])
				$hidden_recipients++;
			else
				$recipients[] = un_htmlspecialchars($row['to_name']);
		}
		$smcFunc['db_free_result']($request);

		if ($hidden_recipients)
			$recipients[] = sprintf($txt['pm_report_pm_hidden'], $hidden_recipients);

		$memberFromName = un_htmlspecialchars($memberFromName);

		$replacements = array(
			'{reporter}' => un_htmlspecialchars($user_info['name']),
			'{author}' => un_htmlspecialchars($memberFromName),
			'{comment}' => $_POST['reason'],
			'{body}' => un_htmlspecialchars($body),
		);
		if (!empty($recipients))
			$replacements['{recipients}'] = implode(', ', $recipients);

		$report_body = str_replace(array_keys($replacements), array_values($replacements), !empty($recipients) ? $txt['reported_pm_body_extra'] : $txt['reported_pm_body_no_extra']);

		preparsecode($report_body);

		$msgOptions = array(
			'id' => 0,
			'body' => $report_body,
			'smileys_enabled' => false,
		);
		$ticketOptions = array(
			'id' => 0,
			'mark_as_read' => true,
			'subject' => $smcFunc['htmltrim']($smcFunc['htmlspecialchars']($txt['reported_pm'] . ': ' . $subject)),
			'private' => false,
			'status' => TICKET_STATUS_NEW,
			'urgency' => TICKET_URGENCY_LOW,
			'assigned' => 0,
			'dept' => $modSettings['report_pms_dept'],
		);
		$posterOptions = array(
			'id' => $user_info['id'],
			'name' => $user_info['name'],
			'email' => $user_info['email'],
			'ip' => $user_info['ip'],
		);

		shd_create_ticket_post($msgOptions, $ticketOptions, $posterOptions);
		shd_clear_active_tickets();

		// Update our nice ticket store with the ticket id
		$context['ticket_id'] = $ticketOptions['id'];
		$context['ticket_form']['ticket'] = $ticketOptions['id'];

		shd_log_action(
			'newticket',
			array(
				'ticket' => $context['ticket_id'],
				'subject' => $ticketOptions['subject'],
			)
		);

		// Handle notifications
		require_once($sourcedir . '/sd_source/SimpleDesk-Notifications.php');
		shd_notifications_notify_newticket($msgOptions, $ticketOptions, $posterOptions);

		// Leave them with a template.
		$context['sub_template'] = 'report_message_complete';
	}
}
?>