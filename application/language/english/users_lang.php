<?php
/**
 * English language file for user management
 */

// Success messages
$lang['user_delete_success'] = 'User has been successfully deleted.';

// Error messages
$lang['user_delete_failed'] = 'Unable to delete user.';
$lang['user_delete_blocked'] = 'DELETION BLOCKED: This user cannot be deleted because they are referenced in other data.';
$lang['user_delete_dependencies'] = 'Dependencies found:';

// Table references
$lang['user_delete_ref_membre'] = 'Member profile';
$lang['user_delete_ref_compte'] = 'Accounts';
$lang['user_delete_ref_volsa_pilot'] = 'Airplane flights (pilot)';
$lang['user_delete_ref_volsa_instructor'] = 'Airplane flights (instructor)';
$lang['user_delete_ref_volsp_pilot'] = 'Glider flights (pilot)';
$lang['user_delete_ref_volsp_instructor'] = 'Glider flights (instructor)';
$lang['user_delete_ref_volsp_towpilot'] = 'Glider flights (tow pilot)';

// Membre deletion messages (same references as users)
$lang['membre_delete_success'] = 'Member has been successfully deleted.';
$lang['membre_delete_failed'] = 'Unable to delete member.';
$lang['membre_delete_blocked'] = 'DELETION BLOCKED: This member cannot be deleted because they are referenced in other data.';
$lang['membre_delete_dependencies'] = 'Dependencies found:';
$lang['membre_delete_ref_compte'] = 'Accounts';
$lang['membre_delete_ref_volsa_pilot'] = 'Airplane flights (pilot)';
$lang['membre_delete_ref_volsa_instructor'] = 'Airplane flights (instructor)';
$lang['membre_delete_ref_volsp_pilot'] = 'Glider flights (pilot)';
$lang['membre_delete_ref_volsp_instructor'] = 'Glider flights (instructor)';
$lang['membre_delete_ref_volsp_towpilot'] = 'Glider flights (tow pilot)';

/* End of file users_lang.php */
/* Location: ./application/language/english/users_lang.php */
