<?php
namespace PpitAccounting\Controller;

use DOMPDFModule\View\Model\PdfModel;
use PpitAccounting\Model\LedgerAccount;
use PpitAccounting\Model\AccountingYear;
use PpitAccounting\Model\Assessment;
use PpitAccounting\Model\Balance;
use PpitAccounting\Model\IncomeStatement;
use PpitAccounting\Model\Journal;
use PpitCore\Form\CsrfForm;
use PpitCore\Model\Context;
use PpitCore\Model\Csrf;
use PpitCore\Model\Place;
use PpitLearning\Model\Evaluation;
use Zend\Session\Container;
use Zend\View\Model\ViewModel;
use Zend\Mvc\Controller\AbstractActionController;

class LedgerAccountController extends AbstractActionController
{
	public function accountAction()
	{
		// Retrieve the context
		$context = Context::getCurrent();
		$place = Place::get($context->getPlaceId());
		
    	$year = $this->params()->fromQuery('year', date('Y'));
    	$account = $this->params()->fromQuery('account');
    	$account = new LedgerAccount($year, $account);
		$view = new ViewModel(array(
				'context' => $context,
				'config' => $context->getconfig(),
				'place' => $place,
				'account' => $account,
		));
		return $view;
	}

	public function balanceAction()
	{
		// Retrieve the context
		$context = Context::getCurrent();
		$place = Place::get($context->getPlaceId());
		$accountingYear = AccountingYear::getCurrent();
		$year = $this->params()->fromQuery('year', $accountingYear->year);
    	$month = $this->params()->fromQuery('month', null);
    	$includes_closing = $this->params()->fromQuery('includes_closing', false);
    	$balance = new Balance($year, $month, $includes_closing);
		$view = new ViewModel(array(
				'context' => $context,
				'config' => $context->getconfig(),
				'place' => $place,
				'year' => $year,
				'balance' => $balance,
		));
		return $view;
	}

	public function balancePdfAction()
	{
		// Retrieve the context
		$context = Context::getCurrent();
		$accountingYear = AccountingYear::getCurrent();
		$year = $this->params()->fromQuery('year', $accountingYear->year);
		$month = $this->params()->fromQuery('month', null);
		$includes_closing = $this->params()->fromQuery('includes_closing', false);
		$balance = new Balance($year, $month, $includes_closing);
    	$pdf = new PdfModel();
    	$pdf->setOption('filename', 'Balance');
    	$pdf->setOption("paperSize", "a4"); //Defaults to 8x11
 		$pdf->setOption("paperOrientation", "portrait"); //Defaults to portrait
     	$pdf->setVariables(array(
				'context' => $context,
				'config' => $context->getconfig(),
				'balance' => $balance,
     	));
		return $pdf;
	}
	
	public function incomeStatementAction()
	{
		// Retrieve the context
		$context = Context::getCurrent();

    	$year = $this->params()->fromQuery('year', date('Y'));
		$incomeStatement = new IncomeStatement($year);
		$view = new ViewModel(array(
				'context' => $context,
				'config' => $context->getconfig(),
				'incomeStatement' => $incomeStatement,
		));
		$view->setTerminal(true);
		return $view;
	}

	public function assessmentAction()
	{
		// Retrieve the context
		$context = Context::getCurrent();
	
		$year = $this->params()->fromQuery('year', date('Y'));
		$assessment = new Assessment($year);
		$view = new ViewModel(array(
				'context' => $context,
				'config' => $context->getconfig(),
				'assessment' => $assessment,
		));
		return $view;
	}
	
	public function exportAction()
    {
    	// Retrieve the context
    	$context = Context::getCurrent();

    	$year = $this->params()->fromQuery('year', date('Y'));
    	$journal_code = $this->params()->fromQuery('journal_code', 'general');

    	$params = array();
    	$params['year'] = $year;
    	$rows = Journal::getList($journal_code, $params, 'sequence', 'DESC');
    	$view = new ViewModel(array(
    			'context' => $context,
				'config' => $context->getconfig(),
    			'year' => $year,
    			'journal_code' => $journal_code,
    			'rows' => $rows,
    	));
    	$view->setTerminal(true);
    	return $view;
    }
}
