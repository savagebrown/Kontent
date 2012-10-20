<?php

/**
* @author Eric Schabell <erics@cs.ru.nl>
* @copyright Copyright 200i5, GPL.
* @package PMS
*/

/**
* Include once - the Manager class.
*/
include_once( 'Manager.php' );

	
/**
* LogManager class - manages the sending of log messages to the system
* logger. This class should be included in all other classes.
* @access public
*
* @package PMS
* @subpackage Manager
*/
class LogManager extends Manager 
{
	/**
	* Constructor - initialize the LogManager.
	* @access public
	* 
	* @param string Type of manager is Log Manager.
	* @return LogManager LogManager object.
	*/
	public function __construct( $manager="Log Manager" ) 
	{
		parent::__construct( $manager );
	}
	
	/**
	 * Custom logline
	 */
	public function adminLogger($file, $user, $mssg)
	{
		$logline  = date('Y-m-d H:i:s').'::';
		$logline .= $user.'::';
		$logline .= $mssg.'::';
		$logline .= $_SERVER['HTTP_USER_AGENT'];
		
		$this->fileLogger($file, $logline);
	}
	
	// TODO: Add different returns
	/**
	 * 
	 */
	public function getLog($file, $type='array')
	{
		$handle = file($file);
		
		if (is_array($handle)) {
			foreach ($handle as $v) {
				$logline_info[] = explode("::", $v);
			}
			return $logline_info;
		} else {
			return false;
		}
		
		
	}

	/**
	* fileLogger - logs a message into given file.
	* @access public
	*
	* @param string The log file name.
	* @param string Message to be logged.
	* @returns boolean True if message logged, otherwise false.
	*/
	static public function fileLogger( $filename, $msg )
	{
		// open file, set pointer to end, if missing create.
		$handle = fopen( $filename, "a");

		if ( !( fwrite( $handle, $msg) ) )
		{
			// something went wrong with writing.
			self::syslogger( "ALERT", "Failed to log to file $filename" );
			return FALSE;
		}
		else
		{
			if ( !( fwrite ( $handle, "\n" ) ) )
			{
				// unable to add newline, just warn.
				self::syslogger( "ALERT", "Failed to add a newline to logfile $filename" );
			}
		}

		// close file.
		fclose( $handle );

		// logging successful!
		return TRUE;
	}


	/**
	* htmlMsg - reports to the user in html form. Provides a keyword/detailed
	* message and return button to go back.
	* @access public
	*
	* @param string Name of message keyword.
	* @param string Details of message.
	* @returns void
	*/
	public function htmlMsg( $keyword, $details )
	{
		print "<center>\n\n";
		print "<p>\n";
		print "<h1>Failure report: </h1>\n";
		print "<h1>'" . $keyword . "' " . "</h1>\n";
		print "<h2>'" . $details . "' " . "</h2>\n";
		print "<br>";
		print "Please go back and try again.\n";
		print "</p>\n";
		print "<hr>\n";
		print "<form name='buttonbar'>\n";
		print "<input type='button' STYLE='color: red;background: green' value='Back' onClick='history.back()'>\n";
		print "</input>\n";
		print "</form>\n";
		print "</center>\n\n";
		return;
	}


	/**
	* syslogger - this method will send the given message to the
	* system logging facility.
	* @access public
	*
	* @param string Logging severity level (LOW, INFO, HIGH, ALERT)
	* @param string The actual message to be logged.
	* @return bool True if message logged, otherwise false.
	**/
	public function syslogger($level, $message)
	{
		// picks up the syslog variables.
		define_syslog_variables();

		// open the logging.
		openlog( "PMS-Log", LOG_PERROR, LOG_USER );

		// send log messages, comments here from AMI TSD document.
		switch ( $level )
		{
			case "LOW" :

				$message = "LOW - " . $message;

				if ( ! syslog( LOG_NOTICE, $message ) )
				{
					print "DEBUG: Unable to reach log system...\n\n";
					return FALSE;
				}
				break;

			case "INFO" :

				$message = "INFO - " . $message;

				if ( ! syslog( LOG_INFO, $message ) )
				{
					print "DEBUG: Unable to reach log system...\n\n";
					return FALSE;
				}
				break;

			case "HIGH" :
				
				$message = "HIGH - " . $message;

				if ( ! syslog( LOG_CRIT, $message ) )
				{
					print "DEBUG: Unable to reach log system...\n\n";
					return FALSE;
				}
				break;

			case "ALERT" :

				$message = "ALERT - " . $message;

				if ( ! syslog( LOG_ALERT, $message ) )
				{
					print "DEBUG: Unable to reach log system...\n\n";
					return FALSE;
				}
				break;

			default :

				print "DEBUG: Unknown severity level: $level\n\n";
				return FALSE;
		}

		// close the logging.
		closelog();
		return TRUE;
	}

}

?>
