#
# Table structure for table 'tx_icsbookmarks'
# 
#
CREATE TABLE tx_icsbookmarks (
  cookie varchar(16) DEFAULT '' NOT NULL,
  type varchar(30) DEFAULT '' NOT NULL,
  data text,
  sorting int(11) DEFAULT '0' NOT NULL,
  tstamp int(11) DEFAULT '0' NOT NULL,
  INDEX `bookmark_group` ( `cookie` , `type` )
);