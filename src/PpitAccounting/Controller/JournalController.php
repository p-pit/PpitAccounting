<?php
namespace PpitAccounting\Controller;

use PpitAccounting\Model\AccountingYear;
use PpitAccounting\Model\Journal;
use PpitAccounting\Model\Operation;
use PpitAccounting\ViewHelper\SsmlJournalViewHelper;
use PpitCommitment\Model\Commitment;
use PpitCommitment\Model\Term;
use PpitCore\Form\CsrfForm;
use PpitCore\Model\Context;
use PpitCore\Model\Csrf;
use PpitCore\Model\Place;
use PpitLearning\Model\Evaluation;
use Zend\Db\Sql\Where;
use Zend\Http\Client;
use Zend\Session\Container;
use Zend\View\Model\ViewModel;
use Zend\Mvc\Controller\AbstractActionController;

class JournalController extends AbstractActionController
{
    public function indexAction()
    {
    	$context = Context::getCurrent();
		if (!$context->isAuthenticated()) $this->redirect()->toRoute('home');
		$place = Place::get($context->getPlaceId());
		$journal_code = $this->params()->fromRoute('journal_code', 'general');
		
		$instance_id = $context->getInstanceId();

//		$menu = Context::getCurrent()->getConfig('menus/p-pit-finance')['entries'];
		$currentEntry = $this->params()->fromQuery('entry', 'journal');

    	return new ViewModel(array(
    			'context' => $context,
    			'config' => $context->getConfig(),
    			'place' => $place,
    			'journal_code' => $journal_code,
//    			'menu' => $menu,
    			'currentEntry' => $currentEntry,
    	));
    }
    
    public function indexV2Action()
    {
    	return $this->indexAction();
    }

    public function getFilters($params)
    {
		$context = Context::getCurrent();
    	
    	// Retrieve the query parameters
    	$filters = array();

    	foreach ($context->getConfig('journal/search')['main'] as $propertyId => $rendering) {
    
    		$property = ($params()->fromQuery($propertyId, null));
    		if ($property) $filters[$propertyId] = $property;
    		$min_property = ($params()->fromQuery('min_'.$propertyId, null));
    		if ($min_property) $filters['min_'.$propertyId] = $min_property;
    		$max_property = ($params()->fromQuery('max_'.$propertyId, null));
    		if ($max_property) $filters['max_'.$propertyId] = $max_property;
    	}

    	foreach ($context->getConfig('journal/search')['more'] as $propertyId => $rendering) {
    	
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
    	$journal_code = $this->params()->fromRoute('journal_code', 'general');

    	// Return the link list
    	$view = new ViewModel(array(
    			'context' => $context,
    			'config' => $context->getconfig(),
    			'journal_code' => $journal_code,
    	));
    	$view->setTerminal(true);
    	return $view;
    }
    
    public function searchV2Action()
    {
    	return $this->searchAction();
    }

    public function getList($dir = 'DESC')
    {
    	// Retrieve the context
    	$context = Context::getCurrent();
		$journal_code = $this->params()->fromRoute('journal_code', 'general');
    	$params = $this->getFilters($this->params());
    	$major = ($this->params()->fromQuery('major', 'sequence'));
    	$dir = ($this->params()->fromQuery('dir', $dir));

    	if (count($params) == 0) $mode = 'todo'; else $mode = 'search';

    	// Retrieve the list
    	$entries = Journal::getList((array_key_exists('year', $params) ? $params['year'] : null), $journal_code, $params, $major, $dir, $mode);

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
    
    public function listV2Action()
    {
    	return $this->listAction();
    }
    
    public function dropboxLinkAction()
    {
    	$context = Context::getCurrent();
    	$document = $this->params()->fromRoute('document', 0);
    	$dropbox = $context->getConfig('ppitDocument')['dropbox'];
        $client = new Client(
    			'https://api.dropboxapi.com/2/files/get_temporary_link',
    			array('adapter' => 'Zend\Http\Client\Adapter\Curl', 'maxredirects' => 0, 'timeout' => 30)
    	);
    	$client->setEncType('application/json');
    	$client->setMethod('POST');
    	$client->getRequest()->getHeaders()->addHeaders(array('Authorization' => 'Bearer '.$dropbox['credential']));
    	$client->setRawBody(json_encode(array('path' => $dropbox['folders']['expenses'].'/'.$document)));
    	$response = $client->send();
    	$this->response->http_status = $response->renderStatusLine();
    	$result = json_decode($response->getBody(), true);
    	if (is_array($result) && array_key_exists('link', $result)) return $this->redirect()->toUrl($result['link']);
    	else {
	    	$this->response->http_status = 400;
    		return $this->response;
    	}
    }
    
    public function exportAction()
    {
    	$place_id = $this->params()->fromQuery('place_id');
    	$place = null;
    	if ($place_id) $place = Place::get($place_id);
    	$fileName = 'Sales';
    	if ($place) $fileName .= '-'.$place->identifier;
    	$fileName .= '-'.date('Y-m-d').'.xlsx';
    	
    	$view = $this->getList('ASC');

   		include 'public/PHPExcel_1/Classes/PHPExcel.php';
   		include 'public/PHPExcel_1/Classes/PHPExcel/Writer/Excel2007.php';

		$workbook = new \PHPExcel;
		(new SsmlJournalViewHelper)->formatXls($workbook, $view);		
		$writer = new \PHPExcel_Writer_Excel2007($workbook);
		
		header('Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition:inline;filename='.$fileName);
		$writer->save('php://output');

		return $this->response;
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
    
    public function detailV2Action()
    {
    	return $this->detailAction();
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
		if ($id) $journal = Journal::retrieve($id);
		else $journal = Journal::instanciate();
		$journal->availableBankJournalEntries = Journal::getAvailableBankJournalEntries();
		if ($journal->bank_journal_entry) $journal->availableBankJournalEntries[] = $journal->bank_journal_entry;
		
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
				if ($journal->loadData($data) != 'OK') throw new \Exception('Client error');

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
	
	public function updateV2Action()
	{
		return $this->updateAction();
	}

	public function registerSalesAction()
	{
		// Retrieve the context
		$context = Context::getCurrent();

		// Retrieve the type and the commitment list
		$type = $this->params()->fromRoute('type');
		$commitmentIds = explode(',', $this->params()->fromQuery('commitments'));
		$commitments = array();
		foreach ($commitmentIds as $commitment_id) {
			$commitment = Commitment::get($commitment_id);
			if ($commitment->status != 'registered') $commitments[] = $commitment;
		}
		
		// Instanciate the csrf form
		$csrfForm = new CsrfForm();
		$csrfForm->addCsrfElement('csrf');
		$error = null;
		$message = null;
		$request = $this->getRequest();
		if ($request->isPost()) {
			
			$connection = Commitment::getTable()->getAdapter()->getDriver()->getConnection();
			$connection->beginTransaction();
			try {
			
				$journal = Journal::instanciate();
				foreach ($commitments as $commitment) {
					if ($commitment->excluding_tax == 0) continue;
					$data['place_id'] = $commitment->account->place_id;
					$data['operation_date'] = $commitment->invoice_date;
					$data['reference'] = $commitment->invoice_identifier;
					$data['caption'] = $commitment->account_name.' - '.$commitment->caption;
					$data['commitment_id'] = $commitment->id;
					$data['rows'] = array();
					if ($commitment->excluding_tax > 0) {
						$data['rows'][] = array(
							'account' => '706',
							'direction' => 1,
							'amount' => $commitment->excluding_tax,
						);
						if ($commitment->tax_amount > 0) {
							$data['rows'][] = array(
								'account' => '44571',
								'direction' => 1,
								'amount' => $commitment->tax_amount,
							);
						}
						$data['rows'][] = array(
							'account' => '411',
							'sub_account' => $commitment->account_identifier,
							'direction' => -1,
							'amount' => $commitment->tax_inclusive,
						);
					}
					else { // Credit case
						$data['rows'][] = array(
							'account' => '709',
							'direction' => -1,
							'amount' => $commitment->excluding_tax,
						);
						if ($commitment->tax_amount) {
							$data['rows'][] = array(
								'account' => '44571',
								'direction' => -1,
								'amount' => $commitment->tax_amount,
							);
						}
						$data['rows'][] = array(
							'account' => '411',
							'sub_account' => $commitment->account_identifier,
							'direction' => 1,
							'amount' => $commitment->tax_inclusive,
						);
					}
					$rc = $journal->loadData($data);
					if ($rc != 'OK') {
						$error = 'Consistency';
					}
					$journal->add('general');
					$commitment->status = 'registered';
					$commitment->update(null);
				}
			}
			catch (\Exception $e) {
				$connection->rollback();
				throw $e;
			}
			$connection->commit();
			$message = 'OK';
		}
    
    	$view = new ViewModel(array(
    		'context' => $context,
    		'type' => $type,
    		'commitments' => $commitments,
    		'csrfForm' => $csrfForm,
    		'message' => $message,
    		'error' => $error,
    	));
    	$view->setTerminal(true);
    	return $view;
	}

	public function registerTermSalesAction()
	{
		// Retrieve the context
		$context = Context::getCurrent();
	
		// Retrieve the type and the commitment list
		$type = $this->params()->fromRoute('type');
		$termIds = explode(',', $this->params()->fromQuery('terms'));
		$terms = array();
		foreach ($termIds as $term_id) {
			$term = Term::get($term_id);
			if ($term->status != 'registered') $terms[] = $term;
		}
	
		// Instanciate the csrf form
		$csrfForm = new CsrfForm();
		$csrfForm->addCsrfElement('csrf');
		$error = null;
		$message = null;
		$request = $this->getRequest();
		if ($request->isPost()) {
				
			$connection = Term::getTable()->getAdapter()->getDriver()->getConnection();
			$connection->beginTransaction();
			try {
					
				$journal = Journal::instanciate();
				foreach ($terms as $term) {
					if ($term->amount == 0) continue;
					
					$tax_rate = 0.2; // To make configurable
					$excluding_tax = round($term->amount / (1 + $tax_rate), 2);
					$tax_amount = $term->amount - $excluding_tax;
					$tax_inclusive = $term->amount;
						
					$data['place_id'] = $term->place_id;
					$data['operation_date'] = $term->settlement_date;
					$data['reference'] = $term->invoice_identifier . (($term->reference) ? ' - ' . $term->reference : '');
					$data['caption'] = $term->name.' - '.$term->commitment_caption;
					$data['commitment_id'] = $term->commitment_id;
					$data['rows'] = array();
					if ($term->amount > 0) {
						$data['rows'][] = array(
							'account' => '706',
							'direction' => 1,
							'amount' => $excluding_tax,
						);
						if ($tax_amount > 0) {
							$data['rows'][] = array(
								'account' => '44571',
								'direction' => 1,
								'amount' => $tax_amount,
							);
						}
						$data['rows'][] = array(
							'account' => '512',
							'sub_account' => $term->account_identifier,
							'direction' => -1,
							'amount' => $tax_inclusive,
						);
					}
					else { // Credit case
						$data['rows'][] = array(
							'account' => '709',
							'direction' => -1,
							'amount' => $excluding_tax,
						);
						if ($tax_amount) {
							$data['rows'][] = array(
								'account' => '44571',
								'direction' => -1,
								'amount' => $tax_amount,
							);
						}
						$data['rows'][] = array(
							'account' => '512',
							'sub_account' => $term->account_identifier,
							'direction' => 1,
							'amount' => $tax_inclusive,
						);
					}
					$rc = $journal->loadData($data);
					if ($rc != 'OK') {
						$error = 'Consistency';
					}
					$journal->add('general');
					$term->status = 'registered';
					$term->update(null);
				}
			}
			catch (\Exception $e) {
				$connection->rollback();
				throw $e;
			}
			$connection->commit();
			$message = 'OK';
		}
	
		$view = new ViewModel(array(
			'context' => $context,
			'type' => $type,
			'terms' => $terms,
			'csrfForm' => $csrfForm,
			'message' => $message,
			'error' => $error,
		));
		$view->setTerminal(true);
		return $view;
	}
	
	public function registerSettlementsAction()
	{
		// Retrieve the context
		$context = Context::getCurrent();
	
		// Retrieve the type and the commitment list
		$type = $this->params()->fromRoute('type');
		$termIds = explode(',', $this->params()->fromQuery('terms'));
		$terms = array();
		foreach ($termIds as $term_id) {
			$term = Term::get($term_id);
			if ($term->status != 'registered') $terms[] = $term;
		}
	
		// Instanciate the csrf form
		$csrfForm = new CsrfForm();
		$csrfForm->addCsrfElement('csrf');
		$error = null;
		$message = null;
		$request = $this->getRequest();
		if ($request->isPost()) {
				
			$connection = Term::getTable()->getAdapter()->getDriver()->getConnection();
			$connection->beginTransaction();
			try {
					
				$journal = Journal::instanciate();
				foreach ($terms as $term) {
					$data['place_id'] = $term->place_id;
					$data['operation_date'] = $term->settlement_date;
					$data['reference'] = $term->invoice_identifier . (($term->reference) ? ' - ' . $term->reference : '');
					$data['caption'] = $term->name.' - '.$term->commitment_caption;
					$data['commitment_id'] = $term->commitment_id;
					$data['rows'] = array();
					if ($term->amount > 0) {
						$data['rows'][] = array(
							'account' => '411',
							'sub_account' => $term->account_identifier,
							'direction' => 1,
							'amount' => $term->amount,
						);
						$data['rows'][] = array(
							'account' => '512',
							'direction' => -1,
							'amount' => $term->amount,
						);
					}
					else { // Credit and overdue case
						$data['rows'][] = array(
							'account' => '512',
							'direction' => -1,
							'amount' => $term->amount,
						);
						$data['rows'][] = array(
							'account' => '411',
							'sub_account' => $term->account_identifier,
							'direction' => 1,
							'amount' => $term->amount,
						);
					}
					$rc = $journal->loadData($data);
					if ($rc != 'OK') {
						$error = 'Consistency';
					}
					$journal->add('general');
					$term->status = 'registered';
					$term->update(null);
				}
			}
			catch (\Exception $e) {
				$connection->rollback();
				throw $e;
			}
			$connection->commit();
			$message = 'OK';
		}
	
		$view = new ViewModel(array(
			'context' => $context,
			'type' => $type,
			'terms' => $terms,
			'csrfForm' => $csrfForm,
			'message' => $message,
			'error' => $error,
		));
		$view->setTerminal(true);
		return $view;
	}
	
	public function qontoAction()
	{
		// Retrieve the context and parameters
		$context = Context::getCurrent();
		$place_id = $this->params()->fromRoute('place_id');
		$year = AccountingYear::getCurrent()->year;
		
		if (!$place_id) $place_id = $context->getInstance()->default_place_id;
		$place = Place::get($place_id);
		
		// Set the filter
		$settled_at_from = '&settled_at_from=' . date($year . '-01-01');
		$settled_at_to = '&settled_at_to=' . date($year . '-12-31');
		
		// Retrieve the transactions from QONTO
		$credentials = $context->getConfig()['ppitUserSettings']['safe'][$context->getInstance()->caption]['qonto'];
		$client = new Client(
			'https://thirdparty.qonto.eu/v2/transactions?slug=' . $credentials['slug'] . '&iban=' . $credentials['iban'] . '&settled_at_from=' . $settled_at_from . '&settled_at_to=' . $settled_at_to,
			['adapter' => 'Zend\Http\Client\Adapter\Curl', 'maxredirects' => 0, 'timeout' => 30]
		);
		$client->setEncType('application/json');
		$client->getRequest()->getHeaders()->addHeaders(array('Authorization' => 'ppit-7209:cbd3664a395d'));
		$client->setMethod('GET');
		$response = $client->send();
		
		$data = array();
		$this->response->setStatusCode($response->getStatusCode());
		$this->response->setReasonPhrase($response->getReasonPhrase());
		if ($response->getStatusCode() == 200) {
			$transactions = json_decode(gzdecode($response->getContent()), true)['transactions'];
			foreach ($transactions as $transaction) {
				$existing = Journal::get($transaction['transaction_id'], 'reference');
				if (!$existing) {
					$data[] = array(
						'status' => 'new',
						'place_id' => $place_id,
						'place_caption' => $place->caption,
						'year' => date('Y'),
						'sequence' => null,
						'operation_date' => substr($transaction['settled_at'], 0, 10),
						'reference' => $transaction['transaction_id'],
						'caption' => $transaction['label'] . (($transaction['reference']) ? ' - ' . $transaction['reference'] : ''),
						'rows' => array(array(
							'account' => '512',
							'direction' => ($transaction['side'] == 'debit') ? -1 : 1,
							'amount' => $transaction['amount_cents'] / 100,
						)),
						'total_amount' => $transaction['amount_cents'] / 100,
						'currency' => 'EUR',
						'update_time' => null,
					);
				}
			}
		}
		
		// Instanciate the csrf form
		$csrfForm = new CsrfForm();
		$csrfForm->addCsrfElement('csrf');
		$request = $this->getRequest();
		if ($request->isPost()) {

			$connection = Term::getTable()->getAdapter()->getDriver()->getConnection();
			$connection->beginTransaction();
			try {

				foreach ($data as $transaction) {
					$journal = Journal::instanciate();
					$rc = $journal->loadData($transaction);
					if ($rc != 'OK') {
						$this->response->setStatusCode('500');
						$this->response->setReasonPhrase('Consistency');
					}
					$journal->add('bank');
				}
			}
			catch (\Exception $e) {
				$connection->rollback();
				$this->response->setStatusCode('500');
				$this->response->setReasonPhrase('Fatal error');
			}
			$connection->commit();
			return $this->redirect()->toRoute('journal/bankStatement');
		}
	
		$view = new ViewModel(array(
			'context' => $context,
			'place' => $place,
			'transactions' => $data,
			'csrfForm' => $csrfForm,
			'post' => $request->isPost(),
		));
		return $view;
	}
	
	public function bankStatementAction()
	{
		// Retrieve the context and parameters
		$context = Context::getCurrent();
		$place_id = $this->params()->fromRoute('place_id');
		$year = $this->params()->fromQuery('year', date('Y'));
		$major = ($this->params()->fromQuery('major', 'operation_date'));
		$dir = ($this->params()->fromQuery('dir', 'ASC'));
		
		if (!$place_id) $place_id = $context->getInstance()->default_place_id;
		$place = Place::get($place_id);
		
		// Retrieve the transactions involving the bank account not already matched
		$operations = [];
		$cursor = Operation::getList('general', $year, ['status' => 'new']);
		foreach ($cursor as $operation) {
			if ($operation->properties['isBankOperation']) $operations[] = $operation;
		}

		$error = null;
		$message = null;
		
		// Retrieve the unmatched bank transactions
		$transactions = Journal::getList($year, 'bank', ['status' => 'new']);
		
		// Aggregate
		$specification = $context->getConfig('accounting_operation');
		$sum = 0;
		$distribution = array();
		foreach ($transactions as $transaction) {
			$majorSpecification = $specification['properties'][$major];
			if ($majorSpecification['type'] == 'specific') $majorSpecification = $context->getConfig($majorSpecification['definition']);
			if ($majorSpecification['type'] == 'number') $sum += $transaction[$major];
			elseif ($majorSpecification['type'] == 'select') {
				if (array_key_exists($transaction->properties[$major], $distribution)) $distribution[$transaction->properties[$major]]++;
				else $distribution[$transaction->properties[$major]] = 1;
			}
		}
		$average = (count($transactions)) ? round($sum / count($transactions), 1) : null;
		
		// Instanciate the csrf form
		$csrfForm = new CsrfForm();
		$csrfForm->addCsrfElement('csrf');
		$request = $this->getRequest();
		if ($request->isPost()) {
			
			$connection = Term::getTable()->getAdapter()->getDriver()->getConnection();
			$connection->beginTransaction();
			try {
				foreach ($transactions as $transaction) {
					$matched = $request->getPost('check-' . $transaction->reference);
					if ($matched) {
						$matching = $request->getPost('sequence-' . $transaction->reference);
						$transaction->sequence = $matching;
						$transaction->status = 'matched';
						Journal::getTable()->save($transaction);
						
						$rows = Journal::getList($year, 'general', ['sequence' => $matching]);
						foreach ($rows as $row) {
							$row->status = 'matched';
							Journal::getTable()->save($row);
						}
					}
				}
			}
			catch (\Exception $e) {
				$connection->rollback();
				throw $e;
			}
			$connection->commit();
			$message = 'OK';
		}
	
		$view = new ViewModel(array(
			'context' => $context,
			'place' => $place,
			'transactions' => $transactions,
			'operations' => $operations,
			'count' => count($transactions),
			'sum' => $sum,
			'average' => $average,
			'distribution' => $distribution,
			'major' => $major,
			'dir' => $dir,
			'csrfForm' => $csrfForm,
			'message' => $message,
			'error' => $error,
		));
		return $view;
	}
/*	
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
				$data = array();
				$data['operation_date'] = $request->getPost('operation_date');
				$data['reference'] = $request->getPost('reference');
				$data['caption'] = $request->getPost('caption');
				$data['bank_journal_reference'] = $request->getPost('bank_journal_reference');
				$data['rows'] = array();
				$row = array();
				$row['account'] = $request->getPost('account_0');
				$row['direction'] = $request->getPost('direction_0');
				$row['amount'] = $request->getPost('amount_0');
				$data['rows'][] = $row;
				if ($journal->loadData($data) != 'OK') throw new \Exception('Client error');
				
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
		$view->setTerminal(true);
		return $view;
	}*/

	public function bankUpdateAction()
	{
		// Retrieve the context
		$context = Context::getCurrent();
	
		$id = (int) $this->params()->fromRoute('id', 0);
		if ($id) $journal = Journal::get($id);
		else $journal = Journal::instanciate();
		$action = $this->params()->fromRoute('act', null);
	
		// Instanciate the csrf form
		$csrfForm = new CsrfForm();
		$csrfForm->addCsrfElement('csrf');
		$error = null;
		if ($action == 'delete') $message = 'confirm-delete';
		elseif ($action) $message =  'confirm-update';
		else $message = null;
		$request = $this->getRequest();
		if ($request->isPost()) {
			$message = null;
			$csrfForm->setInputFilter((new Csrf('csrf'))->getInputFilter());
			$csrfForm->setData($request->getPost());
			 
			if ($csrfForm->isValid()) { // CSRF check
	
				// Load the input data
				$data = array();
				foreach($context->getConfig('journal/bankUpdate') as $propertyId => $unused) {
					$data[$propertyId] = $request->getPost(($propertyId));
				}
				$data['rows'] = array();
				$row = array();
				$row['account'] = '512';
				$row['direction'] = $request->getPost('direction');
				$row['amount'] = $request->getPost('amount');
				$data['rows'][] = $row;
				if ($journal->loadData($data) != 'OK') throw new \Exception('View error');
				
				// Atomically save
				$connection = Journal::getTable()->getAdapter()->getDriver()->getConnection();
				$connection->beginTransaction();
				try {
					if (!$journal->id) $rc = $journal->addBankStatementEntry();
					elseif ($action == 'delete') $rc = $journal->delete($request->getPost('journal_update_time'));
					else $rc = $journal->updateBankStatementEntry($request->getPost('update_time'));
					if ($rc != 'OK') $error = $rc;
					if ($error) $connection->rollback();
					else {
						$connection->commit();
						$message = 'OK';
					}
				}
				catch (\Exception $e) {
					$connection->rollback();
					throw $e;
				}
				$action = null;
			}
		}
		$journal->properties = $journal->toArray();
	
		$view = new ViewModel(array(
				'context' => $context,
				'config' => $context->getconfig(),
				'id' => $id,
				'action' => $action,
				'journal' => $journal,
				'csrfForm' => $csrfForm,
				'error' => $error,
				'message' => $message
		));
		$view->setTerminal(true);
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
    		if (Journal::nextStep($year, '2019-01-24') != 'OK') throw new \Exception('View error');
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
	
	public function computeInterestsAction()
	{
    	// Retrieve the current user
    	$context = Context::getCurrent();
    	$year = $this->params()->fromQuery('year', date('Y'));
    	$account = $this->params()->fromQuery('account');
		$connection = Journal::getTable()->getAdapter()->getDriver()->getConnection();
		$connection->beginTransaction();
    	try {
	    	if (Journal::computeInterests($year, $account) != 'OK') throw new \Exception('View error');
			$connection->commit();
			$message = 'OK';
		}
		catch (Exception $e) {
			$connection->rollback();
			throw $e;
		}
		return $this->response;
	}
    
    public function repairAction()
    {
    	$request = $this->getRequest();
    	$year = $request->getParam('year', null);
    	$select = Journal::getTable()->getSelect()->order(array('sequence ASC', 'id'));
    	$where = new Where;
    	$where->equalTo('year', $year);
    	$select->where($where);
    	$cursor = Journal::getTable()->selectWith($select);
    	$rows = array();
    	foreach($cursor as $row) $rows[] = $row;

    	$oldSequence = -1;
    	$newSequence = 0;
    	$connection = Journal::getTable()->getAdapter()->getDriver()->getConnection();
    	$connection->beginTransaction();
    	try {
	    	foreach($rows as $row){
	    		if ($row->sequence != $oldSequence) {
/*	    			if ($oldSequence != -1) {
	    				$select = Journal::getTable()->getSelect()->where(array('journal_code' => 'bank', 'sequence' => $oldSequence));
	    				$cursor = Journal::getTable()->selectWith($select);
	    				foreach ($cursor as $bankJournalEntry) {
	    					$bankJournalEntry->sequence = $newSequence;
	    					Journal::getTable()->save($bankJournalEntry);
	    				}
	    			}*/
					echo $row->sequence.' => '.$newSequence."\n";
	    			$oldSequence = $row->sequence;
	    			$newSequence++;
	    		}
	    		$row->sequence = $newSequence;
				Journal::getTable()->save($row);
	    	}
			$connection->commit();
    	}
		catch (Exception $e) {
			$connection->rollback();
			throw $e;
		}
	    return $this->response;
    }
    
    public function repriseAction()
    {
    	$entries = Journal::getList('2016', 'bank', array(), 'sequence', 'ASC');
    	$currentId = null;
    	$amounts = array();
    	foreach ($entries as $entry) {
    		if ($entry->sequence) {
	    		if ($entry->sequence.'_'.$entry->journal_code != $currentId) {
	    			$currentId = $entry->sequence.'_'.$entry->journal_code;
	    			$amounts[$currentId] = 0;
	    		}
	    		if ($entry->journal_code == 'bank' || $entry->direction == 1) $amounts[$currentId] += $entry->amount;
    		}
    	}
    	foreach ($entries as $entry) {
    		if ($entry->sequence) {
	    		$entry->total_amount = $amounts[$entry->sequence.'_'.$entry->journal_code];
	    		echo $entry->sequence.'_'.$entry->journal_code.' '.$entry->total_amount.'<br>';
				Journal::getTable()->save($entry);
    		}
    	}
    	return $this->response;
    }
}
