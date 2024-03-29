<?php
// Version: 2.0 Anatidae

function template_shd_staff_list()
{
	global $context, $txt, $settings, $scripturl, $user_info, $options, $modSettings;

	echo '
		<div class="modbuttons clearfix margintop">';

	template_button_strip($context['navigation'], 'bottom');

	echo '
		</div>
		<div id="admin_content">
			<div class="tborder">
				<div class="cat_bar grid_header">
					<h3 class="catbg">
						<img src="', $settings['default_images_url'], '/simpledesk/staff.png" class="shd_icon_minihead" alt="" />
						', $txt['shdp_staff_list_title'], !empty($context['shd_dept_name']) ? ' - ' . $context['shd_dept_name'] : '', '
					</h3>
				</div>
				<div class="description shd_no_margin shd_hide_bottom_border">';

	// Now to do the department list if that's what we're doing.
	if (!empty($context['shd_department_list']))
	{
		echo '
					<form action="', $scripturl, '?action=helpdesk;sa=stafflist" method="post">
						<input type="submit" class="button_submit floatright" value="', $txt['shd_go'], '" />
						<select class="floatright" name="dept">';

		if (!empty($context['shd_department']))
			echo '
							<option value="', $context['shd_department'], '">', $txt['shdp_staff_list_another_dept'], '</option>';
		else
			echo '
							<option value="0">', $txt['shdp_staff_list_sel_dept'], '</option>';
		
		foreach ($context['shd_department_list'] as $id => $dept)
			echo '
							<option value="', $id, '">', $dept, '</option>';

		echo '
						</select>
					</form>';
	}

	echo '
					', $txt['shdp_staff_list_welcome'], '
				</div>
				<table class="shd_ticketlist" cellspacing="0" width="100%">
					<tr class="titlebg">
						<td colspan="2">
							<img src="', $settings['default_images_url'], '/simpledesk/user.png" class="shd_smallicon" alt="" />
							', $txt['shdp_staff_list_member'], '
						</td>
						<td>
							<img src="', $settings['default_images_url'], '/simpledesk/position.png" class="shd_smallicon" alt="" />
							', $txt['shdp_staff_list_position'], '
						</td>
						<td>
							<img src="', $settings['default_images_url'], '/simpledesk/details.png" class="shd_smallicon" alt="" />
							', $txt['shdp_staff_list_online_status'], '
						</td>							
						<td>
							<img src="', $settings['default_images_url'], '/simpledesk/time.png" class="shd_smallicon" alt="" />
							', $txt['shdp_staff_list_last_online'], '
						</td>
						<td>&nbsp;</td>
					</tr>';

		if (empty($context['staff_members']))
			echo '
					<tr class="windowbg2">
						<td colspan="6" class="shd_noticket">', $txt['shdp_staff_list_empty'], '</td>
					</tr>';
		else
		{
			$use_bg2 = true; // start with windowbg2 to differentiate between that and windowbg2
			foreach ($context['staff_members'] AS $member)
			{
				echo '
					<tr class="', ($use_bg2 ? 'windowbg2' : 'windowbg'), '">
						<td width="1%" class="centertext">
							', (!empty($modSettings['shd_display_avatar']) && empty($options['show_no_avatars']) && !empty($member['avatar']['image'])) ? $member['avatar']['image'] : '', '
							', !empty($member['extra']) ? $member['extra'] : '','
						</td>
						<td><strong>', $member['link'], '</strong></td>
						<td><span style="color: ', $member['group_color'], '">', $member['group'], '</span></td>
						<td>
							<img src="', $member['online']['image_href'], '" alt="" />&nbsp;
							', $member['online']['link'], '
						</td>						
						<td>', $member['last_login'], '</td>';
				
				if ($member['view_hd_profile'])
					echo '
						<td>
							<a href="', $member['href'], ';area=helpdesk" class="smalltext floatright">', $txt['shdp_staff_list_helpdesk_profile'], '
							<img src="', $settings['default_images_url'], '/simpledesk/go_to_helpdesk.png" class="shd_icon" alt="" /></a>
						</td>';
				else
					echo '
						<td></td>';

				echo '
					</tr>';

				$use_bg2 = !$use_bg2;
			}
		}

	echo '
				</table>
			</div>
		</div>';
}

?>