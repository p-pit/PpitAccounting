<?php
namespace PpitAccounting;

return array(
    'controllers' => array(
        'invokables' => array(
            'PpitAccounting\Controller\Account' => 'PpitAccounting\Controller\AccountController',
        	'PpitAccounting\Controller\Journal' => 'PpitAccounting\Controller\JournalController',
        ),
    ),

	'console' => array(
			'router' => array(
					'routes' => array(
							'repair' => array(
									'options' => array(
											'route'    => 'journal repair <year>',
											'defaults' => array(
													'controller' => 'PpitAccounting\Controller\Journal',
													'action'     => 'repair'
											)
									)
							),
					),
			),
	),
		
    // The following section is new and should be added to your file
    'router' => array(
        'routes' => array(
            'index' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/',
                    'defaults' => array(
                        '__NAMESPACE__' => 'PpitAccounting\Controller',
                        'controller' => 'Journal',
                        'action'     => 'index',
                    ),
                ),
            ),
        	'account' => array(
                'type'    => 'literal',
                'options' => array(
                    'route'    => '/account',
                    'defaults' => array(
                        'controller' => 'PpitAccounting\Controller\Account',
                        'action'     => 'index',
                    ),
            	),
	            'may_terminate' => true,
            	'child_routes' => array(
            			'account' => array(
            					'type' => 'segment',
            					'options' => array(
            							'route' => '/account',
            							'defaults' => array(
            									'action' => 'account',
            							),
            					),
            			),
            			'balance' => array(
            					'type' => 'segment',
            					'options' => array(
            							'route' => '/balance',
            							'defaults' => array(
            									'action' => 'balance',
            							),
            					),
            			),
            			'balancePdf' => array(
            					'type' => 'segment',
            					'options' => array(
            							'route' => '/balance-pdf',
            							'defaults' => array(
            									'action' => 'balancePdf',
            							),
            					),
            			),
            			'incomeStatement' => array(
            					'type' => 'segment',
            					'options' => array(
            							'route' => '/income-statement',
            							'defaults' => array(
            									'action' => 'incomeStatement',
            							),
            					),
            			),
            			'assessment' => array(
            					'type' => 'segment',
            					'options' => array(
            							'route' => '/assessment',
            							'defaults' => array(
            									'action' => 'assessment',
            							),
            					),
            			),
            	),
        	),
        	'journal' => array(
                'type'    => 'segment',
                'options' => array(
                    'route'    => '/journal',
                    'defaults' => array(
                        'controller' => 'PpitAccounting\Controller\Journal',
                        'action'     => 'index',
                    ),
                ),
            	'may_terminate' => true,
            	'child_routes' => array(
        						'index' => array(
        								'type' => 'segment',
        								'options' => array(
        										'route' => '/index[/:journal_code]',
        										'defaults' => array(
        												'action' => 'index',
        										),
        								),
        						),
        						'search' => array(
        								'type' => 'segment',
        								'options' => array(
        										'route' => '/search[/:journal_code]',
        										'defaults' => array(
        												'action' => 'search',
        										),
        								),
        						),
        						'list' => array(
        								'type' => 'segment',
        								'options' => array(
        										'route' => '/list[/:journal_code]',
        										'defaults' => array(
        												'action' => 'list',
        										),
        								),
        						),
        						'dropboxLink' => array(
        								'type' => 'segment',
        								'options' => array(
        										'route' => '/dropbox-link[/:document]',
        										'defaults' => array(
        												'action' => 'dropboxLink',
        										),
        								),
        						),
            					'bankList' => array(
        								'type' => 'segment',
        								'options' => array(
        										'route' => '/bank-list',
        										'defaults' => array(
        												'action' => 'bankList',
        										),
        								),
        						),
            					'export' => array(
        								'type' => 'segment',
        								'options' => array(
        										'route' => '/export[/:journal_code]',
        										'defaults' => array(
        												'action' => 'export',
        										),
        								),
        						),
	       						'detail' => array(
        								'type' => 'segment',
        								'options' => array(
        										'route' => '/detail[/:id]',
        										'constraints' => array(
        												'id' => '[0-9]*',
        										),
        										'defaults' => array(
        												'action' => 'detail',
        										),
        								),
        						),
            			'update' => array(
            					'type' => 'segment',
            					'options' => array(
            							'route' => '/update[/:id]',
            							'constraints' => array(
            										'id'     => '[0-9]*',
               							),
            							'defaults' => array(
            									'action' => 'update',
            							),
            					),
            			),
            			'updateOld' => array(
            					'type' => 'segment',
            					'options' => array(
            							'route' => '/update-old[/:id]',
            							'constraints' => array(
            										'id'     => '[0-9]*',
               							),
            							'defaults' => array(
            									'action' => 'updateOld',
            							),
            					),
            			),
            			'delete' => array(
		                    'type' => 'segment',
		                    'options' => array(
		                        'route' => '/delete[/:id]',
			                    'constraints' => array(
			                    	'id' => '[0-9]*',
			                    ),
		                    	'defaults' => array(
		                            'action' => 'delete',
		                        ),
		                    ),
		                ),
            			'bankStatement' => array(
            					'type' => 'segment',
            					'options' => array(
            							'route' => '/bank-statement[/:id]',
            							'constraints' => array(
            										'id'     => '[0-9]*',
               							),
            							'defaults' => array(
            									'action' => 'bankStatement',
            							),
            					),
            			),
            			'bankUpdate' => array(
            					'type' => 'segment',
            					'options' => array(
            							'route' => '/bank-update[/:id][/:act]',
            							'constraints' => array(
            										'id'     => '[0-9]*',
               							),
            							'defaults' => array(
            									'action' => 'bankUpdate',
            							),
            					),
            			),
            			'nextStep' => array(
            					'type' => 'segment',
            					'options' => array(
            							'route' => '/next-step',
            							'defaults' => array(
            									'action' => 'nextStep',
            							),
            					),
            			),
            			'previousStep' => array(
            					'type' => 'segment',
            					'options' => array(
            							'route' => '/previous-step',
            							'defaults' => array(
            									'action' => 'previousStep',
            							),
            					),
            			),
            			'computeInterests' => array(
            					'type' => 'segment',
            					'options' => array(
            							'route' => '/compute-interests',
            							'defaults' => array(
            									'action' => 'computeInterests',
            							),
            					),
            			),
            	),
           ),
        ),
    ),
	'bjyauthorize' => array(
		// Guard listeners to be attached to the application event manager
		'guards' => array(
			'BjyAuthorize\Guard\Route' => array(
            	array('route' => 'account', 'roles' => array('admin')),
            	array('route' => 'account/account', 'roles' => array('admin')),
            	array('route' => 'account/balance', 'roles' => array('admin')),
            	array('route' => 'account/balancePdf', 'roles' => array('admin')),
				array('route' => 'account/incomeStatement', 'roles' => array('admin')),
				array('route' => 'account/assessment', 'roles' => array('admin')),
				array('route' => 'journal', 'roles' => array('admin')),
				array('route' => 'journal/index', 'roles' => array('user')),
				array('route' => 'journal/search', 'roles' => array('user')),
            	array('route' => 'journal/list', 'roles' => array('user')),
            	array('route' => 'journal/dropboxLink', 'roles' => array('user')),
				array('route' => 'journal/detail', 'roles' => array('user')),
				array('route' => 'journal/export', 'roles' => array('user')),
				array('route' => 'journal/update', 'roles' => array('admin')),
				array('route' => 'journal/delete', 'roles' => array('admin')),
				array('route' => 'journal/bankList', 'roles' => array('admin')),
				array('route' => 'journal/bankStatement', 'roles' => array('admin')),
				array('route' => 'journal/bankUpdate', 'roles' => array('admin')),
				array('route' => 'journal/nextStep', 'roles' => array('admin')),
            	array('route' => 'journal/previousStep', 'roles' => array('admin')),
            	array('route' => 'journal/computeInterests', 'roles' => array('admin')),
			)
		)
	),
	'ppitAccountingSettings' => array(
		'accounts' => array(
			1011 => array('caption' => 'Capital souscrit, non appelé', 'class' => 1),
			1012 => array('caption' => 'Capital souscrit, appelé, non versé', 'class' => 1),
			1013 => array('caption' => 'Capital souscrit, appelé, versé', 'class' => 1),
			109 => array('caption' => 'Actionnaires – Capital souscrit, non appelé', 'class' => 1),
			129 => array('caption' => 'Résultat de l\'exercice (perte)', 'class' => 1),
			2184 => array('caption' => 'Mobilier', 'class' => 2),
			2751 => array('caption' => 'Dépôts de garantie', 'class' => 2),
			401 => array('caption' => 'Fournisseurs', 'class' => 4),
			404 => array('caption' => 'Fournisseurs d\'immobilisations', 'class' => 4),
			411 => array('caption' => 'Clients', 'class' => 4),
			421 => array('caption' => 'Personnel - Rémunérations dues', 'class' => 4),
			44551 => array('caption' => 'TVA à décaisser', 'class' => 4),
			44566 => array('caption' => 'TVA déductible sur autres biens et services', 'class' => 4),
			44567 => array('caption' => 'Crédit de TVA', 'class' => 4),
			44571 => array('caption' => 'TVA collectée', 'class' => 4),
			44572 => array('caption' => 'TVA déductible sur immobilisations', 'class' => 4),
			447 => array('caption' => 'Autres impôts, taxes et versements assimilés', 'class' => 4),
			455 => array('caption' => 'Sociétaires - Comptes courants', 'class' => 4),
			4561 => array('caption' => 'Associés – Compte d\'apport en société', 'class' => 4),
			44587 => array('caption' => 'Taxe sur le chiffre d\'affaire sur facture à établir', 'class' => 4),
			512 => array('caption' => 'Banque', 'class' => 5),
			604 => array('caption' => 'Achat d\'études et de prestations', 'class' => 6),
			6063 => array('caption' => 'fournitures d’entretien et de petit équipement', 'class' => 6),
			611 => array('caption' => 'Locations', 'class' => 6),
			6135 => array('caption' => 'Locations mobilières', 'class' => 6),
			616 => array('caption' => 'Primes d\'assurance', 'class' => 6),
			617 => array('caption' => 'Etudes et recherches', 'class' => 6),
			6185 => array('caption' => 'Frais de colloques, séminaires, conférences', 'class' => 6),
			6227 => array('caption' => 'Frais d\'actes et de contentieux', 'class' => 6),
			6231 => array('caption' => 'Annonces et insertions', 'class' => 6),
			6233 => array('caption' => 'Participation à la formation', 'class' => 6),
			6234 => array('caption' => 'Cadeaux à la clientèle', 'class' => 6),
			6236 => array('caption' => 'Catalogues et imprimés', 'class' => 6),
			6251 => array('caption' => 'Voyages et déplacements', 'class' => 6),
			626 => array('caption' => 'Frais postaux et de télécommunications', 'class' => 6),
			6278 => array('caption' => 'Autres frais et commissions sur prestations de services', 'class' => 6),
			6281 => array('caption' => 'Concours divers (cotisation)', 'class' => 6),
			6333 => array('caption' => 'Participation à la formation', 'class' => 6),
			63511 => array('caption' => 'Contribution économique territoriale', 'class' => 6),
			6413 => array('caption' => 'Primes et gratifications', 'class' => 6),
			6511 => array('caption' => 'Redevances pour concessions, brevets, licences, marques, procédés, logiciels', 'class' => 6),
			658 => array('caption' => 'Charges diverses de gestion courante', 'class' => 6),
			6615 => array('caption' => 'Intérêts des comptes courants et des dépôts créditeurs', 'class' => 6),
			706 => array('caption' => 'Prestations de services', 'class' => 7),
			758 => array('caption' => 'Produits divers de gestion courante', 'class' => 7),
		),
		'assessmentAccounts' => array(
				1 => array('caption' => 'Immobilisations corporelles', 'direction' => 'D'),
				2 => array('caption' => 'Immobilisations financières', 'direction' => 'D'),
				3 => array('caption' => 'Créances clients et comptes rattachés', 'direction' => 'D'),
				4 => array('caption' => 'Disponibilités', 'direction' => 'D'),
				5 => array('caption' => 'Capital social ou individuel', 'direction' => 'C'),
				6 => array('caption' => 'Résultat de l\'exercice', 'direction' => 'C'),
				7 => array('caption' => 'Emprunts et dettes assimilées', 'direction' => 'C'),
				8 => array('caption' => 'Fournisseurs et comptes rattachés', 'direction' => 'C'),
				9 => array('caption' => 'Autres dettes (dont comptes courants d\'associés de l\'exercice N)', 'direction' => 'C'),
		),
		'assessmentMapping' => array(
				1011 => 5,
				1012 => 5,
				1013 => 5,
				109 => 5,
				129 => 6,
				2184 => 1,
				2751 => 2,
				401 => 8,
				404 => 8,
				411 => 3,
				421 => 3,
				44551 => 7,
				44566 => 7,
				44567 => 7,
				44571 => 7,
				44572 => 7,
				447 => 7,
				455 => 9,
				4561 => 9,
				44587 => 7,
				512 => 4,
		),
/*		'accounts' => array(
			1 => 'Comptes de capitaux',
			10 => 'Capital et réserves',
			1011 => 'Capital souscrit, non appelé',
			1012 => 'Capital souscrit, appelé, non versé',
			1013 => 'Capital souscrit, appelé, versé',
			109 => 'Actionnaires – Capital souscrit, non appelé',
			2 => 'Comptes d\'immobilisation',
			21 => 'Immobilisations corporelles',
			2184 => 'Mobilier',
			2751 => 'Dépôts de garantie',
			4 => 'Comptes de tiers',
			40 => 'Fournisseurs et comptes rattachés',
			401 => 'Fournisseurs',
			404 => 'Fournisseurs d\'immobilisations',
			41 => 'Clients et comptes rattachés',
			411 => 'Clients',
			42 => 'Personnel et comptes rattachés',
			421 => 'Personnel - Rémunérations dues',
			44 => 'Etat et autres collectivités publiques',
			44551 => 'TVA à décaisser',
			44566 => 'TVA déductible sur autres biens et services',
			44567 => 'Crédit de TVA',
			44571 => 'TVA collectée',
			44572 => 'TVA déductible sur immobilisations',
			447 => 'Autres impôts, taxes et versements assimilés',
			45 => 'Groupe et associés',
			455 => 'Sociétaires - Comptes courants',
			44587 => 'Taxe sur le chiffre d\'affaire sur facture à établir',
			4561 => 'Associés – Compte d\'apport en société',
			46 => 'Débiteurs divers et créditeurs divers',
			5 => 'Comptes financiers',
			51 => 'Banques, établissements financiers et assimilés',
			512 => 'Banque',
			6 => 'Comptes de charges',
			60 => 'Achats',
			604 => 'Achat d\'études et de prestations',
			6063 => 'fournitures d’entretien et de petit équipement',
			61 => 'Services extérieurs',
			611 => 'Locations',
			6135 => 'Locations mobilières',
			616 => 'Primes d\'assurance',
			617 => 'Etudes et recherches',
			6185 => 'Frais de colloques, séminaires, conférences',
			62 => 'Autres services extérieurs',
			6227 => 'Frais d\'actes et de contentieux',
			623 => 'Publicité et publications',
			6231 => 'Annonces et insertions',
			6233 => 'Participation à la formation',
			6234 => 'Cadeaux à la clientèle',
			6236 => 'Catalogues et imprimés',
			6251 => 'Voyages et déplacements',
			626 => 'Frais postaux et de télécommunications',
			6278 => 'Autres frais et commissions sur prestations de services',
			6281 => 'Concours divers (cotisation)',
			6333 => 'Participation à la formation',
			63511 => 'Contribution économique territoriale',
			64 => 'Charges de personnel',
			6413 => 'Primes et gratifications',
			65 => 'Autres charges de gestion courante',
			6511 => 'Redevances pour concessions, brevets, licences, marques, procédés, logiciels',
			7 => 'Comptes de produits',
			70 => 'Ventes de produits fabriqués, prestations de services, marchandises',
			706 => 'Prestations de services',
		),*/
		'chartOfAccounts' => array(
			1 => null,
			10 => 1,
			1011 => 10,
			1012 => 10,
			1013 => 10,
			109 => 10,
			2 => null,
			21 => 2,
			2184 => 21,
			2751 => 21,
			4 => null,
			40 => 4,
			401 => 40,
			404 => 40,
			41 => 4,
			411 => 41,
			42 => 4,
			421 => 42,
			44 => 4,
			44551 => 44,
			44566 => 44,
			44567 => 44,
			44571 => 44,
			44572 => 44,
			45 => 4,
			455 => 45,
			46 => '4',
			4561 => 45,
			5 => null,
			51 => 5,
			512 => 51,
			6 => null,
			60 => 6,
			604 => 60,
			6063 => 60,
			61 => 6,
			611 => 61,
			6135 => 61,
			616 => 61,
			617 => 61,
			6185 => 61,
			62 => 6,
			6227 => 62,
			623 => 62,
			6231 => 62,
			6233 => 62,
			6236 => 62,
			6251 => 62,
			626 => 62,
			6278 => 62,
			6281 => 62,
			6333 => 6,
			63511 => 6,
			64 => 6,
			6413 => 64,
			65 => 6,
			6511 => 65,
			658 => 65,
			66 => 6,
			6615 => 66,
			7 => null,
			70 => 7,
			706 => 70,
			75 => 7,
			758 => 75,
		),
		'incomeStatementCaptions' => array(
			1 => '',
				11 => 'Résultat d\'exploitation',
					111 => 'Charges externes',
						1111 => ' Eau, électricité, tel, internet, etc.',
						1112 => 'Loyer',
						1113 => 'Entretien et petit équipement',
						1114 => 'Assurances',
						1115 => 'Honoraires',
						1116 => 'Sous-traitance',
						1117 => 'Communication',
						1118 => 'Frais de transport',
						1119 => 'Autres charges externes',
					112 => 'Charges de personnel',
						1121 => 'Salaires',
						1122 => 'Cotisations sociales',
					113 => 'Impôts et taxes',
						1131 => 'Contribution économique territoriale',
						1132 => 'Taxe d\'apprentissage',
						1133 => 'Taxes foncières',
						1134 => 'Autres (hors I.S.)',
					114 => 'Dotations',
						1141 => 'Amortissements',
						1142 => 'Provisions',
					115 => 'Autres charges d\'exploitation',
						1151 => null,
				12 => 'Résultat d\'exploitation',
					121 => null,
					1211 => 'Prestations de service',
					1212 => 'Autres produits d\'exploitation',
				2 => '',
				21 => 'Résultat financier',
					211 => 'Charges financières',
						2111 => 'Intérêts payés',
				22 => 'Résultat financier',
					221 => 'Produits financiers',
						2211 => 'Intérêts perçus',
						2212 => 'Revenus de placements',
			3 => '',
				31 => 'Résultat exceptionnel',
					311 => 'Charges exceptionnelles',
						3111 => 'Amendes',
						3111 => 'autres charges inattendues',
				32 => 'Résultat exceptionnel',
					221 => 'Produits exceptionnels',
						2211 => 'Plus-values',
						2212 => 'Autres produits inattendus',
			9 => '',
				91 => 'Grand total',
					911 => '',
						9111 => '',
				92 => 'Grand total',
					921 => 'Loss',
						9211 => '',
		),
		'incomeStatementTree' => array(
			1 => array(
				11 => array(
					111  => array(1111, 1112, 1113, 1114, 1115, 1116, 1117, 1118, 1119),
					112 => array(1121, 1122), 
					113 => array(1131, 1132, 1133, 1134),
					114 => array(1141, 1142),
					115 => array(1151)),
				12 => array(
					121 => array(1211, 1212))),
			2 => array(
				21 => array(
					211 => array(2111)), 
				22 => array(
					221 => array(2211, 2212))),
			3 => array(
				31 => array(
					311 => array(3111, 3111)),
				32 => array(
					221 => array(2211, 2212))),
/*			9 => array(
				91 => array(
					911 => array(9111)),
				92 => array(
					921 => array(9211))),*/
		),
		'incomeStatementMapping' => array(
			604 => 1116,
			611 => 1112,
			616 => 1114,
			617 => 1116,
			626 => 1111,
			6063 => 1113,
			6135 => 1112,
			6185 => 1119,
			6227 => 1119,
			6231 => 1117,
			6234 => 1116,
			6236 => 1117,
			6251 => 1118,
			6278 => 1119,
			6281 => 1116,
			6333 => 1121,
			6413 => 1121,
			6511 => 1112,
			658 => 1151,
			6615 => 2111,
			63511 => 1131,
			706 => 1211,
			758 => 1212,
		),
/*		
		'assessmentCaptions' => array(
				1 => 'Actif',
				11 => 'Actif immobilisé',
				111 => 'Immobilisations incorporelles',
				1111 => '- Concessions, brevers, licences, marques...',
				1112 => '- Avances et acomptes',
				112 => 'Immobilisations corporelles',
				1121 => '- Terrains',
				1122 => '- Constructions',
				1123 => '- Installations techniques, matériel et outillages industriels',
				1124 => '- Autres',
				1125 => '- Avances et acomptes',
				113 => 'Immobilisations financières',
				1131 => '- Participations',
				1132 => '- Prêts',
				12 => 'Actif circulant',
				121 => 'Avances et acomptes versés sur commandes',
				122 => 'Créances',
				1221 => '- Créances usagers (clients) et comptes rattachés',
				1222 => '- Créances diverses',
				123 => 'Valeurs mobilières de placement',
				124 => 'Disponibilités',
				2 => 'Passif',
				21 => 'Fonds propres',
				211 => 'Fonds associatifs',
				2111 => '- Fonds associatifs sans droit de reprise',
				2112 => '- Réserves',
				2113 => '- Report à nouveau',
				2114 => '- Résultat de l\'exercice',
				212 => 'Autres fonds associatifs',
				2121 => '- Fonds associatifs avec droit de reprise',
				21211 => '- Apports',
				21211 => '- legs et donations',
				22 => 'Fonds dédiés',
				221 => '',
				2211 => '- sur subventions de fonctionnement',
				2211 => '- sur autres ressources',
				23 => 'Dettes',
				231 => '',
				2311 => '- Emprunts et dettes / établissements de crédit',
				2212 => '- Emprunts et dettes financières divers',
				2213 => '- Avances et acomptes reçus sur commandes en cours',
				2214 => '- Dettes fournisseurs et comptes rattachés',
				2215 => '- Dettes fiscales et sociales',
				2216 => '- Dettes sur immobilisations et comptes rattachés',
				2216 => '- Autres dettes',
		),
		'assessmentMapping' => array(
				1011 => 21211,
				1012 => 21211,
				1013 => 21211,
				109 => 21211,
				2184 => 1123,
		),*/
	),
		
    'view_manager' => array(
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',       // On défini notre doctype
        'not_found_template'       => 'error/404',   // On indique la page 404
        'exception_template'       => 'error/index', // On indique la page en cas d'exception
        'template_map' => array(
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
        ),
        'template_path_stack' => array(
            'ppit-accounting' => __DIR__ . '/../view',
         ),
    ),
	'translator' => array(
		'locale' => 'fr_FR',
		'translation_file_patterns' => array(
			array(
				'type'     => 'phparray',
				'base_dir' => __DIR__ . '/../language',
				'pattern'  => '%s.php',
				'text_domain' => 'ppit-accounting'
			),
	       	array(
	            'type' => 'phparray',
	            'base_dir' => './vendor/zendframework/zendframework/resources/languages/',
	            'pattern'  => 'fr/Zend_Validate.php',
	        ),
		),
	),

	'ppitAccountingDependencies' => array(
	),

	'ppitRoles' => array(
			'PpitAccounting' => array(
			),
	),
	
	'menus' => array(
			'p-pit-finance' => array(
					'expense' => array(
							'route' => 'expense/index',
							'params' => array(),
							'glyphicon' => 'glyphicon-piggy-bank',
							'label' => array(
									'en_US' => 'Expenses',
									'fr_FR' => 'Dépenses',
							),
					),
					'bank-statement' => array(
							'action' => 'Journal',
							'route' => 'journal/index',
							'params' => array('journal_code' => 'bank'),
							'urlParams' => array(),
							'glyphicon' => 'glyphicon-ok',
							'label' => array(
									'en_US' => 'Bank statement',
									'fr_FR' => 'Relevé bancaire',
							),
					),
					'journal' => array(
							'action' => 'Journal',
							'route' => 'journal/index',
							'params' => array(),
							'urlParams' => array(),
							'glyphicon' => 'glyphicon-list-alt',
							'label' => array(
									'en_US' => 'Journal',
									'fr_FR' => 'Journal',
							),
					),
					'balance' => array(
							'action' => 'Balance',
							'route' => 'account/balance',
							'params' => array(),
							'urlParams' => array(),
							'glyphicon' => 'glyphicon-book',
							'label' => array(
									'en_US' => 'General ledger',
									'fr_FR' => 'Grand livre',
							),
					),
			),
	),
		
	'journal/legalInterest' => array(
			'rate' => 0.0203,
			'en_US' => 'Legal interests computation',
			'fr_FR' => 'Calcul des intérêts légaux',
	),
							
	'journal/accountingChart/expense' => array(
			'transport' => array(
					'6251' => array(
							'direction' => -1,
							'source' => 'excluding_tax',
					),
					'44566' => array(
							'direction' => -1,
							'source' => 'tax_amount',
					),
					'401' => array(
							'direction' => 1,
							'source' => 'tax_inclusive',
					),
			),
			'meal' => array(
					'6251' => array(
							'direction' => -1,
							'source' => 'excluding_tax',
					),
					'44566' => array(
							'direction' => -1,
							'source' => 'tax_amount',
					),
					'401' => array(
							'direction' => 1,
							'source' => 'tax_inclusive',
					),
			),
			'phone' => array(
					'626' => array(
							'direction' => -1,
							'source' => 'excluding_tax',
					),
					'44566' => array(
							'direction' => -1,
							'source' => 'tax_amount',
					),
					'401' => array(
							'direction' => 1,
							'source' => 'tax_inclusive',
					),
			),
			'mail' => array(
					'626' => array(
							'direction' => -1,
							'source' => 'excluding_tax',
					),
					'44566' => array(
							'direction' => -1,
							'source' => 'tax_amount',
					),
					'401' => array(
							'direction' => 1,
							'source' => 'tax_inclusive',
					),
			),
			'office' => array(
					'6063' => array(
							'direction' => -1,
							'source' => 'excluding_tax',
					),
					'44566' => array(
							'direction' => -1,
							'source' => 'tax_amount',
					),
					'401' => array(
							'direction' => 1,
							'source' => 'tax_inclusive',
					),
			),
			'library' => array(
					'6181' => array(
							'direction' => -1,
							'source' => 'excluding_tax',
					),
					'44566' => array(
							'direction' => -1,
							'source' => 'tax_amount',
					),
					'401' => array(
							'direction' => 1,
							'source' => 'tax_inclusive',
					),
			),
			'invitation' => array(
					'6234' => array(
							'direction' => -1,
							'source' => 'excluding_tax',
					),
					'44566' => array(
							'direction' => -1,
							'source' => 'tax_amount',
					),
					'401' => array(
							'direction' => 1,
							'source' => 'tax_inclusive',
					),
			),
			'gift' => array(
					'6234' => array(
							'direction' => -1,
							'source' => 'excluding_tax',
					),
					'44566' => array(
							'direction' => -1,
							'source' => 'tax_amount',
					),
					'401' => array(
							'direction' => 1,
							'source' => 'tax_inclusive',
					),
			),
			'miscellaneous' => array(
					'6185' => array(
							'direction' => -1,
							'source' => 'excluding_tax',
					),
					'44566' => array(
							'direction' => -1,
							'source' => 'tax_amount',
					),
					'401' => array(
							'direction' => 1,
							'source' => 'tax_inclusive',
					),
			),
	),

	'journal/index' => array(
			'title' => array('en_US' => 'P-PIT Accounting', 'fr_FR' => 'P-PIT Finances'),
	),
	'journal_statuses' => array(),
	'journal_actions' => array(),
	'journal_properties' => array(
			'year' => array(
					'type' => 'select',
					'modalities' => array(
							'2015' => array(
									'en_US' => '2015',
									'fr_FR' => '2015',
							),
							'2016' => array(
									'en_US' => '2016',
									'fr_FR' => '2016',
							),
					),
					'labels' => array(
							'en_US' => 'Year',
							'fr_FR' => 'Année',
					),
			),
			'sequence' => array(
					'type' => 'input',
					'labels' => array(
							'en_US' => 'Sequence',
							'fr_FR' => 'Séquence',
					),
			),
			'journal_code' => array(
					'type' => 'select',
					'modalities' => array(
							'general' => array(
									'en_US' => 'General',
									'fr_FR' => 'Général',
							),
							'bank' => array(
									'en_US' => 'Bank',
									'fr_FR' => 'Banque',
							),
							'closing' => array(
									'en_US' => 'Closing',
									'fr_FR' => 'Clôture',
							),
					),
					'labels' => array(
							'en_US' => 'Journal',
							'fr_FR' => 'Journal',
					),
			),
			'operation_date' => array(
					'type' => 'date',
					'labels' => array(
							'en_US' => 'Operation date',
							'fr_FR' => 'Date d\'opération',
					),
			),
			'accounting_date' => array(
					'type' => 'date',
					'labels' => array(
							'en_US' => 'Accounting date',
							'fr_FR' => 'Date comptable',
					),
			),
			'reference' => array(
					'type' => 'input',
					'labels' => array(
							'en_US' => 'Reference',
							'fr_FR' => 'Référence',
					),
			),
			'caption' => array(
					'type' => 'input',
					'labels' => array(
							'en_US' => 'Caption',
							'fr_FR' => 'Libellé',
					),
			),
			'direction' => array(
					'type' => 'select',
					'modalities' => array(
							'-1' => array(
									'en_US' => 'Debit',
									'fr_FR' => 'Débit',
							),
							'1' => array(
									'en_US' => 'Credit',
									'fr_FR' => 'Crédit',
							),
					),
					'labels' => array(
							'en_US' => 'Direction',
							'fr_FR' => 'Sens',
					),
			),
			'amount' => array(
					'type' => 'number',
					'minValue' => 0,
					'maxValue' => 99999999,
					'labels' => array(
							'en_US' => 'Amount',
							'fr_FR' => 'Montant',
					),
			),
			'account' => array(
					'type' => 'select',
					'modalities' => array(),
					'labels' => array(
							'en_US' => 'Account',
							'fr_FR' => 'Compte',
					),
			),
			'account_caption' => array(
					'type' => 'input',
					'labels' => array(
							'en_US' => 'Account caption',
							'fr_FR' => 'Libellé du compte',
					),
			),
	),
	'journal/search' => array(
			'title' => array('en_US' => 'Operations', 'fr_FR' => 'Opérations'),
			'todoTitle' => array('en_US' => 'current', 'fr_FR' => 'en cours'),
			'searchTitle' => array('en_US' => 'current', 'fr_FR' => 'recherche'),
			'main' => array(
					'journal_code' => 'select',
					'year' => 'select',
					'account' => 'range',
			),
			'more' => array(
					'operation_date' => 'range',
					'accounting_date' => 'range',
					'reference' => 'contains',
					'caption' => 'contains',
					'direction' => 'select',
					'amount' => 'range',
			),
	),
	'journal/list' => array(
			'account' => 'text',
			'operation_date' => 'text',
			'direction' => 'text',
			'amount' => 'text',
	),
	'journal/detail' => array(
			'title' => array('en_US' => 'Student sheet:', 'fr_FR' => 'Opération'),
			'displayAudit' => false,
	),
	'journal/bankUpdate' => array(
			'operation_date' => array('mandatory' => true),
			'reference' => array('mandatory' => false),
			'caption' => array('mandatory' => true),
			'direction' => array('mandatory' => true),
			'amount' => array('mandatory' => true),
	),
	'schemas' => array(
			'prestation' => array(
					'labels' => array(
							'en_US' => 'Prestation',
							'fr_FR' => 'Prestation',
					),
					'amounts' => array(
							'tax_excluded_amount' => array(
									'en_US' => 'Tax excluded amount',
									'fr_FR' => 'Montant HT',
							),
							'tax_amount' => array(
									'en_US' => 'Tax amount',
									'fr_FR' => 'Montant TVA',
							),
					),
					'ventilation' => array(
							'706' => array('C', 'tax_excluded_amount'),
							'44571' => array('C', 'tax_amount'),
							'411' => array('D', 'tax_excluded_amount', 'tax_amount'),
					),
			),
	),
);
