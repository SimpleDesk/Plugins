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
# File Info: SDPluginInstallTestdata.english_british.php / 2.0 Anatidae      #
##############################################################################
// Version: 2.0 Anatidae; SimpleDesk installation test-data

// Important! Before editing these language files please read the text at the top of index.english.php.

$txt['shdp_install_testdata'] = 'Install Test-Data';
$txt['shdp_install_testdata_desc'] = 'This plugin creates test data for your helpdesk.';

$txt['shdp_install_testdata_title'] = 'SimpleDesk test data';
$txt['shdp_install_testdata_invalidtickets'] = 'Invalid ticket count (1-100000)';
$txt['shdp_install_testdata_invalidclosed'] = 'Invalid % of closed tickets (0-100)';
$txt['shdp_install_testdata_invalidassigned'] = 'Invalid % of assigned tickets (0-100)';
$txt['shdp_install_testdata_invalidstaff'] = 'Invalid staff member';
$txt['shd_install_testdata_invalid_dept'] = 'Invalid department';

$txt['shdp_install_testdata_guestuser'] = '(Guest user)';
$txt['shdp_install_testdata_errors'] = 'Unfortunately, there were some errors';

$txt['shdp_install_testdata_numtickets'] = 'Number of tickets to make';
$txt['shdp_install_testdata_pcresolved'] = '% chance ticket will be marked resolved';
$txt['shdp_install_testdata_pcassigned'] = '% chance ticket will be marked assigned';
$txt['shdp_install_testdata_staff'] = 'User to use as "staff" in the code';
$txt['shdp_install_testdata_nonstaff'] = 'User to use as "not staff" in the code';
$txt['shdp_install_testdata_dept'] = 'Department the tickets should be added to';
$txt['shdp_install_testdata_create'] = 'Create!';
$txt['shdp_install_testdata_added'] = 'Ticket %1$d added with %2$d replies!';

$txt['shdp_install_testdata_del_title'] = 'Clean out the existing tables';
$txt['shdp_install_testdata_warning'] = 'These options are primarily for testing purposes and are not recommended for general use. They are NOT RECOVERABLE: if you have any doubts, take a backup first.';
$txt['shdp_install_testdata_nothingselected'] = 'No options were selected, nothing can be removed for that reason.';

$txt['shdp_install_testdata_purge_actionlog'] = 'Remove all existing action log entries';
$txt['shdp_install_testdata_purge_attachments'] = 'Remove all existing attachments to tickets (leaving forum attachments alone)';
$txt['shdp_install_testdata_purge_cf'] = 'Remove ALL custom value information';
$txt['shdp_install_testdata_purge_cf_values'] = 'Remove only the values from custom fields (leave the fields intact)';
$txt['shdp_install_testdata_purge_roles'] = 'Remove all existing roles';
$txt['shdp_install_testdata_purge_relationships'] = 'Remove all existing relationships between tickets';

$txt['shdp_install_testdata_completed_purge'] = 'completed';

$txt['shdp_install_testdata_clear_sure'] = 'Did I mention that this data was NOT RECOVERABLE? If you press OK, the data will be deleted from your helpdesk, permanently and irretrievably without a backup. Are you SURE you want to proceed?';

?>