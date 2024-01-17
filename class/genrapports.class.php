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


require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

class GenRapports {

	public $group_prefix = '| ';
	
	private $tab_bilan;	
	private $tab_compteresult;	
	private $tab_sig;
	private $tab_update_bilan;

	// GROUPES BILAN
	private $group_bilan = array(
		'10' => array('formula' => '','cat_childs' => array('109%')),
		'20' => array('formula' => '','cat_childs' => array('201%','2801%')),
		'21' => array('formula' => '','cat_childs' => array('203%','2803%')),
		'22' => array('formula' => '','cat_childs' => array('205%','2805%','2905%')),
		'23' => array('formula' => '','cat_childs' => array('206%','207%','2906%','2807%','2907%')),
		'24' => array('formula' => '','cat_childs' => array('208%','2808%','2908%')),
		'25' => array('formula' => '','cat_childs' => array('237%')),
		'30' => array('formula' => '-1*(20+21+22+23+24+25)','cat_childs' => array()),
		'40' => array('formula' => '','cat_childs' => array('211%','212%','2811%','2911%','2812%')),
		'41' => array('formula' => '','cat_childs' => array('213%','214%','2813%','2814%')),
		'42' => array('formula' => '','cat_childs' => array('215%','2815%')),
		'43' => array('formula' => '','cat_childs' => array('218%','2818%')),
		'44' => array('formula' => '','cat_childs' => array('231%','2931%')),
		'45' => array('formula' => '','cat_childs' => array('238%')),
		'50' => array('formula' => '-1*(40+41+42+43+44+45)','cat_childs' => array()),
		'60' => array('formula' => '','cat_childs' => array('261%','266%','2961%','2966%')),
		'61' => array('formula' => '','cat_childs' => array('267%','268%','2968%','2967%')),
		'62' => array('formula' => '','cat_childs' => array('271%','272%','2971%','2972%','27682%')),
		'63' => array('formula' => '','cat_childs' => array('274%','2974%','27684%')),
		'64' => array('formula' => '','cat_childs' => array('275%','2975%','2761%','2976%','27685%','27688%')),
		'70' => array('formula' => '-1*(60+61+62+63+64)','cat_childs' => array()),
		'80' => array('formula' => '-1*(10)+30+50+70','cat_childs' => array()),
		'90' => array('formula' => '','cat_childs' => array('31%','32%','391%','392%')),
		'91' => array('formula' => '','cat_childs' => array('33%','34%','393%','394%')),
		'92' => array('formula' => '','cat_childs' => array('35%','395%')),
		'93' => array('formula' => '','cat_childs' => array('37%','397%')),
		'94' => array('formula' => '','cat_childs' => array('4091%')),
		'100' => array('formula' => '-1*(90+91+92+93+94)','cat_childs' => array()),
		'110' => array('formula' => '','cat_childs' => array('413%','416%','417%','418%','491%','41188%')),
		'131' => array('formula' => '','cat_childs' => array('50%','59%','500%')), // %?
		'287' => array('formula' => '','cat_childs' => array('43%','430%','431%','437%','4382%','4383%','4386%')), // %?
		'283' => array('formula' => '','cat_childs' => array('17%','45%','165%','166%','168%','426%','450%','455%','1675%',)), // %?
		'111' => array('formula' => '','cat_childs' => array(
			'495%','496%','425%','441%','443%','444%','451%','456%','458%','462%','465%','467%','478%','503%',
			'4097%','4098%','4287%','4387%','4456%','4457%','4487%','4560%','4670%','4673%','4687%','4672%','4096%','44585%',
			'40188%','44581%','44582%','44583%','44586%')), // %?
		'112' => array('formula' => '','cat_childs' => array('4562%')),
		'120' => array('formula' => '-1*(110+111+112)','cat_childs' => array()),
		'130' => array('formula' => '','cat_childs' => array('5021%')),		
		'132' => array('formula' => '','cat_childs' => array('52%')),
		'133' => array('formula' => '','cat_childs' => array('51%','53%','54%','510%','512%','517%','518%')), // %?
		'134' => array('formula' => '','cat_childs' => array('486%')),
		'140' => array('formula' => '-1*(130+131+132+133+134)','cat_childs' => array()),
		'150' => array('formula' => '100+120+140','cat_childs' => array()),
		'160' => array('formula' => '','cat_childs' => array('481%')),
		'170' => array('formula' => '','cat_childs' => array('169%')),
		'180' => array('formula' => '','cat_childs' => array('476%')),
		'190' => array('formula' => '80+150+160+170+180','cat_childs' => array()),
		'200' => array('formula' => '','cat_childs' => array('101%','108%')),
		'201' => array('formula' => '','cat_childs' => array('104%')),
		'202' => array('formula' => '','cat_childs' => array('105%')),
		'203' => array('formula' => '','cat_childs' => array('107%','1057%')),
		'210' => array('formula' => '','cat_childs' => array('1061%')),
		'211' => array('formula' => '','cat_childs' => array('1063%')),
		'212' => array('formula' => '','cat_childs' => array('1062%','1064%')),
		'213' => array('formula' => '','cat_childs' => array('1068%')),
		'214' => array('formula' => '','cat_childs' => array('11%')),
		'215' => array('formula' => '','cat_childs' => array('12%')),
		'216' => array('formula' => '','cat_childs' => array('13%')),
		'217' => array('formula' => '','cat_childs' => array('14%')),
		'220' => array('formula' => '200+201+202+203+210+211+212+213+214+215+216+217','cat_childs' => array()),
		'240' => array('formula' => '','cat_childs' => array('1671%')),
		'241' => array('formula' => '','cat_childs' => array('1674%')),
		'250' => array('formula' => '240+241','cat_childs' => array()),
		'261' => array('formula' => '','cat_childs' => array('15%')),
		'260' => array('formula' => '','cat_childs' => array('151%')),		
		'270' => array('formula' => '260+261','cat_childs' => array()),
		'280' => array('formula' => '','cat_childs' => array('161%','16881%')),
		'281' => array('formula' => '','cat_childs' => array('163%','16883%')),
		'282' => array('formula' => '','cat_childs' => array('164%','16884%','514%','5186%','519%')),		
		'284' => array('formula' => '','cat_childs' => array('4191%')),
		'285' => array('formula' => '','cat_childs' => array('403%','4081%','4088%','40199%')),
		'286' => array('formula' => '','cat_childs' => array('421%','422%','424%','427%','4282%','4283%','4284%','4286%')),
		'288' => array('formula' => '','cat_childs' => array('4452%','4455%','44580%','44584%','44587%')),
		'289' => array('formula' => '','cat_childs' => array('442%','446%','447%','4482%','4486%')),
		'290' => array('formula' => '','cat_childs' => array('457%','269%','279%','404%','405%','464%','509%','487%','477%','4084%','41199%','4196%','4197%','4198%','4686%','4671%','467AB%')), // %?
		'300' => array('formula' => '280+281+282+283+284+285+286+287+288+289+290','cat_childs' => array()),
		'320' => array('formula' => '220+250+270+300','cat_childs' => array()),
	);

	// GROUPES COMPTE DE RESULTAT
	private $group_compteresult = array(
		'34' => array('formula' => '','cat_childs' => array('7%')),
		'10' => array('formula' => '','cat_childs' => array('707%','709%')),
		'11' => array('formula' => '','cat_childs' => array('701%','702%','703%')),
		'12' => array('formula' => '','cat_childs' => array('704%','705%','706%','708%')),
		'20' => array('formula' => '10+11+12','cat_childs' => array()),
		'30' => array('formula' => '','cat_childs' => array('713%')),
		'31' => array('formula' => '','cat_childs' => array('720%','730%')),
		'32' => array('formula' => '','cat_childs' => array('740%')),
		'33' => array('formula' => '','cat_childs' => array('781%','791%')),		
		'40' => array('formula' => '30+31+32+33+34+20','cat_childs' => array()),
		'60' => array('formula' => '','cat_childs' => array('6%')),
		'50' => array('formula' => '','cat_childs' => array('607%','6087%','6097%')),
		'51' => array('formula' => '','cat_childs' => array('6037%')),
		'52' => array('formula' => '','cat_childs' => array('601%','602%','6081%','6082%','6091%','6092%')),
		'53' => array('formula' => '','cat_childs' => array('6031%','6032%')),
		'54' => array('formula' => '','cat_childs' => array('604%','605%','606%','6084%','6085%','6086%','6094%','6095%','6096%','61%','62%')),
		'55' => array('formula' => '','cat_childs' => array('63%')),
		'56' => array('formula' => '','cat_childs' => array('641%','644%','648%')),
		'57' => array('formula' => '','cat_childs' => array('645%','646%','647%','648%')),
		'58' => array('formula' => '','cat_childs' => array('6811%','6812%')),
		'59' => array('formula' => '','cat_childs' => array('6815%','6816%','6817%')),		
		'70' => array('formula' => '50+51+52+53+54+55+56+57+58+59+60','cat_childs' => array()),
		'80' => array('formula' => '40+70','cat_childs' => array()),
		'90' => array('formula' => '','cat_childs' => array('755%')),
		'91' => array('formula' => '','cat_childs' => array('655%')),
		'92' => array('formula' => '','cat_childs' => array('761%')),
		'93' => array('formula' => '','cat_childs' => array('762%')),
		'94' => array('formula' => '','cat_childs' => array('763%','764%','765%','768%')),
		'95' => array('formula' => '','cat_childs' => array('786%','796%')),
		'96' => array('formula' => '','cat_childs' => array('766%')),
		'97' => array('formula' => '','cat_childs' => array('767%')),
		'100' => array('formula' => '92+93+94+95+96+97','cat_childs' => array()),
		'110' => array('formula' => '','cat_childs' => array('686%')),
		'111' => array('formula' => '','cat_childs' => array('661%','664%','665%','668%')),
		'112' => array('formula' => '','cat_childs' => array('666%')),
		'113' => array('formula' => '','cat_childs' => array('667%')),
		'120' => array('formula' => '110+111+112+113','cat_childs' => array()),
		'130' => array('formula' => '100+120','cat_childs' => array()),
		'140' => array('formula' => '40+70+90+91+100+120','cat_childs' => array()),
		'150' => array('formula' => '','cat_childs' => array('771%')),
		'151' => array('formula' => '','cat_childs' => array('775%','777%','778%')),
		'152' => array('formula' => '','cat_childs' => array('787%','798%')),
		'160' => array('formula' => '150+151+152','cat_childs' => array()),
		'170' => array('formula' => '','cat_childs' => array('671%')),
		'171' => array('formula' => '','cat_childs' => array('675%','678%')),
		'172' => array('formula' => '','cat_childs' => array('687%')),
		'180' => array('formula' => '170+171+172','cat_childs' => array()),
		'190' => array('formula' => '160+180','cat_childs' => array()),
		'200' => array('formula' => '','cat_childs' => array('691%')),
		'210' => array('formula' => '','cat_childs' => array('695%','697%','689%','698%','699%','789%')),
		'220' => array('formula' => '40+90+100+160','cat_childs' => array()),
		'230' => array('formula' => '70+91+120+180+200+210','cat_childs' => array()),
		'240' => array('formula' => '220+230','cat_childs' => array())
	);

	//
	private $group_sig = array(
		'10' => array('formula' => '','cat_childs' => array('701%')),
		'12' => array('formula' => '','cat_childs' => array('706%','7083%')),
		'20' => array('formula' => '10+12','cat_childs' => array()),
		'30' => array('formula' => '','cat_childs' => array('707%','7070%','7071%','70791%','7085%','7089%','70891%','709%','7097%')),
		'31' => array('formula' => '','cat_childs' => array('7079','70790%')),
		'40' => array('formula' => '30+31','cat_childs' => array()),
		'60' => array('formula' => '20+40','cat_childs' => array()),
		'167' => array('formula' => '','cat_childs' => array('604%','6040%')),
		'90' => array('formula' => '','cat_childs' => array('611%','6041%')),
		'91' => array('formula' => '','cat_childs' => array('6241%','6242%','6244%')),
		'100' => array('formula' => '90+91','cat_childs' => array()),
		'110' => array('formula' => '','cat_childs' => array('607%','609%')),
		'111' => array('formula' => '','cat_childs' => array('6037%')),
		'120' => array('formula' => '110+111','cat_childs' => array()),
		'130' => array('formula' => '20','cat_childs' => array()),
		'140' => array('formula' => '40+120+90+91','cat_childs' => array()),
		'150' => array('formula' => '130+140','cat_childs' => array()),
		'160' => array('formula' => '150/60','cat_childs' => array()),
		'166' => array('formula' => '','cat_childs' => array('606%','6060%','6064%','6181%','623%','6230%','6234%','6236%','6238%','651%')),
		'164' => array('formula' => '','cat_childs' => array('60611%','60612%')),
		'165' => array('formula' => '','cat_childs' => array('6063%')),
		'175' => array('formula' => '','cat_childs' => array('613%','626%','6130%','6131%','61352%','6156%','6261%','6262%')),
		'172' => array('formula' => '','cat_childs' => array('615%','6150%','6155%','61550%')),
		'168' => array('formula' => '','cat_childs' => array('6132%','6152%')),
		'170' => array('formula' => '','cat_childs' => array('61361%','61362%','61368%','61552%')),
		
		
		'176' => array('formula' => '','cat_childs' => array('6231%')),
		'177' => array('formula' => '','cat_childs' => array('616%')),
		'178' => array('formula' => '','cat_childs' => array('6226%','6227%','6281%')),
		'180' => array('formula' => '','cat_childs' => array('60613%','6251%','6257%')),
		'181' => array('formula' => '','cat_childs' => array('627%','6273%','6275%','6272%')),
		'182' => array('formula' => '','cat_childs' => array('6271%')),
		'190' => array('formula' => '164+165+166+167+168+170+172+175+176+177+178+180+181+182','cat_childs' => array()),
		'200' => array('formula' => '150+190','cat_childs' => array()),
		'210' => array('formula' => '','cat_childs' => array('6411%','6414%','6451%','6453%','6456%')),
		'220' => array('formula' => '210','cat_childs' => array()),
		'231' => array('formula' => '','cat_childs' => array('6211%')),
		'240' => array('formula' => '231','cat_childs' => array()),
		'250' => array('formula' => '','cat_childs' => array('62823%','633%','6475%')),
		'260' => array('formula' => '220+240+250','cat_childs' => array()),
		'280' => array('formula' => '260','cat_childs' => array()),
		'290' => array('formula' => '','cat_childs' => array('74%')),
		'292' => array('formula' => '','cat_childs' => array('6312%','6313%','63511%','6354%','637%')),
		'300' => array('formula' => '200+280+290+292','cat_childs' => array()),
		'310' => array('formula' => '300+(-1)*292','cat_childs' => array()),
		'320' => array('formula' => '','cat_childs' => array('758%','763%','79%','791%')),
		'321' => array('formula' => '','cat_childs' => array('68111%','68112%')),
		'322' => array('formula' => '','cat_childs' => array('6875%')),
		'323' => array('formula' => '','cat_childs' => array('78725%')),
		'324' => array('formula' => '','cat_childs' => array('654%','658%')),
		'330' => array('formula' => '300+320+321+322+323+324','cat_childs' => array()),
		'340' => array('formula' => '','cat_childs' => array('661%')),
		'343' => array('formula' => '','cat_childs' => array('768%')),
		'350' => array('formula' => '340+343','cat_childs' => array()),
		'360' => array('formula' => '330+350','cat_childs' => array()),
		'370' => array('formula' => '','cat_childs' => array('671%','6712%')),
		'380' => array('formula' => '370','cat_childs' => array()),
		'390' => array('formula' => '360+380','cat_childs' => array()),
		'410' => array('formula' => '390','cat_childs' => array()),
		'420' => array('formula' => '390+(-1)*350+(-0.15)*172+(-0.15)*168+(-1)*321','cat_childs' => array()),
		'430' => array('formula' => '410','cat_childs' => array()),
	);
	
	public $table_c_accounting_category = 'c_accounting_category';
	public $table_accounting_account = 'accounting_account';
	public $db;

	public function __construct($db){

		global $conf,$langs;

		$this->db = $db;

		//
		$this->tab_update_bilan = array(
			array('numero_compte' => $this->formattedNbNumber('44587'),'cat_pos' => '288','cat_neg' => '111'),
			array('numero_compte' => $this->formattedNbNumber('44571'),'cat_pos' => '288','cat_neg' => '111'),
			array('numero_compte' => $this->formattedNbNumber('445711'),'cat_pos' => '288','cat_neg' => '111'),
			array('numero_compte' => $this->formattedNbNumber('445712'),'cat_pos' => '288','cat_neg' => '111'),
			array('numero_compte' => $this->formattedNbNumber('467'),'cat_pos' => '289','cat_neg' => '111'),
			array('numero_compte' => $this->formattedNbNumber('4513'),'cat_pos' => '283','cat_neg' => '111'),
		);
	}

	private function formattedNbNumber($number,$glue = '0',$before = false){

	    global $conf;

	    $required_length = intval($conf->global->GENRAPPORTS_NUMBERS_TO_USE);	    
	    while(strlen($number) < $required_length):
	        if($before): $number = $glue.$number;
	        else: $number = $number.$glue;
	        endif;	        
	    endwhile;

	    return $number;
	}

	/*****************************************************************/
	// 
	/*****************************************************************/
	public function get_cd_bookkeeping($row,$num_compte,$date_start = '',$date_end = '',$mode = ''){

	    global $conf, $langs, $user;

	    //////////////////////////////////////////////////

	    // ON DEFINIT LE REPERTOIRE PRINCIPAL ET ON LE CREE SI BESOIN
	        $dir = DOL_DATA_ROOT.'/genrapports';
	        if (!is_dir($dir)): if(!mkdir($dir,0755)): setEventMessages($langs->trans('gr_error_crea_folder'), null, 'errors'); endif; endif;

	        $d = date('d-m-Y');
	        $dir_day = $dir.'/'.$d;
	        if (!is_dir($dir_day)): if(!mkdir($dir_day,0755)): setEventMessages($langs->trans('gr_error_crea_folder'), null, 'errors'); endif; endif;

	        $version = explode('.', DOL_VERSION); // ON RECUPERE LA VERSION DE DOLIBARR
	        if(intval($version[0]) >= 18): $csv_details = new ExportCsvUtf8($this->db);
	        else: $csv_details = new ExportCsv($this->db);
	        endif;

	        // ON DONNE UN NOM AU FICHIER
	        $file_title = 'genrapports-bookkeepings-'.$num_compte.'-'.$d.'.'.$csv_details->extension;

	        // ON DEFINIT LE CHEMIN COMPLET DU FICHIER
	        $dir_file = $dir_day.'/'.$file_title;
	        $download_file = urlencode($d.'/'.$file_title);

	        // ON OUVRE LE FICHIER
	        $csv_details->open_file($dir_file,$langs);

	        // ON ECRIT LE HEADER DU FICHIER
	        $csv_details->write_header($langs);

	        $tab_labels = array($langs->trans('gr_file_tablabel_compteaux'),$langs->trans('gr_file_tablabel_label'),$langs->trans('gr_file_tablabel_credit'),$langs->trans('gr_file_tablabel_debit'),$langs->trans('gr_file_tablabel_diff'));
	        $tab_type_labels = array('Text','Text','Text','Text','Text');

	        $csv_details->write_title($tab_labels,$tab_labels,$langs,$tab_type_labels);

	    //////////////////////////////////////////////////

	    $i = 0;

	    $books = new BookKeepingMod($this->db);

	    $params = array();
	    $params['t.'.$row] = $num_compte;

	    if(!empty($date_start)): $params['t.doc_date>='] = $date_start.' 00:00:00'; endif;
	    if(!empty($date_end)): $params['t.doc_date<='] = $date_end.' 23:59:59'; endif;

	    $books->fetchAllByAccount('','',$limit = '',$offset = '',$params); // 

	    $passif = 0;
	    $actif = 0;

	    $nb_bookline = 0;

	    $tab_details = array();
	    $tab_totaux = array();

	    if($mode == "save"):
	        $lock_id = 'lockID';
	        $lock_datestart = $date_start;
	        $lock_datestop = $date_end;
	        $lock_date = date('Y-m-d H:i:s');
	        $lock_user = $user->id;

	        $this->db->begin();
	    endif;

	    $nbt = 0;

	    // POUR CHAQUE LIGNE
	    foreach ($books->lines as $bookline): $nbt++;

	        if(!empty($bookline->subledger_account)): $nb_bookline++;   

	            $label_compte = 'cpt-'.strval($bookline->subledger_account); 

	            // SI LA CLE N'EXISTE PAS DANS TAB, ON LA CREE
	            if(!array_key_exists($label_compte, $tab_details)): $tab_details[$label_compte] = array(); endif;

	            // ON INSERE LE MONTANT (CREDIT+ DEBIT-) DANS TAB[compte_auxiliaire]
	            array_push($tab_details[$label_compte],array('credit' => $bookline->credit, 'debit' => $bookline->debit,'label'=> $bookline->subledger_label));

	            if($mode == "save"):

	                $multic_amount = $bookline->multicurrency_amount;
	                $d_lettering = $bookline->multicurrency_amount;
	                $d_lim_reglement = $bookline->date_lim_reglement;
	                $line_user_modif = $bookline->fk_user_modif;
	                $line_user = $bookline->fk_user;
	                $d_validated = $bookline->date_validated;
	                $d_export = $bookline->date_export;
	                $d_entity = $bookline->entity;
	                $book_tms = $bookline->tms;

	                if(empty($multic_amount)): $multic_amount = "NULL"; endif;
	                if(empty($d_lettering)): $d_lettering = "NULL"; endif;
	                if(empty($d_lim_reglement)): $d_lim_reglement = "NULL"; else: $d_lim_reglement = "'".$bookline->date_lim_reglement."'"; endif; 
	                if(empty($line_user_modif)): $line_user_modif = "NULL"; endif; 
	                if(empty($line_user)): $line_user = "NULL"; endif; 
	                if(empty($d_validated)): $d_validated = "NULL"; else: $d_validated = "'".$bookline->date_validated."'"; endif;
	                if(empty($d_export)): $d_export = "NULL";  else: $d_export = "'".$bookline->date_export."'"; endif;
	                if(empty($d_entity)): $d_entity = $conf->entity; endif;
	                if(empty($book_tms)): $book_tms = date('Y-m-d H:i:s'); endif;

	                $sql_save = "INSERT INTO ".MAIN_DB_PREFIX."accounting_bookkeeping_lock";
	                $sql_save .= " (rowid, entity, piece_num, doc_date, doc_type, doc_ref, fk_doc, fk_docdet, thirdparty_code, subledger_account, subledger_label, numero_compte, label_compte, label_operation, debit, credit, montant, sens, multicurrency_amount, multicurrency_code, lettering_code, date_lettering, date_lim_reglement, fk_user_author, fk_user_modif, date_creation, tms, fk_user, code_journal, journal_label, date_validated, date_export, import_key, extraparams, lock_id, lock_datestart, lock_datestop, lock_date, lock_user)";
	                $sql_save .= " VALUES ('".$bookline->id."','".$d_entity."','".$bookline->piece_num."','".date('Y-m-d',$bookline->doc_date)."','".$bookline->doc_type."','".$bookline->doc_ref."','".$bookline->fk_doc."','".$bookline->fk_docdet."','".$bookline->thirdparty_code."','".$bookline->subledger_account."','".addslashes($bookline->subledger_label)."','".$bookline->numero_compte."','".addslashes($bookline->label_compte)."','".addslashes($bookline->label_operation)."','".$bookline->debit."','".$bookline->credit."','".$bookline->montant."','".$bookline->sens."',".$multic_amount.",'".$bookline->multicurrency_code."','".$bookline->lettering_code."',".$d_lettering.",".$d_lim_reglement.",'".$bookline->fk_user_author."',".$line_user_modif.",'".$bookline->date_creation."','".$book_tms."',".$line_user.",'".$bookline->code_journal."','".addslashes($bookline->journal_label)."',".$d_validated.",".$d_export.",'".$bookline->import_key."','".$bookline->extraparams."','".$lock_id."','".$lock_datestart."','".$lock_datestop."','".$lock_date."','".$lock_user."');";

	                $resql = $this->db->query($sql_save);
	                if (!$resql): $error++; $errors[] = 'ID:'.$bookline->id.' :: Error '.$this->db->lasterror(); endif;

	            endif;

	        //else: $label_compte = 'cpt-INCONNU'; 
	        endif;

	    endforeach;

	    if($mode == "save"):
	        if ($error): $this->db->rollback();
	            setEventMessages($langs->trans('gr_results_save_nberrors').' : '.$error, null, 'errors');
	        else: $this->db->commit();
	            setEventMessages($langs->trans('gr_results_save_copydone'), null, 'mesgs');
	        endif;
	    endif;

	    // POUR CHAQUE COMPTE AUXILIAIRE ON CALCULE SI LE COMPTE EST EN ACTIF OU PASSIF
	    foreach ($tab_details as $compte_aux_key => $details_compte):

	        $credit_cpt_aux = 0;
	        $debit_cpt_aux = 0;
	        $cmpt_auxlabel = "";

	        $nbk = str_replace('cpt-','',$compte_aux_key);
	        $firstChar_nbk = substr($nbk, 0, 1);

	        $sql_nbk = "SELECT nom FROM ".MAIN_DB_PREFIX."societe";
	        $sql_nbk .= " WHERE code_compta_fournisseur = '".$nbk."'";
	        $sql_nbk .= " OR code_compta = '".$nbk."'";

	        /*switch ($firstChar_nbk):
	            case 'F': $sql_nbk .= " WHERE code_compta_fournisseur = '".$nbk."'"; break;
	            case 'C': $sql_nbk .= " WHERE code_compta = '".$nbk."'"; break;
	        endswitch;*/

	        $result_nbk = $this->db->query($sql_nbk);
	        if($result_nbk):
	            $row_nbk = $this->db->fetch_object($result_nbk);
	            $label_compte_aux = $row_nbk->nom;
	            if(empty($label_compte_aux)): $label_compte_aux = strval($nbk); endif;
	        endif;

	        foreach ($details_compte as $detail):

	            $credit_cpt_aux += round($detail['credit'],2);
	            $debit_cpt_aux += round($detail['debit'],2);
	            $cmpt_auxlabel = $label_compte_aux;

	        endforeach;

	        $tab_totaux[$compte_aux_key] = array('credit' => $credit_cpt_aux, 'debit' => $debit_cpt_aux,'label'=> $cmpt_auxlabel);

	    endforeach; 

	    foreach ($tab_totaux as $compte_auxiliaire => $total):
	        
	        $difference = round($total['credit'],2) - round($total['debit'],2);

	        if($difference > 0): $passif += $difference;
	        else: $actif += $difference;endif;

	        $tot_c = number_format($total['credit'],2,',','');
	        $tot_d = number_format($total['debit'],2,',','');
	        $tot_diff = number_format($difference,2,',','');

	        $tab_labels = array( str_replace('cpt-', '', strval($compte_auxiliaire)),$total['label'], $tot_c, $tot_d, $tot_diff);
	        $tab_type_labels = array('Text','Text','Numeric','Numeric','Numeric');

	        $csv_details->write_title($tab_labels,$tab_labels,$langs,$tab_type_labels);

	    endforeach;

	    $csv_details->write_footer($langs);
	    $csv_details->close_file();

	    $year_actpas = explode('-', $date_end);

	    $abs_actif = abs($actif);
	    $abs_passif = abs($passif);

	    /*******************************************************************************************/

	    $line_actif = new BookKeepingMod($this->db);
	    $line_actif->entity = $conf->entity;
	    $line_actif->doc_date = $date_end;
	    $line_actif->piece_num = $line_actif->getNextNumMvt();	    
	    $line_actif->fk_doc = 0;
	    $line_actif->fk_docdet = 0;
	    $line_actif->debit = $abs_actif;
	    $line_actif->credit = 0;
	    $line_actif->montant = $abs_actif;
	    $line_actif->numero_compte = $this->formattedNbNumber($num_compte,'8'); // NBCOUNTER 
	    $line_actif->subledger_label = 'Actif '.$year_actpas[0];
	    $line_actif->label_operation = 'Actif '.$year_actpas[0];
	    $line_actif->fk_user_author = $user->id;

	    $sql_del_401 = "DELETE FROM ".MAIN_DB_PREFIX."accounting_bookkeeping WHERE numero_compte = ".$line_actif->numero_compte." AND entity = '".$conf->entity."'"; // NBCOUNTER
	    $result_401 = $this->db->query($sql_del_401);
	    $a = $line_actif->create($user);

	    $line_passif = new BookKeepingMod($this->db);
	    $line_passif->entity = $conf->entity;
	    $line_passif->doc_date = $date_end;
	    $line_passif->piece_num = $line_passif->getNextNumMvt();	    
	    $line_passif->fk_doc = 0;
	    $line_passif->fk_docdet = 0;
	    $line_passif->debit = 0;
	    $line_passif->credit = $abs_passif;
	    $line_passif->montant = $abs_passif;
	    $line_passif->numero_compte = $this->formattedNbNumber($num_compte,'9'); //// NBCOUNTER
	    $line_passif->subledger_label = 'Passif '.$year_actpas[0];
	    $line_passif->label_operation = 'Passif '.$year_actpas[0];
	    $line_passif->fk_user_author = $user->id;

	    $sql_del_401 = "DELETE FROM ".MAIN_DB_PREFIX."accounting_bookkeeping WHERE numero_compte = ".$line_passif->numero_compte." AND entity = '".$conf->entity."'"; // NBCOUNTER
	    $result_401 = $this->db->query($sql_del_401);
	    $b = $line_passif->create($user);

	    /*************************************************************************************/
	    return array('actif' => $abs_actif, 'passif' => $abs_passif,'total_ligne' => $nb_bookline,'file' => $download_file);
	}

	private function getTab($mode){

		global $conf, $langs;

		$tab = array(
			'start' => array(),
			'insert' => array(),
			'update' => array(),
		);

		// ON REINITIALISE LES GROUPES PERSONNALISES
		$sql_raz_accounting_category = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_c_accounting_category." WHERE entity = '".$conf->entity."'";
		array_push($tab['start'],$sql_raz_accounting_category);

		$sql_raz_accounting_childs ="UPDATE ".MAIN_DB_PREFIX.$this->table_accounting_account." SET fk_accounting_category = '0' WHERE entity = '".$conf->entity."'";
		array_push($tab['start'],$sql_raz_accounting_childs);

		switch ($mode):
			case 'bilan': $groups = $this->group_bilan; break;
			case 'compteresult': $groups = $this->group_compteresult; break;
			case 'sig': $groups = $this->group_sig; break;
		endswitch;

		foreach($groups as $groupkey => $groupinfos):

			$bin = empty($groupinfos['formula'])?0:1;

			$sql_insert = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_c_accounting_category." (rowid, entity, code, label, range_account, sens, category_type, formula, position, fk_country, active) VALUES ";
			$sql_insert.= "('".$groupkey."', '".$conf->entity."', '".$groupkey."', '".$this->db->escape($this->group_prefix.$langs->trans('gr_group'.$mode.'_'.$groupkey))."', '', 0, ".$bin.", '".$groupinfos['formula']."', '".$groupkey."', 1, 1)";
			array_push($tab['insert'],$sql_insert);

			if(!empty($groupinfos['cat_childs'])):
				foreach($groupinfos['cat_childs'] as $catchild):
					$sql_update = "UPDATE ".MAIN_DB_PREFIX.$this->table_accounting_account." SET fk_accounting_category = '".$groupkey."' WHERE entity = '".$conf->entity."' AND account_number LIKE '".$catchild."'";
					array_push($tab['update'],$sql_update);
				endforeach;
			endif;
		endforeach;

		return $tab;

	}


	/*****************************************************************/
	// REQUETES
	/*****************************************************************/
	public function exec_tabsql($mode){

	    global $conf, $langs;

	    switch ($mode):
	    	case 'bilan': $tab = $this->getTab('bilan'); break;
	    	case 'compteresult': $tab = $this->getTab('compteresult'); break;
	    	case 'sig': $tab = $this->getTab('sig'); break;
	    endswitch;

	    // Nombre de requêtes à executer
	    $nb_sql = count($tab['start']) + count($tab['insert']) + count($tab['update']);

	    // On instancie les variables de succès et d'erreurs
	    $success_sql = 0;
	    $errors_sql = array();
	    $i = 0;

	    // START
	    if(!empty($tab['start'])):
		    foreach ($tab['start'] as $request): $i++;
		        $result = $this->db->query($request);
		        if($result): $success_sql++; else: array_push($errors_sql, $i);endif;
		    endforeach;
		endif;

	    // INSERT
	    if(!empty($tab['insert'])):
		    foreach ($tab['insert'] as $request): $i++;
		        $result = $this->db->query($request);
		        if($result): $success_sql++; else: array_push($errors_sql, $i); endif;
		    endforeach;
		endif;

	    // UPDATE
	    if(!empty($tab['update'])):
	    	foreach ($tab['update'] as $request): $i++;
		        $result = $this->db->query($request);
		        if($result): $success_sql++; else: array_push($errors_sql, $i); endif;
		    endforeach;
		endif;

	    if($success_sql == $nb_sql): return true;
	    else:
	        $erreurs = implode(',', $errors_sql);
	        setEventMessages($langs->trans('gr_error_withlines').' '.$erreurs, null, 'errors');
	        return false;
	    endif;
	}


	/*****************************************************************/
	// 
	/*****************************************************************/
	public function tableau_resultat($date_start,$date_end,$showaccountdetail,$mode = '',$action = 'bilan',$array_of_files = array()){ 

		global $conf, $user, $langs;

		
		// SI ON EST PAS EN MODE CALCUL, ON CREE UN FICHIER D'EXPORT
	    if($mode != 'calcul'):

	    	$conf->global->EXPORT_CSV_FORCE_CHARSET = "UTF-8";

	        // ON DEFINIT LE REPERTOIRE PRINCIPAL ET ON LE CREE SI BESOIN
	        $dir = DOL_DATA_ROOT.'/genrapports';
	        if (!is_dir($dir)): if(!mkdir($dir,0755)): setEventMessages($langs->trans('gr_error_crea_folder'), null, 'errors'); endif; endif;

	        $d = date('d-m-Y');
	        $dir_day = $dir.'/'.$d;
	        if (!is_dir($dir_day)): if(!mkdir($dir_day,0755)): setEventMessages($langs->trans('gr_error_crea_folder'), null, 'errors'); endif; endif;

	        $version = explode('.', DOL_VERSION); // ON RECUPERE LA VERSION DE DOLIBARR
	        if(intval($version[0]) >= 18): $csv_file = new ExportCsvUtf8($this->db);
	        else: $csv_file = new ExportCsv($this->db);
	        endif;

	        // ON DONNE UN NOM AU FICHIER
	        $file_date = date('d_m_Y');
	        $file_title = 'genrapports-'.$action.'-'.$file_date.'.'.$csv_file->extension;

	        // ON DEFINIT LE CHEMIN COMPLET DU FICHIER
	        $dir_file = $dir_day.'/'.$file_title;
	        $download_file = urlencode($d.'/'.$file_title);

	        $files_rapport = array();
	        $files_rapport[$action] = DOL_URL_ROOT.'/document.php?modulepart=genrapports&file='.$download_file.'&entity='.$conf->entity;
	        
	        // S'il y a des fichiers envoyés à la fonction, on les ajoute
	        if(!empty($array_of_files)): foreach ($array_of_files as $label => $fichier):
	            $files_rapport[$label] = DOL_URL_ROOT.'/document.php?modulepart=genrapports&file='.$fichier.'&entity='.$conf->entity;
	        endforeach; endif;

	        // ON OUVRE LE FICHIER
	        $csv_file->open_file($dir_file,$langs);

	        // ON ECRIT LE HEADER DU FICHIER
	        $csv_file->write_header($langs);

	    endif;

		// On recupère les catégories
		$AccCat = new AccountancyCategoryMod($this->db);
	    $accountancy_categories = $AccCat->getCats(-1,1); // All and active

	    if (!is_array($accountancy_categories) && $accountancy_categories < 0): setEventMessages(null, $AccCat->errors, 'errors'); return false; endif;

		// 
		$date_start_obj = new DateTime($date_start);
		$date_end_obj = new DateTime($date_end. '23:59:59');
		$date_diff = $date_start_obj->diff($date_end_obj,1);

		// On calcule la difference de dates	    
		$diffmonth = ($date_diff->y * 12) + $date_diff->m;
		$diffmonth++;

		////////
		$res = array();
		$interval = DateInterval::createFromDateString('1 month');

		if($mode != 'calcul'):
	        $tab_head = '<tr class="">';
	        $tab_head .= '<th class="left">'.$langs->trans('AccountingCategory').'</th>';     
	        $tab_head .= '<th class="right" >'.$date_start_obj->format('d/m/Y').' - '.$date_end_obj->format('d/m/Y').'</th>';

	        $tab_labels = array($langs->trans('Group'),html_entity_decode($langs->trans("AccountingCategory")), $date_start_obj->format('d/m/Y').' - '.$date_end_obj->format('d/m/Y'));
	        $tab_type_labels = array('Text','Text','Text');

	    endif;

		$i = 1;
		while($i <= $diffmonth): 

			// On definit la periode
			if($i == 1): 
				$periodstart = $date_start_obj->format('Y-m-d');
				$periodend = dol_print_date(dol_get_last_day($date_start_obj->format('Y'), $date_start_obj->format('m')),'%Y-%m-%d');
			elseif($i == $diffmonth):
				$periodstart = dol_print_date(dol_get_first_day($date_start_obj->format('Y'), $date_start_obj->format('m')),'%Y-%m-%d');
				$periodend = $date_end_obj->format('Y-m-d');
			else: 
				$periodstart = dol_print_date(dol_get_first_day($date_start_obj->format('Y'), $date_start_obj->format('m')),'%Y-%m-%d');
				$periodend = dol_print_date(dol_get_last_day($date_start_obj->format('Y'), $date_start_obj->format('m')),'%Y-%m-%d');
			endif;

			$res[$date_start_obj->format('Y-m')] = array(
				'label' => $langs->trans('MonthShort'.sprintf("%02s", $date_start_obj->format('m'))),
				'period_start' => $periodstart,
				'period_end' => $periodend,
				'month' => $date_start_obj->format('m'),
				'year' => $date_start_obj->format('Y'),
				'categories' => array(),
			);

			if($mode != 'calcul'):
		        $tab_head .= '<th class="right width50" style="white-space:nowrap;">'.$langs->trans('MonthShort'.sprintf("%02s", $date_start_obj->format('m'))).' '.$date_start_obj->format('Y').'</th>';
		        array_push($tab_labels, html_entity_decode($langs->trans('MonthShort'.sprintf("%02s", $date_start_obj->format('m')))));
	            array_push($tab_type_labels, 'Text');
		    endif;

			//
			$date_start_obj->add($interval); $i++;
		endwhile;

		if($mode != 'calcul'):
			$tab_head .= '</tr>';
			$csv_file->write_title($tab_labels,$tab_labels,$langs,$tab_type_labels);
		endif;

		$tab_body = '';
		$tab_detail = array();
		$tab_sum = array();

		// Pour chaque categorie comptable
		foreach ($accountancy_categories as $accountancy_cat):

			$tab_detail[$accountancy_cat['code']] = array();
			$tab_sum[$accountancy_cat['code']] = 0;

    		// ***************************
            if (!empty($accountancy_cat['category_type'])): 

            	$formula = $accountancy_cat['formula']; 

            	$result = strtr($formula, $tab_sum);
	            $r = dol_eval($result, 1);
	            $tab_sum[$accountancy_cat['code']] = $r;

	            if($mode != 'calcul'):
	            	$tab_body .= '<tr class="tab-depth-1 gensubtotal">';
					$tab_body .= '<td><i class="fas fa-folder paddingright" style="color:#666"></i> '.$accountancy_cat['code'].' '.$accountancy_cat['label'].'</td>';
	            	$tab_body .= '<td class="right" style="white-space:nowrap;">'.price($r).'</td>';

	            	$tab_line = array(); $tab_type_line = array();
	            	array_push($tab_line,$accountancy_cat['code']);
	            	array_push($tab_line,$accountancy_cat['label']);
	            	array_push($tab_line,price($r));
	                array_push($tab_type_line, 'Text');
	                array_push($tab_type_line, 'Text');
	                array_push($tab_type_line, 'Numeric');
	            endif;

            	foreach($res as $periodkey => $period):

            		$result2 = strtr($formula, $res[$periodkey]['categories']);
            		$r2 = dol_eval($result2, 1);
            		$res[$periodkey]['categories'][$accountancy_cat['code']] = $r2;

            		if($mode != 'calcul'):
		    			$tab_body .= '<td class="right" style="white-space:nowrap;">'.price($r2).'</td>';
		    			array_push($tab_line,price($r2));
	                	array_push($tab_type_line, 'Numeric');
		    		endif;
		    	endforeach;
		    	if($mode != 'calcul'):
		    		$tab_body .= '</tr>';
		    		$csv_file->write_title($tab_line,$tab_line,$langs,$tab_type_line);
		    	endif;


		    // ***************************
			else:

				// On récupère tous les comptes de la catégorie
		    	$cpts = $AccCat->getCptsCat($accountancy_cat['rowid']);

		    	// Pour chaque compte de la categorie
		    	$cat_html = '';

		    	$tab_detailfull = array();
		    	$tab_detailtypefull = array();

		    	foreach($cpts as $cpt):
		    		
		    		// Pour chaque mois de la periode
		    		$cpt_html = '';
		    		$cpt_total = 0;
		    		$cpt_array = array();
		    		foreach($res as $periodkey => $period):

		    			if(!isset($tab_detail[$accountancy_cat['code']][$periodkey])): $tab_detail[$accountancy_cat['code']][$periodkey] = 0; endif;
		    			if(!isset($res[$periodkey]['categories'][$accountancy_cat['code']])): $res[$periodkey]['categories'][$accountancy_cat['code']] = 0; endif;

		    			$return = $AccCat->getSumDebitCredit($cpt['account_number'], $period['period_start'], $period['period_end'], $accountancy_cat['sens'], 'nofilter', $period['month'], $period['year']);

		    			$cpt_total += $AccCat->sdc;
		    			$tab_detail[$accountancy_cat['code']][$periodkey] += $AccCat->sdc;
		    			$tab_sum[$accountancy_cat['code']] += $AccCat->sdc;
		    			$res[$periodkey]['categories'][$accountancy_cat['code']] += $AccCat->sdc;

		    			if($mode != 'calcul'):
			    			$cpt_html .= '<td class="right" style="white-space:nowrap">'.price($AccCat->sdc).'</td>';
			    			array_push($cpt_array,price($AccCat->sdc));
			    		endif;

		    		endforeach;

		    		if (($showaccountdetail == 'all' || $showaccountdetail != 'no' && $cpt_total != 0) && $mode != 'calcul'):
		    			$cat_html .= '<tr class="tab-depth-2 account-'.$accountancy_cat['code'].'">';
		    			$cat_html .= '<td><i class="fas fa-clipboard-list paddingright" style="color:#b0bb39"></i> '.$cpt['account_number'].' - '.$cpt['account_label'].'</td>';
			    		$cat_html .= '<td class="right">'.price($cpt_total).'</td>';
			    		$cat_html .= $cpt_html;
			    		$cat_html .= '</tr>';

			    		$tab_linedetail = array();
						$tab_type_linedetail = array();
						array_push($tab_linedetail,' ');
						array_push($tab_linedetail,$cpt['account_number'].' - '.$cpt['account_label']);
	            		array_push($tab_linedetail,price($cpt_total));
	                	array_push($tab_type_linedetail, 'Text');
	                	array_push($tab_type_linedetail, 'Text');
	                	array_push($tab_type_linedetail, 'Numeric');

	                	foreach($cpt_array as $cpttab):

	                		array_push($tab_linedetail,$cpttab);
	                		array_push($tab_type_linedetail, 'Numeric');

	                	endforeach;
	                	
	                	array_push($tab_detailfull,$tab_linedetail);
	                	array_push($tab_detailtypefull,$tab_type_linedetail);

			    	endif;

		    	endforeach;

		    	if($mode != 'calcul'):
			    	$c = '';
			    	$fa_class = '';
			    	if(!empty($cat_html)): $c = 'tab-toggle'; $fa_class = 'fa-caret-right';
			    	else: $fa_class = 'fa-caret-right classB';
			    	endif;

			    	$tab_body .= '<tr class="tab-depth-1 '.$c.'" data-target="account-'.$accountancy_cat['code'].'">';
					$tab_body .= '<td><i class="fas '.$fa_class.' icon-toggle paddingright"></i> <i class="fas fa-folder paddingright" style="color:#d2d2d2"></i> '.$accountancy_cat['code'].' '.$accountancy_cat['label'].'</td>';
					$tab_body .= '<td class="right" style="white-space:nowrap">'.price(array_sum($tab_detail[$accountancy_cat['code']])).'</td>';
					
					$tab_line = array();
					$tab_type_line = array();

					array_push($tab_line,$accountancy_cat['code']);
					array_push($tab_line,html_entity_decode($accountancy_cat['label']));
	            	array_push($tab_line,price(array_sum($tab_detail[$accountancy_cat['code']])));
	                array_push($tab_type_line, 'Text');
	                array_push($tab_type_line, 'Text');
	                array_push($tab_type_line, 'Numeric');

					foreach($res as $periodkey => $period):
						$tab_body .= '<td class="right" style="white-space:nowrap">'.price($tab_detail[$accountancy_cat['code']][$periodkey]).'</td>';
						array_push($tab_line,price($tab_detail[$accountancy_cat['code']][$periodkey]));
						array_push($tab_type_line, 'Numeric');
					endforeach;
					$tab_body .= '</tr>';
			    	$tab_body .= $cat_html;

			    	$csv_file->write_title($tab_line,$tab_line,$langs,$tab_type_line);		    	

			    	if(!empty($tab_detailfull)):
			    		foreach($tab_detailfull as $key => $linearray):		    			
					    	$csv_file->write_title($linearray,$linearray,$langs,$tab_detailtypefull[$key]);
			    		endforeach;
			    	endif;
			    endif;

		    endif;

		endforeach;

		if($mode == 'calcul'):

			$line_borp = new BookKeepingMod($this->db);
	        $line_borp->doc_date = $date_end;
	        $line_borp->piece_num = $line_borp->getNextNumMvt();
	        
	        $line_borp->fk_doc = 0;
	        $line_borp->fk_docdet = 0;
	        $line_borp->debit = 0;
	        $line_borp->credit = $tab_sum[240];
	        $line_borp->montant = $tab_sum[240];
	        $line_borp->numero_compte = $this->formattedNbNumber('120'); // NBCOUNTER
	        $line_borp->subledger_label = $langs->trans('gr_borp').' '.$date_end_obj->format('Y');
	        $line_borp->label_operation = $langs->trans('gr_borp').' '.$date_end_obj->format('Y');
	        $line_borp->fk_user_author = $user->id;

	        $sql_del_borp = "DELETE FROM ".MAIN_DB_PREFIX."accounting_bookkeeping WHERE numero_compte = ".$line_borp->numero_compte;
	        $result_borp = $this->db->query($sql_del_borp);
	        $b = $line_borp->create($user);

	        return $tab_sum[240];

		else:

			$csv_file->write_footer($langs);
		    $csv_file->close_file();

	        $tabres = array(
	            'tab_head' => $tab_head,
	            'tab_lines' => $tab_body,
	            'generate_time' => dol_now(),
	            'files' => $files_rapport,
	        );

	        return $tabres;
	    endif;
	}

	/*****************************************************************/
	// 
	/*****************************************************************/
	public function update_bilan($date_start = '',$date_end = ''){

	    global $conf, $langs, $user;
	    $conf->global->EXPORT_CSV_FORCE_CHARSET = "UTF-8";

	    foreach($this->tab_update_bilan as $c_update):

	        $bookkeep = new BookKeepingMod($this->db);
	        $bl = 0;

	        $params = array();
	        $params['t.numero_compte'] = $c_update['numero_compte'];

	        if(!empty($date_start)): $params['t.doc_date>='] = $date_start.' 00:00:00'; endif;
	        if(!empty($date_end)): $params['t.doc_date<='] = $date_end.' 23:59:59'; endif;

	        // ON RECUPERE LES INFOS A CORRIGER
	        $bookkeep->fetchAllByAccount('','',$limit = '',$offset = '',$params);

	        // TOTAUX
	        $total_cred = 0;
	        $total_deb = 0;

	        // POUR CHAQUE LIGNE
	        foreach ($bookkeep->lines as $bookline): $bl++;
	            $total_cred += round($bookline->credit,2);
	            $total_deb += round($bookline->debit,2);
	        endforeach;

	        // On teste si notre valeur est positive ou negative
	        $diff = $total_cred - $total_deb;

	        // SI LA VALEUR EST NEGATIVE
	        if($diff < 0): $update_sql = "UPDATE ".MAIN_DB_PREFIX."accounting_account SET fk_accounting_category = '".$c_update['cat_neg']."' WHERE ".MAIN_DB_PREFIX."accounting_account.account_number LIKE '".$c_update['numero_compte']."%';";
	        else: $update_sql = "UPDATE ".MAIN_DB_PREFIX."accounting_account SET fk_accounting_category = '".$c_update['cat_pos']."' WHERE ".MAIN_DB_PREFIX."accounting_account.account_number LIKE '".$c_update['numero_compte']."%';";
	        endif;

	        // On effectue la requête
	        $result = $this->db->query($update_sql);

	        if(!$result): 
	            setEventMessages($langs->trans('gr_error_maj_accounts'), null, 'errors');
	        endif;
	    endforeach;
	}

	/*****************************************************************/
	// DEV A NOUVEAUX
	/*****************************************************************/
	/*function show_anouveaux($year,$type = 'all'){

	    global $db, $conf, $langs, $user;

	    $entity = $conf->entity;

	    $year_prev = $year - 1;
	    $tab_fournisseur = array();
	    $tab_ndf = array();
	    $tab_client = array();
	    $a="";
	    $view = "";

	    // ON DEFINIT LE REPERTOIRE PRINCIPAL ET ON LE CREE SI BESOIN
	    $dir = DOL_DATA_ROOT.'/genrapports';
	    if (!is_dir($dir)): if(!mkdir($dir,0755)): setEventMessages($langs->trans('gr_error_crea_folder'), null, 'errors'); endif; endif;

	    $d = date('d-m-Y');
	    $dir_day = $dir.'/'.$d;
	    if (!is_dir($dir_day)): if(!mkdir($dir_day,0755)): setEventMessages($langs->trans('gr_error_crea_folder'), null, 'errors'); endif; endif;

	    //////////////////////////////////////////////////

	    // Dû fournisseur
	    if($type == 'fourn' || $type == 'all' ):

	        $sql_dufournisseur = "SELECT";
	        $sql_dufournisseur .= " GROUP_CONCAT(llx_societe.nom SEPARATOR '|') AS tiers,";
	        $sql_dufournisseur .= " GROUP_CONCAT(llx_facture_fourn.ref SEPARATOR '|') AS ref,";
	        $sql_dufournisseur .= " GROUP_CONCAT(llx_facture_fourn.ref_supplier SEPARATOR '|') AS ref_supplier,";
	        $sql_dufournisseur .= " GROUP_CONCAT(llx_facture_fourn.datef SEPARATOR '|') AS datef,";
	        $sql_dufournisseur .= " GROUP_CONCAT(llx_paiementfourn.datep SEPARATOR '|') AS datep,";
	        $sql_dufournisseur .= " GROUP_CONCAT(llx_paiementfourn.ref SEPARATOR '|') AS refP,";
	        $sql_dufournisseur .= " SUM(llx_paiementfourn_facturefourn.amount) AS montant,";
	        $sql_dufournisseur .= " llx_accounting_bookkeeping.numero_compte AS numero_compte,";
	        $sql_dufournisseur .= " llx_accounting_bookkeeping.subledger_account AS compte_auxiliaire";
	        $sql_dufournisseur .= " FROM llx_societe INNER JOIN ((llx_paiementfourn_facturefourn INNER JOIN llx_paiementfourn ON llx_paiementfourn_facturefourn.fk_paiementfourn = llx_paiementfourn.rowid) INNER JOIN llx_facture_fourn ON llx_paiementfourn_facturefourn.fk_facturefourn = llx_facture_fourn.rowid) ON llx_societe.rowid = llx_facture_fourn.fk_soc";
	        $sql_dufournisseur .= " INNER JOIN llx_accounting_bookkeeping ON llx_facture_fourn.ref = llx_accounting_bookkeeping.doc_ref";
	        $sql_dufournisseur .= " WHERE (((llx_facture_fourn.datef) Like '%".$year_prev."%') AND ((llx_paiementfourn.datep) Like '%".$year."%'))";
	        $sql_dufournisseur .= " GROUP BY llx_accounting_bookkeeping.subledger_account";
	        $sql_dufournisseur .= " ORDER BY llx_accounting_bookkeeping.subledger_account";

	        $results_dufournisseur = $db->query($sql_dufournisseur);

	        if($results_dufournisseur): 

	            $csv_anvx_fournisseur = new ExportCsv($db);

	            // ON DONNE UN NOM AU FICHIER
	            $file_title = 'genrapports-anouveaux-fournisseurs-'.$year.'.'.$csv_anvx_fournisseur->extension;

	            // ON DEFINIT LE CHEMIN COMPLET DU FICHIER
	            $dir_file = $dir_day.'/'.$file_title;
	            $download_file = urlencode($d.'/'.$file_title);

	            // ON OUVRE LE FICHIER
	            $csv_anvx_fournisseur->open_file($dir_file,$langs);

	            // ON ECRIT LE HEADER DU FICHIER
	            $csv_anvx_fournisseur->write_header($langs);

	            // ON ECRIT LA PREMIERE LIGNE 
	            $tab_labels = array('Compte auxiliaire','Numero compte','Montant');
	            $tab_type_labels = array('Text','Text','Text');
	            $csv_anvx_fournisseur->write_title($tab_labels,$tab_labels,$langs,$tab_type_labels);

	            $count_dufournisseur = $db->num_rows($results_dufournisseur); $i = 0;

	            $view .= '<div class="pg-col-wrapper"><div class="pg-col">';
	            $view .= '<h2>A Nouveaux - Fournisseurs <span class="white">'.$count_dufournisseur.' resultats</span></h2>';
	            $view .= '<div class="gen-openclose"><div class="chevron bottom"></div></div>';

	            $view .= '<div class="pg-dwnld-small">';
	            $view .= '<a class="dwnld-button" href="'.DOL_URL_ROOT.'/document.php?modulepart=genrapports&file='.$download_file.'&entity='.$entity.'">Télécharger le fichier</a>'; 
	            $view .= '</div>';

	            $view .= '<div class="pg-col-content" style="display:none;"><div class="div-table-responsive"><table id="pg-result-table" class="tagtable liste" style="max-width: 100%;"><tbody>';

	            $view .= '<tr class="liste_titre">';
	            foreach ($tab_labels as $label): $view .= '<th class="liste_titre">'.$label.'</th>'; endforeach;
	            $view .= '</tr>';

	            // ON REMPLI LE TABLEAU AVEC LES VALEURS
	            while ($i < $count_dufournisseur): $i++;
	                $anouveaux_fournisseur = $db->fetch_object($results_dufournisseur);

	                $tab_labels = array(strval($anouveaux_fournisseur->compte_auxiliaire),strval($anouveaux_fournisseur->numero_compte),$anouveaux_fournisseur->montant);
	                $tab_type_labels = array('Text','Text','Numeric');
	                $csv_anvx_fournisseur->write_title($tab_labels,$tab_labels,$langs,$tab_type_labels);

	                $view .= '<tr>';
	                $view .= '<td>'.$anouveaux_fournisseur->compte_auxiliaire.'</td>';
	                $view .= '<td>'.$anouveaux_fournisseur->numero_compte.'</td>';
	                $view .= '<td>'.number_format($anouveaux_fournisseur->montant,2,',','').'</td>';
	                $view .= '</tr>';

	                //var_dump($anouveaux_fournisseur);
	                array_push($tab_fournisseur, $anouveaux_fournisseur);
	            endwhile;

	            $view .= '</tbody></table></div></div>';
	            $view .= '</div></div>';

	            $csv_anvx_fournisseur->write_footer($langs);
	            $csv_anvx_fournisseur->close_file();

	        endif;

	    endif;

	    // Dû ndf
	    if($type == 'ndf' || $type == 'all' ):

	        $sql_dundf = "SELECT";
	        $sql_dundf .= " GROUP_CONCAT(llx_expensereport.ref SEPARATOR '|') AS ref,";
	        $sql_dundf .= " GROUP_CONCAT(llx_expensereport.date_fin SEPARATOR '|') AS date_fin,";
	        $sql_dundf .= " GROUP_CONCAT(llx_payment_expensereport.datep SEPARATOR '|') AS datep,";
	        $sql_dundf .= " SUM(llx_payment_expensereport.amount) as montant,";
	        $sql_dundf .= " llx_accounting_bookkeeping.numero_compte AS numero_compte";
	        $sql_dundf .= " FROM llx_expensereport INNER JOIN llx_payment_expensereport ON llx_expensereport.rowid = llx_payment_expensereport.fk_expensereport";
	        $sql_dundf .= " INNER JOIN llx_accounting_bookkeeping ON llx_expensereport.ref = llx_accounting_bookkeeping.doc_ref";
	        $sql_dundf .= " WHERE (((llx_expensereport.date_fin) Like '%".$year_prev."%') AND ((llx_payment_expensereport.datep) Like '%".$year."%'))";
	        $sql_dundf .= " GROUP BY llx_accounting_bookkeeping.numero_compte";
	        $sql_dundf .= " ORDER BY llx_accounting_bookkeeping.numero_compte";

	        $results_dundf = $db->query($sql_dundf);

	        if($results_dundf): 

	            $csv_anvx_ndf = new ExportCsv($db);

	            // ON DONNE UN NOM AU FICHIER
	            $file_title = 'genrapports-anouveaux-ndf-'.$year.'.'.$csv_anvx_ndf->extension;

	            // ON DEFINIT LE CHEMIN COMPLET DU FICHIER
	            $dir_file = $dir_day.'/'.$file_title;
	            $download_file = urlencode($d.'/'.$file_title);

	            // ON OUVRE LE FICHIER
	            $csv_anvx_ndf->open_file($dir_file,$langs);

	            // ON ECRIT LE HEADER DU FICHIER
	            $csv_anvx_ndf->write_header($langs);

	            // ON ECRIT LA PREMIERE LIGNE 
	            $tab_labels = array('Numero compte','Montant');
	            $tab_type_labels = array('Text','Text');
	            $csv_anvx_ndf->write_title($tab_labels,$tab_labels,$langs,$tab_type_labels);

	            $count_dundf = $db->num_rows($results_dundf); $i = 0;

	            $view .= '<div class="pg-col-wrapper"><div class="pg-col">';
	            $view .= '<h2>A Nouveaux - Note de Frais <span class="white">'.$count_dundf.' resultats</span></h2>';
	            $view .= '<div class="gen-openclose"><div class="chevron bottom"></div></div>';

	            $view .= '<div class="pg-dwnld-small">';
	            $view .= '<a class="dwnld-button" href="'.DOL_URL_ROOT.'/document.php?modulepart=genrapports&file='.$download_file.'&entity='.$entity.'">Télécharger le fichier</a>'; 
	            $view .= '</div>';

	            $view .= '<div class="pg-col-content" style="display:none;"><div class="div-table-responsive"><table id="pg-result-table" class="tagtable liste" style="max-width: 100%;"><tbody>';

	            $view .= '<tr class="liste_titre">';
	            foreach ($tab_labels as $label): $view .= '<th class="liste_titre">'.$label.'</th>'; endforeach;
	            $view .= '</tr>';
	            
	            while ($i < $count_dundf): $i++;
	                $anouveaux_ndf = $db->fetch_object($results_dundf);

	                $tab_labels = array(strval($anouveaux_ndf->numero_compte),$anouveaux_ndf->montant);
	                $tab_type_labels = array('Text','Numeric');
	                $csv_anvx_ndf->write_title($tab_labels,$tab_labels,$langs,$tab_type_labels);

	                $view .= '<tr>';
	                $view .= '<td>'.$anouveaux_ndf->numero_compte.'</td>';
	                $view .= '<td>'.number_format($anouveaux_ndf->montant,2,',','').'</td>';
	                $view .= '</tr>';

	                array_push($tab_ndf, $anouveaux_ndf);
	            endwhile;

	            $view .= '</tbody></table></div></div>';
	            $view .= '</div></div>';

	            $csv_anvx_ndf->write_footer($langs);
	            $csv_anvx_ndf->close_file();

	        endif;

	    endif;

	    // Dû client
	    if($type == 'client' || $type == 'all' ):

	        $sql_duclient = "SELECT";
	        $sql_duclient .= " llx_societe.nom,";
	        $sql_duclient .= " GROUP_CONCAT(llx_accounting_bookkeeping.numero_compte SEPARATOR '|') AS numero_compte,";
	        $sql_duclient .= " GROUP_CONCAT(llx_accounting_bookkeeping.subledger_account SEPARATOR '|') AS compte_auxiliaire,";
	        $sql_duclient .= " GROUP_CONCAT(llx_facture.ref SEPARATOR '|') AS ref,";
	        $sql_duclient .= " GROUP_CONCAT(llx_facture.datef SEPARATOR '|') AS datef,";
	        $sql_duclient .= " GROUP_CONCAT(llx_facture.total SEPARATOR '|') AS total_ht,";
	        $sql_duclient .= " GROUP_CONCAT(llx_facture.total_ttc SEPARATOR '|') AS total_ttc,";
	        $sql_duclient .= " GROUP_CONCAT(llx_paiement.datep SEPARATOR '|') AS datep,";
	        $sql_duclient .= " SUM(llx_paiement_facture.amount) AS montantp";
	        $sql_duclient .= " FROM ((llx_societe INNER JOIN llx_facture ON llx_societe.rowid = llx_facture.fk_soc) INNER JOIN llx_paiement_facture ON llx_facture.rowid = llx_paiement_facture.fk_facture) INNER JOIN llx_paiement ON llx_paiement_facture.fk_paiement = llx_paiement.rowid";
	        $sql_duclient .= " INNER JOIN llx_accounting_bookkeeping ON llx_facture.ref = llx_accounting_bookkeeping.doc_ref";
	        $sql_duclient .= " WHERE (((llx_facture.datef) Like '%".$year_prev."%') AND ((llx_paiement.datep) Like '%".$year."%'))";
	        $sql_duclient .= " GROUP BY llx_societe.nom";
	        $sql_duclient .= " ORDER BY llx_societe.nom";
	        //echo $sql_duclient.'<br>';

	        $results_duclient = $db->query($sql_duclient);

	        if($results_duclient): 

	            $csv_anvx_client = new ExportCsv($db);

	            // ON DONNE UN NOM AU FICHIER
	            $file_title = 'genrapports-anouveaux-clients-'.$year.'.'.$csv_anvx_client->extension;

	            // ON DEFINIT LE CHEMIN COMPLET DU FICHIER
	            $dir_file = $dir_day.'/'.$file_title;
	            $download_file = urlencode($d.'/'.$file_title);

	            // ON OUVRE LE FICHIER
	            $csv_anvx_client->open_file($dir_file,$langs);

	            // ON ECRIT LE HEADER DU FICHIER
	            $csv_anvx_client->write_header($langs);

	            // ON ECRIT LA PREMIERE LIGNE 
	            $tab_labels = array('Client','Montant');
	            $tab_type_labels = array('Text','Text');
	            $csv_anvx_client->write_title($tab_labels,$tab_labels,$langs,$tab_type_labels);

	            $count_duclient = $db->num_rows($results_duclient); $i = 0;

	            $view .= '<div class="pg-col-wrapper"><div class="pg-col">';
	            $view .= '<h2>A Nouveaux - Note de Frais <span class="white">'.$count_duclient.' resultats</span></h2>';
	            $view .= '<div class="gen-openclose"><div class="chevron bottom"></div></div>';

	            $view .= '<div class="pg-dwnld-small">';
	            $view .= '<a class="dwnld-button" href="'.DOL_URL_ROOT.'/document.php?modulepart=genrapports&file='.$download_file.'&entity='.$entity.'">Télécharger le fichier</a>'; 
	            $view .= '</div>';

	            $view .= '<div class="pg-col-content" style="display:none;"><div class="div-table-responsive"><table id="pg-result-table" class="tagtable liste" style="max-width: 100%;"><tbody>';

	            $view .= '<tr class="liste_titre">';
	            foreach ($tab_labels as $label): $view .= '<th class="liste_titre">'.$label.'</th>'; endforeach;
	            $view .= '</tr>';

	            while ($i < $count_duclient): $i++;
	                $anouveaux_client = $db->fetch_object($results_duclient);

	                $tab_labels = array(strval($anouveaux_client->nom),$anouveaux_client->montantp);
	                $tab_type_labels = array('Text','Numeric');
	                $csv_anvx_client->write_title($tab_labels,$tab_labels,$langs,$tab_type_labels);

	                $view .= '<tr>';
	                $view .= '<td>'.$anouveaux_client->nom.'</td>';
	                $view .= '<td>'.number_format($anouveaux_client->montantp,2,',','').'</td>';
	                $view .= '</tr>';

	                array_push($tab_client, $anouveaux_client);
	            endwhile;

	            $view .= '</tbody></table></div></div>';
	            $view .= '</div></div>';

	            $csv_anvx_client->write_footer($langs);
	            $csv_anvx_client->close_file();

	        endif;

	    endif;

	    //return array('fournisseur' => $tab_fournisseur,'ndf' => $tab_ndf,'client' => $tab_client);
	    return $view;

	    // Bornage dates :
	    //SELECT * FROM `llx_facture` WHERE `datef` > '2019-03-31';
	    //SELECT * FROM `llx_facture` WHERE `datef` < '2020-04-01';
	}*/


}