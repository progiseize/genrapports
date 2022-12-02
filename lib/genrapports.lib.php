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

/********************************************/
/*                                          */
/********************************************/
function genrap_AdminPrepareHead(){

    global $langs, $conf, $user;

    $langs->load("genrapports@genrapports");

    $h = 0;
    $head = array();

    if($user->rights->genrapports->configurer):
        $head[$h][0] = dol_buildpath("/genrapports/admin/setup.php", 1);
        $head[$h][1] = $langs->trans($langs->trans('gr_configuration'));
        $head[$h][2] = 'setup';
        $h++;
    endif;
    if($user->rights->genrapports->executer):
        $head[$h][0] = dol_buildpath("/genrapports/index.php", 1);
        $head[$h][1] = $langs->trans($langs->trans('gr_reports'));
        $head[$h][2] = 'index';
        $h++;
    endif;

    complete_head_from_modules($conf, $langs, $object, $head, $h, 'genrapports');

    return $head;
}

/***********/
/**       **/
/***********/
function exec_tabsql($tab){

    global $db, $conf, $langs;

    // Nombre de requêtes à executer
    $nb_sql = count($tab);

    // On instancie les variables de succès et d'erreurs
    $success_sql = 0;
    $errors_sql = array();

    $i = 0;

    // Pour chaque ligne du tableau
    foreach ($tab as $request): $i++;

        // On effectue la requête
        $result = $db->query($request);

        if($result): $success_sql++; 
        else: array_push($errors_sql, $i);
        endif;

    endforeach;

    if($success_sql == $nb_sql):
        //setEventMessages($langs->trans('gr_results_allrequests_ok'), null, 'mesgs');
        return true;
    else:
        $erreurs = implode(',', $errors_sql);
        setEventMessages($langs->trans('gr_error_withlines').' '.$erreurs, null, 'errors');
        return false;
    endif;
}

/***********/
/**       **/
/***********/
function get_cd_bookkeeping($row,$num_compte,$date_start = '',$date_end = '',$mode = ''){

    global $db, $conf, $langs, $user;

    //////////////////////////////////////////////////

    // ON DEFINIT LE REPERTOIRE PRINCIPAL ET ON LE CREE SI BESOIN
        $dir = DOL_DATA_ROOT.'/genrapports';
        if (!is_dir($dir)): if(!mkdir($dir,0755)): setEventMessages($langs->trans('gr_error_crea_folder'), null, 'errors'); endif; endif;

        $d = date('d-m-Y');
        $dir_day = $dir.'/'.$d;
        if (!is_dir($dir_day)): if(!mkdir($dir_day,0755)): setEventMessages($langs->trans('gr_error_crea_folder'), null, 'errors'); endif; endif;

        $csv_details = new ExportCsv($db);

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

    $books = new BookKeepingMod($db);

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

        $db->begin();
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

                $resql = $db->query($sql_save);
                if (!$resql): $error++; $errors[] = 'ID:'.$bookline->id.' :: Error '.$db->lasterror(); endif;

            endif;

        //else: $label_compte = 'cpt-INCONNU'; 
        endif;

    endforeach;

    if($mode == "save"):
        if ($error): $db->rollback();
            setEventMessages($langs->trans('gr_results_save_nberrors').' : '.$error, null, 'errors');
        else: $db->commit();
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

        $result_nbk = $db->query($sql_nbk);
        if($result_nbk):
            $row_nbk = $db->fetch_object($result_nbk);
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

    $line_actif = new BookKeepingMod($db);
    $line_actif->doc_date = $date_end;
    $line_actif->piece_num = $line_actif->getNextNumMvt();
    
    $line_actif->fk_doc = 0;
    $line_actif->fk_docdet = 0;
    $line_actif->debit = $abs_actif;
    $line_actif->credit = 0;
    $line_actif->montant = $abs_actif;
    $line_actif->numero_compte = formattedNbNumber($num_compte,'8'); // NBCOUNTER 
    $line_actif->subledger_label = 'Actif '.$year_actpas[0];
    $line_actif->label_operation = 'Actif '.$year_actpas[0];
    $line_actif->fk_user_author = $user->id;

    $sql_del_401 = "DELETE FROM ".MAIN_DB_PREFIX."accounting_bookkeeping WHERE numero_compte = ".$line_actif->numero_compte; // NBCOUNTER
    $result_401 = $db->query($sql_del_401);
    $a = $line_actif->create($user);

    $line_passif = new BookKeepingMod($db);
    $line_passif->doc_date = $date_end;
    $line_passif->piece_num = $line_passif->getNextNumMvt();
    
    $line_passif->fk_doc = 0;
    $line_passif->fk_docdet = 0;
    $line_passif->debit = 0;
    $line_passif->credit = $abs_passif;
    $line_passif->montant = $abs_passif;
    $line_passif->numero_compte = formattedNbNumber($num_compte,'9'); //// NBCOUNTER
    $line_passif->subledger_label = 'Passif '.$year_actpas[0];
    $line_passif->label_operation = 'Passif '.$year_actpas[0];
    $line_passif->fk_user_author = $user->id;

    $sql_del_401 = "DELETE FROM ".MAIN_DB_PREFIX."accounting_bookkeeping WHERE numero_compte = ".$line_passif->numero_compte; // NBCOUNTER
    $result_401 = $db->query($sql_del_401);
    $b = $line_passif->create($user);

    /*************************************************************************************/
    return array('actif' => $abs_actif, 'passif' => $abs_passif,'total_ligne' => $nb_bookline,'file' => $download_file);
}

/***********/
/**       **/
/***********/
function tableau_resultat($date_start,$date_end,$showaccountdetail,$tab = '', $mode = 'affichage',$action = 'bilan',$array_of_files = array()){

    global $db, $conf, $user, $langs;

    $conf->global->EXPORT_CSV_FORCE_CHARSET = "UTF-8";
    $entity = $conf->entity;

    $view = ""; 
    $error = 0;

    // SI ON EST EN MODE CALCUL, ON EFFECTUE LES REQUETES $TAB
    if($mode == 'calcul'): exec_tabsql($tab); 
    // SINON, ON CREE UN FICHIER D'EXPORT
    else:

        // ON DEFINIT LE REPERTOIRE PRINCIPAL ET ON LE CREE SI BESOIN
        $dir = DOL_DATA_ROOT.'/genrapports';
        if (!is_dir($dir)): if(!mkdir($dir,0755)): setEventMessages($langs->trans('gr_error_crea_folder'), null, 'errors'); endif; endif;

        $d = date('d-m-Y');
        $dir_day = $dir.'/'.$d;
        if (!is_dir($dir_day)): if(!mkdir($dir_day,0755)): setEventMessages($langs->trans('gr_error_crea_folder'), null, 'errors'); endif; endif;

        $csv_file = new ExportCsv($db);

        // ON DONNE UN NOM AU FICHIER
        $file_date = date('d_m_Y');
        $file_title = 'genrapports-'.$action.'-'.$file_date.'.'.$csv_file->extension;

        // ON DEFINIT LE CHEMIN COMPLET DU FICHIER
        $dir_file = $dir_day.'/'.$file_title;
        $download_file = urlencode($d.'/'.$file_title);

        $n = dol_now();

        $files_rapport = array();
        $files_rapport[$action] = DOL_URL_ROOT.'/document.php?modulepart=genrapports&file='.$download_file.'&entity='.$entity;
               
        if(!empty($array_of_files)): foreach ($array_of_files as $label => $fichier):
            $files_rapport[$label] = DOL_URL_ROOT.'/document.php?modulepart=genrapports&file='.$fichier.'&entity='.$entity;
        endforeach; endif;

        /*$view .= '<div class="gen-results">';
        $view .= '<div class="pg-col-desc">'.$langs->trans('gr_index_rapport_generatetime').' '.date('d/m/Y', $n).' &agrave; '.date('H:i:s', $n).'</div>';
        $view .= '<div id="pg-result-buttons">';
        $view .= '<a class="dwnld-button" href="'.DOL_URL_ROOT.'/document.php?modulepart=genrapports&file='.$download_file.'&entity='.$entity.'">'.$langs->trans('gr_button_downloadfile').'</a>';

        if(!empty($array_of_files)): foreach ($array_of_files as $label => $fichier):
            
            $view .= '<a class="dwnld-button" href="'.DOL_URL_ROOT.'/document.php?modulepart=genrapports&file='.$fichier.'&entity='.$entity.'">'.$label.'</a>';

        endforeach; endif;

        $view .= '</div>'; 
        $view .= '</div>';*/

        /*if($action == 'bilan'):
            $view .= '<form  enctype="multipart/form-data" action="'.$_SERVER["PHP_SELF"].'" method="post">';
            $view .= '<input type="hidden" name="action" value="sauvegarde">';
            $view .= '<input type="hidden" name="gen-datestart" value="'.$date_start.'">';
            $view .= '<input type="hidden" name="gen-dateend" value="'.$date_end.'">';
            $view .= '<input type="submit" value="Sauvegarder">';
            $view .= '</form">';
        endif;*/

        // ON OUVRE LE FICHIER
        $csv_file->open_file($dir_file,$langs);

        // ON ECRIT LE HEADER DU FICHIER
        $csv_file->write_header($langs);

    endif;

    $date_start = explode('-', $date_start);
    $date_end = explode('-', $date_end);

    $ndt_start = $date_start;
    $ndt_end = $date_end;

    $dts = $date_start[2].'/'.$date_start[1].'/'.$date_start[0]; 
    $dte = $date_end[2].'/'.$date_end[1].'/'.$date_end[0];

    $months = array(
        1 => $langs->trans("MonthShort01"),
        2 => $langs->trans("MonthShort02"),
        3 => $langs->trans("MonthShort03"),
        4 => $langs->trans("MonthShort04"),
        5 => $langs->trans("MonthShort05"),
        6 => $langs->trans("MonthShort06"),
        7 => $langs->trans("MonthShort07"),
        8 => $langs->trans("MonthShort08"),
        9 => $langs->trans("MonthShort09"),
        10 => $langs->trans("MonthShort10"),
        11 => $langs->trans("MonthShort11"),
        12 => $langs->trans("MonthShort12"),
    );

    $listm = array();

    // On supprime les mois en trop dans l'année
    foreach ($months as $k => $v):

        // SI MEME ANNEE
        if(intval($date_start[0]) == intval($date_end[0])):
            if($k < intval($date_start[1])): unset($months[$k]); endif;
            if($k > intval($date_end[1])): unset($months[$k]); endif;
        endif;

        // SI ANNEE DIFF, ON REARRANGE LES MOIS
        if(intval($date_start[0]) < intval($date_end[0])):
            

            $f_month = intval($date_start[1]);
            while($f_month <= 12):
                $listm[$f_month] = $months[$f_month];
                $f_month++;
            endwhile;

            $i = 0;
            while ($i < intval($date_start[1])): $i++;
                $listm[$i] = $months[$i];
            endwhile;

        endif;
    endforeach;
    if(!empty($listm)): $months = $listm; endif;

    // ON SELECTIONNE LA PLAGE DE DATE
    $date_start = dol_mktime(0, 0, 0, $date_start[1], $date_start[2], $date_start[0]); // 24-12-2020 // 2020-12-24
    $date_end = dol_mktime(23, 59, 59, $date_end[1], $date_end[2], $date_end[0]);

    // $date_start and $date_end are defined. We force $start_year and $nbofyear ----> PERIODE PRECEDENTE
    $tmps = dol_getdate($date_start);
    $start_year = $tmps['year'];
    $start_month = $tmps['mon'];
    $tmpe = dol_getdate($date_end);
    $year_end = $tmpe['year'];
    $month_end = $tmpe['mon'];
    $nbofyear = ($year_end - $start_year) + 1;

    $date_start_previous = dol_time_plus_duree($date_start, -1, 'y');
    $date_end_previous = dol_time_plus_duree($date_end, -1, 'y');

    // --- //

    $cat_id = null;
    $modecompta = $conf->global->ACCOUNTING_MODE;

    // Security check
    if ($user->socid > 0): accessforbidden(); endif;
    if (!$user->rights->accounting->comptarapport->lire): accessforbidden(); endif; 

    $AccCat = new AccountancyCategoryMod($db);
    $cats = $AccCat->getCats();

    if($mode != 'calcul'):

        //$tab_head .= '<th>'.$langs->trans("PreviousPeriod").'</th>';
        //html_entity_decode($langs->trans("PreviousPeriod"))

        $tab_head = '<tr class="liste_titre pgsz-optiontable-coltitle">';
        $tab_head .= '<th colspan="2">'.$langs->trans('AccountingCategory').'</th>';     
        $tab_head .= '<th class="right" >'.$dts.' - '.$dte.'</th>';

        $tab_labels = array(html_entity_decode($langs->trans("AccountingCategory")), $dts.' - '.$dte);
        $tab_type_labels = array('Text','Text');

        $nbm = 0;

        foreach ($months as $k => $v):
            $tab_head .= '<th class="right width50">'.$langs->trans('MonthShort'.sprintf("%02s", intval($k))).'</th>';        
            array_push($tab_labels, html_entity_decode($langs->trans('MonthShort'.sprintf("%02s", intval($k)))));
            array_push($tab_type_labels, 'Text');
        endforeach;

        $tab_head .= '</tr>';

        $csv_file->write_title($tab_labels,$tab_labels,$langs,$tab_type_labels);

    endif;

    $j = 1;
    $sommes = array();
    $totPerAccount = array();

    if (!is_array($cats) && $cats < 0): setEventMessages(null, $AccCat->errors, 'errors');
    elseif (is_array($cats) && count($cats) > 0):

        foreach ($cats as $cat):

            //if($mode != 'calcul'): echo '--- '.$cat['code'].'<br>'; endif;

            // Loop on each group
            if (!empty($cat['category_type'])):
                                
                $formula = $cat['formula'];

                if($mode != 'calcul'):

                    $tab_line = array(); $tab_type_line = array();

                    $view .= '<tr class="oddeven pgsz-optiontable-tr liste_total">';
                    $view .= '<td>'.$cat['code'].'</td>';
                    $view .= '<td>'.$cat['label'].'</td>';

                    array_push($tab_line,$cat['code'].' '.$cat['label']);
                    array_push($tab_type_line, 'Text');

                endif;

                $vars = array();

                // Previous Fiscal year (N-1)
                foreach ($sommes as $code => $det): 
                    if(is_null($det['N'])): $valdet = 0; else: $valdet = $det['N']; endif;
                    $vars[$code] = $valdet;
                endforeach;

                $result = strtr($formula, $vars);
                $r = dol_eval($result, 1);

                if($mode != 'calcul'):
                    // $view .= '<td class="liste_total right">'.price($r).'</td>';
                    // array_push($tab_line,price($r));
                    // array_push($tab_type_line, 'Numeric');                   
                endif;

                $code = $cat['code']; // code of categorie ('VTE', 'MAR', ...)
                $sommes[$code]['NP'] += $r;

                if (is_array($sommes) && !empty($sommes)): foreach ($sommes as $code => $det):

                    if(is_null($det['N'])): $valdet = 0; else: $valdet = $det['N']; endif;
                    $vars[$code] = $valdet;

                endforeach; endif;

                $result = strtr($formula, $vars);
                $r = dol_eval($result, 1);

                if($mode != 'calcul'):

                    $view .= '<td class="right">'.price($r).'</td>';                
                    array_push($tab_line,price($r));
                    array_push($tab_type_line, 'Numeric');

                endif;

                $sommes[$code]['N'] += $r;

                if($mode != 'calcul'):

                    // Detail par mois
                    foreach ($months as $k => $v):
                        if (($k) >= $start_month):
                            foreach ($sommes as $code => $det):
                                if(!is_null($det['M'][$k])): $vars[$code] = $det['M'][$k];
                                else: $vars[$code] = 0;
                                endif;
                                
                            endforeach;
                            $result = strtr($formula, $vars);
                            $r = dol_eval($result, 1);
                            $view .= '<td class="right">'.price($r).'</td>';                            
                            array_push($tab_line,price($r));
                            array_push($tab_type_line, 'Numeric');
                            $sommes[$code]['M'][$k] += $r;
                        endif;
                    endforeach;

                    foreach ($months as $k => $v):
                        if (($k) < $start_month):
                            foreach ($sommes as $code => $det):
                                if(!is_null($det['M'][$k])): $vars[$code] = $det['M'][$k];
                                else: $vars[$code] = 0;
                                endif;
                            endforeach;
                            $result = strtr($formula, $vars);
                            $r = dol_eval($result, 1);
                            $view .= '<td class="liste_total right">'.price($r).'</td>';
                            array_push($tab_line,price($r));
                            array_push($tab_type_line, 'Numeric');
                            $sommes[$code]['M'][$k] += $r;
                        endif;
                    endforeach;

                endif;

                $view .= "</tr>\n";
                if($mode != 'calcul'): $csv_file->write_title($tab_line,$tab_line,$langs,$tab_type_line); /*var_dump($csv_file);*/endif;

            else:

                $code = $cat['code'];

                $totCat = array();
                $totCat['NP'] = 0;
                $totCat['N'] = 0;
                $totCat['M'] = array();
                foreach ($months as $k => $v): $totCat['M'][$k] = 0; endforeach;

                $cpts = $AccCat->getCptsCat($cat['rowid']);
                $arrayofaccountforfilter = array();
                foreach ($cpts as $i => $cpt): $arrayofaccountforfilter[] = $cpt['account_number']; endforeach;

                //if($cat['code'] == '290'):var_dump($csv_file); endif;

                // N-1
                if (!empty($arrayofaccountforfilter)):
                    
                    $return = $AccCat->getSumDebitCredit($arrayofaccountforfilter, $date_start_previous, $date_end_previous, $cat['dc'] ? $cat['dc'] : 0);
                    if($mode != 'calcul'):
                        //var_dump($arrayofaccountforfilter);
                    endif;

                    if ($return < 0): setEventMessages(null, $AccCat->errors, 'errors'); $resultNP = 0;
                    else :
                        foreach ($cpts as $i => $cpt):
                            $resultNP = empty($AccCat->sdcperaccount[$cpt['account_number']]) ? 0 : $AccCat->sdcperaccount[$cpt['account_number']];
                            $totCat['NP'] += $resultNP;
                            $sommes[$code]['NP'] += $resultNP;
                            $totPerAccount[$cpt['account_number']]['NP'] = $resultNP;
                        endforeach;
                    endif;
                endif;

                // -- //

                foreach ($cpts as $i => $cpt):

                    $resultN = 0;
                    foreach ($months as $k => $v):
                        $monthtoprocess = $k; // ($k+1) is month 1, 2, ..., 12
                        $yeartoprocess = $start_year;
                        if (($k + 1) < $start_month) : $yeartoprocess++; endif;

                        //var_dump($monthtoprocess.'_'.$yeartoprocess);
                        $return = $AccCat->getSumDebitCredit($cpt['account_number'], $date_start, $date_end, $cat['dc'] ? $cat['dc'] : 0, 'nofilter', $monthtoprocess, $yeartoprocess);

                        if ($return < 0): setEventMessages(null, $AccCat->errors, 'errors'); $resultM = 0; else: $resultM = $AccCat->sdc; endif;

                        $totCat['M'][$k] += $resultM;
                        $sommes[$code]['M'][$k] += $resultM;
                        $totPerAccount[$cpt['account_number']]['M'][$k] = $resultM;

                        $resultN += $resultM;
                    endforeach;

                    $totCat['N'] += $resultN;
                    $sommes[$code]['N'] += $resultN;
                    $totPerAccount[$cpt['account_number']]['N'] = $resultN;
                endforeach;


                if($mode != 'calcul'):

                    $tab_line = array(); $tab_type_line = array();
                    $label_cpt = "";

                    $view .= '<tr class="oddeven pgsz-optiontable-tr">';

                    // Column group
                    $view .= '<td class="">'.$cat['code'].'</td>';

                    // Label of group
                    $view .= '<td>'.$cat['label'];

                    if (count($cpts) > 0):
                    /* MIS EN COMMENTAIRE POUR NE PAS AVOIR LE DETAIL DES GROUPES PERSONNALISES - NUMEROS DE COMPTES SUPERFLUX                  
                        $i = 0;
                        foreach ($cpts as $cpt):

                            if ($i > 5): $view .= '...)'; $label_cpt .= '...)'; break; endif;

                            if ($i > 0): $view .= ', '; $label_cpt .= ', ';
                            else : $view .= '('; $label_cpt .= '(';
                            endif;

                            $view .= $cpt['account_number']; $label_cpt .= $cpt['account_number'];

                            $i++;
                        endforeach;
                        if ($i <= 5): $view .= ')'; $label_cpt .= ')';endif;*/
                    else:
                        $view .= ' - <span class="warning">'.$langs->trans('GroupIsEmptyCheckSetup').'</span>';
                    endif;
                    $view .= '</td>';

                    $column_group_text = $cat['code'].' '.$cat['label'].' '.$label_cpt;
                    array_push($tab_line,$column_group_text);
                    array_push($tab_type_line, 'Text');

                    //$view .= '<td class="right">'.price($totCat['NP']).'</td>';
                    $view .= '<td class="right">'.price($totCat['N']).'</td>';

                    // array_push($tab_line,price($totCat['NP']));
                    // array_push($tab_type_line, 'Numeric');

                    array_push($tab_line,price($totCat['N']));
                    array_push($tab_type_line, 'Numeric');

                    // Each month
                    foreach ($totCat['M'] as $k => $v) :
                        if (($k + 1) >= $start_month): //var_dump($k, $start_month);
                            $view .= '<td class="right">'.price($v).'</td>';        // TODO HERE                    
                            array_push($tab_line,price($v));
                            array_push($tab_type_line, 'Numeric');
                        endif;
                    endforeach;
                    foreach ($totCat['M'] as $k => $v):
                        if (($k + 1) < $start_month):
                            $view .= '<td class="right">'.price($v).'</td>';
                            array_push($tab_line,price($v));array_push($tab_type_line, 'Numeric');
                        endif;
                    endforeach;

                    $view .='</tr>';
                    
                    $csv_file->write_title($tab_line,$tab_line,$langs,$tab_type_line);

                    //////////////////////////////////////////

                    // Loop on detail of all accounts to output the detail
                    if ($showaccountdetail != 'no') :
                        foreach ($cpts as $i => $cpt):

                            $resultNP = $totPerAccount[$cpt['account_number']]['NP'];
                            $resultN = $totPerAccount[$cpt['account_number']]['N'];

                            if ($showaccountdetail == 'all' || $resultN != 0):

                                
                                $tab_line = array(); $tab_type_line = array();                              

                                $view .= '<tr class="oddeven pgsz-optiontable-tr">';
                                $view .= '<td></td>'; 
                                
                                $view .= '<td class="tdoverflowmax200"> &nbsp; &nbsp; '.length_accountg($cpt['account_number']).' - '.$cpt['account_label'].'</td>';
                                //$view .= '<td class="right">'.price($resultNP).'</td>';
                                $view .= '<td class="right">'.price($resultN).'</td>';
                                
                                array_push($tab_line,length_accountg($cpt['account_number']).' - '.$cpt['account_label']);array_push($tab_type_line, 'Text');
                                //array_push($tab_line,price($resultNP));array_push($tab_type_line, 'Numeric');
                                array_push($tab_line,price($resultN));array_push($tab_type_line, 'Numeric');
                                

                                // Make one call for each month
                                foreach ($months as $k => $v):
                                    if (($k + 1) >= $start_month):
                                        $resultM = $totPerAccount[$cpt['account_number']]['M'][$k];
                                        $view .= '<td class="right">'.price($resultM).'</td>';                                      
                                        array_push($tab_line,price($resultM));array_push($tab_type_line, 'Numeric');                                        
                                    endif;
                                endforeach;

                                foreach ($months as $k => $v):
                                    if (($k + 1) < $start_month):
                                        $resultM = $totPerAccount[$cpt['account_number']]['M'][$k];
                                        $view .= '<td class="right">'.price($resultM).'</td>';                                      
                                        array_push($tab_line,price($resultM));array_push($tab_type_line, 'Numeric');                                        
                                    endif;
                                endforeach;
                                $view .= "</tr>";
                                
                                $csv_file->write_title($tab_line,$tab_line,$langs,$tab_type_line);
                                
                            endif;

                        endforeach;
                    endif;
                endif;      
            endif;

            if($cat['code'] == '290' && $mode != 'calcul'):
                //var_dump($csv_file);
            endif;
        endforeach;
    endif;

    // BENEFICE OU PERTE = $sommes[240]['N'];
    //return $sommes[240]['N'];
    if($mode == 'calcul'): 

        $line_borp = new BookKeepingMod($db);
        $line_borp->doc_date = $date_end;
        $line_borp->piece_num = $line_borp->getNextNumMvt();
        
        $line_borp->fk_doc = 0;
        $line_borp->fk_docdet = 0;
        $line_borp->debit = 0;
        $line_borp->credit = $sommes[240]['N'];
        $line_borp->montant = $sommes[240]['N'];
        $line_borp->numero_compte = formattedNbNumber('120'); // NBCOUNTER
        $line_borp->subledger_label = $langs->trans('gr_borp').' '.$year_actpas[0];
        $line_borp->label_operation = $langs->trans('gr_borp').' '.$year_actpas[0];
        $line_borp->fk_user_author = $user->id;

        $sql_del_borp = "DELETE FROM ".MAIN_DB_PREFIX."accounting_bookkeeping WHERE numero_compte = ".$line_borp->numero_compte;
        $result_borp = $db->query($sql_del_borp);
        $b = $line_borp->create($user);

        return $sommes[240]['N'];
    else: 

        $csv_file->write_footer($langs);
        $csv_file->close_file();

        $tabres = array(
            'tab_head' => $tab_head,
            'tab_lines' => $view,
            'generate_time' => dol_now(),
            'files' => $files_rapport,
        );

        return $tabres;
    endif;
}

/***********/
/**       **/
/***********/
function show_anouveaux($year,$type = 'all'){

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

        /*$sql_dufournisseur = "SELECT llx_societe.nom, llx_facture_fourn.ref, llx_facture_fourn.ref_supplier, llx_facture_fourn.datef, llx_paiementfourn_facturefourn.amount, llx_paiementfourn.datep, llx_paiementfourn.ref AS refP";
        $sql_dufournisseur .=" FROM llx_societe INNER JOIN ((llx_paiementfourn_facturefourn INNER JOIN llx_paiementfourn ON llx_paiementfourn_facturefourn.fk_paiementfourn = llx_paiementfourn.rowid) INNER JOIN llx_facture_fourn ON llx_paiementfourn_facturefourn.fk_facturefourn = llx_facture_fourn.rowid) ON llx_societe.rowid = llx_facture_fourn.fk_soc";
        $sql_dufournisseur .= " WHERE (((llx_facture_fourn.datef) Like '%".$year_prev."%') AND ((llx_paiementfourn.datep) Like '%".$year."%'))";
        $sql_dufournisseur .= " ORDER BY llx_societe.nom";*/

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

        /*
            $sql_dundf = "SELECT llx_expensereport.ref, llx_expensereport.total_ht, llx_expensereport.date_fin, llx_payment_expensereport.amount, llx_payment_expensereport.datep";
            $sql_dundf .= " FROM llx_expensereport INNER JOIN llx_payment_expensereport ON llx_expensereport.rowid = llx_payment_expensereport.fk_expensereport";
            $sql_dundf .= " WHERE (((llx_expensereport.date_fin) Like '%".$year_prev."%') AND ((llx_payment_expensereport.datep) Like '%".$year."%'))";
        */

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
    /*SELECT * FROM `llx_facture` WHERE `datef` > '2019-03-31';
    SELECT * FROM `llx_facture` WHERE `datef` < '2020-04-01';*/
}

/***********/
/**       **/
/***********/
function update_bilan($date_start = '',$date_end = '',$tab_update){

    global $db, $conf, $langs, $user;
    $conf->global->EXPORT_CSV_FORCE_CHARSET = "UTF-8";

    foreach($tab_update as $c_update):

        $bookkeep = new BookKeepingMod($db);
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
        $result = $db->query($update_sql);

        if(!$result): 
            setEventMessages($langs->trans('gr_error_maj_accounts'), null, 'errors');
        endif;


    endforeach;
}

/***********/
/**       **/
/***********/
function formattedNbNumber($number,$glue = '0',$before = false){

    global $conf;

    $required_length = intval($conf->global->GENRAPPORTS_NUMBERS_TO_USE);
    
    while(strlen($number) < $required_length):
        if($before): $number = $glue.$number;
        else: $number = $number.$glue;
        endif;
        
    endwhile;

    return $number;
}

?>