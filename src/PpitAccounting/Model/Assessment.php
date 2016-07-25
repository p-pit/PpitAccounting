<?php
namespace PpitAccounting\Model;

use PpitCore\Model\Context;
use Zend\Db\Sql\Where;
use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;

class AssessmentRow
{
	public $journal_code;
	public $account;
	public $account_caption;
	public $debit_amount = 0;
	public $credit_amount = 0;
}

class Assessment
{
	public $year;
	public $month;
	public $rows;
	public $debit_sum;
	public $credit_sum;
	
	public function __construct($year)
	{
		$context = Context::getCurrent();
    	$balance = new Balance($year, null, false);

    	$assessmentAccounts = $context->getConfig()['ppitAccountingSettings']['assessmentAccounts'];
    	$assessmentMapping = $context->getConfig()['ppitAccountingSettings']['assessmentMapping'];
    	$this->year = $year;

    	$this->rows = array();
    	$this->debit_sum = 0;
    	$this->credit_sum = 0;

    	foreach ($assessmentAccounts as $account => $properties) {
			$assessmentRow = new AssessmentRow;
			$assessmentRow->account = $account;
			$assessmentRow->account_caption = $properties['caption'];
			$this->rows[$account] = $assessmentRow;
    	}

    	foreach ($balance->rows as $row) {

			if (array_key_exists($row->account, $assessmentMapping)) {
	    		$assessmentAccount = $assessmentMapping[$row->account];
	    		// Read the balance
				$this->rows[$assessmentAccount]->debit_amount += $row->debit_amount;
				$this->debit_sum += $row->debit_amount;
				$this->rows[$assessmentAccount]->credit_amount += $row->credit_amount;
				$this->credit_sum += $row->credit_amount;
			}
       	}
	}
}