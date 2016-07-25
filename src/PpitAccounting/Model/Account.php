<?php
namespace PpitAccounting\Model;

use PpitCore\Model\Context;
use Zend\Db\Sql\Where;
use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;

class AccountRow
{
	public $journal_code;
	public $sequence;
	public $operation_date;
	public $accounting_date;
	public $reference;
	public $caption;
	public $proof_id;
	public $currency;
	public $debit_amount;
	public $credit_amount;
}

class Account
{
	public $year;
	public $account;
	public $rows;
	public $debit_sum;
	public $credit_sum;
	public $debit_balance;
	public $credit_balance;
	
	public function __construct($year, $account)
	{
		$context = Context::getCurrent();
		$this->year = $year;
		$this->account = $account;

		// Read the journal
    	$select = Journal::getTable()->getSelect()
    		->where(array('year' => $year, 'journal_code' => 'general', 'account' => $account))
    		->order('sequence');
    	$cursor = Journal::getTable()->selectWith($select);
    	$this->rows = array();
    	$this->debit_sum = 0;
    	$this->credit_sum = 0;
    	$this->debit_balance = 0;
    	$this->credit_balance = 0;
    	foreach($cursor as $row) {
    		$accountRow = new AccountRow;
    		$accountRow->journal_code = $row->journal_code;
    		$accountRow->sequence = $row->sequence;
    		$accountRow->operation_date = $row->operation_date;
    		$accountRow->accounting_date = $row->accounting_date;
    		$accountRow->reference = $row->reference;
    		$accountRow->caption = $row->caption;
    		$accountRow->proof_id = $row->proof_id;
    		$accountRow->currency = $row->currency;
    		if ($row->direction == -1) $accountRow->debit_amount = $row->amount;
    		else $accountRow->credit_amount = $row->amount;
    		$this->debit_sum += $accountRow->debit_amount;
    		$this->credit_sum += $accountRow->credit_amount;
    		$this->rows[] = $accountRow;
    	}
    	if ($this->debit_sum > $this->credit_sum) $this->debit_balance = $this->debit_sum - $this->credit_sum;
    	else $this->credit_balance = $this->credit_sum - $this->debit_sum;
	}
}