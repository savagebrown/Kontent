<?php
/**
* @package SPLIB
* @version $Id: SessionAnalyzer.php,v 1.4 2003/07/23 18:57:31 harry Exp $
*/
/**
* Session Analyzer
* Examines serialized session data (as it appears on the filesystem
* or in a database) and builds objects into which it places the data
* stored in the session
* <code>
* $sa = new SessionAnalyzer();
* $sa->addSession($some_serialized_session_data);
* $sessionStore = $sa->fetch();
* </code>
* @package SPLIB
* @access public
*/
class simple_session_analyzer {
    /**
    * A list of sessions, their data held in SessionStore objects
    * @var array
    */
    var $sessions;

    /**
    * SessionAnalyzer constructor
    * @param object instance of database connection
    * @access public
    */
    function SessionAnalyzer () {
        $this->sessions = array();
    }

    /**
    * Gathers the sessions into a local array for analysis
    * @param string raw serialized session data to parse
    * @return void
    * @access public
    */
    function addSession($rawData) {
        $this->sessions[]=$this->parseSession($rawData);
    }

    /**
    * Iteraters over the SessionStore array
    * @return SessionStore
    * @access public
    */
    function fetch() {
        $session=each($this->sessions);
        if ( $session ) {
            return $session['value'];
        } else {
            reset($this->sessions);
            return false;
        }
    }

    /**
    * Converts serialized session data to an object
    * Thanks to comment from Stefan at;<br />
    * http://www.php.net/manual/en/function.session-decode.php
    * @param string session data
    * @return object stdClass
    * @access public
    */
    function parseSession ($rawData) {
        if ( empty ($rawData) )
            return null;
        // replace values within quotes since
        // there might be ; or {} within the quotes
        // these would mess up the slicing in the next step
        $replaceStr = array();
        $replaceStrCount = 0; //index for the saved replacement strings
        $replaceParts = explode('"',$rawData);
        for ($i = 1; $i < count($replaceParts); $i=$i+2) {
            $replaceStr[$replaceStrCount] = $replaceParts[$i];
            $replaceParts[$i] = "repl_" . $replaceStrCount;
            $replaceStrCount++;
        }
        $rawData = implode('"',$replaceParts);
        $vars = array();
        $varCount = 0;
        $flag = true;
        //slice the string using ; and {} as separators, but keep them
        while ( $flag ) {
            if (strpos($rawData,";") < strpos($rawData,"{")
                    || (strpos($rawData,";") !== false
                        && strpos($rawData,"{") === false )) {
                $vars[$varCount] = substr($rawData,0,strpos($rawData,";")+1);
                $rawData = substr($rawData,strpos($rawData,";")+1,strlen($rawData));
            } else if (strpos($rawData,";") > strpos($rawData,"{")
                            || (strpos($rawData,";") === false
                                && strpos($rawData,"{") !== false )) {
                $vars[$varCount] = substr($rawData,0,strpos($rawData,"}")+1);
                $rawData = substr($rawData,strpos($rawData,"}")+1,strlen($rawData));
            }
            if (strpos($rawData,";") === false && strpos($rawData,"{") === false) {
                $flag = false;
            } else {
                $varCount++;
            }
        }
        //replace the quote substitutes with the real values 
        for ($i = 0; $i < count($vars);$i++) {
            //break apart because there might be more
            // than one string to replace
            $varsParts = explode('"',$vars[$i]);
            for ($j = 1; $j < count($varsParts); $j++) {
                $k = count($replaceStr);
                while ($k > -1) {
                    $pat = "repl_" . $k;
                    if (strpos($varsParts[$j],$pat) !== false) {
                        $varsParts[$j] = str_replace("repl_".$k,
                                                     $replaceStr[$k],
                                                     $varsParts[$j]) ;
                        break;
                    } else {
                        $k--;
                    }
                }
            }
            //glue varsParts
            $vars[$i] = implode('"',$varsParts);
        }
        $session=new SessionStore();
        for ($i=0; $i < sizeof($vars); $i++) {
            $parts = explode("|", $vars[$i]);
            $key = $parts[0];
            $val = unserialize($parts[1]);
            $session->{$key} = $val;
        }
        return $session;
    }
}

/**
* SessionStore
* Container class in which to place unserialized session data
* @package SPLIB
* @access public
*/
class SessionStore {}
?>