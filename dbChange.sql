INSERT INTO `scripts` (`script`, `pagesecurity`, `description`) VALUES ('StockIssues.php', '2', 'Allows entry of stock issues');


ALTER TABLE `companies` ADD `location_1` VARCHAR(255) NULL DEFAULT NULL AFTER `freightact`, ADD `location_2` VARCHAR(255) NULL DEFAULT NULL AFTER `location_1`, ADD `office_1` VARCHAR(255) NULL DEFAULT NULL AFTER `location_2`, ADD `office_2` VARCHAR(255) NULL DEFAULT NULL AFTER `office_1`, ADD `fax_2` VARCHAR(255) NULL DEFAULT NULL AFTER `office_2`, ADD `telephone_2` VARCHAR(255) NULL DEFAULT NULL AFTER `fax_2`, ADD `website` VARCHAR(255) NULL DEFAULT NULL AFTER `telephone_2`;
