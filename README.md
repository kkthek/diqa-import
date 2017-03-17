# diqa-import
Imports Office documents, makes full-text and metadata available for faceted search

DIQAimport

#############################
	Installation	
#############################

 Run once:
 	extensions/DIQAimport/maintenance/Setup.php
 	
 Configure cron-jobs:
 
 	crontab -l | { cat; echo "* * * * *  php /var/www/html/mediawiki/extensions/Import/maintenance/CrawlDirectory.php"; } | crontab -
	crontab -l | { cat; echo "* * * * *  php /var/www/html/mediawiki/maintenance/runJobs.php"; } | crontab -
	
 Create directory which contains the documents (a mount point):
 
	sudo mkdir -p /opt/freigabe

#############################
    Settings
#############################

1. $wgDIQAImportUseAllMetadata

	Stores all extracted metadata in SOLR (NOT in the wiki!) to allow 
	exploring the data via Faceted Search.
	
	Default value: false
 	
#############################
	Usage	
#############################

	1. Go to Special:DIQAimport (as WikiSysop)
	
	2. Mount a Windows folder with Office documents into the linux file system
	
			Usage: bin/mountWinShare.sh \\UNC\Path\to\folder User
			The folder is mounted to: /opt/freigabe
			
			For example: ./mountWinShare.sh //192.168.1.7/testfreigabe Kai
			
	3. Create at least one crawler config. 
			
			Import-Path: /opt/freigabe
			UNC-Path:    \\KAIS-PC\testfreigabe
			Interval: any
	
	4. Optional: Creating tagging rules on Special:DIQAtagging
	
Note:
	If you change the tagging rules later, you have to refresh your semantic data.
	The crawler will do this only for *modified* documents.
	


 	
