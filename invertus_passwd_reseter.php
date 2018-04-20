<?php

/**
 * Make sure this file is placed in your Prestashop's root directory.
 * You can access it www.yourwebsite.tld/invertus_passwd_reseter.php
 * IF your Prestashop installation is in a subdirectory place this file in it and access it like this: www.yourwebsite.tld/subdirectory/invertus_passwd_reseter.php
 *
 * IMPORTANT!!!
 * 1. PLEASE REMOVE this file from your server immediately after you finished resetting passwords.
 * 2. Choose secure passwords (alphanumerical like: a12bhgds) - it will improve your websites security.
 *
 *
 * @author JSC "INVERTUS" | www.prestashopdocumentation.info | comments and suggestions: donatas@invertus.lt |
 * @Licence You are free to make changes and redistribute this code
 */


    /* Include Prestashop configs */
    include("config/settings.inc.php");

    /* SET DB configs */
    $db_host         = _DB_SERVER_;
    $db_name         = _DB_NAME_;
    $db_user         = _DB_USER_;
    $db_password     = _DB_PASSWD_;
    $db_table_prefix = _DB_PREFIX_;

    /* SET users table configs */
    $db_users_table                = "employee";
    $db_users_table_email_field    = "email";
    $db_users_table_password_field = "passwd";

    /* SET cookie key a.k.a SALT */
    $cookie_key  =  _COOKIE_KEY_;

    /* SET new password. You can change it if you want */
    $new_password = 'aaa333aaa';

    /* Create password resetter class */
    $passwordResetter = new prestashopPasswordResetter(  $db_host,  $db_name
                                                      , $db_user,  $db_password
                                                      , $db_table_prefix, $db_users_table
                                                      , $db_users_table_email_field, $db_users_table_password_field);

    /* SET needed variables to password resetter class */
    $passwordResetter->setCookieKey ($cookie_key);
    $passwordResetter->setPassword ($new_password);

    /*IF data sent*/
    if($_POST)
    {
        /* IF NO emails selected and  not password recovery */
        if((!isset($_POST['emails']) || empty($_POST['emails'])) && !isset($_POST['upload_passwd']))
        {
            /*Report error*/
            $passwordResetter->setErrorMessage("No emails selected! Please select email!");
        }
		/* IF password backup requested */
        elseif(isset($_POST['backup']))
		{
			$arr_emails  = $_POST['emails'];
            $result = $passwordResetter->backupPasswordToFile($arr_emails);
			
			/* Triger selected users passwords download */
			$file_name = date('Y_m_H_i_s') . '_' . $db_name;
			header('Content-Disposition: attachment; filename="' .$file_name . '.txt"');
			print $result;
			exit;
			
		}
		/* IF password file uploaded */
		elseif(isset($_POST['upload_passwd']))
		{
			$tmp_file_name = $_FILES['uploadedfile']['tmp_name'];
			$fh = fopen($tmp_file_name, 'r');
			$file_data = fread($fh, filesize($tmp_file_name));
			fclose($fh);
			$passwordResetter->recoverPasswordsFromFile($file_data);			
		}
		/* IF reseting passwords */
		elseif(isset($_POST['reset']))
        {   /* RESET passwords */
            $arr_emails  = $_POST['emails'];
            $passwordResetter->updatePasswordToDb($arr_emails);
        }
    }

?>

<html>
    <head>
        <title> Prestashop Password Resetter script v. 1.0</title>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    </head>
    <body style="background-color: #EEEEEE; margin: 0; padding: 0; font-family: Verdana; font-size: 13px; text-align: center;">

        <!-- centered content div -->
        <div style="position: relative; margin: 200px auto; width: 680px; text-align: left;">

            <h1 style="color:#BC2165; font-size: 24px;">Prestashop Password Resetter script v. 1.1</h1>
            <div style="width: 100%; border-bottom: 1px solid #dddddd;"></div>
            
            <div style="background-color: #fff; padding: 10px 0; width: 100%; margin-top: 5px;">
                &nbsp;<span style="color:red;">IMPORTANT: Please remove this file from your server after you changed password!</span><br></ br><br></ br>
                &nbsp;The new password will be: <span style="color:#BC2165;"> <?php echo $passwordResetter->getNewPassword(); ?></span>
            </div>

            <form action="<?php $_SERVER['REQUEST_URI']; ?>" method="post">

                <p>
                    <b>
                        Choose users:
                    </b>
                </p>

                <!-- PASSWORDS checkbox list -->
                <?php echo $passwordResetter->getUsersEmailsHTML(); ?>

                <input type="hidden" name="submited" value="yes" />
                <p>
                    <input type="submit" value="Reset" name="reset"/>
				</p>
				<p>
					<input type="submit" value="Download old passwords" name="backup" />
                </p>

            </form>


            <!-- SYSTEM MESSAGES -->
            <?php
               echo $passwordResetter->getErrorMessagesHTML();
               echo $passwordResetter->getMessagesHTML();
            ?>
			<div style="width: 100%; border-bottom: 1px solid #dddddd;"></div>
			<p>
                    <b>
                        Choose file to recover old user passwords:</b> (you do not need to selecet emails)						
                    
                </p>
			<p>
				<form enctype="multipart/form-data" action="<?php $_SERVER['REQUEST_URI']; ?>" method="POST">
					<input type="hidden" name="upload_passwd" value="yes" />
					Choose a file to upload: <input name="uploadedfile" type="file" /><br />
					<input type="submit" value="Upload File" />
				</form>
			</p>
			
			<!-- SYSTEM RECOVERY MESSAGES -->
            <?php
               echo $passwordResetter->getRecoveryErrorMessagesHTML();
               echo $passwordResetter->getRecoveryMessagesHTML();
            ?>
			
            <div style="width: 100%; border-bottom: 1px solid #dddddd;"></div>
            <p>Provided by:<a href="http://www.prestashopdocumentation.info" target="_blank">www.prestashopdocumentation.info</a> | questions: <b>donatas@invertus.lt</b></p>
        </div>
    </body>
</html>


<?php
/**
 * Password resetting class
 *
 * @author JSC "INVERTUS" | www.prestashopdocumentation.info | donatas@invertus.lt
 * @Licence You are free to make changes and redistribute this code
 *
 */
class prestashopPasswordResetter{

    /* Database variables */
    private $db_host;
    private $db_name;
    private $db_user;
    private $db_password;
    private $db_link;
    private $db_table_prefix;
    private $db_users_table;
    private $db_users_table_email_field;
    private $db_users_table_password_field;
	private $email_password_delimeter = '|';
	private $email_password_pair_delimeter = '|***|';

    /* Errors and success messages*/
    private $msg = Array();

    /* New password variable*/
    private $new_password;

    /* $cookie_key works as salt for password encryption with md5 function*/
    private $cookie_key;


    /**
     * @param  $db_host
     * @param  $db_name
     * @param  $db_user
     * @param  $db_password
     * @param  $db_table_prefix
     * @param  $db_users_table
     * @param  $db_users_table_email_field
     * @param  $db_users_table_password_field
     */
    public function __construct($db_host, $db_name
                              , $db_user, $db_password
                              , $db_table_prefix, $db_users_table
                              , $db_users_table_email_field, $db_users_table_password_field)
    {
        $this->db_host = $db_host;
        $this->db_name = $db_name;
        $this->db_user = $db_user;
        $this->db_password     = $db_password;
        $this->db_table_prefix = $db_table_prefix;
        /* Table name is used with prefix everywhere */
        $this->db_users_table  = $db_table_prefix . $db_users_table;
        $this->db_users_table_email_field    = $db_users_table_email_field;
        $this->db_users_table_password_field = $db_users_table_password_field;

        $this->db_link = mysql_connect ($this->db_host
                                       ,$this->db_user
                                       ,$this->db_password);
        if(!$this->db_link)
        {
            $this->msg['errors'][] = 'Could not connect to database!';
            return false;
        }

        $db = mysql_select_db ($this->db_name, $this->db_link) or die('Users table does not exist: ' . mysql_error());
        if(!$db)
        {
            $this->msg['errors'][ ]= 'Could not select database: ' . $this->db_name;
            return false;
        }
        return true;
    }


    /**
     * Set new password variable
     *
     * @param  $new_password
     * @return bool
     */
    public function setPassword($new_password)
    {
        $this->new_password = $new_password;
        return true;
    }


    /**
     * Set cookie key variable
     * @param  $cookie_key
     * @return bool
     */
    public function setCookieKey($cookie_key)
    {
        $this->cookie_key = $cookie_key;
        return true;
    }

    /**
     * Sets success messages
     * @param  $msg
     * @return bool
     */
    public function setMessage($msg)
    {
        $this->msg['messages'][] = $msg;
        return true;
    }

    /**
     * Sets error messages
     * @param  $error_msg
     * @return bool
     */
    public function setErrorMessage($error_msg)
    {
        $this->msg['errors'][] = $error_msg;
        return true;
    }

    /**
     * Get success messages
     * @return string
     */
    public function getMessagesHTML()
    {
        $result = "";
        if(isset($this->msg['messages']) && !empty($this->msg['messages']))
        {
            $result .= "<p style=\"color:green;\"><b>Success:</b></p>";
            foreach($this->msg['messages'] as $message)
            {
                $result .= "<p style='color:green;'> $message </p>";
            }
        }
        return $result;

    }

    /**
     * Get success messages
     * @return string
     */
    public function getErrorMessagesHTML()
    {
        $result = "";
        if(isset($this->msg['errors']) && !empty($this->msg['errors']))
        {
            $result .= "<p style=\"color:red;\"><b>Errors:</b></p>";
            foreach($this->msg['errors'] as $error)
            {
                $result .= "<p style='color:red;'> $error </p>";
            }
        }
        return $result;

    }
	
	    /**
     * Get success messages
     * @return string
     */
    public function getRecoveryMessagesHTML()
    {
        $result = "";
        if(isset($this->msg['messages_rec']) && !empty($this->msg['messages_rec']))
        {
            $result .= "<p style=\"color:green;\"><b>Success:</b></p>";
            foreach($this->msg['messages_rec'] as $message)
            {
                $result .= "<p style='color:green;'> $message </p>";
            }
        }
        return $result;

    }

    /**
     * Get success messages
     * @return string
     */
    public function getRecoveryErrorMessagesHTML()
    {
        $result = "";
        if(isset($this->msg['errors_rec']) && !empty($this->msg['errors_rec']))
        {
            $result .= "<p style=\"color:red;\"><b>Errors:</b></p>";
            foreach($this->msg['errors_rec'] as $error)
            {
                $result .= "<p style='color:red;'> $error </p>";
            }
        }
        return $result;

    }


    /**
     * Returns new password in plain text
     * @return
     */
    public function getNewPassword()
    {
        return $this->new_password;
    }
    /**
     * Encrypt new password md5('salt.$new_password') way
     *
     * @param  $arr_emails
     * @return bool
     */
    public function updatePasswordToDb($arr_emails)
    {
        $md5_password = md5($this->cookie_key . $this->new_password);
        foreach($arr_emails as $email)
        {
            $sql =    "UPDATE " . $this->db_users_table
                    . " SET " . $this->db_users_table_password_field . "=" . "'$md5_password'"
                    . " WHERE email=" . "'$email'";

            if (!mysql_query($sql))
            {
                $this->msg['errors'][] = "Password could not be updated for user: " . $email;
            }
            else
            {
                $this->msg['messages'][] = 'Password updated for user: <b style="color:black;">' . $email . '</b>' . ' New password: <span style="color:#BC2165;">'. $this->new_password .'</span>';
            }
        }
        return true;
    }

	public function backupPasswordToFile($arr_emails)
	{
		$result = '';
		
        foreach($arr_emails as $email)
        {
            $sql =    "SELECT " . $this->db_users_table_password_field . ', ' . $this->db_users_table_email_field
                    . " FROM " . $this->db_users_table
                    . " WHERE email=" . "'$email'";

            if (!$dbresult=mysql_query($sql))
            {
                $this->msg['errors'][] = "Password could not be selected for user: " . $email;
            }
            else
            {
				$dbresult = mysql_fetch_assoc($dbresult);
				$result .= $dbresult['email'] . $this->email_password_delimeter . $dbresult['passwd'] .$this->email_password_pair_delimeter;
                //$this->msg['messages'][] = 'Password backuped successfully: <b style="color:black;">' . $email . '</b>';
            }
        }
        return $result;
	}
	
	public function recoverPasswordsFromFile($file_data)
	{
		if(!empty($file_data))
		{
			$pairs_arr = explode($this->email_password_pair_delimeter, $file_data);
		}
		foreach($pairs_arr as $item)		
		{
			if(!empty($item))
			{
				$pair = explode($this->email_password_delimeter, $item);
				$email = $pair[0];
				$password = $pair[1];
				
				$sql =    "UPDATE " . $this->db_users_table
						. " SET " . $this->db_users_table_password_field . "=" . "'$password'"
						. " WHERE email=" . "'$email'";
						
				if (!mysql_query($sql))
				{
					$this->msg['errors_rec'][] = "Password could not be recovered for user: <br>" . $email . '</br>';
				}
				else
				{
					$this->msg['messages_rec'][] = 'Password recovered for user: <b style="color:black;">' . $email . '</b>' . '<br> Password: <span style="color:#BC2165;">'. $password .'</span></br>';
				}
			}
		}
		//print_r($pair_arr );

		return true;
	}
	
    /**
     * Form a emails check box fields
     * @return string
     */
    public function getUsersEmailsHTML()
    {
        $result     = '';
        $arr_emails = $this->_getUsersEmails();

        if(!$arr_emails || empty($arr_emails))
        {
            $this->msg['errors'][] = "No emails found";
        }
        else
        {
            foreach($arr_emails as $email)
            {
                $result .= "<p><label for=\"emails\">". $email ."</label>";
                $result .= "<input type=checkbox name=emails[] value='" . $email . "'></p>";
            }
        }
        return $result;
    }



    /**
     * Gets users emails from database
     *
     * @return array|bool
     */
    private function _getUsersEmails()
    {
        $arr_emails = Array();

        $sql = "SELECT " . $this->db_users_table_email_field . ' , ' . $this->db_users_table_password_field. " FROM " . $this->db_users_table;
        $sql_data = mysql_query($sql);

        if (!$sql_data)
        {
            $this->msg['errors'][] = "It was impossible to fetch users emails from database";
            return false;
        }

        while ($row = mysql_fetch_array($sql_data))
        {
            $arr_emails[] = $row[$this->db_users_table_email_field];
        }
        return $arr_emails;
    }
	
	

}

?>