<?php
/**
 * PpitAccounting V1.0 (https://github.com/p-pit/PpitCore)
 *
 * @link      https://github.com/p-pit/PpitAccounting
 * @copyright Copyright (c) 2016 Bruno Lartillot
 * @license   https://github.com/p-pit/PpitAccounting/blob/master/license.txt GNU-GPL license
 */

namespace PpitAccounting\Controller;

use PpitAccounting\Model\Operation;
use PpitCore\Model\Interaction;
use PpitCore\Model\Place;
use PpitCore\Model\Context;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class AccountingOperationController extends AbstractActionController
{
    public function indexAction()
    {
    	$context = Context::getCurrent();

    	// Retrieve parameters
    	$type = $this->params()->fromRoute('type');
    	$year = $this->params()->fromRoute('year', $context->getConfig('accounting_operation/year')['default']);
    	$place = Place::get($context->getPlaceId());

		$applicationId = 'p-pit-finance';
		return new ViewModel(array(
    			'context' => $context,
    			'type' => $type,
    			'year' => $year,
				'place' => $place,
    			'applicationId' => $applicationId,
		));
    }

    public function getFilters($type, $year, $params)
    {
    	$context = Context::getCurrent();
    	$filters = array();
    	foreach ($context->getConfig('accounting_operation/search')['properties'] as $propertyId => $rendering) {
    		$property = ($params()->fromQuery($propertyId, null));
    		if ($property) $filters[$propertyId] = $property;
    		$min_property = ($params()->fromQuery('min_'.$propertyId, null));
    		if ($min_property!= null) $filters['min_'.$propertyId] = $min_property;
    		$max_property = ($params()->fromQuery('max_'.$propertyId, null));
    		if ($max_property != null) $filters['max_'.$propertyId] = $max_property;
    	}

    	return $filters;
    }

    public function searchAction()
    {
    	$context = Context::getCurrent();
    	$type = $this->params()->fromRoute('type', '');
    	$year = $this->params()->fromRoute('year', $context->getConfig('accounting_operation/year')['default']);

    	$view = new ViewModel(array(
    			'context' => $context,
    			'type' => $type,
    			'year' => $year,
				'places' => Place::getList(array()),
    	));
    	$view->setTerminal(true);
    	return $view;
    }

    public function getList()
    {
    	$context = Context::getCurrent();

    	// Retrieve parameters
    	$type = $this->params()->fromRoute('type', '');
    	$year = $this->params()->fromRoute('year', $context->getConfig('accounting_operation/year')['default']);
    	$params = $this->getFilters($type, $year, $this->params());
    	if (count($params) == 0) $mode = 'todo'; else $mode = 'search';
    	$major = ($this->params()->fromQuery('major', 'sequence'));
    	$dir = ($this->params()->fromQuery('dir', 'ASC'));

    	// Retrieve the list
		$operations = Operation::getList($type, $year, $params, $major, $dir, $mode);

		// Aggregate
		$specification = $context->getConfig('accounting_operation');
		$sum = 0;
		$distribution = array();
		foreach ($operations as $operation) {
			$majorSpecification = $specification['properties'][$major];
			if ($majorSpecification['type'] == 'specific') $majorSpecification = $context->getConfig($majorSpecification['definition']);
			if ($majorSpecification['type'] == 'number') $sum += $operation->properties[$major];
			elseif ($majorSpecification['type'] == 'select') {
				if (array_key_exists($operation->properties[$major], $distribution)) $distribution[$operation->properties[$major]]++;
				else $distribution[$operation->properties[$major]] = 1;
			}
		}
		$average = (count($operations)) ? round($sum / count($operations), 1) : null;
		
    	// Return the link list
    	$view = new ViewModel(array(
    			'context' => $context,
    			'type' => $type,
    			'year' => $year,
    			'operations' => $operations,
    			'count' => count($operations),
    			'sum' => $sum,
    			'average' => $average,
    			'distribution' => $distribution,
    			'mode' => $mode,
    			'params' => $params,
    			'major' => $major,
    			'dir' => $dir,
    	));
    	$view->setTerminal(true);
    	return $view;
    }
    
    public function listAction()
    {
    	return $this->getList();
    }

    public function detailAction()
    {
    	$context = Context::getCurrent();
    	$type = $this->params()->fromRoute('type', '');
    	$year = $this->params()->fromRoute('year', $context->getConfig('accounting_operation/year')['default']);
    	$sequence = (int) $this->params()->fromRoute('sequence', 0);
    	$operation = Operation::get($type, $year, $sequence);

    	$view = new ViewModel(array(
    			'context' => $context,
    			'type' => $type,
    			'year' => $year,
    			'sequence' => $sequence,
    			'operation' => $operation,
    	));
    	$view->setTerminal(true);
    	return $view;
    }
}
