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
 *	This file handles tallying up custom fields. It's rather naughty because, for convenience (and performance), we invoke it during a template.
 *
 *	Even more naughty, we don't bother to separate it out into a separate template. Good practice suggests we should but here, it's almost unnecessarily complicating matters.
 *
 *	@package plugin
 *	@since 2.0
 */

if (!defined('SMF'))
	die('Hacking attempt...');

// Deal with the action
function shd_tally()
{
	global $context, $modSettings, $smcFunc, $settings;

	if (!in_array('tally', $context['shd_plugins']) || empty($modSettings['shdp_tally_fields']))
		return;

	// Figure out if this ticket has what we need. Only worry about tallying if the field would have appeared in this ticket page at least once (ticket or reply)
	$possible_fields = array();
	// Gather fields from the ticket.
	foreach ($context['ticket']['custom_fields'] as $pos => $fields)
		foreach ($fields as $field_id => $field)
			$possible_fields[$field_id] = empty($field['icon']) ? $field['name'] : '<img src="' . $settings['default_images_url'] . '/simpledesk/cf/' . $field['icon'] . '" alt="" class="shd_smallicon" /> ' . $field['name'];
	
	// Gather fields from the replies.
	foreach ($context['custom_fields_replies'] as $dest => $fields)
		foreach ($fields as $field_id => $field)
			$possible_fields[$field_id] = empty($field['icon']) ? $field['name'] : '<img src="' . $settings['default_images_url'] . '/simpledesk/cf/' . $field['icon'] . '" alt="" class="shd_smallicon" /> ' . $field['name'];

	// Make sure we figure out what the admin selected, combined with what's available.
	if (!empty($possible_fields))
	{
		$fields = explode(',', $modSettings['shdp_tally_fields']);
		$get_fields = array();
		foreach ($fields as $field)
		{
			if (isset($possible_fields[$field]))
				$get_fields[] = (int) $field;
		}
	}

	if (empty($get_fields))
		return;

	// So we have a list of fields we should be getting. First, we need to get the message ids.
	$msgs = array();
	$query = $smcFunc['db_query']('', '
		SELECT id_msg
		FROM {db_prefix}helpdesk_ticket_replies
		WHERE id_ticket = {int:ticket}',
		array(
			'ticket' => $context['ticket_id'],
		)
	);
	while ($row = $smcFunc['db_fetch_row']($query))
		$msgs[] = $row[0]; // Should *always* be a message in a ticket.
	$smcFunc['db_free_result']($query);

	$values = array();
	$query = shd_db_query('', '
		SELECT cfv.id_field, cfv.value, cfv.post_type, cf.field_type
		FROM {db_prefix}helpdesk_custom_fields_values AS cfv
			INNER JOIN {db_prefix}helpdesk_custom_fields AS cf ON (cfv.id_field = cf.id_field)
		WHERE ((cfv.id_post = {int:ticket} AND cfv.post_type = 1)
			OR (cfv.id_post IN ({array_int:msgs}) AND cfv.post_type = 2))
			AND cfv.id_field IN ({array_int:fields})',
		array(
			'ticket' => $context['ticket_id'],
			'msgs' => $msgs,
			'fields' => $get_fields,
		)
	);
	while ($row = $smcFunc['db_fetch_assoc']($query))
	{
		if (!isset($values[$row['id_field']]))
			$values[$row['id_field']] = 0;

		switch ($row['field_type'])
		{
			case CFIELD_TYPE_INT:
				if ($row['post_type'] == CFIELD_REPLY || !empty($modSettings['shdp_tally_include_ticket']))
					$values[$row['id_field']] += (int) $row['value'];
				break;
			case CFIELD_TYPE_FLOAT:
				if ($row['post_type'] == CFIELD_REPLY || !empty($modSettings['shdp_tally_include_ticket']))
					$values[$row['id_field']] += (float) $row['value'];
				break;
			default;
				break;
		}
	}
	$smcFunc['db_free_result']($query);

	if (empty($values))
		return;

	$context['shdp_tally'] = array(
		'displaying' => $get_fields,
		'field_names' => $possible_fields,
		'values' => $values,
	);
	// Add it to the template list.
	array_unshift($context['leftcolumn_templates'], 'shdp_tally');
}

// And now for a shocking display of not keeping things separated, the template.
function template_shdp_tally()
{
	global $context, $txt, $settings;

	echo '
				<div class="tborder">
					<div class="title_bar grid_header">
						<h3 class="titlebg">
							<img src="', $settings['default_images_url'], '/simpledesk/custom_fields.png" alt="">', $txt['shdp_tally_totals'], '
						</h3>
					</div>
					<div class="windowbg2" style="padding-top: 0.5em;">
						<div class="information" style="margin: 0 1.2em 0 1.2em;">
							<ul style="list-style-type: none; padding-left: 0; margin: 0;">';

	foreach ($context['shdp_tally']['displaying'] as $field_id)
	{
		echo '
								<li>
									<dl>
										<dt>', $context['shdp_tally']['field_names'][$field_id], ':</dt>
										<dd>', $context['shdp_tally']['values'][$field_id], '</dd>
									</dl>
								</li>';

	};

	echo '
							</ul>
						</div>
						<span class="botslice"><span></span></span>
					</div>
				</div>
				<br />';
}

// Admin settings
function shdp_tally_options($return_config = false)
{
	global $txt, $scripturl, $context, $settings, $sc, $modSettings, $smcFunc;

	$field_list = array();
	$query = $smcFunc['db_query']('', '
		SELECT id_field, icon, field_name, field_type
		FROM {db_prefix}helpdesk_custom_fields
		WHERE active = 1
			AND field_type IN ({array_int:numeric_types})
		ORDER BY field_order',
		array(
			'numeric_types' => array(CFIELD_TYPE_INT, CFIELD_TYPE_FLOAT),
		)
	);
	while ($row = $smcFunc['db_fetch_assoc']($query))
		$field_list[$row['id_field']] = $row;
	$smcFunc['db_free_result']($query);

	$context['post_url'] = $scripturl . '?action=admin;area=helpdesk_options;sa=tally;save';
	$context['settings_title'] = $txt['shdp_tally'];
	$context['settings_icon'] = 'custom_fields.png';

	if (empty($field_list))
		$config_vars = array(
			$txt['shdp_tally_no_fields'],
		);
	else
	{
		$config_vars = array(
			array('check', 'shdp_tally_include_ticket'),
			'',
			$txt['shdp_tally_field_list'],
		);

		$selected = !empty($modSettings['shdp_tally_fields']) ? explode(',', $modSettings['shdp_tally_fields']) : array();
		foreach ($field_list as $field_id => $field)
		{
			$string = 'shdp_tally_custom_field_' . $field_id;
			$config_vars[] = array('check', $string);
			$modSettings[$string] = in_array($field_id, $selected) ? 1 : 0;
			$txt[$string] = empty($field['icon']) ? $field['field_name'] : '<img src="' . $settings['default_images_url'] . '/simpledesk/cf/' . $field['icon'] . '" alt="" class="shd_smallicon" /> ' . $field['field_name'];
		}
	}

	if (isset($_GET['save']) && !empty($field_list))
	{
		checkSession();

		// We need to mangle-worzel the config values for saving, because we're not using the above.
		$fields = array();
		foreach ($field_list as $field_id => $field)
			if (!empty($_POST['shdp_tally_custom_field_' . $field_id]))
				$fields[] = $field_id;

		$_POST['shdp_tally_fields'] = implode(',', $fields);
		$save_vars = array(
			array('check', 'shdp_tally_include_ticket'),
			array('text', 'shdp_tally_fields'),
		);

		saveDBSettings($save_vars);
		redirectexit('action=admin;area=helpdesk_options;sa=tally');
	}

	return $config_vars;
}

function shd_tally_adminmenu(&$admin_areas)
{
	global $context, $modSettings, $txt;

	// Enabled?
	if (!in_array('tally', $context['shd_plugins']))
		return;

	$admin_areas['helpdesk_info']['areas']['helpdesk_options']['subsections']['tally'] = array($txt['shdp_tally']);
}

function shd_tally_hdadminopts()
{
	global $context, $modSettings, $txt;

	// Enabled?
	if (!in_array('tally', $context['shd_plugins']))
		return;

	$context[$context['admin_menu_name']]['tab_data']['tabs']['tally'] = array(
		'description' => $txt['shdp_tally_desc'],
		'function' => 'shdp_tally_options',
	);
}

?>