<?php
namespace PpitAccounting\Model;

use PpitCore\Model\Context;
use Zend\Db\Sql\Where;
use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;

class IncomeStatement
{
	public $captions;
	public $tree;
	public $year;
	public $table;
	public $gain = 0;
	public $loss = 0;
	
	private $mapping;

	public function __construct($year)
	{
		$context = Context::getCurrent();
		$this->captions = $context->getConfig()['ppitAccountingSettings']['incomeStatementCaptions'];
		$this->mapping = $context->getConfig()['ppitAccountingSettings']['incomeStatementMapping'];
		$this->tree = $context->getConfig()['ppitAccountingSettings']['incomeStatementTree'];
		$this->year = $year;

		// Generate the result table
		$this->table = array();
		foreach($this->captions as $code => $caption) $this->table[$code] = 0;

		// Filter on income statement accounts
		$filter = array();
		foreach ($this->mapping as $account => $code) $filter[] = $account;

		// Read the journal
    	$select = Journal::getTable()->getSelect();
    	$where = new Where;
    	$where->in('account', $filter);
    	$where->equalTo('journal_code', 'general');
    	$select->where($where);
    	$select->where(array('year' => $year));
    	$cursor = Journal::getTable()->selectWith($select);
    	$rows = array();
    	$sum = 0;
    	foreach($cursor as $row) {
    		$amount = $row->amount * $row->direction;
    		$sum += $amount;
    		$resultRow = $this->mapping[$row->account];
    		$this->table[$resultRow] += $amount;
    		$level3Sum = ((int)$resultRow) / 10;
    		$this->table[$level3Sum] += $amount;
    		$level2Sum = ((int)$resultRow) / 100;
    		$this->table[$level2Sum] += $amount;
    		$level1Sum = ((int)$resultRow) / 1000;
    		$this->table[$level1Sum] += $amount;
    	}
    	if ($sum >= 0) $this->gain = $sum; else $this->loss = - $sum;
    }
}