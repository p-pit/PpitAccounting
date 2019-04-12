<?php
namespace PpitAccounting\Model;

use PpitAccounting\Model\AccountingYear;
use PpitCore\Model\Context;
use PpitCore\Model\Document;
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
    public $status;
    public $place_id;
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
    public $total_amount;
    public $currency;
    public $account;
    public $sub_account;
    public $expense_id;
    public $commitment_id;
    public $update_time;

    // Joined properties
    public $place_identifier;
    public $place_caption;
    public $account_name;
    
    // Transient properties
    public $rows = array();
    public $availableBankJournalEntries;
    public $bank_journal_reference;
    public $bank_journal_entry;
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
        $this->status = (isset($data['status'])) ? $data['status'] : null;
        $this->place_id = (isset($data['place_id'])) ? $data['place_id'] : null;
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
        $this->total_amount = (isset($data['total_amount'])) ? $data['total_amount'] : null;
        $this->currency = (isset($data['currency'])) ? $data['currency'] : null;
        $this->account = (isset($data['account'])) ? $data['account'] : null;
        $this->sub_account = (isset($data['sub_account'])) ? $data['sub_account'] : null;
        $this->expense_id = (isset($data['expense_id'])) ? $data['expense_id'] : null;
        $this->commitment_id = (isset($data['commitment_id'])) ? $data['commitment_id'] : null;
        $this->update_time = (isset($data['update_time'])) ? $data['update_time'] : null;

        // Joined properties
        $this->place_identifier = (isset($data['place_identifier'])) ? $data['place_identifier'] : null;
        $this->place_caption = (isset($data['place_caption'])) ? $data['place_caption'] : null;
        $this->account_name = (isset($data['account_name'])) ? $data['account_name'] : null;
    }

    public function getProperties() {
    	$context = Context::getCurrent();
    	
    	$data = array();
    	$data['id'] = (int) $this->id;
    	$data['status'] = $this->status;
    	$data['place_id'] = (int) $this->place_id;
    	$data['place_caption'] = $this->place_caption;
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
    	$data['total_amount'] = $this->total_amount;
    	$data['currency'] = $this->currency;
    	$data['account'] = $this->account;
    	$data['sub_account'] = $this->sub_account;
    	$data['expense_id'] = $this->expense_id;
    	$data['commitment_id'] = $this->commitment_id;

    	if (array_key_exists($this->account, $context->getConfig('ppitAccountingSettings')['accounts'])) {
    		$data['account_caption'] = $context->getConfig('ppitAccountingSettings')['accounts'][$this->account]['caption'];
    	}
    	
    	return $data;
    }

    public function toArray() {
    	$data = $this->getProperties();
    	unset($data['place_caption']);
    	unset($data['account_caption']);
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

    public static function getList($year, $journal_code, $params, $major = 'sequence', $dir = 'DESC', $mode = 'todo')
    {
    	$select = Journal::getTable()->getSelect()
    		->join('commitment', 'commitment.id = accounting_journal.commitment_id', array(), 'left')
    		->join('core_account', 'core_account.id = commitment.account_id', array('account_name' => 'name'), 'left')
    		->join('core_place', 'core_place.id = core_account.place_id', array('place_identifier' => 'identifier', 'place_caption' => 'caption'), 'left')
    		->order(array($major.' '.$dir, 'sequence DESC', 'journal_code','id'));
    	 
    	$where = new Where;
		if (!$year) $year = AccountingYear::getCurrent()->year;
    	$where->equalTo('accounting_journal.year', $year);
    	if ($journal_code) $where->equalTo('journal_code', $journal_code);

    	// Todo list vs search modes
    	if ($mode == 'todo') {
//    		$where->equalTo('accounting_journal.status', 'new');
/*    		if ($journal_code == 'general') {
	    		$where->greaterThanOrEqualTo('accounting_journal.account', '6');
	    		$where->lessThanOrEqualTo('accounting_journal.account', '799999');
    		}
    		elseif ($journal_code == 'bank') $where->equalTo('sequence', 0);*/
    	}
    	else {
    	
    		// Set the filters
    		foreach ($params as $propertyId => $property) {
    			if ($propertyId == 'journal_code') $where->equalTo('journal_code', $property);
    			elseif ($propertyId == 'place_id') $where->equalTo('core_account.place_id', $property);
    			elseif (substr($propertyId, 0, 4) == 'min_') $where->greaterThanOrEqualTo('accounting_journal.'.substr($propertyId, 4), $property);
    			elseif (substr($propertyId, 0, 4) == 'max_') $where->lessThanOrEqualTo('accounting_journal.'.substr($propertyId, 4), $property);
    			else $where->like('accounting_journal.'.$propertyId, '%'.$property.'%');
    		}
    	}
    	
    	$select->where($where);
    	$cursor = Journal::getTable()->selectWith($select);
    	$entries = array();
    	foreach($cursor as $entry) {
    		$entry->properties = $entry->getProperties();
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
    
    public static function retrieve($id)
    {
    	$journal = Journal::getTable()->get($id);
    	$select = Journal::getTable()->getSelect()->where(array('year' => $journal->year, 'sequence' => $journal->sequence));
    	$cursor = Journal::getTable()->selectWith($select);
    	$rows = array();
    	foreach ($cursor as $entry) {
    		if ($entry->journal_code == 'general') {
	    		$row = array();
	    		$row['account'] = $entry->account;
	    		$row['direction'] = $entry->direction;
	    		$row['amount'] = $entry->amount;
	    		$rows[] = $row;
    		}
    		elseif ($entry->journal_code == 'bank') {
    			$journal->bank_journal_reference = $entry->id;
    			$journal->bank_journal_entry = $entry;
    		}
    	}
    	$journal->rows = $rows;
    	return $journal;
    }
    
    public static function instanciate()
    {
    	$journalEntry = new Journal;
    	$journalEntry->status = 'new';
    	return $journalEntry;
    }
    
	public function loadData($data)
	{
		if (array_key_exists('status', $data)) {
    		$this->status = trim(strip_tags($data['status']));
    		if (!$this->status || strlen($this->status) > 255) return 'Integrity';
		}
		if (array_key_exists('place_id', $data)) {
			$place_id = (int) $data['place_id'];
			if ($this->place_id != $place_id) $auditRow['place_id'] = $this->place_id = $place_id;
		}
		if (array_key_exists('operation_date', $data)) {
    		$this->operation_date = trim(strip_tags($data['operation_date']));
    		if ($this->operation_date && !checkdate(substr($this->operation_date, 5, 2), substr($this->operation_date, 8, 2), substr($this->operation_date, 0, 4))) return 'Integrity';
		}
		if (array_key_exists('reference', $data)) {
			$this->reference = trim(strip_tags($data['reference']));
    		if (strlen($this->reference) > 255) return 'Integrity';
		}
		if (array_key_exists('caption', $data)) {
			$this->caption = trim(strip_tags($data['caption']));
    		if (!$this->caption || strlen($this->caption) > 255) return 'Integrity';
		}
		if (array_key_exists('proof_url', $data)) {
			$this->proof_url = trim(strip_tags($data['proof_url']));
    		if (strlen($this->proof_url) > 255) return 'Integrity';
		}
		if (array_key_exists('bank_journal_reference', $data)) {
			$this->bank_journal_reference = (int) $data['bank_journal_reference'];
		}
		$this->rows = array();
		$this->total_amount = 0;
		foreach ($data['rows'] as $row) {
			if ($row['direction'] == 1) $this->total_amount += $row['amount'];
			$this->rows[] = $row;
		}
		if (array_key_exists('expense_id', $data)) {
			$this->expense_id = (int) $data['expense_id'];
		}
		if (array_key_exists('commitment_id', $data)) {
			$this->commitment_id = (int) $data['commitment_id'];
		}
		return 'OK';
	}

	public function add($journal_code = 'general')
	{
		$context = Context::getCurrent();
		$accountingYear = AccountingYear::getCurrent();
		$this->year = $accountingYear->year;
		if ($journal_code =='general') $this->sequence = $accountingYear->next_value;
		$this->journal_code = $journal_code;
		$this->accounting_date = date('Y-m-d');
		$this->currency = 'EUR';
		foreach ($this->rows as $row) {
			if ($row['account']) {
				$this->id = 0;
				$this->account = $row['account'];
				if (array_key_exists('sub_account', $row)) $this->sub_account = $row['sub_account'];
				$this->direction = $row['direction'];
				$this->amount = $row['amount'];
				Journal::getTable()->save($this);
			}
		}

		if ($journal_code =='general') {
			$accountingYear->next_value++;
			AccountingYear::getTable()->save($accountingYear);
			
			if ($this->bank_journal_reference) {
				$bankJournalEntry = Journal::getTable()->get($this->bank_journal_reference);
				$bankJournalEntry->sequence = $this->sequence;
				Journal::getTable()->save($bankJournalEntry);
			}
		}
	}

	public function update($update_time)
	{
		$context = Context::getCurrent();
		
		// Delete the link from the bank journal to this entry
		$journalEntry = Journal::getTable()->get($this->id);
		if ($journalEntry->bank_journal_reference) {
			$bankJournalEntry = Journal::getTable()->get($journalEntry->bank_journal_reference);
			$bankJournalEntry->sequence = null;
			Journal::getTable()->save($bankJournalEntry);
		}

		Journal::getTable()->multipleDelete(array('journal_code' => 'general', 'year' => $this->year, 'sequence' => $this->sequence));
		
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
	
		// Add the link from the bank journal to this entry
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
		return 'OK';
	}

	public function updateBankStatementEntry($updateTime)
	{
		$accountingYear = AccountingYear::getCurrent();
		$this->year = $accountingYear->year;
		$this->journal_code = 'bank';
		$this->accounting_date = date('Y-m-d');
		$this->currency = 'EUR';
		foreach ($this->rows as $row) {
			if ($row['account']) {
				$this->account = $row['account'];
				$this->direction = $row['direction'];
				$this->amount = $row['amount'];
				Journal::getTable()->save($this);
			}
		}
		return 'OK';
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
    	$row->status = 'new';
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
    	$row->status = 'new';
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
    	$row->status = 'new';
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

    public static function computeInterests($year, $account)
    {
    	$context = Context::getCurrent();
    	Journal::getTable()->multipleDelete(array('journal_code' => 'general', 'year' => $year, 'reference' => $context->getConfig('journal/legalInterest')[$context->getLocale()]));

    	$select = Journal::getTable()->getSelect()->where(array('journal_code' => 'general', 'year' => $year, 'account' => $account))->order(array('operation_date'));
    	$cursor = Journal::getTable()->selectWith($select);
		$datetime1 = strtotime('01/01/'.$year);
		$sum = 0;
		$interval = 0;
    	foreach ($cursor as $operation) {
    		if ($operation->direction == 1) $sum += $operation->amount;
    		else $sum -= $operation->amount;
			
    		$datetime2 = strtotime(substr($operation->operation_date, 5, 2).'/'.substr($operation->operation_date, 8, 2).'/'.substr($operation->operation_date, 0, 4));
			$interval = round(($datetime2 - $datetime1) / 86400, 0);
			if ($sum > 0 && $interval > 0) {

				$journalEntry = Journal::instanciate();
				$data = array();
				$data['operation_date'] = $operation->operation_date;
				$data['reference'] = $context->getConfig('journal/legalInterest')[$context->getLocale()];
				$data['caption'] = $operation->operation_date.': '.($sum - 10000).' * '.($context->getConfig('journal/legalInterest')['rate']*100).' % * '.$interval.' / 365';
				$data['rows'] = array();
				$amount = round(($sum - 10000) * $context->getConfig('journal/legalInterest')['rate'] * $interval / 365, 2);
				
				$row = array();
				$row['account'] = '455';
				$row['direction'] = 1;
				$row['amount'] = $amount;
				$data['rows'][] = $row;

				$row = array();
				$row['account'] = '6615';
				$row['direction'] = -1;
				$row['amount'] = $amount;
				$data['rows'][] = $row;
				
				$journalEntry->loadData($data);
				$journalEntry->add();

				echo $sum.' - '.$interval." days ";
				if ($interval > 0) echo $amount;
				echo '<br>';
				
				$sum += $amount;
			}

			$datetime1 = $datetime2;
    	}

    	$datetime2 = strtotime('12/31/'.$year);
    	$interval = round(($datetime2 - $datetime1) / 86400, 0);
    	if ($sum > 0 && $interval > 0) {
    	
    		$journalEntry = Journal::instanciate();
    		$data = array();
    		$data['operation_date'] = $year.'-12-31';
    		$data['reference'] = $context->getConfig('journal/legalInterest')[$context->getLocale()];
    		$data['caption'] = $year.'-12-31: '.($sum - 10000).' * '.($context->getConfig('journal/legalInterest')['rate']*100).' % * '.$interval.' / 365';
    		$data['rows'] = array();
    		$amount = round(($sum - 10000) * $context->getConfig('journal/legalInterest')['rate'] * $interval / 365, 2);
    	
    		$row = array();
    		$row['account'] = '455';
    		$row['direction'] = 1;
    		$row['amount'] = $amount;
    		$data['rows'][] = $row;
    	
    		$row = array();
    		$row['account'] = '6615';
    		$row['direction'] = -1;
    		$row['amount'] = $amount;
    		$data['rows'][] = $row;
    	
    		$journalEntry->loadData($data);
    		$journalEntry->add();
    	}
    	
    	echo $sum.' - '.$interval." days ";
    	if ($interval > 0) echo $amount;
    	echo '<br>';
    	 
    	return 'OK';
    }

    public function isDeletable()
    {
    	$context = Context::getCurrent();
    
    	// Check dependencies
    	$config = $context->getConfig();
    	foreach($config['ppitAccountingDependencies'] as $dependency) {
    		if ($dependency->isUsed($this)) return false;
    	}
    
    	if ($this->journal_code == 'bank' && $this->sequence) return false;
    	return true;
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