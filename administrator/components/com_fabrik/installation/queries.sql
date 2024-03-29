CREATE TABLE IF NOT EXISTS `#__fabrik_log` (
	`id` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`timedate_created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ,
	`referring_url` VARCHAR( 255 ) NOT NULL ,
	`message_type` CHAR( 60 ) NOT NULL ,
	`message` TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS `#__fabrik_cron` (
	`id` INT( 6 ) NOT NULL AUTO_INCREMENT,
	`label` VARCHAR( 100 ) NOT NULL ,
	`frequency` SMALLINT( 6 ) NOT NULL ,
	`unit` VARCHAR( 15 ) NOT NULL ,
	`created` DATETIME NOT NULL ,
	`created_by` INT( 6 ) NOT NULL ,
	`created_by_alias` VARCHAR( 30 ) NOT NULL ,
	`modified` DATETIME NOT NULL ,
	`modified_by` VARCHAR( 30 ) NOT NULL ,
	`checked_out` INT( 6 ) NOT NULL ,
	`checked_out_time` DATETIME NOT NULL ,
	`state` TINYINT( 1 ) NOT NULL ,
	`plugin` VARCHAR( 50 ) NOT NULL,
	`lastrun` DATETIME NOT NULL,
	`attribs` TEXT NOT NULL,
	PRIMARY KEY ( `id` )
);

CREATE TABLE IF NOT EXISTS `#__fabrik_form_sessions` (
	`id` INT( 6 ) NOT NULL AUTO_INCREMENT,
	`hash` VARCHAR( 255 ) NOT NULL ,
	`user_id` INT( 6 ) NOT NULL ,
	`form_id` INT( 6 ) NOT NULL ,
	`row_id` INT( 10 ) NOT NULL ,
	`last_page` INT( 4 ) NOT NULL ,
	`referring_url` VARCHAR( 255 ) NOT NULL ,
	`data` TEXT NOT NULL ,
	`time_date` TIMESTAMP NOT NULL,
	PRIMARY KEY ( `id` )
);

CREATE TABLE IF NOT EXISTS `#__fabrik_visualizations` (
			`id` INT NOT NULL AUTO_INCREMENT,
			`plugin` VARCHAR( 100 ) NOT NULL,
			`label` VARCHAR( 255 ) NOT NULL ,
			`intro_text` TEXT NOT NULL,
			`created` DATETIME NOT NULL ,
			`created_by` INT( 11 ) NOT NULL ,
			`created_by_alias` VARCHAR( 100 ) NOT NULL ,
			`modified` DATETIME NOT NULL ,
			`modified_by` INT( 11 ) NOT NULL ,
			`checked_out` INT( 11 ) NOT NULL ,
			`checked_out_time` DATETIME NOT NULL ,
			`publish_up` DATETIME NOT NULL ,
			`publish_down` DATETIME NOT NULL ,
			`state` INT( 1 ) NOT NULL ,
			`attribs` TEXT NOT NULL,
			PRIMARY KEY ( `id` ));

CREATE TABLE IF NOT EXISTS `#__fabrik_calendar_events` (
			`id` INT( 6 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
			`visualization_id` INT( 6 ) NOT NULL ,
			`label` VARCHAR( 255 ) NOT NULL ,
			`location` VARCHAR( 255 ) NOT NULL ,
			`start_date` DATETIME NOT NULL ,
			`end_date` DATETIME NOT NULL ,
			`event_type` INT( 2 ) NOT NULL ,
			`all_day` INT( 1 ) NOT NULL ,
			`repeat` INT( 1 ) NOT NULL ,
			`repeat_occurs` VARCHAR( 50 ) NOT NULL ,
			`repeate_every` INT( 5 ) NOT NULL ,
			`repeat_until` VARCHAR( 255 ) NOT NULL ,
			`repeat_occurances` INT( 6 ) NOT NULL ,
			`repeat_until_date` DATE NOT NULL ,
			`event_category` INT( 3 ) NOT NULL ,
			`access` INT( 3 ) NOT NULL ,
			`created_by` INT( 6 ) NOT NULL ,
			`created_by_alias` VARCHAR( 150 ) NOT NULL ,
			`description` TEXT NOT NULL ,
			`priority` INT( 3 ) NOT NULL ,
			`status` INT( 3 ) NOT NULL ,
			`url` VARCHAR( 255 ) NOT NULL);

CREATE TABLE IF NOT EXISTS `#__fabrik_packages` (
			`id` INT NOT NULL AUTO_INCREMENT,
			`label` VARCHAR(255) NOT NULL,
			`state` TINYINT(1) NOT NULL,
			`attribs` TEXT NOT NULL,
			`checked_out` INT(4) NOT NULL,
			`checked_out_time` DATETIME,
			`tables` TEXT NOT NULL,
			`created` DATETIME NOT NULL ,
			`modified` DATETIME NOT NULL ,
			`modified_by` INT( 6 ) NOT NULL,
			`template` VARCHAR( 255 ) NOT NULL,
			PRIMARY KEY ( `id` ));

       	
CREATE TABLE IF NOT EXISTS `#__fabrik_jsactions` (
			`id` INT NOT NULL AUTO_INCREMENT,
			`element_id` INT(10) NOT NULL, 
			`action` VARCHAR(255) NOT NULL,
			`code` TEXT NOT NULL,
			`attribs` TEXT NOT NULL,
			PRIMARY KEY ( `id` ));


CREATE TABLE IF NOT EXISTS `#__fabrik_joins` (
			`id` INT NOT NULL AUTO_INCREMENT,
			`table_id` INT(6) NOT NULL,
			`element_id` INT(6) NOT NULL,
			`join_from_table` VARCHAR(255) NOT NULL,
			`table_join` VARCHAR(255) NOT NULL,
			`table_key` VARCHAR(255) NOT NULL,
			`table_join_key` VARCHAR(255) NOT NULL, 
			`join_type` VARCHAR(255) NOT NULL,
			`group_id` INT(10) NOT NULL,
			`attribs` TEXT NOT NULL,
			PRIMARY KEY ( `id` ));

CREATE TABLE IF NOT EXISTS `#__fabrik_connections` (
			`id` INT NOT NULL AUTO_INCREMENT,
			`host` VARCHAR(255) NOT NULL, 
			`user` VARCHAR(255) NOT NULL,
			`password` VARCHAR(255) NOT NULL,
			`database` VARCHAR(255) NOT NULL, 
			`description` VARCHAR(255) NOT NULL,
			`state` INT(1) NOT NULL default '0',
			`checked_out` INT(4) NOT NULL,
			`checked_out_time` DATETIME,
			`default` INT(1) NOT NULL DEFAULT '0',
			`attribs` TEXT NOT NULL,
			PRIMARY KEY ( `id` ));

CREATE TABLE IF NOT EXISTS `#__fabrik_tables` (
			`id` INT NOT NULL AUTO_INCREMENT,
			`label` VARCHAR (255)  NOT NULL ,
			`introduction` TEXT  NOT NULL ,
			`form_id` INT(4) NOT NULL ,
			`db_table_name` VARCHAR(255) NOT NULL,
			`db_primary_key` VARCHAR(255) NOT NULL,
			`auto_inc` INT(1) NOT NULL,
			`connection_id` int (6)  NOT NULL ,
			`created` DATETIME, 
			`created_by` INT(4) NOT NULL, 
			`created_by_alias` VARCHAR(255) NOT NULL, 
			`modified` DATETIME,
			`modified_by` INT(4) NOT NULL,
			`checked_out` INT(4) NOT NULL,
			`checked_out_time` DATETIME, 
			`state` INT(1) NOT NULL DEFAULT 0,
			`publish_up` DATETIME, 
			`publish_down` DATETIME, 
			`access` INT(4) NOT NULL, 
			`hits` INT(4) NOT NULL,
			`rows_per_page` INT(5) NOT NULL,
			`template` varchar (255) NOT NULL,
			`order_by` varchar (255) NOT NULL,
			`order_dir` varchar(6) NOT NULL default 'ASC',
			`filter_action` varchar(30) NOT NULL, 
			`group_by` VARCHAR(255) NOT NULL, 
			`private` TINYINT( 1 ) NOT NULL DEFAULT '0',
			`attribs` TEXT NOT NULL,
			PRIMARY KEY ( `id` ));

CREATE TABLE IF NOT EXISTS `#__fabrik_validations` (
			`id` INT NOT NULL AUTO_INCREMENT,
			`element_id` INT(4) NOT NULL ,
			`validation_plugin` VARCHAR (100)  NOT NULL ,
			 `message` varchar(255) null,
			`clent_side_validation` INT(1) NOT NULL default 0,
			`checked_out` INT(4) NOT NULL,
			`checked_out_time` DATETIME, 
			`attribs` TEXT NOT NULL,
			PRIMARY KEY ( `id` ));

CREATE TABLE IF NOT EXISTS `#__fabrik_forms` (
				`id` INT( 4 ) NOT NULL auto_increment,
				`label` VARCHAR( 255 ) NOT NULL ,
				`record_in_database` INT( 4 ) NOT NULL,
				`error` VARCHAR( 150 ) NOT NULL ,
				`intro` TEXT NOT NULL ,
				`created` datetime NOT NULL ,
				`created_by` INT( 11 ) NOT NULL ,
				`created_by_alias` VARCHAR( 100 ) NOT NULL ,
				`modified` datetime NOT NULL ,
				`modified_by` INT( 11 ) NOT NULL ,
				`checked_out` INT ( 11 ) NOT NULL,
				`checked_out_time` datetime NOT NULL ,
				`publish_up` DATETIME, 
				`publish_down` DATETIME, 				
				`reset_button_label` VARCHAR (100) NOT NULL,
				`submit_button_label` VARCHAR (100) NOT NULL,
				`form_template` varchar( 255), 
				`view_only_template` varchar(255),
				`state` INT(1) NOT NULL DEFAULT 0,
				`private` TINYINT( 1 ) NOT NULL DEFAULT '0',
				`attribs` TEXT NOT NULL,
				PRIMARY KEY ( `id` ));

CREATE TABLE IF NOT EXISTS `#__fabrik_elements` (
				`id` INT( 11 ) NOT NULL auto_increment,
				`name` VARCHAR( 100 ) NOT NULL ,
				`group_id` INT( 4 ) NOT NULL ,
				`plugin` VARCHAR(100) NOT NULL ,
				`label` TEXT ,
				`checked_out` int(11) NOT NULL ,
				`checked_out_time` datetime NOT NULL ,
				`created` datetime NOT NULL ,
				`created_by` INT( 11 ) NOT NULL ,
				`created_by_alias` varchar(100) NOT NULL ,
				`modified` datetime NOT NULL ,
				`modified_by` INT( 11 ) NOT NULL ,
				`width` INT( 4 ) NOT NULL ,
				`height` INT( 4 ) NOT NULL default '0',
				`default` TEXT NOT NULL ,
				`hidden` INT (1) NOT NULL ,
				`eval` INT (1) NOT NULL ,
				`ordering` int( 4 ) NOT NULL ,
				`show_in_table_summary` int(1), 
				`can_order` int(1), 
				`filter_type` VARCHAR (20),
				`filter_exact_match` int(1),
				`state` int(1) NOT NULL default '0',
				`button_javascript` text NOT NULL,
				`link_to_detail` int(1) NOT NULL default '0',
				`primary_key` int(1) NOT NULL default '0',
				`auto_increment` int(1) NOT NULL default '0',
				`access` int(1) NOT NULL default '0',
				`use_in_page_title` int(1) NOT NULL default '0',
				`sub_values` TEXT NOT NULL,
				`sub_labels` TEXT NOT NULL,
				`sub_intial_selection` TEXT NOT NULL,
				`parent_id` MEDIUMINT( 6 ) NOT NULL,
				`attribs` TEXT NOT NULL,
				PRIMARY KEY ( `id` ));

CREATE TABLE IF NOT EXISTS `#__fabrik_plugins` (
				`id` INT( 4 ) NOT NULL auto_increment,
				`name` VARCHAR( 100 ) NOT NULL ,
				`label` VARCHAR(255) NOT NULL,
				`type` 	VARCHAR(100) NOT NULL,
				`state` TINYINT(1),
				`iscore` TINYINT(1),
				`checked_out` VARCHAR(6) NOT NULL,
				`checked_out_time` 	DATETIME,
				`params` TEXT,
				PRIMARY KEY ( `id` ));


CREATE TABLE IF NOT EXISTS `#__fabrik_formgroup` (
				`id` INT ( 6 ) NOT NULL auto_increment,
				`form_id` INT( 4 ) NOT NULL ,
				`group_id` INT( 4 ) NOT NULL ,
				`ordering` INT( 4 ) NOT NULL,
				PRIMARY KEY ( `id` ));
		
CREATE TABLE IF NOT EXISTS `#__fabrik_groups` (
				`id` INT( 4 ) NOT NULL auto_increment,
				`name` VARCHAR( 100 ) NOT NULL ,
				`css` TEXT NOT NULL ,
				`label` VARCHAR( 100 ) NOT NULL ,
				`state` INT(1) NOT NULL default '0',
				`created` datetime NOT NULL ,
				`created_by` INT( 11 ) NOT NULL ,
				`created_by_alias` VARCHAR( 100 ) NOT NULL ,
				`modified` datetime NOT NULL ,
				`modified_by` INT( 11 ) NOT NULL ,
				`checked_out` INT( 11 ) NOT NULL ,
				`checked_out_time` datetime NOT NULL ,
				`is_join` INT(1) NOT NULL DEFAULT '0',
				`private` TINYINT( 1 ) NOT NULL DEFAULT '0',
				`attribs` TEXT NOT NULL,
			PRIMARY KEY ( `id` ));
					