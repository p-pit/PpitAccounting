<?php
namespace PpitAccounting\Controller;

use PpitAccounting\Model\Journal;
use PpitAccounting\ViewHelper\SsmlJournalViewHelper;
use PpitCore\Form\CsrfForm;
use PpitCore\Model\Context;
use PpitCore\Model\Csrf;
use PpitLearning\Model\Evaluation;
use Zend\Session\Container;
use Zend\View\Model\ViewModel;
use Zend\Mvc\Controller\AbstractActionController;

class JournalController extends AbstractActionController
{
    public function indexAction()
    {
    	$context = Context::getCurrent();
		if (!$context->isAuthenticated()) $this->redirect()->toRoute('home');

		$instance_id = $context->getInstanceId();

		$menu = $context->getconfig('menu');
		$currentEntry = $this->params()->fromQuery('entry', 'journal');

    	return new ViewModel(array(
    			'context' => $context,
    			'config' => $context->getConfig(),
    			'menu' => $menu,
    			'currentEntry' => $currentEntry,
    	));
    }

    public function getFilters($params)
    {
		$context = Context::getCurrent();
    	
    	// Retrieve the query parameters
    	$filters = array();

    	foreach ($context->getInstance()->specifications['ppitAccounting']['journal/search']['main'] as $propertyId => $rendering) {
    
    		$property = ($params()->fromQuery($propertyId, null));
    		if ($property) $filters[$propertyId] = $property;
    		$min_property = ($params()->fromQuery('min_'.$propertyId, null));
    		if ($min_property) $filters['min_'.$propertyId] = $min_property;
    		$max_property = ($params()->fromQuery('max_'.$propertyId, null));
    		if ($max_property) $filters['max_'.$propertyId] = $max_property;
    	}

    	foreach ($context->getInstance()->specifications['ppitAccounting']['journal/search']['more'] as $propertyId => $rendering) {
    	
    		$property = ($params()->fromQuery($propertyId, null));
    		if ($property) $filters[$propertyId] = $property;
    		$min_property = ($params()->fromQuery('min_'.$propertyId, null));
    		if ($min_property) $filters['min_'.$propertyId] = $min_property;
    		$max_property = ($params()->fromQuery('max_'.$propertyId, null));
    		if ($max_property) $filters['max_'.$propertyId] = $max_property;
    	}
    	 
    	return $filters;
    }

    public function searchAction()
    {
    	// Retrieve the context
    	$context = Context::getCurrent();
    
    	// Return the link list
    	$view = new ViewModel(array(
    			'context' => $context,
    			'config' => $context->getconfig(),
    	));
    	$view->setTerminal(true);
    	return $view;
    }

    public function getList()
    {
    	// Retrieve the context
    	$context = Context::getCurrent();
    
    	$params = $this->getFilters($this->params());
    
    	$major = ($this->params()->fromQuery('major', 'sequence'));
    	$dir = ($this->params()->fromQuery('dir', 'DESC'));
    
    	if (count($params) == 0) $mode = 'todo'; else $mode = 'search';
    
    	// Retrieve the list
    	$entries = Journal::getList($params, $major, $dir, $mode);

    	// Return the link list
    	$view = new ViewModel(array(
    			'context' => $context,
    			'config' => $context->getconfig(),
    			'entries' => $entries,
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
    
    public function exportAction()
    {
    	$view = $this->getList();

   		include 'public/PHPExcel_1/Classes/PHPExcel.php';
   		include 'public/PHPExcel_1/Classes/PHPExcel/Writer/Excel2007.php';

		$workbook = new \PHPExcel;
		(new SsmlJournalViewHelper)->formatXls($workbook, $view);		
		$writer = new \PHPExcel_Writer_Excel2007($workbook);
		
		header('Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition:inline;filename=Fichier.xlsx ');
		$writer->save('php://output');
    }

    public function detailAction()
    {
    	// Retrieve the context
    	$context = Context::getCurrent();
    	 
    	$id = (int) $this->params()->fromRoute('id', 0);
    
    	$view = new ViewModel(array(
    			'context' => $context,
    			'config' => $context->getconfig(),
    			'id' => $id,
    	));
    	$view->setTerminal(true);
    	return $view;
    }

    public function bankListAction()
    {
    	// Retrieve the context
    	$context = Context::getCurrent();
    
    	// Retrieve the book entry
		$journal = new Journal;
    	$journal->availableBankJournalEntries = Journal::getAvailableBankJournalEntries();

    	$view = new ViewModel(array(
    			'context' => $context,
    			'config' => $context->getconfig(),
    			'journal' => $journal,
    	));
    	$view->setTerminal(true);
    	return $view;
    }
    
	public function updateAction()
	{
		// Retrieve the context
		$context = Context::getCurrent();
	
		// Retrieve the book entry
		$id = (int) $this->params()->fromRoute('id', 0);
/*		if ($id) $instance = Instance::getTable()->get($id);
		else */ $journal = new Journal;
		$journal->availableBankJournalEntries = Journal::getAvailableBankJournalEntries();
		
		// Instanciate the csrf form
		$csrfForm = new CsrfForm();
		$csrfForm->addCsrfElement('csrf');
		$message = null;
		$error = null;
		$request = $this->getRequest();
		if ($request->isPost()) {
	
			$csrfForm->setInputFilter((new Csrf('csrf'))->getInputFilter());
			$csrfForm->setData($request->getPost());
	
			if ($csrfForm->isValid()) { // CSRF check
	
				$journal->loadDataFromRequest($request);
	
				// Atomically save
				try {
					$connection = Journal::getTable()->getAdapter()->getDriver()->getConnection();
					$connection->beginTransaction();
					if ($journal->id) $journal->update($request->getPost('update_time'));
					else $journal->add();
					$connection->commit();
					$message = 'OK';
				}
				catch (Exception $e) {
					$connection->rollback();
					throw $e;
				}
			}
		}
		$view = new ViewModel(array(
				'context' => $context,
				'config' => $context->getconfig(),
				'journal' => $journal,
				'id' => $id,
				'csrfForm' => $csrfForm,
				'message' => $message,
				'error' => $error,
		));
		$view->setTerminal(true);
		return $view;
	}

	public function updateOldAction()
	{
		// Retrieve the context
		$context = Context::getCurrent();
	
		// Retrieve the book entry
		$id = (int) $this->params()->fromRoute('id', 0);
		/*		if ($id) $instance = Instance::getTable()->get($id);
			else */ $journal = new Journal;
		$journal->availableBankJournalEntries = Journal::getAvailableBankJournalEntries();
	
		// Instanciate the csrf form
		$csrfForm = new CsrfForm();
		$csrfForm->addCsrfElement('csrf');
		$message = null;
		$error = null;
		$request = $this->getRequest();
		if ($request->isPost()) {
	
			$csrfForm->setInputFilter((new Csrf('csrf'))->getInputFilter());
			$csrfForm->setData($request->getPost());
	
			if ($csrfForm->isValid()) { // CSRF check
	
				$journal->loadDataFromRequest($request);
	
				// Atomically save
				try {
					$connection = Journal::getTable()->getAdapter()->getDriver()->getConnection();
					$connection->beginTransaction();
					if ($journal->id) $journal->update($request->getPost('update_time'));
					else $journal->add();
					$connection->commit();
					$message = 'OK';
				}
				catch (Exception $e) {
					$connection->rollback();
					throw $e;
				}
			}
		}
		$view = new ViewModel(array(
				'context' => $context,
				'config' => $context->getconfig(),
				'journal' => $journal,
				'id' => $id,
				'csrfForm' => $csrfForm,
				'message' => $message,
				'error' => $error,
		));
		return $view;
	}
	
	public function bankStatementAction()
	{
		// Retrieve the context
		$context = Context::getCurrent();
	
		// Retrieve the book entry
		$id = (int) $this->params()->fromRoute('id', 0);
		$journal = new Journal;
	
		// Instanciate the csrf form
		$csrfForm = new CsrfForm();
		$csrfForm->addCsrfElement('csrf');
		$message = null;
		$error = null;
		$request = $this->getRequest();
		if ($request->isPost()) {
	
			$csrfForm->setInputFilter((new Csrf('csrf'))->getInputFilter());
			$csrfForm->setData($request->getPost());
	
			if ($csrfForm->isValid()) { // CSRF check
	
				$journal->loadDataFromRequest($request);
	
				// Atomically save
				try {
					$connection = Journal::getTable()->getAdapter()->getDriver()->getConnection();
					$connection->beginTransaction();
					if ($journal->id) $journal->updateBankStatementEntry($request->getPost('update_time'));
					else $journal->addBankStatementEntry();
					$connection->commit();
					$message = 'OK';
				}
				catch (Exception $e) {
					$connection->rollback();
					throw $e;
				}
			}
		}
		$view = new ViewModel(array(
				'context' => $context,
				'config' => $context->getconfig(),
				'journal' => $journal,
				'id' => $id,
				'csrfForm' => $csrfForm,
				'message' => $message,
				'error' => $error,
		));
		//		$view->setTerminal(true);
		return $view;
	}

	public function nextStepAction()
	{
    	// Retrieve the current user
    	$context = Context::getCurrent();
    	$year = $this->params()->fromQuery('year', date('Y'));
		try {
			$connection = Journal::getTable()->getAdapter()->getDriver()->getConnection();
			$connection->beginTransaction();
    		if (Journal::nextStep($year, '2016-03-31') != 'OK') throw new \Exception('View error');
			$connection->commit();
			$message = 'OK';
		}
		catch (Exception $e) {
			$connection->rollback();
			throw $e;
		}
	}
	
	public function previousStepAction()
	{
    	// Retrieve the current user
    	$context = Context::getCurrent();
    	$year = $this->params()->fromQuery('year', date('Y'));
			try {
			$connection = Journal::getTable()->getAdapter()->getDriver()->getConnection();
			$connection->beginTransaction();
	    	if (Journal::undoLastStep($year) != 'OK') throw new \Exception('View error');
			$connection->commit();
			$message = 'OK';
		}
		catch (Exception $e) {
			$connection->rollback();
			throw $e;
		}
	}
/*	
	public function exportAction()
    {
    	// Retrieve the current user
    	$context = Context::getCurrent();

    	$year = $this->params()->fromQuery('year', date('Y'));
    	$journal_code = $this->params()->fromQuery('journal_code', 'general');

    	$params = array();
    	$params['year'] = $year;
    	$params['journal_code'] = $journal_code;
    	$rows = Journal::getList($params, 'sequence', 'DESC');
    	$view = new ViewModel(array(
    			'context' => $context,
				'config' => $context->getconfig(),
    			'year' => $year,
    			'journal_code' => $journal_code,
    			'rows' => $rows,
    	));
    	$view->setTerminal(true);
    	return $view;
    }*/
}
