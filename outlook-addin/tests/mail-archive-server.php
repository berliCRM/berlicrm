<?php
// Isolated CRM/DB fixtures; the only real files written are inside this test's temp directory.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
$root = dirname(__DIR__, 2);
$fixture = sys_get_temp_dir() . '/berlicrm-mail-' . bin2hex(random_bytes(8));
$stubs = array(
    'include/Webservices/DescribeObject.php' => 'function vtws_describe($module,$user){ if(!$user->canRead)throw new WebServiceException("DENIED","Read denied"); return array("createable"=>$user->canCreate,"fields"=>FakeMail::$fields[$module]); }',
    'include/Webservices/Query.php' => 'function vtws_query($query,$user){ FakeMail::$queries[]=$query; preg_match("/limit ([0-9]+),100/",$query,$m); return array_slice(FakeMail::$matches,(int)$m[1],100); }',
    'include/Webservices/Retrieve.php' => 'function vtws_retrieve($id,$user){ if(isset(FakeMail::$denied[$id]))throw new WebServiceException("DENIED","Read denied"); if(!isset(FakeMail::$records[$id]))throw new WebServiceException("MISSING","Missing record"); return FakeMail::$records[$id]; }',
    'include/Webservices/Create.php' => 'function vtws_create($module,$values,$user){ if($module!=="Emails")throw new RuntimeException("Wrong module"); FakeMail::$creates++;if(FakeMail::$failCreate)throw new RuntimeException("Uncertain create");$id="10x".(100+FakeMail::$creates);FakeMail::$saved[]=$values;FakeMail::$records[$id]=$values+array("id"=>$id);global $adb;$adb->relations[$values["parent_id"].":".$id]=true;return array("id"=>$id); }',
    'include/fields/DateTimeField.php' => 'class DateTimeField{static function getDBTimeZone(){return "UTC";}}',
);
foreach($stubs as $file=>$source){if(!is_dir(dirname($fixture.'/'.$file)))mkdir(dirname($fixture.'/'.$file),0700,true);file_put_contents($fixture.'/'.$file,'<?php '.$source);}
mkdir($fixture.'/storage');
set_include_path($fixture.PATH_SEPARATOR.get_include_path());
class WebServiceErrorCode {public static $ACCESSDENIED='DENIED',$RECORDNOTFOUND='MISSING';}
class WebServiceException extends Exception {public $code;function __construct($code,$message){$this->code=$code;parent::__construct($message);}}
class VtigerWebserviceObject {
    private $name;function __construct($name){$this->name=$name;}static function fromName($db,$name){return new self($name);}
    function getEntityId(){return array('Contacts'=>4,'Accounts'=>12,'Emails'=>10,'Users'=>19)[$this->name];}
}
function vtws_getId($prefix,$id){return $prefix.'x'.$id;}
function isPermitted($module,$action,$id){return FakeMail::$denyEdit?'no':'yes';}
function decideFilePath(){return 'storage/';}
function sanitizeUploadFileName($name,$bad){return preg_match('/\.php$/i',$name)?$name.'.txt':$name;}
require $root.'/libraries/htmlpurifier/library/HTMLPurifier.auto.php';
function vtlib_purify($html){$config=HTMLPurifier_Config::createDefault();$config->set('Cache.DefinitionImpl',null);return (new HTMLPurifier($config))->purify($html);}
class FakeMail {
    static $fields=array(),$queries=array(),$matches=array(),$records=array(),$denied=array(),$saved=array(),$creates=0,$failCreate=false,$denyEdit=false;
}
class MailDb {
    public $links=array(),$files=array(),$relations=array(),$attachments=array(),$entities=array(),$fileRelations=array(),$releases=0,$locked=false,$fail='', $next=1000;
    function pquery($sql,$p){
        if($this->fail!==''&&strpos($sql,$this->fail)===0)return false;
        if(strpos($sql,'SELECT GET_LOCK')===0)return array(array('acquired'=>$this->locked?0:1));
        if(strpos($sql,'SELECT RELEASE_LOCK')===0){$this->releases++;return array();}
        if(strpos($sql,'SELECT emailid, state')===0){$key=$p[0].':'.$p[1];return isset($this->links[$key])?array($this->links[$key]):array();}
        if(strpos($sql,'INSERT INTO vtiger_outlook_mail_link')===0){$key=$p[0].':'.$p[1];if(isset($this->links[$key]))throw new RuntimeException('Duplicate link');$this->links[$key]=array('emailid'=>null,'state'=>$p[2]);return array();}
        if(strpos($sql,'UPDATE vtiger_outlook_mail_link')===0){$this->links[$p[2].':'.$p[3]]=array('emailid'=>$p[0],'state'=>$p[1]);return array();}
        if(strpos($sql,'INSERT INTO vtiger_seactivityrel')===0){$this->relations['4x'.$p[0].':10x'.$p[1]]=true;return array();}
        if(strpos($sql,'SELECT attachmentid, state')===0){$key=implode(':',$p);return isset($this->files[$key])?array($this->files[$key]):array();}
        if(strpos($sql,'INSERT INTO vtiger_outlook_mail_file')===0){$this->files[$p[0].':'.$p[1].':'.$p[2]]=array('attachmentid'=>null,'state'=>$p[3]);return array();}
        if(strpos($sql,'UPDATE vtiger_outlook_mail_file')===0){$this->files[$p[2].':'.$p[3].':'.$p[4]]=array('attachmentid'=>$p[0],'state'=>$p[1]);return array();}
        if(strpos($sql,'INSERT INTO vtiger_crmentity')===0){$this->entities[$p[0]]=$p;return array();}
        if(strpos($sql,'INSERT INTO vtiger_attachments')===0){$this->attachments[$p[0]]=$p;return array();}
        if(strpos($sql,'INSERT INTO vtiger_seattachmentsrel')===0){$this->fileRelations[$p[0].':'.$p[1]]=true;return array();}
        if(strpos($sql,'SELECT attachmentsid FROM vtiger_seattachmentsrel')===0)return isset($this->fileRelations[$p[0].':'.$p[1]])?array(array('attachmentsid'=>$p[1])):array();
        throw new RuntimeException('Unexpected SQL: '.$sql);
    }
    function num_rows($rows){return count($rows);}function query_result($rows,$i,$key){return $rows[$i][$key];}
    function fetchByAssoc($rows,$i,$decode){return $rows[0];}function getUniqueID($table){return ++$this->next;}function formatDate($date,$db){return $date;}
}
function check($value,$why){if(!$value)throw new RuntimeException($why);}
function rejects($work,$part){try{$work();}catch(Throwable $error){check(strpos($error->getMessage(),$part)!==false,'Unexpected error: '.$error->getMessage());return;}throw new RuntimeException('Expected failure: '.$part);}
try {
    require $root.'/include/Webservices/Custom/OutlookArchiveEmail.php';
    $adb=new MailDb();$root_directory=$fixture;$upload_badext=array('php');$user=(object)array('id'=>3,'canRead'=>true,'canCreate'=>true);
    FakeMail::$fields['Contacts']=array_map(function($name){return array('name'=>$name,'label'=>$name==='email'?'Primäre E-Mail &amp; Kontakt':$name);},array('id','firstname','lastname','email','account_id'));
    FakeMail::$fields['Emails']=array(array('name'=>'subject','label'=>'Betreff','mandatory'=>true,'fieldsize'=>255),array('name'=>'assigned_user_id','label'=>'Zuständig','mandatory'=>true),
        array('name'=>'ccmail','label'=>'CC','fieldsize'=>-1),array('name'=>'saved_toid','label'=>'An','fieldsize'=>'-1'),array('name'=>'description','label'=>'Text','fieldsize'=>-1));
    $from="o'neil@example.test";
    for($i=1;$i<=101;$i++)FakeMail::$matches[]=array('id'=>'4x'.$i,'email'=>$from,'firstname'=>'Ada','lastname'=>'Lovelace','account_id'=>$i===1?'12x1':'');
    FakeMail::$records['12x1']=array('accountname'=>'A &amp; B');FakeMail::$records['4x1']=array('email'=>$from);FakeMail::$records['4x2']=array('email'=>strtoupper($from));FakeMail::$records['4x3']=array('email'=>$from);
    $found=berli_outlookArchiveEmail(array('mode'=>'find','from'=>strtoupper($from)),$user);
    check($found['contactEmailLabel']==='Primäre E-Mail & Kontakt','Find returns current decoded CRM field label');
    check(berli_outlookArchiveEmail(array('mode'=>'check'),$user)['contactEmailLabel']==='Primäre E-Mail & Kontakt','Session check returns field label');
    FakeMail::$fields['Contacts'][3]['label']='Geschäftliche Adresse';
    check(berli_outlookArchiveEmail(array('mode'=>'check'),$user)['contactEmailLabel']==='Geschäftliche Adresse','Renamed label must not be cached across requests');
    FakeMail::$fields['Contacts'][3]['label']='Primäre E-Mail &amp; Kontakt';
    check(count($found['contacts'])===101&&count(FakeMail::$queries)===2,'All matching contacts must be paged');
    check($found['contacts'][0]['company']==='A & B'&&strpos(FakeMail::$queries[0],"o''neil")!==false,'Company label and escaped exact email');
    check(FakeMail::$creates===0&&!$adb->links,'Finding contacts must not write');
    FakeMail::$denied['12x1']=true;
    check(berli_outlookArchiveEmail(array('mode'=>'find','from'=>$from),$user)['contacts'][0]['company']==='','Unreadable company must not leak');unset(FakeMail::$denied['12x1']);
    $input=array('mode'=>'save','contactIds'=>array('4x1','4x2'),'mail'=>array('from'=>$from,'mailbox'=>'owner@example.test','itemId'=>'mail1','internetMessageId'=>'<mail1@example.test>',
        'subject'=>'Original subject','body'=>'<p>Text</p><script>alert(1)</script><img src="x" onerror="evil()">','to'=>array('owner@example.test'),'cc'=>array('cc@example.test'),'dateTimeCreated'=>'2026-11-02T10:00:00.000Z'));
    $result=berli_outlookArchiveEmail($input,$user);$email=$result['emailId'];
    check($result['created']&&FakeMail::$creates===1&&count($adb->relations)===2,'One Emails record with both contacts');
    $saved=FakeMail::$saved[0];
    check($saved['assigned_user_id']==='19x3'&&$saved['email_flag']==='SAVED'&&$saved['from_email']===$from,'Owner and saved flag, never send');
    check($saved['saved_toid']==='owner@example.test'&&$saved['ccmail']==='cc@example.test'&&$saved['date_start']==='2026-11-02'&&$saved['time_start']==='10:00:00','Mail headers/date must be stored');
    check(strpos($saved['description'],'<script>')===false&&strpos($saved['description'],'onerror')===false&&strpos($saved['description'],'Text')!==false,'Active mail HTML must be purified');
    check(!berli_outlookArchiveEmail($input,$user)['created']&&FakeMail::$creates===1&&count($adb->relations)===2,'Retry must not duplicate email or relations');
    $input['contactIds']=array('4x3');berli_outlookArchiveEmail($input,$user);
    check(FakeMail::$creates===1&&count($adb->relations)===3,'Additional selections link existing email');
    FakeMail::$denyEdit=true;rejects(function()use($input,$user){berli_outlookArchiveEmail($input,$user);},'Schreibberechtigung');FakeMail::$denyEdit=false;
    FakeMail::$denied['4x3']=true;rejects(function()use($input,$user){berli_outlookArchiveEmail($input,$user);},'Read denied');unset(FakeMail::$denied['4x3']);
    FakeMail::$records['4x3']['email']='different@example.test';rejects(function()use($input,$user){berli_outlookArchiveEmail($input,$user);},'Von-Adresse');FakeMail::$records['4x3']['email']=$from;
    $user->canCreate=false;rejects(function()use($input,$user){berli_outlookArchiveEmail($input,$user);},'Berechtigung');$user->canCreate=true;
    foreach(array(array('contactIds'=>array('19x3')),array('contactIds'=>array()),array('mail'=>array_merge($input['mail'],array('dateTimeCreated'=>'2026-02-30T10:00:00Z')))) as $change){
        $bad=array_merge($input,$change);rejects(function()use($bad,$user){berli_outlookArchiveEmail($bad,$user);},isset($change['mail'])?'Nachrichtendatum':(empty($change['contactIds'])?'mindestens':'Datensatz-ID'));
    }
    $input['mail']['attachments']=array(array('name'=>'../../test.php','type'=>'text/plain','content'=>base64_encode('File content')));
    berli_outlookArchiveEmail($input,$user);
    check(count($adb->attachments)===1&&file_get_contents($fixture.'/storage/1001_test.php.txt')==='File content','Attachment path safety, bad extension and content');
    berli_outlookArchiveEmail($input,$user);check(count($adb->attachments)===1,'Attachments must not duplicate on retry');
    $invalid=$input;$invalid['mail']['attachments'][0]['content']='not base64!!!';rejects(function()use($invalid,$user){berli_outlookArchiveEmail($invalid,$user);},'ungültig');
    $input['mail']['internetMessageId']='<uncertain@example.test>';FakeMail::$failCreate=true;
    rejects(function()use($input,$user){berli_outlookArchiveEmail($input,$user);},'Uncertain');FakeMail::$failCreate=false;$before=FakeMail::$creates;
    rejects(function()use($input,$user){berli_outlookArchiveEmail($input,$user);},'nicht eindeutig');check(FakeMail::$creates===$before,'Uncertain email create must not retry blindly');
    $input['mail']['internetMessageId']='<relation-failure@example.test>';$adb->fail='INSERT INTO vtiger_seactivityrel';
    rejects(function()use($input,$user){berli_outlookArchiveEmail($input,$user);},'Verknüpfung');$adb->fail='';$before=FakeMail::$creates;
    berli_outlookArchiveEmail($input,$user);check(FakeMail::$creates===$before,'Relation failure must resume against same email');
    $input['mail']['internetMessageId']='<file-failure@example.test>';$adb->fail='INSERT INTO vtiger_attachments';
    rejects(function()use($input,$user){berli_outlookArchiveEmail($input,$user);},'Verknüpfung');$adb->fail='';$before=FakeMail::$creates;
    rejects(function()use($input,$user){berli_outlookArchiveEmail($input,$user);},'Anhangablage');check(FakeMail::$creates===$before,'Uncertain attachment write must not duplicate email');
    $regression=$input;$regression['mail']['attachments']=array();
    foreach(array(array(),array('cc@example.test','second@example.test'))as$index=>$cc){
        $regression['mail']['internetMessageId']='<text-length-'.$index.'@example.test>';$regression['mail']['cc']=$cc;
        berli_outlookArchiveEmail($regression,$user);
        check(end(FakeMail::$saved)['ccmail']===implode(',',$cc),'Negative TEXT fieldsize must allow empty and multiple CC addresses without truncation');
    }
    FakeMail::$fields['Emails'][2]['fieldsize']=8;FakeMail::$fields['Emails'][2]['label']='Kopie &amp; Auswahl';
    $regression['mail']['internetMessageId']='<positive-length@example.test>';$before=FakeMail::$creates;$linksBefore=count($adb->links);
    rejects(function()use($regression,$user){berli_outlookArchiveEmail($regression,$user);},'Kopie & Auswahl');
    check(FakeMail::$creates===$before&&count($adb->links)===$linksBefore,'Actual positive field limits still fail before a write or pending reservation');
    FakeMail::$fields['Emails'][2]['fieldsize']=-1;
    $adb->locked=true;rejects(function()use($input,$user){berli_outlookArchiveEmail($input,$user);},'bereits');$adb->locked=false;
    check($adb->releases>=8,'Locks must release on success and error');
    echo "Mail archive server checks passed: exact paged lookup, dynamic labels, TEXT/positive field lengths, company rights, create/link, HTML, dates, attachments, permissions and safe retries.\n";
} finally {
    foreach(array_keys($stubs)as$file)unlink($fixture.'/'.$file);
    foreach(glob($fixture.'/storage/*')as$file)unlink($file);
    rmdir($fixture.'/include/Webservices');rmdir($fixture.'/include/fields');rmdir($fixture.'/include');rmdir($fixture.'/storage');rmdir($fixture);
}
