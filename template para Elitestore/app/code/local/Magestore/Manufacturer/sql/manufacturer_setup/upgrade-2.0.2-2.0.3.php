<?php
 
$installer = $this;
$connection = $installer->getConnection();

$installer->startSetup();

$installer->run("ALTER TABLE `manufacturer`
	ADD COLUMN `imagebanner1` VARCHAR(255) NOT NULL COLLATE 'utf8_unicode_ci',
	ADD COLUMN `default_imagebanner1` TINYINT(1) NOT NULL DEFAULT '1',
    ADD COLUMN `imagebanner2` VARCHAR(255) NOT NULL COLLATE 'utf8_unicode_ci',
    ADD COLUMN `default_imagebanner2` TINYINT(1) NOT NULL DEFAULT '1',
    ADD COLUMN `imagebanner3` VARCHAR(255) NOT NULL COLLATE 'utf8_unicode_ci',
    ADD COLUMN `default_imagebanner3` TINYINT(1) NOT NULL DEFAULT '1',
    ADD COLUMN `textobanner1` TEXT NULL COLLATE 'utf8_unicode_ci',
    ADD COLUMN `default_textobanner1` TINYINT(1) NOT NULL DEFAULT '1',
    ADD COLUMN `textobanner2` TEXT NULL COLLATE 'utf8_unicode_ci',
    ADD COLUMN `default_textobanner2` TINYINT(1) NOT NULL DEFAULT '1',
    ADD COLUMN `textobanner3` TEXT NULL COLLATE 'utf8_unicode_ci',
    ADD COLUMN `default_textobanner3` TINYINT(1) NOT NULL DEFAULT '1',
    ADD COLUMN `linkbanner1` TEXT NULL COLLATE 'utf8_unicode_ci',
    ADD COLUMN `default_linkbanner1` TINYINT(1) NOT NULL DEFAULT '1',
    ADD COLUMN `linkbanner2` TEXT NULL COLLATE 'utf8_unicode_ci',
    ADD COLUMN `default_linkbanner2` TINYINT(1) NOT NULL DEFAULT '1',
    ADD COLUMN `linkbanner3` TEXT NULL COLLATE 'utf8_unicode_ci',
    ADD COLUMN `default_linkbanner3` TINYINT(1) NOT NULL DEFAULT '1',
    ADD COLUMN `morefor1` TEXT NULL COLLATE 'utf8_unicode_ci',
    ADD COLUMN `default_morefor1` TINYINT(1) NOT NULL DEFAULT '1',
    ADD COLUMN `morefor2` TEXT NULL COLLATE 'utf8_unicode_ci',
    ADD COLUMN `default_morefor2` TINYINT(1) NOT NULL DEFAULT '1',
    ADD COLUMN `morefor3` TEXT NULL COLLATE 'utf8_unicode_ci',
    ADD COLUMN `default_morefor3` TINYINT(1) NOT NULL DEFAULT '1',
    ADD COLUMN `morefor4` TEXT NULL COLLATE 'utf8_unicode_ci',
    ADD COLUMN `default_morefor4` TINYINT(1) NOT NULL DEFAULT '1',
    ADD COLUMN `morefor5` TEXT NULL COLLATE 'utf8_unicode_ci',
    ADD COLUMN `default_morefor5` TINYINT(1) NOT NULL DEFAULT '1',
    ADD COLUMN `morefor6` TEXT NULL COLLATE 'utf8_unicode_ci',
    ADD COLUMN `default_morefor6` TINYINT(1) NOT NULL DEFAULT '1',
    ADD COLUMN `morefor7` TEXT NULL COLLATE 'utf8_unicode_ci',
    ADD COLUMN `default_morefor7` TINYINT(1) NOT NULL DEFAULT '1',
    ADD COLUMN `morefor8` TEXT NULL COLLATE 'utf8_unicode_ci',
    ADD COLUMN `default_morefor8` TINYINT(1) NOT NULL DEFAULT '1',
    ADD COLUMN `linkmorefor1` TEXT NULL COLLATE 'utf8_unicode_ci',
    ADD COLUMN `default_linkmorefor1` TINYINT(1) NOT NULL DEFAULT '1',
    ADD COLUMN `linkmorefor2` TEXT NULL COLLATE 'utf8_unicode_ci',
    ADD COLUMN `default_linkmorefor2` TINYINT(1) NOT NULL DEFAULT '1',
    ADD COLUMN `linkmorefor3` TEXT NULL COLLATE 'utf8_unicode_ci',
    ADD COLUMN `default_linkmorefor3` TINYINT(1) NOT NULL DEFAULT '1',
    ADD COLUMN `linkmorefor4` TEXT NULL COLLATE 'utf8_unicode_ci',
    ADD COLUMN `default_linkmorefor4` TINYINT(1) NOT NULL DEFAULT '1',
    ADD COLUMN `linkmorefor5` TEXT NULL COLLATE 'utf8_unicode_ci',
    ADD COLUMN `default_linkmorefor5` TINYINT(1) NOT NULL DEFAULT '1',
    ADD COLUMN `linkmorefor6` TEXT NULL COLLATE 'utf8_unicode_ci',
    ADD COLUMN `default_linkmorefor6` TINYINT(1) NOT NULL DEFAULT '1',
    ADD COLUMN `linkmorefor7` TEXT NULL COLLATE 'utf8_unicode_ci',
    ADD COLUMN `default_linkmorefor7` TINYINT(1) NOT NULL DEFAULT '1',
    ADD COLUMN `linkmorefor8` TEXT NULL COLLATE 'utf8_unicode_ci',
    ADD COLUMN `default_linkmorefor8` TINYINT(1) NOT NULL DEFAULT '1'");	
 
$installer->endSetup();