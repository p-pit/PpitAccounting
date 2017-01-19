<?php
namespace PpitAccounting\ViewHelper;

use PpitCore\Model\Context;
use PpitAccounting\Model\Journal;

class SsmlJournalViewHelper
{
	public static function formatXls($workbook, $view)
	{
		$context = Context::getCurrent();
		$translator = $context->getServiceManager()->get('translator');

		$title = $context->getConfig('commitmentAccount/search')['title'][$context->getLocale()];
		
		// Set document properties
		$workbook->getProperties()->setCreator('P-PIT')
			->setLastModifiedBy('P-PIT')
			->setTitle($title)
			->setSubject($title)
			->setDescription($title)
			->setKeywords($title)
			->setCategory($title);

		$sheet = $workbook->getActiveSheet();
		
		$i = 0;
		$colNames = array(1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D', 5 => 'E', 6 => 'F', 7 => 'G', 8 => 'H', 9 => 'I', 10 => 'J');
		
		foreach($context->getConfig('journal_properties') as $propertyId => $property) {
			$i++;
			$sheet->setCellValue($colNames[$i].'1', $property['labels'][$context->getLocale()]);
		}

		$j = 1;
		foreach ($view->entries as $entry) {
			$j++;
			$i = 0;
			foreach($context->getConfig('journal_properties') as $propertyId => $property) {
				$i++;
				if ($property['type'] == 'date') $sheet->setCellValue($colNames[$i].$j, $context->decodeDate($entry->properties[$propertyId]));
				elseif ($property['type'] == 'number') $sheet->setCellValue($colNames[$i].$j, $context->formatFloat($entry->properties[$propertyId], 2));
				else $sheet->setCellValue($colNames[$i].$j, $entry->properties[$propertyId]);
			}
		}
		$i = 0;
		foreach($context->getConfig('journal_properties') as $propertyId => $property) {
			$i++;
			$sheet->getColumnDimension($colNames[$i])->setAutoSize(true);
		}
	}
}