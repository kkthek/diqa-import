<?php
/**
 * Language file for DIQAimport
 */

$messages = array();

$messages['de'] = array(
	'diqaimport' => 'DIQA-Import',
	'diqatagging' => 'DIQA-Import-Taggingregeln',
	'diqaimportassistent' => 'DIQA-Import-Assistent',
	'diqa-import-desc' => 'Import-Extension',
	'diqa-import-title' => 'Dokumente importieren',
	'diqa-tagging-title' => 'Dokumente taggen',
	'diqa-import-assistent-title' => 'Dokumente-Regel Assistent',
		
	# Special
	'diqa-import-path-fs' => 'Import-Pfad im Dateisystem',
	'diqa-url-prefix' => 'URL-Prefix (z.B. UNC-Pfad-Prefix)',
	'diqa-time-to-start' => 'Zeitpunkt',
	'diqa-date-to-start' => 'Startdatum',
	'diqa-time-interval' => 'Zeitinterval',
	'diqa-time-interval-0' => 'einmal täglich',
	'diqa-time-interval-1' => 'stündlich',
	'diqa-save-button' => 'Speichern',
	'diqa-cancel-button' => 'Abbrechen',
	'diqa-back-button' => 'Zurück',
	'diqa-test-button' => 'Testen',
	'diqa-crawler-type' => 'Crawler-Typ',
	'diqa-last-run-at' => 'Zuletzt gestartet',
	'diqa-documents-processed' => 'Dokumente bearbeitet',
	'diqa-status-text' => 'Status',
	'diqa-import-check-path' => 'Prüfe Pfad',
	'diqa-import-remove-entry' => 'Entfernen',
	'diqa-import-edit-entry' => 'Bearbeiten',
	'diqa-import-copy-entry' => 'Kopieren',
	'diqa-import-remove-rule' => 'Entfernen',
	'diqa-import-edit-rule' => 'Bearbeiten',
	'diqa-import-copy-rule' => 'Kopieren',
	'diqa-import-test-rule' => 'Testen',
	'diqa-import-refresh' => 'Aktualisieren',
	'diqa-import-wiki-refresh' => 'Semantische Daten aktualisieren',
	'diqa-import-force-crawl' => 'Crawl erzwingen',
	'diqa-import-exporttagging' => 'Regeln als XML exportieren',
	'diqa-import-importtagging' => 'Regeln von XML importieren',
	'diqa-import-tagging-type' => 'Regeltyp',
	'diqa-import-tagging-priority' => 'Reihenfolge',
	'diqa-import-tagging-crawledProperty' => 'Dokumenteingenschaft',
	'diqa-import-tagging-attribute' => 'Attribut',
	'diqa-import-tagging-constraint' => 'Regulärer Ausdruck',
	'diqa-import-tagging-type-metadata' => 'Kopiere Attribut',
	'diqa-import-tagging-type-regex' => 'Regulärer Ausdruck',
	'diqa-import-tagging-return-value' => 'Rückgabewert',
	'diqa-import-test-article' => 'Teste gegen Artikel',
	'diqa-import-test-result' => 'Resultat',
	'diqa-import-rule-applied' => 'Regel angewendet',
	'diqa-import-instead-rule-applied' => 'Stattdessen folg. Regel angewendet',
	'diqa-import-rule-output' => 'Regel-Ausgabe',
	'diqa-import-yes' => 'ja',
	'diqa-import-no' => 'nein',
	'diqa-import-active' => 'Aktiv',
	'diqa-import-no-rule-applied' => 'Keine Regel angewendet',
	'diqa-import-attribute-property-hint' => 'Benutze * um alle Attribute/Kategorien zu sehen',
	'diqa-import-crawled-property-hint' => 'Benutze * um alle Dokumenteneigenschaften zu sehen',
	'diqa-import-returnvalue-hint' => 'darf auch leer sein. In diesem Fall muss der reguläre Ausdruck mind. 1 Sub-pattern enthalten',
		
	# JS
	'diqa-import-open-document' => 'Dokument öffnen',
	'diqa-import-open-document-dir' => 'Dokument-Verz. öffnen'
);

$magicWords['de'] = array(
		'chooseTaggingValue' => array (0, 'chooseTaggingValue'),
);

$magicWords['en'] = $magicWords['de'];