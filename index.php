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

$version = explode('.', DOL_VERSION); // ON RECUPERE LA VERSION DE DOLIBARR

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/cactioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

require_once DOL_DOCUMENT_ROOT.'/accountancy/class/bookkeeping.class.php';
// SURCHARGE PROGISEIZE 
if(intval($version[0]) == 15): require_once 'class/bookkeepingmod_v15.class.php'; // 15 
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

// Load traductions files requiredby by page
$langs->load("companies");
$langs->load("other");

// Protection if external user
if ($user->societe_id > 0): accessforbidden(); endif;

date_default_timezone_set('Europe/Paris');

///
/**************************************************************/
/**             REQUETES SQL CONSTRUCTION BILAN             **/
/**************************************************************/
$tab_bilan = array(
		"DELETE FROM llx_c_accounting_category;",
		"INSERT INTO llx_c_accounting_category (rowid, entity, code, label, range_account, sens, category_type, formula, position, fk_country, active) VALUES 
		(10, 1, '10', '/ Capital souscrit non appelé', '', 0, 0, '', 10, 1, 1),
		(20, 1, '20', '/ Frais detablissement', '', 0, 0, '', 20, 1, 1),
		(21, 1, '21', '/ Frais de recherche et de développement', '', 0, 0, '', 21, 1, 1),
		(22, 1, '22', '/ Concessions brevets licences marques procédés droits et valeurs similaires', '', 0, 0, '', 22, 1, 1),
		(23, 1, '23', '/ Fonds commercial (dont droit au bail)', '', 0, 0, '', 23, 1, 1),
		(24, 1, '24', '/ Autres', '', 0, 0, '', 24, 1, 1),
		(25, 1, '25', '/ Avances et acomptes', '', 0, 0, '', 25, 1, 1),
		(30, 1, '30', '/ Immobilisations incorporelles', '', 0, 1, '-1*(20+21+22+23+24+25)', 30, 1, 1),
		(40, 1, '40', '/ Terrains', '', 0, 0, '', 40, 1, 1),
		(41, 1, '41', '/ Constructions', '', 0, 0, '', 41, 1, 1),
		(42, 1, '42', '/ Installations techniques matériel et outillage industriels', '', 0, 0, '', 42, 1, 1),
		(43, 1, '43', '/ Autres', '', 0, 0, '', 43, 1, 1),
		(44, 1, '44', '/ Immobilisations corporelles en cours', '', 0, 0, '', 44, 1, 1),
		(45, 1, '45', '/ Avances et acomptes', '', 0, 0, '', 45, 1, 1),
		(50, 1, '50', '/ Immobilisations corporelles', '', 0, 1, '-1*(40+41+42+43+44+45)', 50, 1, 1),
		(60, 1, '60', '/ Participations', '', 0, 0, '', 60, 1, 1),
		(61, 1, '61', '/ Créances rattachées à des participations', '', 0, 0, '', 61, 1, 1),
		(62, 1, '62', '/ Autres titres immobilisés', '', 0, 0, '', 62, 1, 1),
		(63, 1, '63', '/ Prêts', '', 0, 0, '', 63, 1, 1),
		(64, 1, '64', '/ Autres', '', 0, 0, '', 64, 1, 1),
		(70, 1, '70', '/ Immobilisations financières (dont à moins dun an)', '', 0, 1, '-1*(60+61+62+63+64)', 70, 1, 1),
		(80, 1, '80', '/ Total actifs immobilisés', '', 0, 1, '-1*(10)+30+50+70', 80, 1, 1),
		(90, 1, '90', '/ Matières premières et autres approvisionnements', '', 0, 0, '', 90, 1, 1),
		(91, 1, '91', '/ En-cours de production (biens et services ', '', 0, 0, '', 91, 1, 1),
		(92, 1, '92', '/ Produits intermédiaires et finis', '', 0, 0, '', 92, 1, 1),
		(93, 1, '93', '/ Marchandises', '', 0, 0, '', 93, 1, 1),
		(94, 1, '94', '/ Avances et acomptes versés sur commandes', '', 0, 0, '', 94, 1, 1),
		(100, 1, '100', '/ Stocks et en-cours', '', 0, 1, '-1*(90+91+92+93+94)', 100, 1, 1),
		(110, 1, '110', '/ Créances clients et comptes rattachés (ventes ou prestations de services)', '', 0, 0, '', 110, 1, 1),
		(111, 1, '111', '/ Autres', '', 0, 0, '', 111, 1, 1),
		(112, 1, '112', '/ Capital souscrit appelé non versé', '', 0, 0, '', 112, 1, 1),
		(120, 1, '120', '/ Créances (dont à plus dun an)', '', 0, 1, '-1*(110+111+112)', 120, 1, 1),
		(130, 1, '130', '/ Actions propres', '', 0, 0, '', 130, 1, 1),
		(131, 1, '131', '/ Autres titres', '', 0, 0, '', 131, 1, 1),
		(132, 1, '132', '/ Instruments de trésorerie', '', 0, 0, '', 132, 1, 1),
		(133, 1, '133', '/ Disponibilités', '', 0, 0, '', 133, 1, 1),
		(134, 1, '134', '/ Charges constatées davance (dont à plus dun an)', '', 0, 0, '', 134, 1, 1),
		(140, 1, '140', '/ Valeurs mobilières de placement', '', 0, 1, '-1*(130+131+132+133+134)', 140, 1, 1),
		(150, 1, '150', '/ TOTAL ACTIF CIRCULANT', '', 0, 1, '100+120+140', 150, 1, 1),
		(160, 1, '160', '/ Charges à répartir sur plusieurs exercices (total 3)', '', 0, 0, '', 160, 1, 1),
		(170, 1, '170', '/ Primes de remboursement des obligations (total 4)', '', 0, 0, '', 170, 1, 1),
		(180, 1, '180', '/ Ecarts de conversion Actif (total 5)', '', 0, 0, '', 180, 1, 1),
		(190, 1, '190', '/ TOTAL GÉNÉRAL BILAN ACTIF', '', 0, 1, '80+150+160+170+180', 190, 1, 1),
		(200, 1, '200', '/ Capital (dont versé)', '', 0, 0, '', 200, 1, 1),
		(201, 1, '201', '/ Primes démission de fusion ou dapport', '', 0, 0, '', 201, 1, 1),
		(202, 1, '202', '/ Ecarts de réévaluation', '', 0, 0, '', 202, 1, 1),
		(203, 1, '203', '/ Ecarts déquivalence', '', 0, 0, '', 203, 1, 1),
		(210, 1, '210', '/ Réserve légale', '', 0, 0, '', 210, 1, 1),
		(211, 1, '211', '/ Réserves statutaires ou contractuelles', '', 0, 0, '', 211, 1, 1),
		(212, 1, '212', '/ Réserves réglementées', '', 0, 0, '', 212, 1, 1),
		(213, 1, '213', '/ Autres', '', 0, 0, '', 213, 1, 1),
		(214, 1, '214', '/ Report à nouveau', '', 0, 0, '', 214, 1, 1),
		(215, 1, '215', '/ Résultat de lexercice (bénéfice ou perte)', '', 0, 0, '', 215, 1, 1),
		(216, 1, '216', '/ Subventions dinvestissement', '', 0, 0, '', 216, 1, 1),
		(217, 1, '217', '/ Provisions réglementées', '', 0, 0, '', 217, 1, 1),
		(220, 1, '220', '/ TOTAL CAPITAUX PROPRES', '', 0, 1, '200+201+202+203+210+211+212+213+214+215+216+217', 220, 1, 1),
		(240, 1, '240', '/ Produit des émissions de titres participatifs', '', 0, 0, '', 240, 1, 1),
		(241, 1, '241', '/ Avances conditionnées', '', 0, 0, '', 241, 1, 1),
		(250, 1, '250', '/ Total 1 bis', '', 0, 1, '240+241', 250, 1, 1),
		(260, 1, '260', '/ Provisions pour risques', '', 0, 0, '', 260, 1, 1),
		(261, 1, '261', '/ Provisions pour charges', '', 0, 0, '', 261, 1, 1),
		(270, 1, '270', '/ Total 2', '', 0, 1, '260+261', 270, 1, 1),
		(280, 1, '280', '/ Emprunts obligataires convertibles', '', 0, 0, '', 280, 1, 1),
		(281, 1, '281', '/ Autres emprunts obligataires', '', 0, 0, '', 281, 1, 1),
		(282, 1, '282', '/ Emprunts et dettes auprès des établissements de crédit', '', 0, 0, '', 282, 1, 1),
		(283, 1, '283', '/ Emprunts et dettes financières divers (dont emprunts participatifs)', '', 0, 0, '', 283, 1, 1),
		(284, 1, '284', '/ Avances et acomptes reçus sur commandes en cours', '', 0, 0, '', 284, 1, 1),
		(285, 1, '285', '/ Dettes fournisseurs et comptes rattachés', '', 0, 0, '', 285, 1, 1),
		(286, 1, '286', '/ Personnel', '', 0, 0, '', 286, 1, 1),
		(287, 1, '287', '/ Organismes sociaux', '', 0, 0, '', 287, 1, 1),
		(288, 1, '288', '/ Etat et taxes sur le CA', '', 0, 0, '', 288, 1, 1),
		(289, 1, '289', '/ Autres dettes fiscales et sociales', '', 0, 0, '', 289, 1, 1),
		(290, 1, '290', '/ Autres dettes', '', 0, 0, '', 290, 1, 1),
		(300, 1, '300', '/ TOTAL DETTES', '', 0, 1, '280+281+282+283+284+285+286+287+288+289+290', 300, 1, 1),
		(320, 1, '320', '/ TOTAL GÉNÉRAL PASSIF (1+1bis+2+3+4)', '', 0, 1, '220+250+270+300', 320, 1, 1);",
		"UPDATE llx_accounting_account SET fk_accounting_category = '0' WHERE 1;",
		"UPDATE llx_accounting_account SET fk_accounting_category = '10' WHERE llx_accounting_account.account_number LIKE '109%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '20' WHERE llx_accounting_account.account_number LIKE '201%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '20' WHERE llx_accounting_account.account_number LIKE '2801%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '21' WHERE llx_accounting_account.account_number LIKE '203%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '21' WHERE llx_accounting_account.account_number LIKE '2803%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '22' WHERE llx_accounting_account.account_number LIKE '205%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '22' WHERE llx_accounting_account.account_number LIKE '2805%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '22' WHERE llx_accounting_account.account_number LIKE '2905%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '23' WHERE llx_accounting_account.account_number LIKE '206%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '23' WHERE llx_accounting_account.account_number LIKE '2906%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '23' WHERE llx_accounting_account.account_number LIKE '207%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '23' WHERE llx_accounting_account.account_number LIKE '2807%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '23' WHERE llx_accounting_account.account_number LIKE '2907%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '24' WHERE llx_accounting_account.account_number LIKE '208%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '24' WHERE llx_accounting_account.account_number LIKE '2808%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '24' WHERE llx_accounting_account.account_number LIKE '2908%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '25' WHERE llx_accounting_account.account_number LIKE '237%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '40' WHERE llx_accounting_account.account_number LIKE '211%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '40' WHERE llx_accounting_account.account_number LIKE '2811%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '40' WHERE llx_accounting_account.account_number LIKE '2911%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '40' WHERE llx_accounting_account.account_number LIKE '212%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '40' WHERE llx_accounting_account.account_number LIKE '2812%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '41' WHERE llx_accounting_account.account_number LIKE '213%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '41' WHERE llx_accounting_account.account_number LIKE '2813%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '41' WHERE llx_accounting_account.account_number LIKE '214%';",		
		"UPDATE llx_accounting_account SET fk_accounting_category = '41' WHERE llx_accounting_account.account_number LIKE '2814%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '42' WHERE llx_accounting_account.account_number LIKE '215%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '42' WHERE llx_accounting_account.account_number LIKE '2815%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '43' WHERE llx_accounting_account.account_number LIKE '218%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '43' WHERE llx_accounting_account.account_number LIKE '2818%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '44' WHERE llx_accounting_account.account_number LIKE '231%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '44' WHERE llx_accounting_account.account_number LIKE '2931%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '45' WHERE llx_accounting_account.account_number LIKE '238%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '60' WHERE llx_accounting_account.account_number LIKE '261%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '60' WHERE llx_accounting_account.account_number LIKE '2961%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '60' WHERE llx_accounting_account.account_number LIKE '266%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '60' WHERE llx_accounting_account.account_number LIKE '2966%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '61' WHERE llx_accounting_account.account_number LIKE '267%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '61' WHERE llx_accounting_account.account_number LIKE '268%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '61' WHERE llx_accounting_account.account_number LIKE '2968%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '61' WHERE llx_accounting_account.account_number LIKE '2967%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '62' WHERE llx_accounting_account.account_number LIKE '271%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '62' WHERE llx_accounting_account.account_number LIKE '2971%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '62' WHERE llx_accounting_account.account_number LIKE '272%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '62' WHERE llx_accounting_account.account_number LIKE '2972%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '62' WHERE llx_accounting_account.account_number LIKE '27682%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '63' WHERE llx_accounting_account.account_number LIKE '274%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '63' WHERE llx_accounting_account.account_number LIKE '2974%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '63' WHERE llx_accounting_account.account_number LIKE '27684%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '64' WHERE llx_accounting_account.account_number LIKE '275%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '64' WHERE llx_accounting_account.account_number LIKE '2975%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '64' WHERE llx_accounting_account.account_number LIKE '2761%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '64' WHERE llx_accounting_account.account_number LIKE '2976%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '64' WHERE llx_accounting_account.account_number LIKE '27685%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '64' WHERE llx_accounting_account.account_number LIKE '27688%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '90' WHERE llx_accounting_account.account_number LIKE '31%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '90' WHERE llx_accounting_account.account_number LIKE '391%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '90' WHERE llx_accounting_account.account_number LIKE '32%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '90' WHERE llx_accounting_account.account_number LIKE '392%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '91' WHERE llx_accounting_account.account_number LIKE '33%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '91' WHERE llx_accounting_account.account_number LIKE '393%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '91' WHERE llx_accounting_account.account_number LIKE '34%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '91' WHERE llx_accounting_account.account_number LIKE '394%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '92' WHERE llx_accounting_account.account_number LIKE '35%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '92' WHERE llx_accounting_account.account_number LIKE '395%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '93' WHERE llx_accounting_account.account_number LIKE '37%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '93' WHERE llx_accounting_account.account_number LIKE '397%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '94' WHERE llx_accounting_account.account_number LIKE '4091%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '110' WHERE llx_accounting_account.account_number LIKE '41188%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '110' WHERE llx_accounting_account.account_number LIKE '491%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '110' WHERE llx_accounting_account.account_number LIKE '413%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '110' WHERE llx_accounting_account.account_number LIKE '416%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '110' WHERE llx_accounting_account.account_number LIKE '417%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '110' WHERE llx_accounting_account.account_number LIKE '418%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '111' WHERE llx_accounting_account.account_number LIKE '40188%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '111' WHERE llx_accounting_account.account_number LIKE '4096%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '111' WHERE llx_accounting_account.account_number LIKE '495%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '111' WHERE llx_accounting_account.account_number LIKE '496%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '111' WHERE llx_accounting_account.account_number LIKE '4097%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '111' WHERE llx_accounting_account.account_number LIKE '4098%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '111' WHERE llx_accounting_account.account_number LIKE '425%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '111' WHERE llx_accounting_account.account_number LIKE '4287%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '111' WHERE llx_accounting_account.account_number LIKE '4387%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '111' WHERE llx_accounting_account.account_number LIKE '441%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '111' WHERE llx_accounting_account.account_number LIKE '443%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '111' WHERE llx_accounting_account.account_number LIKE '444%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '111' WHERE llx_accounting_account.account_number LIKE '4456%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '111' WHERE llx_accounting_account.account_number LIKE '4457%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '111' WHERE llx_accounting_account.account_number LIKE '44581%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '111' WHERE llx_accounting_account.account_number LIKE '44582%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '111' WHERE llx_accounting_account.account_number LIKE '44583%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '111' WHERE llx_accounting_account.account_number LIKE '44586%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '111' WHERE llx_accounting_account.account_number LIKE '4487%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '111' WHERE llx_accounting_account.account_number LIKE '451%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '111' WHERE llx_accounting_account.account_number LIKE '456';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '111' WHERE llx_accounting_account.account_number LIKE '4560%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '111' WHERE llx_accounting_account.account_number LIKE '458%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '111' WHERE llx_accounting_account.account_number LIKE '462%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '111' WHERE llx_accounting_account.account_number LIKE '465%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '111' WHERE llx_accounting_account.account_number LIKE '467';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '111' WHERE llx_accounting_account.account_number LIKE '4670%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '111' WHERE llx_accounting_account.account_number LIKE '4673%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '111' WHERE llx_accounting_account.account_number LIKE '4687%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '111' WHERE llx_accounting_account.account_number LIKE '478%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '111' WHERE llx_accounting_account.account_number LIKE '503%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '111' WHERE llx_accounting_account.account_number LIKE '44585%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '111' WHERE llx_accounting_account.account_number LIKE '4672%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '112' WHERE llx_accounting_account.account_number LIKE '4562%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '130' WHERE llx_accounting_account.account_number LIKE '502%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '131' WHERE llx_accounting_account.account_number LIKE '50';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '131' WHERE llx_accounting_account.account_number LIKE '59%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '131' WHERE llx_accounting_account.account_number LIKE '500%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '132' WHERE llx_accounting_account.account_number LIKE '52%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '133' WHERE llx_accounting_account.account_number LIKE '51';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '133' WHERE llx_accounting_account.account_number LIKE '510%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '133' WHERE llx_accounting_account.account_number LIKE '53%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '133' WHERE llx_accounting_account.account_number LIKE '54%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '133' WHERE llx_accounting_account.account_number LIKE '512%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '133' WHERE llx_accounting_account.account_number LIKE '517%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '133' WHERE llx_accounting_account.account_number LIKE '518%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '134' WHERE llx_accounting_account.account_number LIKE '486%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '160' WHERE llx_accounting_account.account_number LIKE '481%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '170' WHERE llx_accounting_account.account_number LIKE '169%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '180' WHERE llx_accounting_account.account_number LIKE '476%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '200' WHERE llx_accounting_account.account_number LIKE '101%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '200' WHERE llx_accounting_account.account_number LIKE '108%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '201' WHERE llx_accounting_account.account_number LIKE '104%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '202' WHERE llx_accounting_account.account_number LIKE '105%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '203' WHERE llx_accounting_account.account_number LIKE '107%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '203' WHERE llx_accounting_account.account_number LIKE '1057%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '210' WHERE llx_accounting_account.account_number LIKE '1061%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '211' WHERE llx_accounting_account.account_number LIKE '1063%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '212' WHERE llx_accounting_account.account_number LIKE '1062%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '212' WHERE llx_accounting_account.account_number LIKE '1064%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '213' WHERE llx_accounting_account.account_number LIKE '1068%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '214' WHERE llx_accounting_account.account_number LIKE '11%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '215' WHERE llx_accounting_account.account_number LIKE '12%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '216' WHERE llx_accounting_account.account_number LIKE '13%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '217' WHERE llx_accounting_account.account_number LIKE '14%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '240' WHERE llx_accounting_account.account_number LIKE '1671%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '241' WHERE llx_accounting_account.account_number LIKE '1674%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '261' WHERE llx_accounting_account.account_number LIKE '15%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '260' WHERE llx_accounting_account.account_number LIKE '151%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '283' WHERE llx_accounting_account.account_number LIKE '165%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '283' WHERE llx_accounting_account.account_number LIKE '166%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '283' WHERE llx_accounting_account.account_number LIKE '1675%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '283' WHERE llx_accounting_account.account_number LIKE '168%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '283' WHERE llx_accounting_account.account_number LIKE '17%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '283' WHERE llx_accounting_account.account_number LIKE '426%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '283' WHERE llx_accounting_account.account_number LIKE '45';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '283' WHERE llx_accounting_account.account_number LIKE '450%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '283' WHERE llx_accounting_account.account_number LIKE '455%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '280' WHERE llx_accounting_account.account_number LIKE '161%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '280' WHERE llx_accounting_account.account_number LIKE '16881%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '281' WHERE llx_accounting_account.account_number LIKE '163%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '281' WHERE llx_accounting_account.account_number LIKE '16883%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '282' WHERE llx_accounting_account.account_number LIKE '164%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '282' WHERE llx_accounting_account.account_number LIKE '16884%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '282' WHERE llx_accounting_account.account_number LIKE '514%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '282' WHERE llx_accounting_account.account_number LIKE '5186%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '282' WHERE llx_accounting_account.account_number LIKE '519%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '282' WHERE llx_accounting_account.account_number LIKE '5124';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '282' WHERE llx_accounting_account.account_number LIKE '51240%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '284' WHERE llx_accounting_account.account_number LIKE '4191%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '285' WHERE llx_accounting_account.account_number LIKE '40199%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '285' WHERE llx_accounting_account.account_number LIKE '403%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '285' WHERE llx_accounting_account.account_number LIKE '4081%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '285' WHERE llx_accounting_account.account_number LIKE '4088%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '286' WHERE llx_accounting_account.account_number LIKE '421%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '286' WHERE llx_accounting_account.account_number LIKE '422%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '286' WHERE llx_accounting_account.account_number LIKE '424%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '286' WHERE llx_accounting_account.account_number LIKE '427%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '286' WHERE llx_accounting_account.account_number LIKE '4282%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '286' WHERE llx_accounting_account.account_number LIKE '4284%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '286' WHERE llx_accounting_account.account_number LIKE '4286%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '287' WHERE llx_accounting_account.account_number LIKE '43';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '287' WHERE llx_accounting_account.account_number LIKE '430%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '287' WHERE llx_accounting_account.account_number LIKE '431%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '287' WHERE llx_accounting_account.account_number LIKE '437%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '287' WHERE llx_accounting_account.account_number LIKE '4382%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '287' WHERE llx_accounting_account.account_number LIKE '4386%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '288' WHERE llx_accounting_account.account_number LIKE '4452%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '288' WHERE llx_accounting_account.account_number LIKE '4455%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '288' WHERE llx_accounting_account.account_number LIKE '44580%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '288' WHERE llx_accounting_account.account_number LIKE '44584%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '288' WHERE llx_accounting_account.account_number LIKE '44587%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '289' WHERE llx_accounting_account.account_number LIKE '442%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '289' WHERE llx_accounting_account.account_number LIKE '446%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '289' WHERE llx_accounting_account.account_number LIKE '447%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '289' WHERE llx_accounting_account.account_number LIKE '4482%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '289' WHERE llx_accounting_account.account_number LIKE '4486%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '290' WHERE llx_accounting_account.account_number LIKE '457%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '290' WHERE llx_accounting_account.account_number LIKE '269%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '290' WHERE llx_accounting_account.account_number LIKE '279%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '290' WHERE llx_accounting_account.account_number LIKE '404%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '290' WHERE llx_accounting_account.account_number LIKE '405%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '290' WHERE llx_accounting_account.account_number LIKE '4084%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '290' WHERE llx_accounting_account.account_number LIKE '4088';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '290' WHERE llx_accounting_account.account_number LIKE '41199%';",		
		"UPDATE llx_accounting_account SET fk_accounting_category = '290' WHERE llx_accounting_account.account_number LIKE '4196%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '290' WHERE llx_accounting_account.account_number LIKE '4197%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '290' WHERE llx_accounting_account.account_number LIKE '4198%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '290' WHERE llx_accounting_account.account_number LIKE '464%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '290' WHERE llx_accounting_account.account_number LIKE '4686%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '290' WHERE llx_accounting_account.account_number LIKE '4671%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '290' WHERE llx_accounting_account.account_number LIKE '467AB%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '290' WHERE llx_accounting_account.account_number LIKE '509%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '290' WHERE llx_accounting_account.account_number LIKE '487%';",
		"UPDATE llx_accounting_account SET fk_accounting_category = '290' WHERE llx_accounting_account.account_number LIKE '477%';",
	);

/**************************************************************/
/**        REQUETES SQL CONSTRUCTION COMPTE RESULTAT        **/
/**************************************************************/
$tab_compteresult = array(
	"DELETE FROM llx_c_accounting_category;",
	"INSERT INTO llx_c_accounting_category (rowid, entity, code, label, range_account, sens, category_type, formula, position, fk_country, active) 
	VALUES 
	(10, 1, '10', '/ Ventes de marchandises', '', 0, 0, '', 10, 1, 1),
	(11, 1, '11', '/ Biens vendus', '', 0, 0, '', 11, 1, 1),
	(12, 1, '12', '/ Services vendus', '', 0, 0, '', 12, 1, 1),
	(20, 1, '20', '/ CA net', '', 0, 1, '10+11+12', 20, 1, 1),
	(30, 1, '30', '/ Production stockée', '', 0, 0, '', 30, 1, 1),
	(31, 1, '31', '/ Production immobilisée', '', 0, 0, '', 31, 1, 1),
	(32, 1, '32', '/ Subventions dexploitation', '', 0, 0, '', 32, 1, 1),
	(33, 1, '33', '/ Reprises sur provisions', '', 0, 0, '', 33, 1, 1),
	(34, 1, '34', '/ Autres produits', '', 0, 0, '', 34, 1, 1),
	(40, 1, '40', '/ Total produits dexploitation (I)', '', 0, 1, '30+31+32+33+34+20', 40, 1, 1),
	(50, 1, '50', '/ Achats de marchandises', '', 0, 0, '', 50, 1, 1),
	(51, 1, '51', '/ Variation de stocks de marchandises', '', 0, 0, '', 51, 1, 1),
	(52, 1, '52', '/ Achats de matières premières', '', 0, 0, '', 52, 1, 1),
	(53, 1, '53', '/ Variations de stocks', '', 0, 0, '', 53, 1, 1),
	(54, 1, '54', '/ Autres charges externes', '', 0, 0, '', 54, 1, 1),
	(55, 1, '55', '/ Impôts taxes et versements assimilés', '', 0, 0, '', 55, 1, 1),
	(56, 1, '56', '/ Salaires et traitements', '', 0, 0, '', 56, 1, 1),
	(57, 1, '57', '/ Charges sociales', '', 0, 0, '', 57, 1, 1),
	(58, 1, '58', '/ Dotations aux amortissements et dépréciations', '', 0, 0, '', 58, 1, 1),
	(59, 1, '59', '/ Dotations aux provisions', '', 0, 0, '', 59, 1, 1),
	(60, 1, '60', '/ Autres charges', '', 0, 0, '', 60, 1, 1),
	(70, 1, '70', '/ Total charges dexploitation (II)', '', 0, 1, '50+51+52+53+54+55+56+57+58+59+60', 70, 1, 1),
	(80, 1, '80', '/ RESULTAT DEXPLOITATION (I-II)', '', 0, 1, '40+70', 80, 1, 1),
	(90, 1, '90', '/ Bénéfice attribué ou perte transférée (III)', '', 0, 0, '', 90, 1, 1),
	(91, 1, '91', '/ Perte supportée ou bénéfice transféré (IV)', '', 0, 0, '', 91, 1, 1),
	(92, 1, '92', '/ Produits financiers de participation', '', 0, 0, '', 92, 1, 1),
	(93, 1, '93', '/ Produits financiers dautres valeurs mobilières et créances de lactif immobilisé', '', 0, 0, '', 93, 1, 1),
	(94, 1, '94', '/ Autres intérêts et produits assimilés', '', 0, 0, '', 94, 1, 1),
	(95, 1, '95', '/ Reprises sur provisions et dépréciations et transferts de charges', '', 0, 0, '', 95, 1, 1),
	(96, 1, '96', '/ Différences positives de change', '', 0, 0, '', 96, 1, 1),
	(97, 1, '97', '/ Produits nets sur cessions de valeurs mobilières de placement', '', 0, 0, '', 97, 1, 1),
	(100, 1, '100', '/ Total produits financiers (V)', '', 0, 1, '92+93+94+95+96+97', 100, 1, 1),
	(110, 1, '110', '/ Dotations aux amortissements aux dépréciations et aux provisions', '', 0, 0, '', 110, 1, 1),
	(111, 1, '111', '/ Intérêts et charges assimilées', '', 0, 0, '', 111, 1, 1),
	(112, 1, '112', '/ Différences négatives de change', '', 0, 0, '', 112, 1, 1),
	(113, 1, '113', '/ Charges nettes sur cessions de valeurs mobilières de placement', '', 0, 0, '', 113, 1, 1),
	(120, 1, '120', '/ Total charges financières (VI)', '', 0, 1, '110+111+112+113', 120, 1, 1),
	(130, 1, '130', '/ Resultat financier (V-VI)', '', 0, 1, '100+120', 130, 1, 1),
	(140, 1, '140', '/ RESULTAT COURANT avant impôts (I-II+III-IV+V-VI)', '', 0, 1, '40+70+90+91+100+120', 140, 1, 1),
	(150, 1, '150', '/ Produits exceptionnels sur opérations de gestion', '', 0, 0, '', 150, 1, 1),
	(151, 1, '151', '/ Produits exceptionnels sur opérations en capital', '', 0, 0, '', 151, 1, 1),
	(152, 1, '152', '/ Reprises sur provisions et dépréciation et transferts de charges', '', 0, 0, '', 152, 1, 1),
	(160, 1, '160', '/ Total produits exceptionnels (VII)', '', 0, 1, '150+151+152', 160, 1, 1),
	(170, 1, '170', '/ Charges exceptionnelles sur opérations de gestion', '', 0, 0, '', 170, 1, 1),
	(171, 1, '171', '/ Charges exceptionnelles sur opérations en capital', '', 0, 0, '', 171, 1, 1),
	(172, 1, '172', '/ Dotations aux amortissements aux dépréciations et aux provisions', '', 0, 0, '', 172, 1, 1),
	(180, 1, '180', '/ Total charges exceptionnelles (VIII)', '', 0, 1, '170+171+172', 180, 1, 1),
	(190, 1, '190', '/ RESULTAT EXCEPTIONNEL (VII-VIII)', '', 0, 1, '160+180', 190, 1, 1),
	(200, 1, '200', '/ Participation des salariés aux résultats (IX)', '', 0, 0, '', 200, 1, 1),
	(210, 1, '210', '/ Impôts sur les bénéfices (X)', '', 0, 0, '', 210, 1, 1),
	(220, 1, '220', '/ Total des produits (I+III+V+VII)', '', 0, 1, '40+90+100+160', 220, 1, 1),
	(230, 1, '230', '/ Total des charges (II+IV+VI+VIII+IX+X)', '', 0, 1, '70+91+120+180+200+210', 230, 1, 1),
	(240, 1, '240', '/ BENEFICE OU PERTE', '', 0, 1, '220+230', 240, 1, 1);",
	"UPDATE llx_accounting_account SET fk_accounting_category = '0' WHERE 1;",
	"UPDATE llx_accounting_account SET fk_accounting_category = '34' WHERE llx_accounting_account.account_number LIKE '7%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '10' WHERE llx_accounting_account.account_number LIKE '707%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '10' WHERE llx_accounting_account.account_number LIKE '709%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '11' WHERE llx_accounting_account.account_number LIKE '701%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '11' WHERE llx_accounting_account.account_number LIKE '702%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '11' WHERE llx_accounting_account.account_number LIKE '703%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '12' WHERE llx_accounting_account.account_number LIKE '704%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '12' WHERE llx_accounting_account.account_number LIKE '705%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '12' WHERE llx_accounting_account.account_number LIKE '706%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '12' WHERE llx_accounting_account.account_number LIKE '708%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '30' WHERE llx_accounting_account.account_number LIKE '713%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '31' WHERE llx_accounting_account.account_number LIKE '720%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '31' WHERE llx_accounting_account.account_number LIKE '730%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '32' WHERE llx_accounting_account.account_number LIKE '740%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '33' WHERE llx_accounting_account.account_number LIKE '781%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '33' WHERE llx_accounting_account.account_number LIKE '791%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '60' WHERE llx_accounting_account.account_number LIKE '6%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '50' WHERE llx_accounting_account.account_number LIKE '607%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '50' WHERE llx_accounting_account.account_number LIKE '6087%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '50' WHERE llx_accounting_account.account_number LIKE '6097%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '51' WHERE llx_accounting_account.account_number LIKE '6037%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '52' WHERE llx_accounting_account.account_number LIKE '601%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '52' WHERE llx_accounting_account.account_number LIKE '602%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '52' WHERE llx_accounting_account.account_number LIKE '6081%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '52' WHERE llx_accounting_account.account_number LIKE '6082%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '52' WHERE llx_accounting_account.account_number LIKE '6091%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '52' WHERE llx_accounting_account.account_number LIKE '6092%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '53' WHERE llx_accounting_account.account_number LIKE '6031%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '53' WHERE llx_accounting_account.account_number LIKE '6032%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '54' WHERE llx_accounting_account.account_number LIKE '604%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '54' WHERE llx_accounting_account.account_number LIKE '605%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '54' WHERE llx_accounting_account.account_number LIKE '606%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '54' WHERE llx_accounting_account.account_number LIKE '6084%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '54' WHERE llx_accounting_account.account_number LIKE '6085%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '54' WHERE llx_accounting_account.account_number LIKE '6086%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '54' WHERE llx_accounting_account.account_number LIKE '6094%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '54' WHERE llx_accounting_account.account_number LIKE '6095%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '54' WHERE llx_accounting_account.account_number LIKE '6096%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '54' WHERE llx_accounting_account.account_number LIKE '61%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '54' WHERE llx_accounting_account.account_number LIKE '62%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '55' WHERE llx_accounting_account.account_number LIKE '63%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '56' WHERE llx_accounting_account.account_number LIKE '641%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '56' WHERE llx_accounting_account.account_number LIKE '644%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '56' WHERE llx_accounting_account.account_number LIKE '648%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '57' WHERE llx_accounting_account.account_number LIKE '645%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '57' WHERE llx_accounting_account.account_number LIKE '646%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '57' WHERE llx_accounting_account.account_number LIKE '647%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '57' WHERE llx_accounting_account.account_number LIKE '648%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '58' WHERE llx_accounting_account.account_number LIKE '6811%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '58' WHERE llx_accounting_account.account_number LIKE '6812%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '59' WHERE llx_accounting_account.account_number LIKE '6815%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '59' WHERE llx_accounting_account.account_number LIKE '6816%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '59' WHERE llx_accounting_account.account_number LIKE '6817%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '90' WHERE llx_accounting_account.account_number LIKE '755%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '91' WHERE llx_accounting_account.account_number LIKE '655%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '92' WHERE llx_accounting_account.account_number LIKE '761%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '93' WHERE llx_accounting_account.account_number LIKE '762%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '94' WHERE llx_accounting_account.account_number LIKE '763%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '94' WHERE llx_accounting_account.account_number LIKE '764%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '94' WHERE llx_accounting_account.account_number LIKE '765%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '94' WHERE llx_accounting_account.account_number LIKE '768%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '95' WHERE llx_accounting_account.account_number LIKE '786%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '95' WHERE llx_accounting_account.account_number LIKE '796%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '96' WHERE llx_accounting_account.account_number LIKE '766%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '97' WHERE llx_accounting_account.account_number LIKE '767%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '110' WHERE llx_accounting_account.account_number LIKE '686%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '111' WHERE llx_accounting_account.account_number LIKE '661%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '111' WHERE llx_accounting_account.account_number LIKE '664%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '111' WHERE llx_accounting_account.account_number LIKE '665%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '111' WHERE llx_accounting_account.account_number LIKE '668%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '112' WHERE llx_accounting_account.account_number LIKE '666%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '113' WHERE llx_accounting_account.account_number LIKE '667%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '150' WHERE llx_accounting_account.account_number LIKE '771%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '151' WHERE llx_accounting_account.account_number LIKE '775%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '151' WHERE llx_accounting_account.account_number LIKE '777%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '151' WHERE llx_accounting_account.account_number LIKE '778%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '152' WHERE llx_accounting_account.account_number LIKE '787%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '152' WHERE llx_accounting_account.account_number LIKE '798%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '170' WHERE llx_accounting_account.account_number LIKE '671%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '171' WHERE llx_accounting_account.account_number LIKE '675%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '171' WHERE llx_accounting_account.account_number LIKE '678%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '172' WHERE llx_accounting_account.account_number LIKE '687%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '200' WHERE llx_accounting_account.account_number LIKE '691%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '210' WHERE llx_accounting_account.account_number LIKE '695%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '210' WHERE llx_accounting_account.account_number LIKE '697%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '210' WHERE llx_accounting_account.account_number LIKE '689%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '210' WHERE llx_accounting_account.account_number LIKE '698%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '210' WHERE llx_accounting_account.account_number LIKE '699%';",
	"UPDATE llx_accounting_account SET fk_accounting_category = '210' WHERE llx_accounting_account.account_number LIKE '789%';"
);

/**************************************************************/
/**        REQUETES SQL CONSTRUCTION SIG        **/
/**************************************************************/

$tab_sig = array(
	"DELETE t1 FROM llx_accounting_account AS t1, llx_accounting_account AS t2 WHERE t1.rowid > t2.rowid AND t1.account_number = t2.account_number;",
	"DELETE FROM `llx_c_accounting_category`;",
	"INSERT INTO `llx_c_accounting_category` (`rowid`, `entity`, `code`, `label`, `range_account`, `sens`, `category_type`, `formula`, `position`, `fk_country`, `active`) VALUES 
	(10, 1, '10', '/ Production vendue de biens', '', 0, 0, '', 10, 1, 1),
	(12, 1, '12', '/ Production vendue de service', '', 0, 0, '', 12, 1, 1),
	(20, 1, '20', '/ TOTAL PRODUCTION VENDUE', '', 0, 1, '10+12', 20, 1, 1),
	(30, 1, '30', '/ Ventes de marchandises', '', 0, 0, '', 30, 1, 1),
	(31, 1, '31', '/ Ventes de marchandises Export', '', 0, 0, '', 31, 1, 1),
	(40, 1, '40', '/ TOTAL MARCHANDISES VENDUES', '', 0, 1, '30+31', 40, 1, 1),
	(60, 1, '60', '/ TOTAL PRODUITS EXPLOITATION', '', 0, 1, '20+40', 60, 1, 1),
	(90, 1, '90', '/ Achats de sous-traitance', '', 0, 0, '', 90, 1, 1),
	(91, 1, '91', '/ Transport (ventes et achats)', '', 0, 0, '', 91, 1, 1),
	(100, 1, '100', '/ TOTAL ACHATS + STT', '', 0, 1, '90+91', 100, 1, 1),
	(110, 1, '110', '/ Achats de marchandises', '', 0, 0, '', 110, 1, 1),
	(111, 1, '111', '/ Variations de stock de marchandises', '', 0, 0, '', 111, 1, 1),
	(120, 1, '120', '/ TOTAL ACHATS MARCHANDISES', '', 0, 1, '110+111', 120, 1, 1),
	(130, 1, '130', '/ MARGE BRUTE SUR PRODUCTION', '', 0, 1, '20', 130, 1, 1),
	(140, 1, '140', '/ MARGE BRUTE SUR MARCHANDISES', '', 0, 1, '40+120+90+91', 140, 1, 1),
	(150, 1, '150', '/ MARGE BRUTE TOTALE', '', 0, 1, '130+140', 150, 1, 1),
	(160, 1, '160', '/ Taux de marge brute totale', '', 0, 1, '150/ 60', 160, 1, 1),
	(164, 1, '164', '/ Energie', '', 0, 0, '', 164, 1, 1),
	(165, 1, '165', '/ Petit equipement ', '', 0, 0, '', 165, 1, 1),
	(166, 1, '166', '/ Autres achats et charges externes', '', 0, 0, '', 166, 1, 1),
	(167, 1, '167', '/ Sous traitance diverse (hors production)', '', 0, 0, '', 167, 1, 1),
	(168, 1, '168', '/ Locations immobilières et charges locatives', '', 0, 0, '', 168, 1, 1),
	(170, 1, '170', '/ Locations et entretien vehicules', '', 0, 0, '', 170, 1, 1),
	(172, 1, '172', '/ Leasings mobiliers', '', 0, 0, '', 172, 1, 1),
	(175, 1, '175', '/ Maintenance informatique & télécommunications', '', 0, 0, '', 175, 1, 1),
	(176, 1, '176', '/ Site web', '', 0, 0, '', 176, 1, 1),
	(177, 1, '177', '/ Assurances', '', 0, 0, '', 177, 1, 1),
	(178, 1, '178', '/ Honoraires', '', 0, 0, '', 178, 1, 1),
	(180, 1, '180', '/ Déplacements', '', 0, 0, '', 180, 1, 1),
	(181, 1, '181', '/ Frais services bancaires', '', 0, 0, '', 181, 1, 1),
	(182, 1, '182', '/ Commissions affacturage', '', 0, 0, '', 182, 1, 1),
	(190, 1, '190', '/ TOTAL CHARGES EXTERNES', '', 0, 1, '164+165+166+167+168+170+172+175+176+177+178+180+181+182', 190, 1, 1),
	(200, 1, '200', '/ VALEUR AJOUTEE', '', 0, 1, '150+190', 200, 1, 1),
	(210, 1, '210', '/ Salaires et charges indirectes', '', 0, 0, '', 210, 1, 1),
	(220, 1, '220', '/ TOTAL INDIRECTS', '', 0, 1, '210', 220, 1, 1),
	(231, 1, '231', '/ Interims directs', '', 0, 0, '', 231, 1, 1),
	(240, 1, '240', '/ TOTAL DIRECTS', '', 0, 1, '231', 240, 1, 1),
	(250, 1, '250', '/ Produits & charges diverses de personnel', '', 0, 0, '', 250, 1, 1),
	(260, 1, '260', '/ TOTAL FRAIS DE PERSONNEL', '', 0, 1, '220+240+250', 260, 1, 1),
	(280, 1, '280', '/ TOTAL FRAIS DE PERSONNEL DEXPLOITATION', '', 0, 1, '260', 280, 1, 1),
	(290, 1, '290', '/ Subventions dexploitation', '', 0, 0, '', 290, 1, 1),
	(292, 1, '292', '/ Impôts et taxes', '', 0, 0, '', 292, 1, 1),
	(300, 1, '300', '/ EXCEDENT BRUT EXPLOITATION', '', 0, 1, '200+280+290+292', 300, 1, 1),
	(310, 1, '310', '/ EBITDA', '', 0, 1, '300+(-1)*292', 310, 1, 1),
	(320, 1, '320', '/ Autres produits dexploitation', '', 0, 0, '', 320, 1, 1),
	(321, 1, '321', '/ Immobilisations incorporelles', '', 0, 0, '', 321, 1, 1),
	(322, 1, '322', '/ Immobilisations corporelles', '', 0, 0, '', 322, 1, 1),
	(323, 1, '323', '/ Dotations aux provisions exceptionnelles', '', 0, 0, '', 323, 1, 1),
	(324, 1, '324', '/ Autres charges dexploitation', '', 0, 0, '', 324, 1, 1),
	(330, 1, '330', '/ RESULTAT DEXPLOITATION', '', 0, 1, '300+320+321+322+323+324', 330, 1, 1),
	(340, 1, '340', '/ Interêts sur emprunts', '', 0, 0, '', 340, 1, 1),
	(343, 1, '343', '/ Produits financiers', '', 0, 0, '', 343, 1, 1),
	(350, 1, '350', '/ RESULTAT FINANCIER', '', 0, 1, '340+343', 350, 1, 1),
	(360, 1, '360', '/ RESULTAT COURANT', '', 0, 1, '330+350', 360, 1, 1),
	(370, 1, '370', '/ Charges exceptionnelles', '', 0, 0, '', 370, 1, 1),
	(380, 1, '380', '/ RESULTAT EXCEPTIONNEL', '', 0, 1, '370', 380, 1, 1),
	(390, 1, '390', '/ RESULTAT NET  AVANT IMPÔTS', '', 0, 1, '360+380', 390, 1, 1),
	(410, 1, '410', '/ RESULTAT NET APRES IMPÔTS', '', 0, 1, '390', 410, 1, 1),
	(420, 1, '420', '/ EBIT', '', 0, 1, '390+(-1)*350+(-0.15)*172+(-0.15)*168+(-1)*321', 420, 1, 1),
	(430, 1, '430', '/ CAPACITE AUTOFINANCEMENT', '', 0, 1, '410', 430, 1, 1);",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '0' WHERE 1;",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '10' WHERE `llx_accounting_account`.`account_number` LIKE '701%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '12' WHERE `llx_accounting_account`.`account_number` LIKE '706%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '12' WHERE `llx_accounting_account`.`account_number` LIKE '7083%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '30' WHERE `llx_accounting_account`.`account_number` LIKE '707';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '30' WHERE `llx_accounting_account`.`account_number` LIKE '7070%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '30' WHERE `llx_accounting_account`.`account_number` LIKE '7071%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '30' WHERE `llx_accounting_account`.`account_number` LIKE '70791%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '30' WHERE `llx_accounting_account`.`account_number` LIKE '7085%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '30' WHERE `llx_accounting_account`.`account_number` LIKE '7089%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '30' WHERE `llx_accounting_account`.`account_number` LIKE '70891%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '30' WHERE `llx_accounting_account`.`account_number` LIKE '709%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '30' WHERE `llx_accounting_account`.`account_number` LIKE '7097%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '31' WHERE `llx_accounting_account`.`account_number` LIKE '7079';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '31' WHERE `llx_accounting_account`.`account_number` LIKE '70790%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '90' WHERE `llx_accounting_account`.`account_number` LIKE '611%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '90' WHERE `llx_accounting_account`.`account_number` LIKE '6041%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '91' WHERE `llx_accounting_account`.`account_number` LIKE '6241%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '91' WHERE `llx_accounting_account`.`account_number` LIKE '6242%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '91' WHERE `llx_accounting_account`.`account_number` LIKE '6244%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '110' WHERE `llx_accounting_account`.`account_number` LIKE '607%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '110' WHERE `llx_accounting_account`.`account_number` LIKE '609%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '111' WHERE `llx_accounting_account`.`account_number` LIKE '6037%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '164' WHERE `llx_accounting_account`.`account_number` LIKE '60611%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '164' WHERE `llx_accounting_account`.`account_number` LIKE '60612%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '165' WHERE `llx_accounting_account`.`account_number` LIKE '6063%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '166' WHERE `llx_accounting_account`.`account_number` LIKE '606';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '166' WHERE `llx_accounting_account`.`account_number` LIKE '6060%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '166' WHERE `llx_accounting_account`.`account_number` LIKE '6064%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '166' WHERE `llx_accounting_account`.`account_number` LIKE '6181%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '166' WHERE `llx_accounting_account`.`account_number` LIKE '623';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '166' WHERE `llx_accounting_account`.`account_number` LIKE '6230%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '166' WHERE `llx_accounting_account`.`account_number` LIKE '6234%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '166' WHERE `llx_accounting_account`.`account_number` LIKE '6236%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '166' WHERE `llx_accounting_account`.`account_number` LIKE '6238%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '166' WHERE `llx_accounting_account`.`account_number` LIKE '651%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '167' WHERE `llx_accounting_account`.`account_number` LIKE '604';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '167' WHERE `llx_accounting_account`.`account_number` LIKE '6040%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '168' WHERE `llx_accounting_account`.`account_number` LIKE '6132%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '168' WHERE `llx_accounting_account`.`account_number` LIKE '6152%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '170' WHERE `llx_accounting_account`.`account_number` LIKE '61361%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '170' WHERE `llx_accounting_account`.`account_number` LIKE '61362%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '170' WHERE `llx_accounting_account`.`account_number` LIKE '61368%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '170' WHERE `llx_accounting_account`.`account_number` LIKE '61552%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '172' WHERE `llx_accounting_account`.`account_number` LIKE '615';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '172' WHERE `llx_accounting_account`.`account_number` LIKE '6150%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '172' WHERE `llx_accounting_account`.`account_number` LIKE '6155';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '172' WHERE `llx_accounting_account`.`account_number` LIKE '61550%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '175' WHERE `llx_accounting_account`.`account_number` LIKE '613';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '175' WHERE `llx_accounting_account`.`account_number` LIKE '6130%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '175' WHERE `llx_accounting_account`.`account_number` LIKE '6131%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '175' WHERE `llx_accounting_account`.`account_number` LIKE '61352%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '175' WHERE `llx_accounting_account`.`account_number` LIKE '6156%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '175' WHERE `llx_accounting_account`.`account_number` LIKE '6261%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '175' WHERE `llx_accounting_account`.`account_number` LIKE '6262%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '175' WHERE `llx_accounting_account`.`account_number` LIKE '626%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '176' WHERE `llx_accounting_account`.`account_number` LIKE '6231%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '177' WHERE `llx_accounting_account`.`account_number` LIKE '616%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '178' WHERE `llx_accounting_account`.`account_number` LIKE '6226%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '178' WHERE `llx_accounting_account`.`account_number` LIKE '6227%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '178' WHERE `llx_accounting_account`.`account_number` LIKE '6281%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '180' WHERE `llx_accounting_account`.`account_number` LIKE '60613%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '180' WHERE `llx_accounting_account`.`account_number` LIKE '6251%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '180' WHERE `llx_accounting_account`.`account_number` LIKE '6257%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '181' WHERE `llx_accounting_account`.`account_number` LIKE '627%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '181' WHERE `llx_accounting_account`.`account_number` LIKE '6273%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '181' WHERE `llx_accounting_account`.`account_number` LIKE '6275%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '181' WHERE `llx_accounting_account`.`account_number` LIKE '6272%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '182' WHERE `llx_accounting_account`.`account_number` LIKE '6271%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '210' WHERE `llx_accounting_account`.`account_number` LIKE '6411%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '210' WHERE `llx_accounting_account`.`account_number` LIKE '6414%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '210' WHERE `llx_accounting_account`.`account_number` LIKE '6451%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '210' WHERE `llx_accounting_account`.`account_number` LIKE '6453%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '210' WHERE `llx_accounting_account`.`account_number` LIKE '6456%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '231' WHERE `llx_accounting_account`.`account_number` LIKE '6211%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '250' WHERE `llx_accounting_account`.`account_number` LIKE '62823%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '250' WHERE `llx_accounting_account`.`account_number` LIKE '633%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '250' WHERE `llx_accounting_account`.`account_number` LIKE '6475%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '290' WHERE `llx_accounting_account`.`account_number` LIKE '74%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '292' WHERE `llx_accounting_account`.`account_number` LIKE '6312%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '292' WHERE `llx_accounting_account`.`account_number` LIKE '6313%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '292' WHERE `llx_accounting_account`.`account_number` LIKE '63511%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '292' WHERE `llx_accounting_account`.`account_number` LIKE '6354%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '292' WHERE `llx_accounting_account`.`account_number` LIKE '637%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '320' WHERE `llx_accounting_account`.`account_number` LIKE '758%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '320' WHERE `llx_accounting_account`.`account_number` LIKE '763%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '320' WHERE `llx_accounting_account`.`account_number` LIKE '79%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '320' WHERE `llx_accounting_account`.`account_number` LIKE '791%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '321' WHERE `llx_accounting_account`.`account_number` LIKE '68111%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '321' WHERE `llx_accounting_account`.`account_number` LIKE '68112%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '322' WHERE `llx_accounting_account`.`account_number` LIKE '6875%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '323' WHERE `llx_accounting_account`.`account_number` LIKE '78725%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '324' WHERE `llx_accounting_account`.`account_number` LIKE '654%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '324' WHERE `llx_accounting_account`.`account_number` LIKE '658%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '340' WHERE `llx_accounting_account`.`account_number` LIKE '661%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '343' WHERE `llx_accounting_account`.`account_number` LIKE '768%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '370' WHERE `llx_accounting_account`.`account_number` LIKE '671%';",
	"UPDATE `llx_accounting_account` SET `fk_accounting_category` = '370' WHERE `llx_accounting_account`.`account_number` LIKE '6712%';"
);

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

$tab_update_bilan = array(
		array('numero_compte' => formattedNbNumber('44587'),'cat_pos' => '288','cat_neg' => '111'),
		array('numero_compte' => formattedNbNumber('44571'),'cat_pos' => '288','cat_neg' => '111'),
		array('numero_compte' => formattedNbNumber('445711'),'cat_pos' => '288','cat_neg' => '111'),
		array('numero_compte' => formattedNbNumber('445712'),'cat_pos' => '288','cat_neg' => '111'),
		array('numero_compte' => formattedNbNumber('467'),'cat_pos' => '289','cat_neg' => '111'),
		array('numero_compte' => formattedNbNumber('4513'),'cat_pos' => '283','cat_neg' => '111'),
	);

$acts = array('bilan','compte_resultat','sig','resultat','sauvegarde','anouveaux');


/*******************************************************************
* ACTIONS
********************************************************************/



if(!empty($action) && in_array($action, $acts)):

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
		case 'bilan': 
			$res_401 = get_cd_bookkeeping('numero_compte',401,$date_start,$date_end);
			$res_411 = get_cd_bookkeeping('numero_compte',411,$date_start,$date_end);

			$array_of_files = array($langs->trans('gr_balance_aux').' 401' => $res_401['file'],$langs->trans('gr_balance_aux').' 411' => $res_411['file']);
			
			$borp = tableau_resultat($date_start,$date_end,'no',$tab_compteresult,'calcul');

			$tab_to_show = $tab_bilan;
			$ret = exec_tabsql($tab_bilan);
			if($ret): 
				update_bilan($date_start,$date_end,$tab_update_bilan);
				setEventMessages($langs->trans('gr_results_allrequests_ok'), null, 'mesgs');
			endif;
			

		break;
		case 'compte_resultat': 
			$tab_to_show = $tab_compteresult;
			if(exec_tabsql($tab_compteresult)): setEventMessages($langs->trans('gr_results_allrequests_ok'), null, 'mesgs'); endif;
		break;
		case 'sig': 
			$tab_to_show = $tab_sig;
			if(exec_tabsql($tab_sig)): setEventMessages($langs->trans('gr_results_allrequests_ok'), null, 'mesgs'); endif;
		break;
		/*case 'sauvegarde':

			$res_401 = get_cd_bookkeeping('numero_compte',401,$date_start,$date_end,'save');
			$res_411 = get_cd_bookkeeping('numero_compte',411,$date_start,$date_end,'save');

			$array_of_files = array('Balance Auxiliaire 401' => $res_401['file'],'Balance Auxiliaire 411' => $res_411['file']);


			$borp = tableau_resultat($date_start,$date_end,'no',$tab_compteresult,'calcul');
			$tab_to_show = $tab_bilan;
			if(exec_tabsql($tab_bilan)): setEventMessages('Toutes les requêtes ont été éxecutées.', null, 'mesgs'); endif;

		break;
		case 'anouveaux':
			$year_anouveaux = GETPOST('gen-year-anouveaux');
			$type_anouveaux = GETPOST('gen-type-anouveaux');
			$view_anouveaux = show_anouveaux($year_anouveaux,$type_anouveaux);
		break;*/
		default: $tab_to_show = ""; break;
		endswitch;
	endif;

	// AJOUTER CORRECTEMENT LE BOUTON DE SAUVEGARDE AVEC UN FORM ET ACTION = SAUVEGARDE
	// Si action sauvegarde -> utiliser fonction get_cd_bookkeeping en mode 'save'
	// Faire un affichage specifique pour ces données grace au lockid

	// Ensuite, tester le blocage des champs par javascript

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

	    <?php if (in_array($action, $acts) && $action != 'anouveaux' && !$error): $rapport = tableau_resultat($date_start,$date_end,$showaccountdetail,$tab_to_show,'affichage',$action,$array_of_files); ?>

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
