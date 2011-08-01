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

function shdp_tidy_child_boards_process(&$boardIndexOptions, &$categories)
{
	global $settings, $modSettings, $context, $txt;

	if (!in_array('tally', $context['shd_plugins']) || empty($modSettings['tidy_child_boards']) || $modSettings['tidy_child_boards'] < 2 || $modSettings['tidy_child_boards'] > 4)
		return;

	if (file_exists($settings['theme_dir'] . '/images/on.png'))
		$ext = 'png';
	elseif (file_exists($settings['theme_dir'] . '/images/on.gif'))
		$ext = 'gif';
	else
		$ext = '';

	foreach ($categories as $cat_id => $category)
		foreach ($category['boards'] as $board_id => $board)
			if (!empty($board['children']))
			{
				$limit = ceil(count($board['children']) / $modSettings['tidy_child_boards']);
				$children = array();
				$this_count = $limit + 1;
				$this_division = 0;
				foreach ($board['children'] as $child)
				{
					if($this_count >= $limit)
					{
						$this_division++;
						$this_count = 0;
					}
					$children[$this_division][] = $child;
					$this_count++;
				}
				
				$new_html = '<div class="board_children">';
				foreach ($children as $key => $child_block)
				{
					$new_html .= '<div class="tidy_child"><ul>';

					foreach($child_block as $child)
					{
						$new_html .= '<li>';

						if (!empty($modSettings['tidy_child_boards_icon']) && !empty($ext))
							$new_html .= '<img src="' . $settings['images_url'] . '/' . ($child['new'] ? 'on' : 'off') . '.' . $ext . '" width="12" height="12" alt=""> ';

						if (!$child['is_redirect'])
							$link = '<a href="' . $child['href'] . '" ' . ($child['new'] ? 'class="new_posts" ' : '') . 'title="' . ($child['new'] ? $txt['new_posts'] : $txt['old_posts']) . ' (' . $txt['board_topics'] . ': ' . comma_format($child['topics']) . ', ' . $txt['posts'] . ': ' . comma_format($child['posts']) . ')">' . $child['name'] . '</a>';
						else
							$link = '<a href="' . $child['href'] . '" title="' . comma_format($child['posts']) . ' ' . $txt['redirects'] . '">' . $child['name'] . '</a>';

						if (!empty($modSettings['tidy_child_boards_new']) && $child['new'])
							$link .= ' <a href="' . $child['href'] . '" title="' . $txt['new_posts'] . ' (' . $txt['board_topics'] . ': ' . comma_format($child['topics']) . ', ' . $txt['posts'] . ': ' . comma_format($child['posts']) . ')"><img src="' . $settings['lang_images_url'] . '/new.gif" class="new_posts" alt="" /></a>';

						// Has it posts awaiting approval?
						if ($child['can_approve_posts'] && ($child['unapproved_posts'] | $child['unapproved_topics']))
							$link .= ' <a href="' . $scripturl . '?action=moderate;area=postmod;sa=' . ($child['unapproved_topics'] > $child['unapproved_posts'] ? 'topics' : 'posts') . ';brd=' . $child['id'] . ';' . $context['session_var'] . '=' . $context['session_id'] . '" title="' . sprintf($txt['unapproved_posts'], $child['unapproved_topics'], $child['unapproved_posts']) . '" class="moderation_link">(!)</a>';

						if (!empty($modSettings['tidy_child_boards_bold']) && $child['new'])
							$link = '<strong>' . $link . '</strong>';

						$new_html .= $link . '</li>';
					}

					$new_html .= '</ul></div>';
				}

				$new_html .= '</div>';
				$categories[$cat_id]['boards'][$board_id]['description'] .= $new_html;
				$categories[$cat_id]['boards'][$board_id]['children'] = array();
				$done = true;
			}

	if (!empty($done))
	{
		$context['html_headers'] .= '<style type="text/css">.tidy_child ul { list-style:none; padding:0 0.5em; } .tidy_child { display:block; float:left; width:' . floor(($context['browser']['is_ie'] ? 120 : 100) / $modSettings['tidy_child_boards']) . '%; }</style>';
	}
}

function shdp_tidy_child_boards_admin(&$config_vars, &$return_config)
{
	global $txt;

	$config_vars = array_merge($config_vars, array(
		'',
		array('select', 'tidy_child_boards', array(1 => $txt['tidy_child_boards_no'], 2 => $txt['tidy_child_boards_2col'], 3 => $txt['tidy_child_boards_3col'], 4 => $txt['tidy_child_boards_4col'])),
		array('check', 'tidy_child_boards_bold'),
		array('check', 'tidy_child_boards_icon'),
		array('check', 'tidy_child_boards_new')
	));
}

?>