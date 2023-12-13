CREATE TABLE tx_rsmbouncemailprocessor_domain_model_bouncereport
(
	newsletterid           int(11) NOT NULL DEFAULT '0',
	timeprocessed          int(11) NOT NULL DEFAULT '0',
	countmails             int(11) NOT NULL DEFAULT '0',
	countprocessed         int(11) NOT NULL DEFAULT '0',
	countunknownreason     int(11) NOT NULL DEFAULT '0',
	countnosenderfound     int(11) NOT NULL DEFAULT '0',
	countuserunknown       int(11) NOT NULL DEFAULT '0',
	countquotaexceeded     int(11) NOT NULL DEFAULT '0',
	countconnectionrefused int(11) NOT NULL DEFAULT '0',
	countheadererror       int(11) NOT NULL DEFAULT '0',
	countoutofoffice       int(11) NOT NULL DEFAULT '0',
	countfilterlist        int(11) NOT NULL DEFAULT '0',
	countmessagesize       int(11) NOT NULL DEFAULT '0',
	countpossiblespam      int(11) NOT NULL DEFAULT '0',
);

CREATE TABLE tx_rsmbouncemailprocessor_domain_model_recipientreport
(
	email                  varchar(255) DEFAULT '' NOT NULL,
	countunknownreason     int(11) NOT NULL DEFAULT '0',
	countnosenderfound     int(11) NOT NULL DEFAULT '0',
	countuserunknown       int(11) NOT NULL DEFAULT '0',
	countquotaexceeded     int(11) NOT NULL DEFAULT '0',
	countconnectionrefused int(11) NOT NULL DEFAULT '0',
	countheadererror       int(11) NOT NULL DEFAULT '0',
	countoutofoffice       int(11) NOT NULL DEFAULT '0',
	countfilterlist        int(11) NOT NULL DEFAULT '0',
	countmessagesize       int(11) NOT NULL DEFAULT '0',
	countpossiblespam      int(11) NOT NULL DEFAULT '0',
);

CREATE TABLE tx_rsmbouncemailprocessor_domain_model_deletelog
(
	email       varchar(255) DEFAULT '' NOT NULL,
	origpid     int(11) NOT NULL DEFAULT '0',
	reasontext  varchar(255) DEFAULT '' NOT NULL,
	reasonvalue int(11) NOT NULL DEFAULT '0',
	deletetime  int(11) NOT NULL DEFAULT '0',
);

CREATE TABLE tx_rsmbouncemailprocessor_domain_model_listunsubscribeheaderlog
(
	email       varchar(255) DEFAULT '' NOT NULL,
	origpid     int(11) NOT NULL DEFAULT '0',
	deletetime  int(11) NOT NULL DEFAULT '0',
);
