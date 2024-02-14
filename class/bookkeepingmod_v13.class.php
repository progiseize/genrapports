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

/**
 * Modification apportées: 
 * 
 * -----------------------------------
 * function create()
 * 
 * OLD : $sql .= ", ".(!empty($this->label_compte) ? ("'".$this->db->escape($this->label_compte)."'") : "NULL");
 * NEW : $sql .= ", ".(!empty($this->label_operation) ? ("'".$this->db->escape($this->label_operation)."'") : "NULL");
 * 
 * -----------------------------------
 * function fetchAllByAccount()
 * 
 * ADD : $sql .= ", t.date_validated,";
 * ADD : $sql .= " t.extraparams";
 * 
 * OLD: $sqlwhere[] = $key.'=\''.$this->db->idate($value).'\'';
 * NEW: $sqlwhere[] = $key.'=\''.$value.'\'';
 * 
 * ADD : $line->entity = $obj->entity;
 * 
 * OLD : $line->doc_date = $this->db->jdate($obj->doc_date);
 * NEW : $line->doc_date = $obj->doc_date;
 * NEW : $line->extraparams = $obj->extraparams;
 * 
 * */

class BookKeepingMod extends BookKeeping
{
	
	public function create(User $user, $notrigger = false)
	{
		global $conf, $langs;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$error = 0;

		// Clean parameters
		if (isset($this->doc_type)) {
			$this->doc_type = trim($this->doc_type);
		}
		if (isset($this->doc_ref)) {
			$this->doc_ref = trim($this->doc_ref);
		}
		if (isset($this->fk_doc)) {
			$this->fk_doc = (int) $this->fk_doc;
		}
		if (isset($this->fk_docdet)) {
			$this->fk_docdet = (int) $this->fk_docdet;
		}
		if (isset($this->thirdparty_code)) {
			$this->thirdparty_code = trim($this->thirdparty_code);
		}
		if (isset($this->subledger_account)) {
			$this->subledger_account = trim($this->subledger_account);
		}
		if (isset($this->subledger_label)) {
			$this->subledger_label = trim($this->subledger_label);
		}
		if (isset($this->numero_compte)) {
			$this->numero_compte = trim($this->numero_compte);
		}
		if (isset($this->label_compte)) {
			$this->label_compte = trim($this->label_compte);
		}
		if (isset($this->label_operation)) {
			$this->label_operation = trim($this->label_operation);
		}
		if (isset($this->debit)) {
			$this->debit = (float) $this->debit;
		}
		if (isset($this->credit)) {
			$this->credit = (float) $this->credit;
		}
		if (isset($this->montant)) {
			$this->montant = (float) $this->montant;
		}
		if (isset($this->amount)) {
			$this->amount = (float) $this->amount;
		}
		if (isset($this->sens)) {
			$this->sens = trim($this->sens);
		}
		if (isset($this->import_key)) {
			$this->import_key = trim($this->import_key);
		}
		if (isset($this->code_journal)) {
			$this->code_journal = trim($this->code_journal);
		}
		if (isset($this->journal_label)) {
			$this->journal_label = trim($this->journal_label);
		}
		if (isset($this->piece_num)) {
			$this->piece_num = trim($this->piece_num);
		}
		if (empty($this->debit)) $this->debit = 0.0;
		if (empty($this->credit)) $this->credit = 0.0;

		// Check parameters
		if (($this->numero_compte == "") || $this->numero_compte == '-1' || $this->numero_compte == 'NotDefined')
		{
			$langs->loadLangs(array("errors"));
			if (in_array($this->doc_type, array('bank', 'expense_report')))
			{
				$this->errors[] = $langs->trans('ErrorFieldAccountNotDefinedForBankLine', $this->fk_docdet, $this->doc_type);
			} else {
				//$this->errors[]=$langs->trans('ErrorFieldAccountNotDefinedForInvoiceLine', $this->doc_ref,  $this->label_compte);
				$mesg = $this->doc_ref.', '.$langs->trans("AccountAccounting").': '.$this->numero_compte;
				if ($this->subledger_account && $this->subledger_account != $this->numero_compte)
				{
					$mesg .= ', '.$langs->trans("SubledgerAccount").': '.$this->subledger_account;
				}
				$this->errors[] = $langs->trans('ErrorFieldAccountNotDefinedForLine', $mesg);
			}

			return -1;
		}

		$this->db->begin();

		$this->piece_num = 0;

		// First check if line not yet already in bookkeeping.
		// Note that we must include doc_type - fk_doc - numero_compte - label to be sure to have unicity of line (we may have several lines
		// with same doc_type, fk_doc, numero_compte for 1 invoice line when using localtaxes with same account)
		// WARNING: This is not reliable, label may have been modified. This is just a small protection.
		// The page to make journalization make the test on couple doc_type - fk_doc only.
		$sql = "SELECT count(*) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " WHERE doc_type = '".$this->db->escape($this->doc_type)."'";
		$sql .= " AND fk_doc = ".$this->fk_doc;
		//$sql .= " AND fk_docdet = " . $this->fk_docdet;					// This field can be 0 if record is for several lines
		$sql .= " AND numero_compte = '".$this->db->escape($this->numero_compte)."'";
		$sql .= " AND label_operation = '".$this->db->escape($this->label_operation)."'";
		$sql .= " AND entity IN (".getEntity('accountancy').")";

		$resql = $this->db->query($sql);

		if ($resql) {
			$row = $this->db->fetch_object($resql);
			if ($row->nb == 0)
			{
				// Determine piece_num
				$sqlnum = "SELECT piece_num";
				$sqlnum .= " FROM ".MAIN_DB_PREFIX.$this->table_element;
				$sqlnum .= " WHERE doc_type = '".$this->db->escape($this->doc_type)."'"; // For example doc_type = 'bank'
				$sqlnum .= " AND fk_docdet = ".$this->db->escape($this->fk_docdet); // fk_docdet is rowid into llx_bank or llx_facturedet or llx_facturefourndet, or ...
				$sqlnum .= " AND doc_ref = '".$this->db->escape($this->doc_ref)."'"; // ref of source object
				$sqlnum .= " AND entity IN (".getEntity('accountancy').")";

				dol_syslog(get_class($this).":: create sqlnum=".$sqlnum, LOG_DEBUG);
				$resqlnum = $this->db->query($sqlnum);
				if ($resqlnum) {
					$objnum = $this->db->fetch_object($resqlnum);
					$this->piece_num = $objnum->piece_num;
				}

				dol_syslog(get_class($this).":: create this->piece_num=".$this->piece_num, LOG_DEBUG);
				if (empty($this->piece_num)) {
					$sqlnum = "SELECT MAX(piece_num)+1 as maxpiecenum";
					$sqlnum .= " FROM ".MAIN_DB_PREFIX.$this->table_element;
					$sqlnum .= " WHERE entity IN (".getEntity('accountancy').")";

					dol_syslog(get_class($this).":: create sqlnum=".$sqlnum, LOG_DEBUG);
					$resqlnum = $this->db->query($sqlnum);
					if ($resqlnum) {
						$objnum = $this->db->fetch_object($resqlnum);
						$this->piece_num = $objnum->maxpiecenum;
					}
					dol_syslog(get_class($this).":: create this->piece_num=".$this->piece_num, LOG_DEBUG);
				}
				if (empty($this->piece_num)) {
					$this->piece_num = 1;
				}

				$now = dol_now();

				$sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element." (";
				$sql .= "doc_date";
				$sql .= ", date_lim_reglement";
				$sql .= ", doc_type";
				$sql .= ", doc_ref";
				$sql .= ", fk_doc";
				$sql .= ", fk_docdet";
				$sql .= ", thirdparty_code";
				$sql .= ", subledger_account";
				$sql .= ", subledger_label";
				$sql .= ", numero_compte";
				$sql .= ", label_compte";
				$sql .= ", label_operation";
				$sql .= ", debit";
				$sql .= ", credit";
				$sql .= ", montant";
				$sql .= ", sens";
				$sql .= ", fk_user_author";
				$sql .= ", date_creation";
				$sql .= ", code_journal";
				$sql .= ", journal_label";
				$sql .= ", piece_num";
				$sql .= ', entity';
				$sql .= ") VALUES (";
				$sql .= "'".$this->db->idate($this->doc_date)."'";
				$sql .= ", ".(!isset($this->date_lim_reglement) || dol_strlen($this->date_lim_reglement) == 0 ? 'NULL' : "'".$this->db->idate($this->date_lim_reglement)."'");
				$sql .= ", '".$this->db->escape($this->doc_type)."'";
				$sql .= ", '".$this->db->escape($this->doc_ref)."'";
				$sql .= ", ".$this->fk_doc;
				$sql .= ", ".$this->fk_docdet;
				$sql .= ", ".(!empty($this->thirdparty_code) ? ("'".$this->db->escape($this->thirdparty_code)."'") : "NULL");
				$sql .= ", ".(!empty($this->subledger_account) ? ("'".$this->db->escape($this->subledger_account)."'") : "NULL");
				$sql .= ", ".(!empty($this->subledger_label) ? ("'".$this->db->escape($this->subledger_label)."'") : "NULL");
				$sql .= ", '".$this->db->escape($this->numero_compte)."'";
				$sql .= ", ".(!empty($this->label_operation) ? ("'".$this->db->escape($this->label_operation)."'") : "NULL");
				$sql .= ", '".$this->db->escape($this->label_operation)."'";
				$sql .= ", ".$this->debit;
				$sql .= ", ".$this->credit;
				$sql .= ", ".$this->montant;
				$sql .= ", ".(!empty($this->sens) ? ("'".$this->db->escape($this->sens)."'") : "NULL");
				$sql .= ", '".$this->db->escape($this->fk_user_author)."'";
				$sql .= ", '".$this->db->idate($now)."'";
				$sql .= ", '".$this->db->escape($this->code_journal)."'";
				$sql .= ", ".(!empty($this->journal_label) ? ("'".$this->db->escape($this->journal_label)."'") : "NULL");
				$sql .= ", ".$this->db->escape($this->piece_num);
				$sql .= ", ".(!isset($this->entity) ? $conf->entity : $this->entity);
				$sql .= ")";

				$resql = $this->db->query($sql);
				if ($resql) {
					$id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);

					if ($id > 0) {
						$this->id = $id;
						$result = 0;
					} else {
						$result = -2;
						$error++;
						$this->errors[] = 'Error Create Error '.$result.' lecture ID';
						dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);
					}
				} else {
					$result = -1;
					$error++;
					$this->errors[] = 'Error '.$this->db->lasterror();
					dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);
				}
			} else {	// Already exists
				$result = -3;
				$error++;
				$this->error = 'BookkeepingRecordAlreadyExists';
				dol_syslog(__METHOD__.' '.$this->error, LOG_WARNING);
			}
		} else {
			$result = -5;
			$error++;
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);
		}

		// Uncomment this and change MYOBJECT to your own tag if you
		// want this action to call a trigger.
		//if (! $error && ! $notrigger) {

		// // Call triggers
		// $result=$this->call_trigger('MYOBJECT_CREATE',$user);
		// if ($result < 0) $error++;
		// // End call triggers
		//}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return $result;
		}
	}

	public function fetchAllByAccount($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND', $option = 0)
	{
		global $conf;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$this->lines = array();

		$sql = 'SELECT';
		$sql .= ' t.rowid,';
		$sql .= " t.doc_date,";
		$sql .= " t.doc_type,";
		$sql .= " t.doc_ref,";
		$sql .= " t.fk_doc,";
		$sql .= " t.fk_docdet,";
		$sql .= " t.thirdparty_code,";
		$sql .= " t.subledger_account,";
		$sql .= " t.subledger_label,";
		$sql .= " t.numero_compte,";
		$sql .= " t.label_compte,";
		$sql .= " t.label_operation,";
		$sql .= " t.debit,";
		$sql .= " t.credit,";
		$sql .= " t.montant as amount,";
		$sql .= " t.sens,";
		$sql .= " t.multicurrency_amount,";
		$sql .= " t.multicurrency_code,";
		$sql .= " t.lettering_code,";
		$sql .= " t.date_lettering,";
		$sql .= " t.fk_user_author,";
		$sql .= " t.import_key,";
		$sql .= " t.code_journal,";
		$sql .= " t.journal_label,";
		$sql .= " t.piece_num,";
		$sql .= " t.date_creation,";
		$sql .= " t.date_export";

		$sql .= ", t.date_validated,";
		$sql .= " t.extraparams";


		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key == 't.doc_date') {
					$sqlwhere[] = $key.'=\''.$this->db->idate($value).'\'';
				} elseif ($key == 't.doc_date>=' || $key == 't.doc_date<=') {
					$sqlwhere[] = $key.'\''.$this->db->idate($value).'\'';
				} elseif ($key == 't.numero_compte>=' || $key == 't.numero_compte<=' || $key == 't.subledger_account>=' || $key == 't.subledger_account<=') {
					$sqlwhere[] = $key.'\''.$this->db->escape($value).'\'';
				} elseif ($key == 't.fk_doc' || $key == 't.fk_docdet' || $key == 't.piece_num') {
					$sqlwhere[] = $key.'='.$value;
				} elseif ($key == 't.subledger_account' || $key == 't.numero_compte') {
					$sqlwhere[] = $key.' LIKE \''.$this->db->escape($value).'%\'';
				} elseif ($key == 't.date_creation>=' || $key == 't.date_creation<=') {
					$sqlwhere[] = $key.'\''.$this->db->idate($value).'\'';
				} elseif ($key == 't.date_export>=' || $key == 't.date_export<=') {
					$sqlwhere[] = $key.'\''.$this->db->idate($value).'\'';
				} elseif ($key == 't.credit' || $key == 't.debit') {
					$sqlwhere[] = natural_search($key, $value, 1, 1);
                } elseif ($key == 't.reconciled_option') {
                    $sqlwhere[] = 't.lettering_code IS NULL';
                } elseif ($key == 't.code_journal' && !empty($value)) {
                	if (is_array($value)) {
                		$sqlwhere[] = natural_search("t.code_journal", join(',', $value), 3, 1);
                	} else {
                    	$sqlwhere[] = natural_search("t.code_journal", $value, 3, 1);
                	}
				} else {
					$sqlwhere[] = natural_search($key, $value, 0, 1);
				}
			}
		}
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t';
		$sql .= ' WHERE 1 = 1';
		$sql .= " AND entity IN (".getEntity('accountancy').")";
		if (count($sqlwhere) > 0) {
			$sql .= ' AND '.implode(' '.$filtermode.' ', $sqlwhere);
		}
		// Affichage par compte comptable
		if (!empty($option)) {
			$sql .= ' AND t.subledger_account IS NOT NULL';
			$sql .= ' ORDER BY t.subledger_account ASC';
		} else {
			$sql .= ' ORDER BY t.numero_compte ASC';
		}

		if (!empty($sortfield)) {
			$sql .= ', '.$sortfield.' '.$sortorder;
		}
		if (!empty($limit)) {
			$sql .= ' '.$this->db->plimit($limit + 1, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);

			$i = 0;
			while (($obj = $this->db->fetch_object($resql)) && (empty($limit) || $i < min($limit, $num))) {
				$line = new BookKeepingLine();

				$line->id = $obj->rowid;
				$line->entity = $obj->entity;

				$line->doc_date = $obj->doc_date;
				$line->doc_type = $obj->doc_type;
				$line->doc_ref = $obj->doc_ref;
				$line->fk_doc = $obj->fk_doc;
				$line->fk_docdet = $obj->fk_docdet;
				$line->thirdparty_code = $obj->thirdparty_code;
				$line->subledger_account = $obj->subledger_account;
				$line->subledger_label = $obj->subledger_label;
				$line->numero_compte = $obj->numero_compte;
				$line->label_compte = $obj->label_compte;
				$line->label_operation = $obj->label_operation;
				$line->debit = $obj->debit;
				$line->credit = $obj->credit;
				$line->montant = $obj->amount; // deprecated
				$line->amount = $obj->amount;
				$line->sens = $obj->sens;
				$line->multicurrency_amount = $obj->multicurrency_amount;
				$line->multicurrency_code = $obj->multicurrency_code;
				$line->lettering_code = $obj->lettering_code;
				$line->date_lettering = $obj->date_lettering;
				$line->fk_user_author = $obj->fk_user_author;
				$line->import_key = $obj->import_key;
				$line->code_journal = $obj->code_journal;
				$line->journal_label = $obj->journal_label;
				$line->piece_num = $obj->piece_num;
				$line->date_creation = $this->db->jdate($obj->date_creation);
				$line->date_export = $this->db->jdate($obj->date_export);
				$line->extraparams = $obj->extraparams;

				$this->lines[] = $line;

				$i++;
			}
			$this->db->free($resql);

			return $num;
		} else {
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}
	
		

}