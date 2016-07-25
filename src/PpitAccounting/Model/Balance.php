<?php
namespace PpitAccounting\Model;

use PpitCore\Model\Context;
use Zend\Db\Sql\Where;
use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;

class BalanceRow
{
	public $journal_code;
	public $account;
	public $account_caption;
	public $debit_amount = 0;
	public $credit_amount = 0;
}

class Balance
{
	public $year;
	public $month;
	public $rows;
	public $debit_sum;
	public $credit_sum;
	
	public function __construct($year, $month, $includes_closing = false)
	{
		$context = Context::getCurrent();
		$accounts = $context->getConfig()['ppitAccountingSettings']['accounts'];
		$this->year = $year;

		// Read the journal
    	$select = Journal::getTable()->getSelect()
    		->where(array('year' => $year))
    		->order('account');
    	if ($month) $select->where->like('operation_date', $year.'-'.$month.'%');
    	if ($includes_closing) $select->where->in('journal_code', array('general', 'closing'));
    	else $select->where->in('journal_code', array('general'));
    	$cursor = Journal::getTable()->selectWith($select);
    	$this->rows = array();
    	$this->debit_sum = 0;
    	$this->credit_sum = 0;
    	$currentAccount = null;
    	foreach($cursor as $row) {
    		if ($row->account != $currentAccount) {
    			$this->rows[$row->account] = new BalanceRow;
    			$this->rows[$row->account]->account = $row->account;
    			$this->rows[$row->account]->account_caption = $accounts[$row->account]['caption'];
    			$currentAccount = $row->account;
    		}
    		if ($row->direction == -1) {
    			$this->rows[$row->account]->debit_amount += $row->amount;
    			$this->debit_sum += $row->amount;
    		}
    		else {
    			$this->rows[$row->account]->credit_amount += $row->amount;
    			$this->credit_sum += $row->amount;
    		}
    	}
	}
}