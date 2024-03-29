<?php
###########################################################
#       Simple Desk Project - www.simpledesk.net          #
###########################################################
#     An advanced help desk modifcation built on SMF      #
###########################################################
#                                                         #
#       * Copyright 2010 - SimpleDesk.net                 #
#                                                         #
# This file and its contents are subject to the license   #
# included with this distribution, license.txt, which     #
# states that this software is New BSD Licensed.          #
# Any questions, please contact SimpleDesk.net            #
#                                                         #
###########################################################
# SimpleDesk Version: 2.0 Anatidae                        #
# File Info: index.php / 2.0 Anatidae                     #
###########################################################

/**
 *	@package plugin-installtestdata
 *	@since 2.0
*/

if (!defined('SHD_VERSION'))
	die('Hacking attempt...');

/*
 *	Return information about this plugin.
 *
 *	details
 *	- name: a $txt reference for the plugin's name (so it can be translated), if not present as a $txt will be used as a literal. (Note, see includes - language below)
 *	- description: a $txt reference one line description of the mod (translatable) - if not present, it will be used as a literal.
 *	- author: Author's name, literal
 *	- website: Website to link back to the author
 *	- version: Plugin version
 *	- compatibility: Array of supported SD version-strings
 *	
 *	includes
 *	- source: a key-value pair array of file names to include at strategic points, key name is the point to include it on, value is a filename or array of filenames to include within the plugin's dir
 *	- language: a key-value pair of array of language files to include, much like source.
 *
 *	hooks
 *	- key-value pair of hook name to function name or array of function names to be called at the hook point
 *
 *	@since 1.1
*/
function shdplugin_install_testdata()
{
	return array(
		'details' => array( // general plugin details
			'title' => 'shdp_install_testdata',
			'description' => 'shdp_install_testdata_desc',
			'author' => 'SimpleDesk Team',
			'website' => 'http://www.simpledesk.net/',
			'version' => '1.0',
			'compatibility' => array(
				'SimpleDesk 2.0 Anatidae', // should tie up with the SHD_VERSION constants
			),
			'acp_url' => $GLOBALS['boardurl'] . '/install-testdata.php',
		),
		'includes' => array(
			'source' => array(
			),
			'language' => array(
				'hdadmin' => 'SDPluginInstallTestdata',
			),
		),
		'hooks' => array( // what functions to call when
		),
	);
}


?>