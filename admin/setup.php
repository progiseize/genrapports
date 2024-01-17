<?php
/* 
 * Copyright (C) 2022 ProgiSeize <contact@progiseize.fr>
 *
 * This program and files/directory inner it is free software: you can 
 * redistribute it and/or modify it under the terms of the 
 * GNU Affero General Public License (AGPL) as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AGPL for more details.
 *
 * You should have received a copy of the GNU AGPL
 * along with this program.  If not, see <https://www.gnu.org/licenses/agpl-3.0.html>.
 */

$res=0;
if (! $res && file_exists("../main.inc.php")): $res=@include '../main.inc.php'; endif;
if (! $res && file_exists("../../main.inc.php")): $res=@include '../../main.inc.php'; endif;
if (! $res && file_exists("../../../main.inc.php")): $res=@include '../../../main.inc.php'; endif;

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

dol_include_once('./genrapports/lib/genrapports.lib.php');


// Change this following line to use the correct relative path from htdocs
dol_include_once('/module/class/skeleton_class.class.php');

// Protection if external user
if ($user->societe_id > 0): accessforbidden(); endif;


/*******************************************************************
* ACTIONS
********************************************************************/

$action = GETPOST('action');

if ($action == 'set_options'):

    $error = 0;

    if(!dolibarr_set_const($db, "GENRAPPORTS_NUMBERS_TO_USE",GETPOST('genrapports-numbers-to-use'),'chaine',0,'',$conf->entity)): $error++; endif;

    if(!$error):$db->commit(); setEventMessages('Configuration sauvegardée.', null, 'mesgs');
    else: $db->rollback(); setEventMessages('Une erreur est survenue', null, 'errors');
    endif;

endif;

// $form=new Form($db);

/***************************************************
* VIEW
****************************************************/
$array_js = array();
$array_css = array('/genrapports/css/genrapports.css');

llxHeader('',$langs->trans('gr_setup_title').' :: '.$langs->trans('Module300306Name'),'','','','',$array_js,$array_css); ?>

<div id="pgsz-option" class="genrapports">

    <h1>Configuration GenRapports</h1>
    <?php $head = genrap_AdminPrepareHead(); dol_fiche_head($head, 'setup','GenRapports', 0,'progiseize@progiseize'); ?>

    <?php if ($user->rights->genrapports->configurer): ?>

    <h2><?php echo $langs->trans('gr_setup_title'); ?></h2>
    <form enctype="multipart/form-data" action="<?php echo $_SERVER["PHP_SELF"]; ?>" method="post" id="">

        <input type="hidden" name="action" value="set_options">

        <table class="gentab bt">
            <thead>
                <tr class="" >
                    <th class="left"><?php echo $langs->trans('Parameter'); ?></th>
                    <th class="right"><?php echo $langs->trans('Value'); ?></th>
                </tr>
            </thead>
            <tbody>  
                <tr class="">
                    <td class="left bold pgsz-optiontable-fieldname"><?php echo $langs->trans('gr_setup_howmany_numbers'); ?></td>               
                    <td class="right pgsz-optiontable-field "><input type="number" name="genrapports-numbers-to-use" value="<?php echo (GETPOST('genrapports-numbers-to-use'))?GETPOST('genrapports-numbers-to-use'):$conf->global->GENRAPPORTS_NUMBERS_TO_USE;?>"></td>
                </tr>
            </tbody>
        </table>
        <div class="right pgsz-buttons" style="padding:16px 0;">
            <input type="submit" class="genbtn" name="" value="<?php echo $langs->trans('Save'); ?>">
        </div>
    </form>
    <?php endif; ?>

</div>



<?php
// End of page
llxFooter();
$db->close();

?>