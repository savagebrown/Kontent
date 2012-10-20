CREATE TABLE php_sessions (
  session_id varchar(40) NOT NULL default '',
  last_active int(11) NOT NULL default '0',
  data text NOT NULL,
  PRIMARY KEY  (session_id)
)