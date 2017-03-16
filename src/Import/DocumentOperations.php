<?php
namespace DIQA\Import;

use DIQA\Util\QueryUtils;
use DIQA\Util\LoggerUtils;

/**
 * Document operations which may be re-used at several places.
 * 
 * @author Kai
 *
 */
class DocumentOperations {
	
	private static $logger;
	
	private static function ensureLogger() {
		if (is_null(self::$logger)) {
			self::$logger = new LoggerUtils('DocumentOperations', 'DIQAimport');
		}
	}
	/**
	 * Cleans the imported documents and remove those
	 * pages for which no files exist anymore.
	 *
	 * @param number $delay Delay between a chunk of 100 pages (seconds)
	 * @return number Number of deleted pages
	 */
	public static function cleanupAllDocuments($delay) {
		
		self::ensureLogger();
		
		$filesDeleted = 0;
		$offset = 0;
	
		$fileLocationPrintout = new \SMWPrintRequest ( \SMWPrintRequest::PRINT_PROP, "DIQAFileLocation",
				\SMWPropertyValue::makeUserProperty ( 'DIQAFileLocation' ) );
		do {
			$pages = QueryUtils::executeBasicQuery ( '[[DIQAFileLocation::+]]', [
					$fileLocationPrintout
					], [
					'limit' => '100',
					'offset' => "$offset"
					] );
	
			while ( $res = $pages->getNext () ) {
				$pageID = $res [0]->getNextText ( SMW_OUTPUT_WIKI );
				$fileLocation = $res [1]->getNextText ( SMW_OUTPUT_WIKI );
				$mwTitle = \Title::newFromText ( $pageID );
				if (! file_exists ( $fileLocation )) {
					$a = new \Article ( $mwTitle );
					$a->doDelete ( 'according file to document does not exist anymore' );
					self::$logger->log("Page deleted: ".$mwTitle->getPrefixedText());
					$filesDeleted++;
				}
			}
	
			$offset += 100;
			if (count ( $pages->getResults() ) > 0) {
				sleep ( $delay );
			}
		} while ( count ( $pages->getResults() ) > 0 );
		return $filesDeleted;
	}
}