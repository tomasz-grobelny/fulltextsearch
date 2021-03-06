<?php
/**
 * FullTextSearch - Full text search framework for Nextcloud
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2018
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\FullTextSearch\Command;

use Exception;
use OCA\FullTextSearch\Model\ExtendedBase;
use OCA\FullTextSearch\Model\Index;
use OCA\FullTextSearch\Model\IndexDocument;
use OCA\FullTextSearch\Service\MiscService;
use OCA\FullTextSearch\Service\ProviderService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class DocumentProvider extends ExtendedBase {


	/** @var ProviderService */
	private $providerService;

	/** @var MiscService */
	private $miscService;


	/**
	 * Index constructor.
	 *
	 * @param ProviderService $providerService
	 * @param MiscService $miscService
	 */
	public function __construct(ProviderService $providerService, MiscService $miscService
	) {
		parent::__construct();

		$this->providerService = $providerService;
		$this->miscService = $miscService;
	}


	/**
	 *
	 */
	protected function configure() {
		parent::configure();
		$this->setName('fulltextsearch:document:provider')
			 ->setDescription('Get document from index')
			 ->addArgument('userId', InputArgument::REQUIRED, 'userId')
			 ->addArgument('providerId', InputArgument::REQUIRED, 'providerId')
			 ->addArgument('documentId', InputArgument::REQUIRED, 'documentId')
			 ->addOption('content', 'c', InputOption::VALUE_NONE, 'return some content');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int|null|void
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$providerId = $input->getArgument('providerId');
		$documentId = $input->getArgument('documentId');
		$userId = $input->getArgument('userId');

		$provider = $this->providerService->getProvider($providerId);

		$index = new Index($providerId, $documentId);
		$index->setOwnerId($userId);
		$index->setStatus(Index::INDEX_FULL);
		$indexDocument = $provider->updateDocument($index);
		if ($indexDocument->getIndex()
						  ->isStatus(Index::INDEX_REMOVE)) {
			throw new Exception('Unknown document');
		}
		
		$output->writeln('Document: ');
		$output->writeln(json_encode($indexDocument, JSON_PRETTY_PRINT));

		if ($input->getOption('content') !== true) {
			return;
		}

		$output->writeln('Content: ');
		$content = $indexDocument->getContent();
		if ($indexDocument->isContentEncoded() === IndexDocument::ENCODED_BASE64) {
			$content = base64_decode($content, true);
		}

		$output->writeln(substr($content, 0, 60));
	}


}



