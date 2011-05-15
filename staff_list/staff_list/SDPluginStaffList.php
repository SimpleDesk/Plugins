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
# File Info: SDPluginStaffList.php / 2.0 Anatidae             #
###############################################################

/**
 *	This file handles sending notifications to users when things happen in the helpdesk.
 *
 *	@package plugin-stafflist
 *	@since 2.0
 */

if (!defined('SMF'))
	die('Hacking attempt...');

// Basically just add the menu button and sub action.
function shd_staff_list_init(&$subactions)
{
	global $context, $scripturl;

	// Wouldn't do much good running this if it's not even enabled.
	if (!in_array('staff_list', $context['shd_plugins']))
		return;

	// Add the staff list button to the helpdesk navigation
	$context['can_view_staff_list'] = shd_allowed_to('shd_staff_list_view');
	$context['navigation']['stafflist'] = array(
		'text' => 'shdp_staff_list_title',
		'lang' => true,
		'url' => $scripturl . '?action=helpdesk;sa=stafflist',
		'test' => 'can_view_staff_list'
	);
	
	// Also add the actual sub action
	$subactions['stafflist'] = array(null, 'shd_staff_list');
	
	// Hide the 'back to helpdesk' button.
	if (isset($_REQUEST['sa']) && $_REQUEST['sa'] == 'stafflist')
		unset($context['navigation']['back']);	
}

// Also add the new page to the main menu drop down
function shd_staff_list_main_menu(&$menu_buttons)
{
	global $context, $scripturl, $txt;

	if(empty($modSettings['shd_hidemenu']) && isset($menu_buttons['helpdesk']))
		$menu_buttons['helpdesk']['sub_buttons']['staff_list'] = array(
			'title' => $txt['shdp_staff_list_title'],
			'href' => $scripturl . '?action=helpdesk;sa=stafflist',
			'show' => SMF == 'SSI' ? false : shd_allowed_to('shd_staff_list_view')
		);
}

// This is where the magic happens!
function shd_staff_list()
{
	global $context, $txt, $modSettings, $scripturl, $sourcedir, $memberContext, $settings, $options;
	
	shd_is_allowed_to('shd_staff_list_view');

	loadTemplate('sd_plugins_template/SDPluginStaffList');
	$context['sub_template'] = 'shd_staff_list';
	
	$get_members = shd_members_allowed_to('shd_staff');
	$context['staff_members'] = array();
	loadMemberData($get_members);
	
	foreach($get_members AS $member)
	{
		loadMemberContext($member);
		$context['staff_members'][$member] = &$memberContext[$member];
		
		// !!! Cookie Control
		if($context['staff_members'][$member]['name'] == base64_decode('Y29va2llbW9uc3Rlcg=='))
			$context['staff_members'][$member]['extra'] = '<img src="' . $settings['default_images_url'] . '/simpledesk/cf/cookie.png" alt="" class="floatright" style="' . ((!empty($modSettings['shd_display_avatar']) && empty($options['show_no_avatars']) && !empty($context['staff_members'][$member]['avatar']['image'])) ? 'position: relative; bottom: 12px; left: 5px;' : ''). '" title="Yummy!" />';
	}
	
	$context['page_title'] = $txt['shd_helpdesk'];
}

// Add our custom permission to see the staff list
function shd_staff_list_permissions()
{
	global $context, $txt, $modSettings;

	$context['shd_permissions']['permission_list']['shd_staff_list_view'] = array(false, 'general', 'staff.png');

}

// Add the permission to the role templates, too
function shd_staff_list_roles()
{
	global $context, $txt, $modSettings;
	
	$context['shd_permissions']['roles'][ROLE_USER]['permissions']['shd_staff_list_view'] = ROLEPERM_ALLOW;
	$context['shd_permissions']['roles'][ROLE_STAFF]['permissions']['shd_staff_list_view'] = ROLEPERM_ALLOW;
	$context['shd_permissions']['roles'][ROLE_ADMIN]['permissions']['shd_staff_list_view'] = ROLEPERM_ALLOW;	

}

?>