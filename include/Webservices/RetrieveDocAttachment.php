<?php
/*************************************************************************************************
 * Copyright 2012-2014 JPL TSolucio, S.L.  --  This file is a part of coreBOS.
* You can copy, adapt and distribute the work under the "Attribution-NonCommercial-ShareAlike"
* Vizsage Public License (the "License"). You may not use this file except in compliance with the
* License. Roughly speaking, non-commercial users may share and modify this code, but must give credit
* and share improvements. However, for proper details please read the full License, available at
* http://vizsage.com/license/Vizsage-License-BY-NC-SA.html and the handy reference for understanding
* the full license at http://vizsage.com/license/Vizsage-Deed-BY-NC-SA.html. Unless required by
* applicable law or agreed to in writing, any software distributed under the License is distributed
* on an  "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
* See the License for the specific language governing permissions and limitations under the
* License terms of Creative Commons Attribution-NonCommercial-ShareAlike 3.0 (the License).
* modified by crm-now
*************************************************************************************************/

function berli_retrievedocattachment($all_ids, $returnfile, $user) {
	global $log;
	$db = PearDatabase::getInstance();
	$entities=array();
	$docWSId=berli_getWSEntityId('Documents');
	$log->debug("Entering function vtws_retrievedocattachment");
        $all_ids="(".str_replace($docWSId,'',$all_ids).")";
        $query = "SELECT n.notesid, n.filename, n.filelocationtype
                  FROM vtiger_notes n
                  INNER JOIN vtiger_crmentity c ON c.crmid=n.notesid
                  WHERE n.notesid in $all_ids and n.filelocationtype in ('I','E') and c.deleted=0";
	$result = $db->pquery($query,array());
	$num_of_rows=$db->num_rows($result);
    for($i=0;$i<$num_of_rows;$i++){
        $id=$docWSId.$db->query_result($result,$i,'notesid');
        $webserviceObject = VtigerWebserviceObject::fromId($db,$id);
		$handlerPath = $webserviceObject->getHandlerPath();
		$handlerClass = $webserviceObject->getHandlerClass();
		
		require_once $handlerPath;

		$handler = new $handlerClass($webserviceObject,$user,$db,$log);
		$meta = $handler->getMeta();
		$entityName = $meta->getObjectEntityName($id);
		$types = vtws_listtypes(null, $user);
		if(!in_array($entityName,$types['types'])){
			throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Permission to perform the operation is denied");
		}
		if($meta->hasReadAccess()!==true){
			throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Permission to write is denied");
		}

		if($entityName !== $webserviceObject->getEntityName()){
			throw new WebServiceException(WebServiceErrorCode::$INVALIDID,"Id specified is incorrect");
		}
		
		if(!$meta->hasPermission(EntityMeta::$RETRIEVE,$id)){
			throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Permission to read given object ($id) is denied");
		}
		
		$ids = vtws_getIdComponents($id);
		if(!$meta->exists($ids[1])){
			throw new WebServiceException(WebServiceErrorCode::$RECORDNOTFOUND,"Document Record you are trying to access is not found");
		}

		$document_id = $ids[1];
		$filetype=$db->query_result($result,$i,'filelocationtype');
		if ($filetype=='E'){
			$entity["recordid"] = $db->query_result($result,$i,'notesid');
			$entity["filetype"] = $fileType;
			$entity["filename"] = $db->query_result($result,$i,'filename');
			$entity["filesize"] = 0;
			$entity["attachment"] = base64_encode('') ;
		} 
		elseif ($filetype=='I') {
			$entity = vtws_retrievedocattachment_get_attachment($document_id,true,$returnfile);
		}        
		$entities[$id]=$entity;
		VTWS_PreserveGlobal::flush();
    } 
	$log->debug("Leaving function vtws_retrievedocattachment");
	return $entities;
}


function vtws_retrievedocattachment_get_attachment($fileid,$nr=false,$returnfile=true,$base64encode = true) {
	global $log;
	$log->debug("Entering function vtws_retrievedocattachment_get_attachment($fileid)");
	$db = PearDatabase::getInstance();
	
	$recordpdf=array();
	$query = 'SELECT vtiger_attachments.attachmentsid,path,filename,filesize,filetype,name FROM vtiger_attachments
	INNER JOIN vtiger_seattachmentsrel ON vtiger_seattachmentsrel.attachmentsid = vtiger_attachments.attachmentsid
	INNER JOIN vtiger_notes ON vtiger_notes.notesid = vtiger_seattachmentsrel.crmid
	WHERE vtiger_notes.notesid = ?';
	$result = $db->pquery($query,array($fileid));
	if ($db->num_rows($result)==0 && $nr==false) {
		throw new WebServiceException(WebServiceErrorCode::$RECORDNOTFOUND,"Attachment Record you are trying to access is not found ($fileid)");
	}
	if($db->num_rows($result) == 1)	{
		$fileType = $db->query_result($result, 0, "filetype");
		$name = $db->query_result($result, 0, "name");
		$name = decode_html($name);
		$filepath = $db->query_result($result,0,'path');
		$attachid = $db->query_result($result,0,'attachmentsid');
		$saved_filename = $attachid."_".$name;

		$filesize = filesize($filepath.$saved_filename);
		if(!fopen($filepath.$saved_filename, "r")) {
			$log->debug('unable to open file');
			return array();
			throw new WebServiceException(WebServiceErrorCode::$ACCESSDENIED,"Unable to open file $saved_filename. Object is denied");
		}
		else {
			$fileContent = $returnfile ? fread(fopen($filepath.$saved_filename, "r"), $filesize) : '';
		}
		if($fileContent != '')	{
			$log->debug('Updating download count');
			$sql="update vtiger_notes set filedownloadcount=filedownloadcount+1 where notesid= ?";
			$res=$db->pquery($sql,array($fileid));
		}
		$recordpdf["recordid"] = $fileid;
		$recordpdf["filetype"] = $fileType;
		$recordpdf["filename"] = $name;
		$recordpdf["filesize"] = $filesize;
		if ($base64encode == true) {
			$recordpdf["attachment"] = base64_encode($fileContent);
		}
		else {
			$recordpdf["attachment"] = $fileContent;
		}
	}
	
	$log->debug("Leaving function vtws_retrievedocattachment_get_attachment($fileid)");
    return $recordpdf;
}

if (!function_exists('berli_getWSEntityId')) {
	function berli_getWSEntityId($entityName) {
		$db = PearDatabase::getInstance();
		$rs = $db->pquery("select id from vtiger_ws_entity where name=?",array($entityName));
		$wsid = $db->query_result($rs, 0, 'id').'x';
		return $wsid;
	}
}

?>