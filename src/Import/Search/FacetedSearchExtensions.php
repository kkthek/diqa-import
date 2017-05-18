<?php
namespace DIQA\Import\Search;

use Action;
use CurlHttpRequest;
use Exception;
use SMW\ApplicationFactory;


class FacetedSearchExtensions extends Action {

	public function show() {

		$data = [];
		$data['objectid'] = $this->getRequest()->getVal('objectid');
		$data['ns'] = $this->getRequest()->getVal('ns', NS_MAIN);

		$title = \Title::newFromText($data['objectid'], $data['ns']);
		$type = $this->getRequest()->getVal('type');

		switch($type) {
			
			case "open_file":

				$this->openFile($title, $data);
				break;
					
			default:
				$out = $this->getOutput();
				$out->setPageTitle( 'Error' );
				$out->addHTML("<p>Dieser Typ Direktannotation existiert nicht!</p>");

		}
	}

	private function openFile($title, $data) {
		
		$url = sprintf("/%s", $title->getPrefixedDBkey());
		self::redirect($url, $data);
		
	}

	private function printoutText($header, $text) {
		$out = $this->getOutput();
		$out->setPageTitle( $header );
		$out->addHTML(sprintf("<p>%s</p>", htmlspecialchars($text)));
	}

	private static function redirect($relative_url, $data) {
		global $wgServer, $wgScriptPath, $wgODBFSRedirectURL;

		$url = $wgServer . $wgScriptPath . '/index.php';
		$url .= $relative_url;
		$url = str_replace("<title>", urlencode($data['title']), $url);
		$url = str_replace("<ns>", urlencode($data['ns']), $url);
			
		header("Location: $url");
		die();
	}

	/*
	 * (non-PHPdoc) @see Action::getName()
	*/
	public function getName() {
		return "directannotation";
	}

	/*
	 * (non-PHPdoc) @see Action::execute()
	*/
	public function execute() {
		// do nothing
	}


}