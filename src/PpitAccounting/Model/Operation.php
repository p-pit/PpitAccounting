<?php
/**
 * PpitAccounting V1.0 (https://github.com/p-pit/PpitCore)
 *
 * @link      https://github.com/p-pit/PpitCore
 * @copyright Copyright (c) 2016 Bruno Lartillot
 * @license   https://github.com/p-pit/PpitCore/blob/master/license.txt GNU-GPL license
 */

namespace PpitAccounting\Model;

use PpitAccounting\Model\Journal;
use PpitCore\Model\Context;
use PpitCore\Model\Generic;
use PpitCore\Model\Place;
use Zend\Db\Sql\Where;
use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;

/**
  * Operation is the class supporting accounting operations as a whole, based on journal entries having the same sequence number
  */
class Operation
{
	// Transient properties
    public $properties;

    public static function getList($type, $year, $params, $major = 'sequence', $dir = 'DESC', $mode = 'search')
    {
    	$context = Context::getCurrent();

    	$select = Journal::getTable()->getSelect()
			->order(array($major.' '.$dir, 'accounting_journal.sequence DESC', 'journal_code'))
			->join('core_place', 'core_place.id = accounting_journal.place_id', array('place_identifier' => 'identifier', 'place_caption' => 'caption'), 'left')
    		->limit(200);

    	$where = new Where;
    	if ($year) $where->equalTo('year', $year);
    	if ($type) $where->equalTo('journal_code', $type);
    	 
    	// Todo list vs search modes
    	if ($mode == 'todo') {
	    	$where->equalTo('accounting_journal.status', 'new');
    	}
    	else {
    		// Set the filters
	    	$where->notEqualTo('accounting_journal.status', 'deleted');
    		foreach ($params as $propertyId => $value) {
    			if (substr($propertyId, 0, 4) == 'min_' || substr($propertyId, 0, 4) == 'max_') $propertyName = substr($propertyId, 4);
    			else $propertyName = $propertyId;
    			$property = $context->getConfig('accounting_operation')['properties'][$propertyName];
    			if ($property['type'] == 'specific') $property = $context->getConfig($property['definition']);
/*    			if ($propertyId == 'type') {
    				if ($value == '*') $where->notEqualTo('accounting_journal.journal_code', '');
    				else $where->equalTo('accounting_journal.journal_code', $value);
    			}*/
    			if ($propertyId == 'place_identifier') {
    				if ($value == '*') $where->notEqualTo('core_place.identifier', '');
    				else $where->like('core_place.identifier', '%'.$value.'%');
    			}
    			elseif (substr($propertyId, 0, 4) == 'min_') $where->greaterThanOrEqualTo(substr($propertyId, 4), $value);
    			elseif (substr($propertyId, 0, 4) == 'max_') $where->lessThanOrEqualTo(substr($propertyId, 4), $value);
    			elseif (strpos($value, ',')) $where->in($propertyId, array_map('trim', explode(', ', $value)));
    			elseif ($value == '*') $where->notEqualTo($propertyId, '');
    			elseif ($property['type'] == 'select') $where->equalTo('accounting_journal.'.$propertyId, $value);
    			else $where->like('accounting_journal.'.$propertyId, '%'.$value.'%');
    		}
    	}
    	$select->where($where);

    	$cursor = Journal::getTable()->selectWith($select);
    	$operations = array();
    	foreach ($cursor as $row) {
    		
			// Filter on authorized perimeter
			$keep = true;
    		if (array_key_exists('p-pit-admin', $context->getPerimeters())) {
					$keep = false;
					foreach ($context->getPerimeters()['p-pit-admin']['place_id'] as $value) {
						if ($row->place_id == $value) $keep = true;
					}
			}
    	    if (array_key_exists('p-pit-accounting', $context->getPerimeters())) {
					$keep = false;
					foreach ($context->getPerimeters()['p-pit-accounting'] as $propertyId => $values) {
						foreach ($values as $value) {
							if ($row->properties[$propertyId] == $value) $keep = true;
						}
					}
			}
			if ($keep) {
				if (!array_key_exists($row->sequence.'_'.$row->journal_code, $operations)) {
					$operations[$row->sequence.'_'.$row->journal_code] = new Operation;
					$operations[$row->sequence.'_'.$row->journal_code]->properties['id'] = $row->id; // Any of the journal ids for this operation
					$operations[$row->sequence.'_'.$row->journal_code]->properties['type'] = $row->journal_code;
					$operations[$row->sequence.'_'.$row->journal_code]->properties['status'] = $row->status;
					$operations[$row->sequence.'_'.$row->journal_code]->properties['place_identifier'] = $row->place_identifier;
					$operations[$row->sequence.'_'.$row->journal_code]->properties['place_caption'] = $row->place_caption;
					$operations[$row->sequence.'_'.$row->journal_code]->properties['year'] = $row->year;
					$operations[$row->sequence.'_'.$row->journal_code]->properties['sequence'] = $row->sequence;
					$operations[$row->sequence.'_'.$row->journal_code]->properties['operation_date'] = $row->operation_date;
					$operations[$row->sequence.'_'.$row->journal_code]->properties['accounting_date'] = $row->accounting_date;
					$operations[$row->sequence.'_'.$row->journal_code]->properties['reference'] = $row->reference;
					$operations[$row->sequence.'_'.$row->journal_code]->properties['caption'] = $row->caption;
					$operations[$row->sequence.'_'.$row->journal_code]->properties['proof_id'] = $row->proof_id;
					$operations[$row->sequence.'_'.$row->journal_code]->properties['proof_url'] = $row->proof_url;
					$operations[$row->sequence.'_'.$row->journal_code]->properties['total_amount'] = $row->total_amount;
					$operations[$row->sequence.'_'.$row->journal_code]->properties['currency'] = $row->currency;
					$operations[$row->sequence.'_'.$row->journal_code]->properties['expense_id'] = $row->expense_id;
					$operations[$row->sequence.'_'.$row->journal_code]->properties['commitment_id'] = $row->commitment_id;
					$operations[$row->sequence.'_'.$row->journal_code]->properties['update_time'] = $row->update_time;
				}
//				if ($row->direction == 1) $operations[$row->sequence]->properties['total_amount'] += $row->amount;
			}
    	}
    	return $operations;
    }
    
    public static function get($type, $year, $sequence)
    {
    	$operations = Operation::getList($type, $year, $sequence);
    	reset($operations);
    	return current($operations);
    }
}
