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
# File Info: SDPluginReportToHelpdesk.php / 1.0 Felidae       #
###############################################################

/**
 *	This file handles sending notifications to users when things happen in the helpdesk.
 *
 *	@package source
 *	@since 1.1
 */

if (!defined('SMF'))
	die('Hacking attempt...');

// Deal with the action
function shd_report_to_helpdesk_actions(&$actionArray)
{
	global $context;

	if (!in_array('report_to_helpdesk', $context['shd_plugins']))
		return;

	$actionArray['reporttm'] = array('sd_plugins_source/report_to_helpdesk/SDPluginReportToHelpdeskMain.php', 'shd_report_to_helpdesk');
}

?>