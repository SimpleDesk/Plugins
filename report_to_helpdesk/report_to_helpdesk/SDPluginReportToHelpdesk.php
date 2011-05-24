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
# File Info: SDPluginReportToHelpdesk.php / 2.0 Anatidae      #
###############################################################

/**
 *	This file handles bootstrapping the report to helpdesk plugin, minimising the per-page performance changes.
 *
 *	@package source
 *	@since 2.0
 */

if (!defined('SMF'))
	die('Hacking attempt...');

// Deal with the action
function shd_report_to_helpdesk_actions(&$actionArray)
{
	global $context, $modSettings;

	if (!in_array('report_to_helpdesk', $context['shd_plugins']))
		return;

	if (!empty($modSettings['report_posts_dept']))
		$actionArray['reporttm'] = array('sd_plugins_source/report_to_helpdesk/SDPluginReportToHelpdeskMain.php', 'shd_report_to_helpdesk');
}

?>