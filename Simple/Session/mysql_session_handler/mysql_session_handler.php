<?php
/**
 * MySQL Session Handler for PHP
 *
 * Copyright 2000-2003 Jon Parise <jon@php.net>.  All rights reserved.
 *
 * Ported to MySQL by Harry Fuecks <hfuecks@phppatterns.com>
 *
 *  Redistribution and use in source and binary forms, with or without
 *  modification, are permitted provided that the following conditions
 *  are met:
 *  1. Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *  2. Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in the
 *     documentation and/or other materials provided with the distribution.
 *
 *  THIS SOFTWARE IS PROVIDED BY THE AUTHOR AND CONTRIBUTORS ``AS IS'' AND
 *  ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 *  IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 *  ARE DISCLAIMED.  IN NO EVENT SHALL THE AUTHOR OR CONTRIBUTORS BE LIABLE
 *  FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 *  DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS
 *  OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 *  HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 *  LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
 *  OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF
 *  SUCH DAMAGE. 
 *
 *
 * Usage Notes
 * ~~~~~~~~~~~
 * - In php.ini, set session.save_handler to 'user'.
 * - In php.ini, set session.save_path to the name of the database table.
 * - Modify the $params string in mysql_session_open() to match your setup.
 * - Create the table structure using the follow schema:
 *   
 *        CREATE TABLE php_session (
 *          session_id varchar(40) NOT NULL default '',
 *          last_active int(11) NOT NULL default '0',
 *          data text NOT NULL,
 *          PRIMARY KEY  (session_id)
 *        )
 *
 * @author  Jon Parise <jon@php.net>, Harry Fuecks <hfuecks@phppatterns.com>
 * @version 1.0
 *
 * $Id: mysql_session_handler.php,v 1.1 2003/06/01 21:36:50 harry Exp $ 
 *
 */

/* Make sure PHP is configured for our custom session handler. */
assert(ini_get('session.save_handler') == 'user');

/* Get the name of the session table.  Default to 'php_sessions'. */
if ($mysql_session_table = ini_get('session.save_path')) {
    $mysql_session_table = 'php_sessions';
}

/* Global MySQL database connection handle. */
$mysql_session_handle = null;

/**
 * Opens a new session.
 *
 * @param   string  $save_path      The value of session.save_path.
 * @param   string  $session_name   The name of the session ('PHPSESSID').
 *
 * @return  boolean True on success, false on failure.
 */
function mysql_session_open($save_path, $session_name)
{
    global $mysql_session_handle;

    /* See: http://www.php.net/manual/function.mysql-connect.php */
    $host='localhost';
    $user='root';
    $pass='nuriotan';
    /* See: http://www.php.net/manual/function.mysql-select-db.php */
    $dbas='savagebrown';

    $mysql_session_handle = mysql_connect($host,$user,$pass);
    mysql_select_db($dbas,$mysql_session_handle);
    return $mysql_session_handle;
}

/**
 * Closes the current session.
 *
 * @return  boolean True on success, false on failure.
 */
function mysql_session_close()
{
    global $mysql_session_handle;

    if (isset($mysql_session_handle)) {
        return mysql_close($mysql_session_handle);
    }

    return true;
}

/**
 * Reads the requested session data from the database.
 *
 * @param   string  $key    Unique session ID of the requested entry.
 *
 * @return  string  The requested session data.  A failure condition will
 *                  result in an empty string being returned.
 */
function mysql_session_read($key)
{
    global $mysql_session_handle, $mysql_session_table;

    $key = mysql_escape_string($key);
    $now = time();

    /*
     * Attempt to retrieve a row of existing session data.
     *
     * We begin by starting a new transaction.  All of the session-related
     * operations with happen within this transcation.  The transaction will
     * be committed by either session_write() or session_destroy(), depending
     * on which is called.
     *
     * We mark this SELECT statement as FOR UPDATE because it is probable that
     * we will be updating this row later on in session_write(), and performing
     * an exclusive lock on this row for the lifetime of the transaction is
     * desirable.
     */
    $query = "select data from $mysql_session_table " .
             "where session_id = '$key';";
    $result = mysql_query($query,$mysql_session_handle);

    /*
     * If we were unable to retrieve an existing row of session data, insert a
     * new row.  This ensures that the UPDATE operation in session_write() will
     * succeed.
     */
    if (($result === false) || (mysql_num_rows($result) != 1)) {
        $query = "insert into $mysql_session_table " .
                 "(session_id, last_active, data) " .
                 "values('$key', $now, '');";

        $result = mysql_query($query, $mysql_session_handle);

        /* If the insertion succeeds, return an empty string of data. */
        if (($result !== false) && (@mysql_affected_rows($result) == 1)) {
            @mysql_free_result($result);
            return '';
        }

        /*
         * If the insertion fails, it may be due to a race condition that
         * exists between multiple instances of this session handler in the
         * case where a new session is created by multiple script instances
         * at the same time (as can occur when multiple session-aware frames
         * exist).
         *
         * In this case, we attempt another SELECT operation which will
         * hopefully retrieve the session data inserted by the competing
         * instance.
         */
        $query = "select data from $mysql_session_table " .
                 "where session_id = '$key';";
        $result = mysql_query($query,$mysql_session_handle);

        /* If this attempt also fails, give up and return an empty string. */
        if (($result === false) || (@mysql_num_rows($result) != 1)) {
            @mysql_free_result($result);
            return '';
        }
    }

    /* Extract and return the 'data' value from the successful result. */
    $data = mysql_result($result, 0, 'data');
    @mysql_free_result($result);

    return $data;
}

/**
 * Writes the provided session data with the requested key to the database.
 *
 * @param   string  $key        Unique session ID of the current entry.
 * @param   string  $val        String containing the session data.
 *
 * @return  boolean True on success, false on failure.
 */
function mysql_session_write($key, $val)
{
    global $mysql_session_handle, $mysql_session_table;

    $key = mysql_escape_string($key);
    $val = mysql_escape_string($val);
    $now = time();

    /* Built and execute the update query. */
    $query = "update $mysql_session_table set last_active=$now, data='$val' " .
             "where session_id='$key';";

    $result = mysql_query($query,$mysql_session_handle);

    $success = ($result !== false);
    @mysql_free_result($result);
    return $success;
}

/**
 * Destroys the requested session.
 *
 * @param   string  $key        Unique session ID of the requested entry.
 *
 * @return  boolean True on success, false on failure.
 */
function mysql_session_destroy($key)
{
    global $mysql_session_handle, $mysql_session_table;

    $key = mysql_escape_string($key);

    /* Built and execute the deletion query. */
    $query = "delete from $mysql_session_table where session_id = '$key';";
    $result = mysql_query($query,$mysql_session_handle);

    /* A successful deletion query will affect a single row. */
    $success = (($result !== false) && (@mysql_affected_rows($result) == 1));
    @mysql_free_result($result);

    return $success;
}

/**
 * Performs session garbage collection based on the provided lifetime.
 *
 * Sessions that have been inactive longer than $maxlifetime sessions will be
 * deleted.
 *
 * @param   int     $maxlifetime    Maximum lifetime of a session.
 *
 * @return  boolean True on success, false on failure.
 */
function mysql_session_gc($maxlifetime)
{
    global $mysql_session_handle, $mysql_session_table;

    $expiry = time() - $maxlifetime;
    $query = "delete from $mysql_session_table where last_active < $expiry;";

    return (mysql_query($query,$mysql_session_handle) !== false);
}

/* Register the session handling functions with PHP. */
session_set_save_handler(
    'mysql_session_open',
    'mysql_session_close',
    'mysql_session_read',
    'mysql_session_write',
    'mysql_session_destroy',
    'mysql_session_gc'
);

?>