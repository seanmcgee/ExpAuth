<?php
/**
 * @version    $Id: expauth.php 7180 2012-08-15 16:51:53Z  $
 * @license    GNU/GPL
 */
 
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();
 
jimport('joomla.event.plugin');
 
/* CHANGE THESE PARAMETERS TO CUSTOMIZE YOUR INSTALLATION */
$expauth_grpid = 61; // This variable indicates which User Group ID expires
$expauth_expdays = 180; // This variable sets the expiration period
$expauth_mail = "sean@webdude.us"; // This variable sets the e-mail address notifications are to be sent to
$expauth_sitename = "Joomla!"; // This variable sets the name of the Joomla installation
$expauth_errorurl = "http://webdude.us"; //This variable sets the URL the expired user redirects to
 
/**
 * 
 *
 * 
 */
class plgAuthenticationMyauth extends JPlugin
{
    /**
     * Constructor
     *
     * For php4 compatability we must not use the __constructor as a constructor for plugins
     * because func_get_args ( void ) returns a copy of all passed arguments NOT references.
     * This causes problems with cross-referencing necessary for the observer design pattern.
     *
     * @param object $subject The object to observe
     * @since 1.5
     */
    function plgAuthenticationMyauth(& $subject) {
        parent::__construct($subject);
    }
 
    /**
     * This method should handle any authentication and report back to the subject
     * This example uses simple authentication - it checks if the password is the reverse
     * of the username (and the user exists in the database).
     *
     * @access    public
     * @param     array     $credentials    Array holding the user credentials ('username' and 'password')
     * @param     array     $options        Array of extra options
     * @param     object    $response       Authentication response object
     * @return    boolean
     * @since 1.5
     */
    function onUserAuthenticate( $credentials, $options, &$response )
    {
        /*
         * 
         * The mixed variable $return would be set to false
         * if the authentication routine fails or an integer userid of the authenticated
         * user if the routine passes
         */
        $db =& JFactory::getDBO();
        $query = 'SELECT `id`'
            . ' FROM #__users'
            . ' WHERE username=' . $db->quote( $credentials['username'] );
        $db->setQuery( $query );
        $result = $db->loadResult();
 
        // if the query does not return a result, then the user does not exist.
        if (!$result) {
            $response->status = JAUTHENTICATE_STATUS_FAILURE;
            $response->error_message = 'User does not exist';
        }
 
        // if there is a result, then this routine will get the user's registration date and 
        // then, check to see if the user belongs to an expiring group.
        if($result)
        {
        	//get user's id and store it in variable $userid
			$userid = $result;
			
			//get user's register date for later use and store it in variable $regdate
			$query = 'SELECT `registerDate`'
            . ' FROM #__users'
            . ' WHERE username=' . $db->quote( $credentials['username'] );
            $db->setQuery( $query );
            $result = $db->loadResult();
            $regdate = $result;
            
            //get user's different group id's and store them in the array $group_id
            $query = 'SELECT `group_id`'
            . ' FROM #__user_usergroup_map'
            . ' WHERE user_id=' . $userid;
            $db->setQuery( $query );
            $result = (array) $db->loadColumn();
            $group_id = $result;
            
            //check each of user's group id's
            for ($i = 0; $i <= count($group_id); $i++) {
            	//if the $group_id is the expiring group ($expauth_grpid), then execute the following code.
            	if ($group_id[$i] == $expauth_grpid)
            		{ 
            			//calculate the number of days a user account has been in existance from registration date
	            		$numdays = round(abs(strtotime($regdate)-strtotime(date("m.d.y")))/86400);
	            		
	            		//check if user account is older than Expiration period ($expauth_expdays) [see line 14]
	            		if ($numdays > $expauth_expdays)
	            			{
	            				//if user's account is expired execute this code
	            				//calculate and set the day age of the account
	            				$days = $numdays - 180;
	            				//set the message to be sent to notify of expired user accessing account
	            				$message = "Expired user trying to access " . $expauth_sitename . ":" . "\r" . "[USERNAME]: " . $credentials['username'] . " just tried to log in and is expired. Their credentials expired " . $days . " days ago.";
	            				$headers = "From: " . $expauth_mail . "\r\n" . "Reply-To: " . $expauth_mail . "\r\n" . "X-Mailer: PHP/" . phpversion();
	            				//send the message
	            				mail($expauth_mail, 'Expired User', $message, $headers);
	            				//send the expired user to an error page
	            				$response->status = JAUTHENTICATE_STATUS_FAILURE;
	            				$response->error_message = 'Your user credentials have expired!';
	            				die();
	            			} else { 
	            				//if user's account is not expired, execute this code
	            				//if you would like a special routine to execute if the user is not expired, put it here.
	            			}
            		}
            }
            $email = JUser::getInstance($userid); // Bring this in line with the rest of the system
            $response->email = $email->email;
            $response->status = JAUTHENTICATE_STATUS_SUCCESS;

        }
        else
        {
            $response->status = JAUTHENTICATE_STATUS_FAILURE;
            $response->error_message = 'Invalid username and password';
        }
    }
}
?>