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
# SimpleDesk Version: 1.0 Felidae                             #
# File Info: SimpleDesk-Notifications.php / 1.0 Felidae       #
###############################################################

/**
 *	This file handles sending notifications to users when things happen in the helpdesk.
 *
 *	@package source
 *	@since 1.1
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

	// Handle notifications
	require_once($sourcedir . '/sd_source/SimpleDesk-Notifications.php');
	shd_notifications_notify_newticket($msgOptions, $ticketOptions, $posterOptions);

	// Back to the post we reported!
	redirectexit('reportsent;topic=' . $topic . '.msg' . $_POST['msg'] . '#msg' . $_POST['msg']);
}

?>