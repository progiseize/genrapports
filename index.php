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

ini_set("xdebug.var_display_max_children", '-1');
ini_set("xdebug.var_display_max_data", '-1');
ini_set("xdebug.var_display_max_depth", '-1');

$res=0;
if (! $res && file_exists("../main.inc.php")): $res=@include '../main.inc.php'; endif;
if (! $res && file_exists("../../main.inc.php")): $res=@include '../../main.inc.php'; endif;

// Protection if external user
if ($user->societe_id > 0): accessforbidden(); endif;

$version = explode('.', DOL_VERSION); // ON RECUPERE LA VERSION DE DOLIBARR

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/cactioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

require_once DOL_DOCUMENT_ROOT.'/accountancy/class/bookkeeping.class.php';
// SURCHARGE PROGISEIZE 
if(intval($version[0]) == 16): require_once 'class/bookkeepingmod_v16.class.php'; // 16 
elseif(intval($version[0]) == 15): require_once 'class/bookkeepingmod_v15.class.php'; // 15 
elseif(intval($version[0]) == 14): require_once 'class/bookkeepingmod_v14.class.php'; // 14 
elseif(intval($version[0]) == 13): require_once 'class/bookkeepingmod_v13.class.php'; // 13
elseif(intval($version[0]) <= 12): require_once 'class/bookkeepingmod_v12.class.php'; // 12 et inférieurs (non testées)
endif;

require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountancycategory.class.php';
require_once 'class/accountancycategorymod.class.php'; // SURCHARGE PROGISEIZE 

require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/report.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/export/export_csv.modules.php';

dol_include_once('./genrapports/lib/genrapports.lib.php');
dol_include_once('./genrapports/class/genrapports.class.php');

$genrapports = new GenRapports($db);

date_default_timezone_set('Europe/Paris');

/*******************************************************************
* VARIABLES
********************************************************************/
$action = GETPOST('action');
$months = array(
	$langs->trans("MonthShort01"),$langs->trans("MonthShort02"),$langs->trans("MonthShort03"),
	$langs->trans("MonthShort04"),$langs->trans("MonthShort05"),$langs->trans("MonthShort06"),
	$langs->trans("MonthShort07"),$langs->trans("MonthShort08"),$langs->trans("MonthShort09"),
	$langs->trans("MonthShort10"),$langs->trans("MonthShort11"),$langs->trans("MonthShort12"),
);

$date_start = GETPOST('gen-datestart');
$date_end = GETPOST('gen-dateend');

if(empty($date_start)):

	$month_start = $conf->global->SOCIETE_FISCAL_MONTH_START;
	if($month_start <= 9): $month_start = '0'.$month_start; endif;
	$date_start = date('Y').'-'.$month_start.'-01';

endif;
if(empty($date_end)):
	$date_end = date('Y-m-d',strtotime(date("Y-m-d", strtotime($date_start)) . " + 364 day")); 
	//$date_end = date('Y').'-12-31'; 
endif;

$now = dol_now();
$array_of_files = array();

$showaccountdetail = GETPOST('showaccountdetail');

$conf->global->EXPORT_CSV_FORCE_CHARSET = "UTF-8";
$entity = $conf->entity;

$acts = array('bilan','compte_resultat','sig','resultat','sauvegarde','anouveaux');
$sim_actions = array('bilan','compte_resultat','sig');

/*******************************************************************
* ACTIONS
********************************************************************/

if(!empty($action)):

	// SI BILAN, COMPTE RESULTAT OU SIG
	if(in_array($action, $sim_actions)):

		$error = 0;

		$dstart = new DateTime($date_start);
		$dend = new DateTime($date_end);
		$diff_date = $dstart->diff($dend);
		// INTERVAL > 12
		if($diff_date->y > 0): $error++; setEventMessages($langs->transnoentities('gr_error_morethan_oneyear'), null, 'errors'); endif;
		// DATE INVERSEE
		if($diff_date->invert): $error++; setEventMessages($langs->transnoentities('gr_error_enddate_morethan_startdate'), null, 'errors'); endif;

		if(!$error):
			switch ($action):

				// BILAN
				case 'bilan': 

					//
					$res_401 = $genrapports->get_cd_bookkeeping('numero_compte',401,$date_start,$date_end);
					$res_411 = $genrapports->get_cd_bookkeeping('numero_compte',411,$date_start,$date_end);
					$array_of_files = array(
						$langs->trans('gr_balance_aux').' 401' => $res_401['file'],
						$langs->trans('gr_balance_aux').' 411' => $res_411['file']
					);
					
					// On execute en 1er le compte de résultat ?
					if($genrapports->exec_tabsql('compteresult')):

						// CALCUL
						//$borp = $genrapports->tableau_resultat($date_start,$date_end,'no','calcul');
						//var_dump($borp);
						
						// On execute les requetes pour le bilan
						if($genrapports->exec_tabsql('bilan')):
							$genrapports->update_bilan($date_start,$date_end);
							setEventMessages($langs->trans('gr_results_allrequests_ok'), null, 'mesgs');
						endif;

					endif;
				break;

				// COMPTE DE RESULTAT
				case 'compte_resultat': 
					if($genrapports->exec_tabsql('compteresult')):setEventMessages($langs->trans('gr_results_allrequests_ok'), null, 'mesgs'); endif;
				break;

				// SIG
				case 'sig': 
					if($genrapports->exec_tabsql('sig')): setEventMessages($langs->trans('gr_results_allrequests_ok'), null, 'mesgs'); endif;
				break;
			endswitch;
		endif;

	endif;

	/*
	// SAUVEGARDE - DEV a verifier
	// AJOUTER CORRECTEMENT LE BOUTON DE SAUVEGARDE AVEC UN FORM ET ACTION = SAUVEGARDE
	// Si action sauvegarde -> utiliser fonction get_cd_bookkeeping en mode 'save'
	// Faire un affichage specifique pour ces données grace au lockid
	// Ensuite, tester le blocage des champs par javascript
	case 'sauvegarde':
		$res_401 = get_cd_bookkeeping('numero_compte',401,$date_start,$date_end,'save');
		$res_411 = get_cd_bookkeeping('numero_compte',411,$date_start,$date_end,'save');
		$array_of_files = array('Balance Auxiliaire 401' => $res_401['file'],'Balance Auxiliaire 411' => $res_411['file']);
		$borp = tableau_resultat($date_start,$date_end,'no',$tab_compteresult,'calcul');
		$tab_to_show = $tab_bilan;
		if(exec_tabsql($tab_bilan)): setEventMessages('Toutes les requêtes ont été éxecutées.', null, 'mesgs'); endif;
	break;
	// A NOUVEAUX - DEV a verifier
	case 'anouveaux':
		$year_anouveaux = GETPOST('gen-year-anouveaux');
		$type_anouveaux = GETPOST('gen-type-anouveaux');
		$view_anouveaux = $genrapports->show_anouveaux($year_anouveaux,$type_anouveaux);
	break;*/

endif;


/***************************************************
* VIEW
****************************************************/
llxHeader('',$langs->trans('Module300306Name'),'','','','',array('/genrapports/js/genrapports.js'),array('/genrapports/css/genrapports.css')); ?>

<!-- CONTENEUR GENERAL -->
<div id="pgsz-option" class="genrapports">

	<h1><?php echo $langs->trans('gr_index_title'); ?></h1>
	<?php $head = genrap_AdminPrepareHead(); dol_fiche_head($head, 'index','GenRapports', 0,'progiseize@progiseize'); ?>

	<?php if($user->rights->genrapports->executer): ?>

		<?php // FORMULAIRE DE PARAMETRAGE DE RAPPORT ?>
		<form enctype="multipart/form-data" action="<?php print $_SERVER["PHP_SELF"]; ?>" method="post" class="gen-form-wrapper">
			<input type="hidden" name="token" value="<?php echo newtoken(); ?>">
			<table class="noborder centpercent pgsz-option-table" style="border-top:none;" id="genrapports-params">
		        <tbody>
		            <tr class="titre">
		                <td class="nobordernopadding valignmiddle col-title" style="" colspan="6">
		                    <div class="titre inline-block" style="padding:16px 0"><?php echo $langs->trans('gr_index_export_title'); ?></div>
		                </td>
		            </tr>
		            <?php // TITRES COLONNES TABLEAU // $form->textwithpicto(texte_a_afficher,'infobulle'); ?>
		            <tr class="liste_titre pgsz-optiontable-coltitle">
		                <th><?php echo $langs->trans('gr_index_export_type'); ?></th>
		                <th><?php echo $langs->trans('gr_index_export_datestart'); ?></th>
		                <th><?php echo $langs->trans('gr_index_export_dateend'); ?></th>
		                <th><?php echo $langs->trans('gr_index_export_showdetail'); ?></th>
		                <th width="120" class="center"></th>
		            </tr>
		            <tr class="oddeven pgsz-optiontable-tr">
		                <td>
		                	<select id="select-select-action" class="genrap-slct centpercent" name="action">
								<option value="bilan" <?php if($action == '' || $action == 'bilan'): echo 'selected=""'; endif; ?>><?php echo $langs->trans('gr_export_type_bilan'); ?></option>
								<option value="compte_resultat" <?php if($action == 'compte_resultat'): echo 'selected=""'; endif; ?>><?php echo $langs->trans('gr_export_type_compteres'); ?></option>
								<option value="sig" <?php if($action == 'sig'): echo 'selected=""'; endif; ?>><?php echo $langs->trans('gr_export_type_sig'); ?></option>
							</select>
		        		</td>
		                <td><input type="date" name="gen-datestart" value="<?php echo $date_start; ?>"></td>
		                <td><input type="date" name="gen-dateend" value="<?php echo $date_end; ?>"></td>
		                <td>
		                	<select id="showaccountdetail" class="flat pdx" name="showaccountdetail">
								<option value="no" <?php if($showaccountdetail == '' || $showaccountdetail == 'no'): echo 'selected=""'; endif; ?>><?php echo $langs->trans('No'); ?></option>
								<option value="yes" <?php if($showaccountdetail == 'yes'): echo 'selected=""'; endif; ?>><?php echo $langs->trans('AccountWithNonZeroValues'); ?></option>
								<option value="all" <?php if($showaccountdetail == 'all'): echo 'selected=""'; endif; ?>><?php echo $langs->trans('All'); ?></option>
							</select>
		                </td>
		                <td><input type="submit" class="button pgsz-button-submit" value="<?php echo $langs->trans('gr_button_generate'); ?>" ></td>
		            </tr>
		        </tbody>
		    </table>
	    </form>

	    <?php // TABLEAU RESULTAT ?>

	    <?php if (in_array($action, $sim_actions) && !$error): 

	    	$rapport = $genrapports->tableau_resultat($date_start,$date_end,$showaccountdetail,'affichage',$action,$array_of_files); ?>

	    	<table class="noborder centpercent pgsz-option-table" style="border-top:none;" id="genrapports-tabresults">
		        <tbody>
		            <tr class="titre">
		                <td class="nobordernopadding valignmiddle col-title" style="" colspan="2">
		                    <div class="titre inline-block" style="padding:16px 0"><?php echo $langs->trans('gr_index_rapport_title'); ?> 
		                    <span class="gendate"><?php echo $langs->transnoentities('gr_index_rapport_generatetime',date('d/m/Y',$rapport['generate_time']),date('H:i',$rapport['generate_time'])); ?></span>
		                </div>
		                </td>
		                <td class="nobordernopadding valignmiddle right" colspan="13">
		                	<?php $i = 0; foreach($rapport['files'] as $label => $url_file): $i++; ?>
		                		<?php if($i == 1): $label = $langs->trans('gr_button_downloadfile_'.$label); endif; ?>
		                		<a class="button pgsz-button-small" href="<?php echo $url_file; ?>"><?php echo $label; ?></a>
		                	<?php endforeach; ?>
		                </td>
		            </tr>
		            <?php echo $rapport['tab_head']; ?>
		            <?php echo $rapport['tab_lines']; ?>
		        </tbody>
		    </table>

		    <?php //var_dump($rapport); ?>

		<?php endif; ?>

	<?php endif; ?>

</div>




<?php

// End of page
llxFooter();
$db->close();

?>
