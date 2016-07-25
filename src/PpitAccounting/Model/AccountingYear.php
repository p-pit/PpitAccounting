<?php
namespace PpitAccounting\Model;

use PpitCore\Model\Context;
use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;

class AccountingYear implements InputFilterAwareInterface
{
    public $id;
    public $year;
    public $status;
    public $next_value;
    
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
        $this->status = (isset($data['status'])) ? $data['status'] : null;
        $this->next_value = (isset($data['next_value'])) ? $data['next_value'] : null;
    }

    public function toArray() {
    	$data = array();
    	$data['id'] = (int) $this->id;
    	$data['year'] = $this->year;
    	$data['status'] = $this->status;
    	$data['next_value'] = (int) $this->next_value;
    	return $data;
    }
   
    public static function getCurrent()
    {
    	$accountingYear = AccountingYear::getTable()->get('current', 'status');
    	return $accountingYear;
    }

    public static function getNext($current)
    {
    	$accountingYear = AccountingYear::getTable()->get($current->year + 1);
    	return $accountingYear;
    }
    
    public static function instanciate($year)
    {
    	$accountingYear = new AccountingYear;
    	$accountingYear->year = $year;
    	$accountingYear->status = 'current';
    	$accountingYear->next_value = 1;
    	AccountingYear::getTable()->save($accountingYear);
    	return $accountingYear;
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
    	if (!AccountingYear::$table) {
    		$sm = Context::getCurrent()->getServiceManager();
    		AccountingYear::$table = $sm->get('PpitAccounting\Model\AccountingYearTable');
    	}
    	return AccountingYear::$table;
    }
}