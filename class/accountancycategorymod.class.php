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
 * Modification apportée : function getSumDebitCredit()
 * 	OLD : $sql .= " AND t.numero_compte IN (".$this->db->sanitize($listofaccount).")";
 * 	NEW : $sql .= " AND t.numero_compte IN (".$listofaccount.")";
 * */


class AccountancyCategoryMod extends AccountancyCategory// extends CommonObject
{
	
	/**
	 * Function to show result of an accounting account from the ledger with a direction and a period
	 *
	 * @param int|array	$cpt 				Accounting account or array of accounting account
	 * @param string 	$date_start			Date start
	 * @param string 	$date_end			Date end
	 * @param int 		$sens 				Sens of the account:  0: credit - debit (use this by default), 1: debit - credit
	 * @param string	$thirdparty_code	Thirdparty code
	 * @param int       $month 				Specifig month - Can be empty
	 * @param int       $year 				Specifig year - Can be empty
	 * @return integer 						<0 if KO, >= 0 if OK
	 */
	public function getSumDebitCredit($cpt, $date_start, $date_end, $sens, $thirdparty_code = 'nofilter', $month = 0, $year = 0)
	{
		global $conf;

		$this->sdc = 0;
		$this->sdcpermonth = array();

		$sql = "SELECT SUM(t.debit) as debit, SUM(t.credit) as credit";
		if (is_array($cpt)): $sql .= ", t.numero_compte as accountancy_account"; endif;
		$sql .= " FROM ".MAIN_DB_PREFIX."accounting_bookkeeping as t";		
		$sql .= " WHERE t.entity = ".$conf->entity;

		if (is_array($cpt)):

			$listofaccount = '';
			foreach ($cpt as $cptcursor):
				if ($listofaccount) {
					$listofaccount .= ",";
				}
				$listofaccount .= "'".$cptcursor."'";
			endforeach;
			$sql .= " AND t.numero_compte IN (".$listofaccount.")"; // MODIFICATION PROGISEIZE

		else: $sql .= " AND t.numero_compte = '".$this->db->escape($cpt)."'";
		endif;

		if (!empty($date_start) && !empty($date_end) && (empty($month) || empty($year))): $sql .= " AND (t.doc_date BETWEEN '".$this->db->idate($date_start)."' AND '".$this->db->idate($date_end)."')"; endif;
		if (!empty($month) && !empty($year)): $sql .= " AND (t.doc_date BETWEEN '".$this->db->idate(dol_get_first_day($year, $month))."' AND '".$this->db->idate(dol_get_last_day($year, $month))."')"; endif;
		if ($thirdparty_code != 'nofilter'): $sql .= " AND t.thirdparty_code = '".$this->db->escape($thirdparty_code)."'"; endif;
		if (is_array($cpt)): $sql .= " GROUP BY t.numero_compte"; endif;

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			if ($num) {
				$obj = $this->db->fetch_object($resql);
				if ($sens == 1) {
					$this->sdc = $obj->debit - $obj->credit;
				} else {
					$this->sdc = $obj->credit - $obj->debit;
				}
				if (is_array($cpt)) {
					$this->sdcperaccount[$obj->accountancy_account] = $this->sdc;
				}
			}
			return $num;
		} else {
			$this->error = "Error ".$this->db->lasterror();
			$this->errors[] = $this->error;
			dol_syslog(__METHOD__." ".$this->error, LOG_ERR);
			return -1;
		}
	}

}
