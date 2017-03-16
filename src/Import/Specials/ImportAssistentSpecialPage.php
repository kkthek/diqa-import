<?php
namespace DIQA\Import\Specials;

use SMW\SpecialPage;
use Philo\Blade\Blade;
use DIQA\Import\Models\TaggingRule;

class ImportAssistentSpecialPage extends SpecialPage {
	
	const ERROR_NOT_ALLOWED = 100;
	
	private $blade;
	public function __construct() {
		parent::__construct ( 'DIQAImportAssistent' );
		global $wgHooks;
		$views = __DIR__ . '/../../../views';
		$cache = __DIR__ . '/../../../cache';
		
		$this->blade = new Blade( $views, $cache );
		
	}
	
	/**
	 * Overloaded function that is responsible for the creation of the Special Page
	 */
	public function execute($par) {
		global $wgOut, $wgRequest, $wgServer, $wgScriptPath;
		
		$wgOut->setPageTitle ( wfMessage ( 'diqa-import-assistent-title' )->text () );
		
		try {
			
			$html = '';
			$this->checkPriviledges();
			$this->dispatchRequest ($html);
					
			
		} catch(Exception $e) {
			$html = $this->blade->view ()->make ( "specials.general.import-special-error", 
					[	'msg' => $e->getMessage() ] )->render ();
		}
		$wgOut->addHTML ( $html );
	}
	
	/**
	 * Checks if the user is allowed to access.
	 *
	 * @throws Exception if not
	 */
	private function checkPriviledges() {
		global $wgUser;
		if (!in_array('sysop', $wgUser->getGroups())) {
			throw new Exception('Not allowed to access', self::ERROR_NOT_ALLOWED);
		}
	}
	
	/**
	 * Handles a web-request.
	 *
	 */
	private function dispatchRequest(& $html) {
		
		if (isset($_POST['diqa_import_assistent_categories'])) {
			
			$category = $_POST['diqa_import_assistent_categories'];
			$path = $_POST['diqa_import_assistent_path'];
			
			$this->createRules($category, $path);
		}
		
		$this->showDefaultContent($html);
	}
	
	/**
	 * Shows the default content of the DIQAimport special page.
	 *
	 * @param string (out) $html
	 */
	private function showDefaultContent(& $html) {
		
		// read directory proposals
		global $IP;
		$content = file_get_contents("$IP/images/.diqa-import-directories");
		$lines = explode("\n", $content);
		$rootNode = new TreeNode();
		foreach($lines as $line) {
			$parts = explode("/", $line);
			$node = $rootNode;
			foreach($parts as $part) {
				if (trim($part) === '') continue;
				if ($node->containsChildWithTitle($part)) {
					$node = $node->getChildByTitle($part);
				} else {
					$node = $node->addChild(new TreeNode($part, $part));
				}
			}
		}
		
		$taggingRules = TaggingRule::where('rule_class', 'LIKE', '_assistent_get%')
		->orderBy('rule_class')
		->orderBy('priority')
		->get();
		
		$html = $this->blade->view ()->make ( "specials.assistent.special-page",
				[	'tree' => $rootNode->getTreeAsJSON(),
					'taggingRules' => $taggingRules
				] )->render ();
	}
	
	private function createRules($category, $dirs) {
		$dirs = str_replace(' ', '\s', $dirs);
		
		$entry = new TaggingRule();
		$entry->rule_class = '_assistent_get'.$category;
		$entry->crawled_property = 'DIQAFileLocation';
		$entry->type = 'regex';
		$entry->parameters = $dirs;
		$entry->return_value = $category;
		$entry->priority = 0;
				
		$entry->save ();
	}
	
	
}

/**
 * Tree representation for use with fancytree js-lib
 * @author Kai
 *
 */
class TreeNode implements \JsonSerializable {
	
	private $key;
	private $title;
	private $children;
	private $folder;
	
	public function jsonSerialize() {
		$obj = new \stdClass();
		$obj->key = $this->key;
		$obj->title = $this->title;
		$obj->children = $this->children;
		$obj->folder = $this->folder;
		return $obj;
	}
	
	/**
	 * @return the $key
	 */
	public function getKey() {
		return $this->key;
	}

	/**
	 * @return the $title
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * @return the $children
	 */
	public function getChildren() {
		return $this->children;
	}

	public function __construct($key = 'root', $title = 'root') {
		$this->key = $key;
		$this->title = $title;
		$this->children = [];
		$this->folder = true;
	}
	
	public function containsChildWithTitle($title) {
		return !is_null($this->getChildByTitle($title));
	}
	
	public function getChildByTitle($title) {
		foreach($this->children as $c) {
			if ($c->getTitle() === $title) {
				return $c;
			}
		}
		return NULL;
	}
	
	public function addChild($child) {
		$this->children[] = $child;
		return $child;
	}
	
	public function getTreeAsJSON() {
		return json_encode($this->getChildren());
	}
}