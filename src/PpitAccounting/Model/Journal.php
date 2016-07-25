<?php
namespace PpitAccounting\Model;

use PpitAccounting\Model\AccountingYear;
use PpitContact\Model\Community;
use PpitCore\Model\Context;
use PpitDocument\Model\Document;
use Zend\Db\Sql\Where;
use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;

class AccountBalance
{
	public $account;
	public $class;
	public $balance;
	
	public function __construct($account, $class)
	{
		$this->account = $account;
		$this->class = $class;
		$this->balance = 0;
	}
}

class Journal implements InputFilterAwareInterface
{
    public $id;
    public $year;
    public $sequence;
    public $journal_code;
    public $operation_date;
    public $accounting_date;
    public $reference;
    public $caption;
    public $proof_id;
    public $proof_url;
    public $direction;
    public $amount;
    public $currency;
    public $account;
    public $sub_account;
	public $update_time;

    // Transient properties
    public $rows = array();
    public $availableBankJournalEntries;
    public $bank_journal_reference;
    public $files;
    public $properties;
    
    protected $inputFilter;

    // Static fields
    private static $table;
    
    public function getArrayCopy()
    {
        return get_object_vars($this);
    }

    public function exchangeArray($data)
    {
        $this->id = (isset($data['id'])) ? $data['id'] : null;
        $this->year = (isset($data['year'])) ? $data['year'] : null;
        $this->sequence = (isset($data['sequence'])) ? $data['sequence'] : null;
        $this->journal_code = (isset($data['journal_code'])) ? $data['journal_code'] : null;
        $this->operation_date = (isset($data['operation_date'])) ? $data['operation_date'] : null;
        $this->accounting_date = (isset($data['accounting_date'])) ? $data['accounting_date'] : null;
        $this->reference = (isset($data['reference'])) ? $data['reference'] : null;
        $this->caption = (isset($data['caption'])) ? $data['caption'] : null;
        $this->proof_id = (isset($data['proof_id'])) ? $data['proof_id'] : null;
        $this->proof_url = (isset($data['proof_url'])) ? $data['proof_url'] : null;
        $this->direction = (isset($data['direction'])) ? $data['direction'] : null;
        $this->amount = (isset($data['amount'])) ? $data['amount'] : null;
        $this->currency = (isset($data['currency'])) ? $data['currency'] : null;
        $this->account = (isset($data['account'])) ? $data['account'] : null;
        $this->sub_account = (isset($data['sub_account'])) ? $data['sub_account'] : null;
        $this->update_time = (isset($data['update_time'])) ? $data['update_time'] : null;
    }

    public function toArray() {
    	$data = array();
    	$data['id'] = (int) $this->id;
    	$data['year'] = $this->year;
    	$data['sequence'] = (int) $this->sequence;
    	$data['journal_code'] = $this->journal_code;
    	$data['operation_date'] = ($this->operation_date) ? $this->operation_date : null;
    	$data['accounting_date'] = ($this->accounting_date) ? $this->accounting_date : null;
    	$data['reference'] = $this->reference;
    	$data['caption'] = $this->caption;
    	$data['proof_id'] = $this->proof_id;
    	$data['proof_url'] = $this->proof_url;
    	$data['direction'] = $this->direction;
    	$data['amount'] = $this->amount;
    	$data['currency'] = $this->currency;
    	$data['account'] = $this->account;
    	$data['sub_account'] = $this->sub_account;
    	return $data;
    }

    public static function getAvailableBankJournalEntries()
    {
		$accountingYear = AccountingYear::getCurrent();
    	$select = Journal::getTable()->getSelect()
	    	->where(array('year' => $accountingYear->year, 'journal_code' => 'bank', 'sequence' => 0))
    		->order(array('sequence', 'id'));
    	$cursor = Journal::getTable()->selectWith($select);
    	$entries = array();
    	foreach($cursor as $entry) $entries[] = $entry;
    	return $entries;
    }

    public static function getList($params, $major, $dir, $mode = 'todo')
    {
    	$select = Journal::getTable()->getSelect()
    		->order(array($major.' '.$dir, 'sequence DESC', 'id'));
    	 
    	$where = new Where;
    	
    	// Todo list vs search modes
    	if ($mode == 'todo') {
    		$where->equalTo('year', date('Y'));
    		$where->greaterThanOrEqualTo('accounting_journal.account', '6');
    		$where->lessThanOrEqualTo('accounting_journal.account', '799999');
    	}
    	else {
    	
    		// Set the filters
    		foreach ($params as $propertyId => $property) {
    			if ($propertyId == 'journal_code') $where->equalTo('journal_code', $property);
    			elseif (substr($propertyId, 0, 4) == 'min_') $where->greaterThanOrEqualTo('accounting_journal.'.substr($propertyId, 4), $property);
    			elseif (substr($propertyId, 0, 4) == 'max_') $where->lessThanOrEqualTo('accounting_journal.'.substr($propertyId, 4), $property);
    			else $where->like('accounting_journal.'.$propertyId, '%'.$property.'%');
    		}
    	}
    	
    	$select->where($where);
    	$cursor = Journal::getTable()->selectWith($select);
    	$entries = array();
    	foreach($cursor as $entry) {
    		$entry->properties = $entry->toArray();
    		$entries[] = $entry;
    	}
    	return $entries;
    }

    public static function get($id, $column = 'id')
    {
    	$entry = Journal::getTable()->get($id, $column);
    	if (!$entry) return null;
    	return $entry;
    }
    
	public function loadData($data, $files)
	{
		$this->operation_date = $data['operation_date'];
		$this->reference = $data['reference'];
		$this->caption = $data['caption'];
		$this->files = $files;
		$this->bank_journal_reference = $data['bank_journal_reference'];
		$this->rows = array();
		foreach ($data['rows'] as $row) {
			$this->rows[] = $row;
		}
		return 'OK';
	}

	public function loadDataFromRequest($request) {
		$data = array();
		$data['operation_date'] = $request->getPost('operation_date');
		$data['reference'] = $request->getPost('reference');
		$data['caption'] = $request->getPost('caption');
		$data['bank_journal_reference'] = $request->getPost('bank_journal_reference');
		$data['rows'] = array();
		for ($i = 0; $i < 10; $i++) {
			$row = array();
			$row['account'] = $request->getPost('account_'.$i);
			$row['direction'] = $request->getPost('direction_'.$i);
			$row['amount'] = $request->getPost('amount_'.$i);
			$data['rows'][] = $row;
		}

    	// Retrieve the order form
    	$files = $request->getFiles()->toArray();
		
    	if ($this->loadData($data, $files) != 'OK') throw new \Exception('Client error');
	}

	public function add()
	{
		$context = Context::getCurrent();
	    if ($this->files) {
			$root_id = $context->getConfig()['ppitAccountingSettings']['accountingFolderId']; 
    		$document = Document::instanciate($root_id);
    		$document->files = $this->files;
    		$document->saveFile(true, '/P-PIT Finance/');
    		$this->proof_id = $document->save();
    	}
		$accountingYear = AccountingYear::getCurrent();
		$this->year = $accountingYear->year;
		$this->sequence = $accountingYear->next_value;
		$this->journal_code = 'general';
		$this->accounting_date = date('Y-m-d');
		$this->currency = 'EUR';
		foreach ($this->rows as $row) {
			if ($row['account']) {
				$this->id = 0;
				$this->account = $row['account'];
				$this->direction = $row['direction'];
				$this->amount = $row['amount'];
				Journal::getTable()->save($this);
			}
		}
		$accountingYear->next_value++;
		AccountingYear::getTable()->save($accountingYear);
		
		if ($this->bank_journal_reference) {
			$bankJournalEntry = Journal::getTable()->get($this->bank_journal_reference);
			$bankJournalEntry->sequence = $this->sequence;
			Journal::getTable()->save($bankJournalEntry);
		}
	}

	public function addBankStatementEntry()
	{
		$accountingYear = AccountingYear::getCurrent();
		$this->year = $accountingYear->year;
		$this->journal_code = 'bank';
		$this->accounting_date = date('Y-m-d');
		$this->currency = 'EUR';
		foreach ($this->rows as $row) {
			if ($row['account']) {
				$this->id = 0;
				$this->account = $row['account'];
				$this->direction = $row['direction'];
				$this->amount = $row['amount'];
				Journal::getTable()->save($this);
			}
		}
	}
	
    public static function closeProductsCharges($year, $operation_date, $accountingYear)
    {
    	$context = Context::getCurrent();
    
    	// Retrieve the next accounting entry
    	$next_value = $accountingYear->next_value;
    	 
    	// Read the general journal
    	$select = Journal::getTable()->getSelect()
 		   	->where(array('year' => $year, 'journal_code' => 'general'));
    	$cursor = Journal::getTable()->selectWith($select);
    	 
    	// Initialize the balances for each product or charge account
    	$accounts = array();
    	foreach ($context->getConfig()['ppitAccountingSettings']['accounts'] as $account => $properties) {
			$accounts[$account] = array('class' => $properties['class'], 'balance' => 0);
    	}
    
		// Compute the balances
		$sum = 0;
    	foreach ($cursor as $row) {
    		$account = &$accounts[$row->account];
    		if ($account['class'] == 6 || $account['class'] == 7) {
    			$account['balance'] += $row->amount * $row->direction;
				$sum += $row->amount * $row->direction;
    		}
    	}
    	// Generate the closing journal entry
    	$row = new Journal;
    	$row->year = $year;
    	$row->sequence = $next_value;
    	$row->journal_code = 'closing';
    	$row->operation_date = $year.'-12-31';
    	$row->accounting_date = $year.'-12-31';
       	$row->reference = 'Closing';
    	$row->caption = 'Closing';
    	$row->currency = 'EUR';
    	foreach ($accounts as $account => $properties) {
    		if ($properties['class'] == 6 || $properties['class'] == 7) {
	    		$row->id = null;
	    		$row->account = $account;
	    		if ($properties['balance'] != 0) {
	    			if ($properties['balance'] < 0) {
	    				$row->direction = '1';
	    				$row->amount = - $properties['balance'];
	    			}
	    			else {
	    				$row->direction = '-1';
	    				$row->amount = $properties['balance'];
	       			}
	    			Journal::getTable()->save($row);
	    		}
    		}
    	}
	    $row->id = null;
    	if ($sum < 0) {
        	$row->account = '129';
    		$row->direction = '-1';
    		$row->amount = - $sum;
    	}
    	else {
        	$row->account = '120';
    		$row->direction = '1';
    		$row->amount = $sum;
       	}
    	Journal::getTable()->save($row);
    	
    	// Increment the counter
    	$accountingYear->next_value++;
    	$accountingYear->status = 'Products & charges closed';
    	AccountingYear::getTable()->save($accountingYear);
    }

    public static function closeAccounts($year, $operation_date, $accountingYear)
    {
    	$context = Context::getCurrent();

    	// Ensure the next year journal
    	$nextAccountingYear = AccountingYear::getNext($accountingYear);
    	if (!$accountingYear) $nextAccountingYear = AccountingYear::instanciate($accountingYear->year + 1);

    	// Retrieve the next accounting entry
    	$next_value = $accountingYear->next_value;
    
    	// Read the general journal
    	$select = Journal::getTable()->getSelect()
	    	->where(array('year' => $year));
    	$cursor = Journal::getTable()->selectWith($select);
    
    	// Initialize the balances for each product or charge account
    	$accounts = array();
    	foreach ($context->getConfig()['ppitAccountingSettings']['accounts'] as $account => $properties) {
    		$accounts[$account] = new AccountBalance($account, $properties['class']);
    	}

    	// Compute the balances
    	$sum = 0;
    	foreach ($cursor as $row) {
    		$account = $accounts[$row->account];
    		if ($account->class != 6 && $account->class != 7) {
    			$account->balance += $row->amount * $row->direction;
    			$sum += $row->amount * $row->direction;
    		}
    	}

    	// Generate the closing journal entry
    	$row = new Journal;
    	$row->year = $year;
    	$row->sequence = $next_value;
    	$row->journal_code = 'closing';
    	$row->operation_date = $year.'-12-31';
    	$row->accounting_date = $year.'-12-31';
       	$row->reference = 'Closing';
    	$row->caption = 'Closing';
    	$row->currency = 'EUR';

    	// Generate the opening y+1 journal entry
    	$nextRow = new Journal;
    	$nextRow->year = $year + 1;
    	$nextRow->sequence = 0;
    	$nextRow->journal_code = 'general';
    	$nextRow->operation_date = ($year+1).'-01-01';
    	$nextRow->accounting_date = ($year+1).'-01-01';
    	$nextRow->reference = 'Carry forward';
    	$nextRow->caption = 'Carry forward';
    	$nextRow->currency = 'EUR';
    	 
    	foreach ($accounts as $accountBalance) {
    		if ($accountBalance->class != 6 && $accountBalance->class != 7) {
    			$row->id = null;
    			$row->account = $accountBalance->account;
    			
    			$nextRow->id = null;
    			$nextRow->account = $accountBalance->account;
    			
    			if ($accountBalance->balance != 0) {
    				if ($accountBalance->balance < 0) {
    					$row->direction = '1';
    					$row->amount = - $accountBalance->balance;

    					$nextRow->direction = '-1';
    					$nextRow->amount = - $accountBalance->balance;
    				}
    				else {
    					$row->direction = '-1';
    					$row->amount = $accountBalance->balance;
    					
    					$nextRow->direction = '1';
    					$nextRow->amount = $accountBalance->balance;
    				}
    				Journal::getTable()->save($row);
    				Journal::getTable()->save($nextRow);
    			}
    		}
    	}

    	// Increment the counter
    	$accountingYear->next_value++;
    	$accountingYear->status = 'Accounts closed';
    	AccountingYear::getTable()->save($accountingYear);
    }

    public static function nextStep($year, $operation_date)
    {
    	$context = Context::getCurrent();

    	$accountingYear = AccountingYear::getTable()->get($year, 'year');

    	if ($accountingYear->status == 'current') Journal::closeProductsCharges($year, $operation_date, $accountingYear);
    	elseif ($accountingYear->status == 'Products & charges closed') Journal::closeAccounts($year, $operation_date, $accountingYear);
    	else return 'Consistency';

    	return 'OK';
    }

    public static function undoLastStep($year)
    {
    	$context = Context::getCurrent();
    
    	// Retrieve the next accounting entry
    	$accountingYear = AccountingYear::getTable()->get($year, 'year');
    	$next_value = $accountingYear->next_value;

    	if ($accountingYear->status == 'Products & charges closed') {
    	 
	    	// Decrement the counter
	    	$accountingYear->next_value--;
	    	$accountingYear->status = 'current';
	    	AccountingYear::getTable()->save($accountingYear);
    		
    		// Delete the last closing journal entry
	    	Journal::getTable()->multipleDelete(array('year' => $year, 'sequence' => $accountingYear->next_value));
    	}
    	elseif ($accountingYear->status == 'Accounts closed') {
    	
    		// Decrement the counter
    		$accountingYear->next_value--;
    		$accountingYear->status = 'Products & charges closed';
    		AccountingYear::getTable()->save($accountingYear);
    	
    		// Delete the last closing journal entry
    		Journal::getTable()->multipleDelete(array('year' => $year, 'sequence' => $accountingYear->next_value));
    		
    		// Delete the next opening journal entry
	    	Journal::getTable()->multipleDelete(array('year' => $year+1, 'sequence' => 0));
    	}
    	else return 'Consistency';
    	 
    	return 'OK';
    }

    public function setInputFilter(InputFilterInterface $inputFilter)
    {
        throw new \Exception("Not used");
    }

    public function getInputFilter()
    {
        throw new \Exception("Not used");
    }

    public static function getTable()
    {
    	if (!Journal::$table) {
    		$sm = Context::getCurrent()->getServiceManager();
    		Journal::$table = $sm->get('PpitAccounting\Model\JournalTable');
    	}
    	return Journal::$table;
    }
}