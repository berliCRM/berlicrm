<?php 
/************************************************************************************
 * CleverReach API helper
 * by crm-now.de
 *************************************************************************************/
 
require_once ("modules/berliCleverReach/providers/rest_client.php");

class cleverreachAPI
{
	private $rest;
	
	// data fields to create on CleverReach. Array key is name, value type [text/number/gender/date]. MUST be lowercase
	public static $fields = array(	"fname"=>"text",
									"lname"=>"text",
									"salutation"=>"text",
									"company"=>"text",
									"letter_salutation"=>"text");
	
	/**
	 * Function to get an API token by login
	 */
	public static function getToken($client_id,$username,$password) {
		
		$rest = new CR\tools\rest("https://rest.cleverreach.com/v2");
		$token = $rest->post('/login', array("client_id"=>$client_id,"login"=>$username,"password"=>$password));
		if ($token) {
			$_SESSION['crtokentime']=time();
			$_SESSION['crtoken']=$token;
		}
		return $token;
	}
	
	/**
	 * Function to authorize API and return a rest object
	 */
	public function getrest() {
		
		if (!is_object($this->rest)) $this->rest = new CR\tools\rest("https://rest.cleverreach.com/v2");

		// used cached token if it isn't stale
		if (isset($_SESSION['crtoken']) && $_SESSION['crtokentime']+86400 > time()) {
			$token=$_SESSION['crtoken'];
		}
		else {
			
			$apicredentials= berliCleverReach_Module_Model::getApiCredentials();
			
			// refresh token from db
			$token = $this->rest->post('/login/refresh?token='.$apicredentials["accesstoken"],array());
			
			// store refreshed token
			berliCleverReach_Module_Model::updateToken($token);
			
			//cache in sessionvars
			$_SESSION['crtokentime']=time();
			$_SESSION['crtoken']=$token;	
		}
		$this->rest->setAuthMode("jwt", $token);
		
		return $this->rest;
	}

	/**
	 * Function to fetch all Receivers of a CleverReach group by ID
	 * returns array of objects indexed by email
	 */
	public function fetchCleverReachGroupByID($crgroupid) {
		
		$pagesize = 5000; 		# maximum per call 
		$page = 0;				# pages start at 0
		$clvrcontacts = array();
		
		do {
			$somereceivers = $this->rest->get("/groups/{$crgroupid}/receivers/",array("pagesize"=>$pagesize,"page"=>$page++));
		
			foreach ($somereceivers as $rec) {
				unset($rec->attributes,$rec->activated,$rec->registered,$rec->source);	// unused, save memory
                $rec->email = strtolower($rec->email);
				$clvrcontacts[$rec->email]=$rec;
			}
		
		} while (count($somereceivers)==$pagesize);
			
		return $clvrcontacts;
	}

    /**
    * Function to create global attributes on CleverReach
    */
    public function createCleverReachAttributes() {
        $clvrfields = (array) $this->rest->get("/attributes");

        foreach ($clvrfields as $clvrfield) {
            $tmpfields[$clvrfield->name]=$clvrfield->type;
        }

        $fieldsneeded = array_diff_assoc(cleverreachAPI::$fields,$tmpfields);

        // creating attributes for the first time can take MINUTES after an successful API request
        foreach ($fieldsneeded as $fieldname => $fieldtype)	{
            if (isset($tmpfields[$fieldname]) && $tmpfields[$fieldname] != $fieldtype) {
               throw new Exception('{"status":"error","message":"Cannot change type of attribute '.$fieldname.' to '.$fieldtype.'"}');
             }
            else {
                $newfield = array("name"=>$fieldname, "type"=>$fieldtype);
                $this->rest->post("/attributes", $newfield);
            }
        }
        return count($fieldsneeded);
    }
}

?>
