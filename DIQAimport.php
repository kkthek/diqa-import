<?php

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;
use DIQA\Import\Models\CrawlerConfig;
use Illuminate\Database\QueryException;

/**
 * The main file of the DIQA Import extension
 *
 * @file
 * @ingroup DIQA
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This file is part of a MediaWiki extension, it is not a valid entry point.' );
}

define( 'DIQA_IMPORT_VERSION', '0.1' );

########################################
# Default settings
########################################

global $wgDIQAImportUseAllMetadata;
$wgDIQAImportUseAllMetadata = false;

########################################

global $wgVersion;
global $wgExtensionCredits;
global $wgExtensionMessagesFiles;
global $wgHooks;
global $wgResourceModules;
global $wgExtensionFunctions;

// register extension
$wgExtensionCredits[ 'diqa' ][] = array(
	'path' => __FILE__,
	'name' => 'Import',
	'author' => array( 'DIQA Projektmanagement GmbH' ),
	'license-name' => 'GPL-2.0+',
	'url' => 'http://www.diqa-pm.com',
	'descriptionmsg' => 'diqa-import-desc',
	'version' => DIQA_IMPORT_VERSION,
);

$dir = dirname( __FILE__ );

$wgExtensionMessagesFiles['DIQAimport'] = $dir . '/DIQAimport.i18n.php';
$wgExtensionFunctions[] = 'wfDIQAimportSetup';

// allow sysops to crawl/tag by default. can be revoked in LocalSettings
global $wgGroupPermissions;
$wgGroupPermissions['sysop']['diqa-crawl'] = true;
$wgGroupPermissions['sysop']['diqa-tag'] = true;

global $wgJobClasses;
$wgJobClasses['ImportDocumentJob'] = 'DIQA\Import\ImportDocumentJob';
$wgJobClasses['CrawlDirectoryJob'] = 'DIQA\Import\CrawlDirectoryJob';
$wgJobClasses['RefreshDocumentsJob'] = 'DIQA\Import\RefreshDocumentsJob';

$GLOBALS['wgAPIModules']['diqa_import'] = 'DIQA\Import\Api\DIQAImportAPI';

$wgResourceModules['ext.enhancedretrieval.diqaimport'] = array(
		'localBasePath' => $dir,
		'remoteExtPath' => 'Import',
		'scripts' => array(
				'libs/xfs_facetedsearch.js',
				'libs/import-page.js'
				
		),
		'styles' => [ 'skins/diqa-import.css'
				    ],
		
		'dependencies' => [ 'jquery.ui.autocomplete', 'jquery.ui.datepicker', 'jquery.tablesorter'],
		'messages' => array(
				'diqa-import-open-document',
				'diqa-import-open-document-dir',
				
		),
);

$wgResourceModules['ext.enhancedretrieval.diqaimport-assistent'] = array(
		'localBasePath' => $dir,
		'remoteExtPath' => 'Import',
		'scripts' => array(
				'libs/fancytree/jquery.fancytree-all.js',
				'libs/import-assistent-page.js'

		),
		'styles' => [ 
		'libs/fancytree/skin-win8/ui.fancytree.min.css'
		],

		'dependencies' => ['jquery.ui.core', 'jquery.effects.core', 'jquery.ui.slider' ],
		'messages' => array(
				'diqa-import-open-document',
				'diqa-import-open-document-dir',

		),
		//'group' => 'jquery.ui',
);

$wgHooks['ParserAfterStrip'][] = 'DIQA\Import\TaggingRuleParserFunction::parserAfterStrip';
$wgHooks['ParserFirstCallInit'][] = 'wfDIQAimportRegisterParserHooks';
$wgHooks['fs_saveArticle'][] = 'DIQA\Import\MetadataIndexer::addExtractedMetadata';
$wgHooks['LoadExtensionSchemaUpdates'][] = 'wfDIQAimportDBUpdate';

function wfDIQAimportRegisterParserHooks(Parser $parser)
{
	// Create a function hook associating the name of the parser function with the method to call
	$parser->setFunctionHook('chooseTaggingValue', 'DIQA\Import\TaggingRuleParserFunction::chooseTaggingValue');
	 
	// add JS modules
	global $wgOut, $wgTitle;
	
	$wgOut->addModules('ext.enhancedretrieval.diqaimport');
	
	if (!is_null($wgTitle) && ($wgTitle->getNamespace() == NS_SPECIAL && $wgTitle->getText() == 'DIQAImportAssistent')) {
		$wgOut->addModules('ext.enhancedretrieval.diqaimport-assistent');
	}
}

/**
 * Updates DB schema (if necessary)
 */
function wfDIQAimportDBUpdate() {
	require_once('maintenance/Setup.php');
	$setup = new SetupDIQAImport();
	$setup->execute();
}

/**
 * Setup import extension
 */
function wfDIQAimportSetup() {
	
	global $wgOut;
	
	global $fsgExtraPropertiesToRequest;
	$fsgExtraPropertiesToRequest[] = 'smwh_diqa_import_fullpath_xsdvalue_t';
	$fsgExtraPropertiesToRequest[] = 'smwh_DIQAFileLocation_xsdvalue_t';
	$fsgExtraPropertiesToRequest[] = 'smwh_DIQAFilename_xsdvalue_t';
	$fsgExtraPropertiesToRequest[] = 'smwh_DIQAFilesuffix_xsdvalue_t';
	
	
	global $wgSpecialPages;
	$wgSpecialPages['DIQAImport'] = array('DIQA\Import\Specials\ImportSpecialPage');
	$wgSpecialPages['DIQATagging'] = array('DIQA\Import\Specials\TaggingSpecialPage');
	$wgSpecialPages['DIQAImportAssistent'] = array('DIQA\Import\Specials\ImportAssistentSpecialPage');
	
	wfDIQAInitializeEloquent();
	
	global $fsgTitleProperty;
	$fsgTitleProperty = isset($fsgTitleProperty) ? $fsgTitleProperty : '';
	// serialize crawler config to JS
	$script = "";
	$script .= "\nvar DIQA = DIQA || {};";
	$script .= "\nDIQA.IMPORT = DIQA.IMPORT || {};";
	$script .= "\nDIQA.IMPORT.crawlerConfig = [];";
	$script .= "\nDIQA.IMPORT.fsgTitleProperty = '{$fsgTitleProperty}';";
	$extractedMetadata = json_encode(wfDIQAGetMetadata());
	$script .= "\nDIQA.IMPORT.extractedMetadata = {$extractedMetadata};";
	
	try {
		$entries = CrawlerConfig::all ();
		$mappings = [];
		foreach($entries as $e) {
			$mappings[] = [
				'root_path' => $e->getRootPath(), 
				'url_prefix' => $e->getURLPrefix() 
			];
		}
		usort($mappings, function($e, $f) { 
			return strlen($f['root_path']) - strlen($e['root_path']);
		});
		$script .= "\nDIQA.IMPORT.crawlerConfig = ".json_encode($mappings).";";
	} catch(QueryException $e) {
		// ignore. table may not exist before setup
	}
	$wgOut->addScript(
			'<script type="text/javascript">'.$script.'</script>'
	);
	
	return true;
}

function wfDIQAGetMetadata() {
	global $IP;
	$filename = "$IP/images/.diqa-import-metadata";
	
	$content = file_exists($filename) && is_readable($filename) ? file_get_contents($filename) : '';
	$list =  explode(',', $content);
	usort($list, function($e, $f) {
		return strcmp(strtolower($e), strtolower($f));
	});
	return $list;
}

/**
 * Setups Eloquent ORM-Mapper
 */
function wfDIQAInitializeEloquent() {
	$capsule = new Capsule ();

	global $wgDBname, $wgDBuser, $wgDBpassword;
	$capsule->addConnection ( [
			'driver' => 'mysql',
			'host' => 'localhost',
			'database' => $wgDBname,
			'username' => $wgDBuser,
			'password' => $wgDBpassword,
			'charset' => 'utf8',
			'collation' => 'utf8_unicode_ci',
			'prefix' => ''
			] );

	// Set the event dispatcher used by Eloquent models... (optional)

	$capsule->setEventDispatcher ( new Dispatcher ( new Container () ) );

	// Make this Capsule instance available globally via static methods... (optional)
	$capsule->setAsGlobal ();

	// Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
	$capsule->bootEloquent ();
}

/**
 * Returns absolute Wiki-URL from relative path.
 * 
 * @param string $url relative URL
 * @return string
 */
function wfDIQAURL($url) {
	global $wgServer, $wgScriptPath;
	$url = ltrim($url, '/');
	return $wgServer.$wgScriptPath.'/index.php/'.$url;
}
