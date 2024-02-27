<!-- load draganddrop script 
            <script type="text/javascript" src="libraries/jquery/jquery.min.js"></script>
<script type="text/javascript" src="resources/Connector.js"></script>
<script type="text/javascript" src="layouts/vlayout/modules/berliWidgets/resources/Detail.js"></script>
-->
{foreach key=index item=jsModel from=$SCRIPTS}
	<script type="{$jsModel->getType()}" src="{$jsModel->getSrc()}"></script>
{/foreach}


<form class="form-horizontal" id="EditView" name="EditView" method="post" action="index.php" enctype="multipart/form-data">
	<input type="hidden" id="recordid" value="{$RECORDID}" />
	<input type="hidden" id="modulename" value="{$MODULE}" />
	<div class="dropcontainer" style="position: center;">
		<!-- Drag and Drop container-->
		<div class="upload-area" style="display: flex; margin-left:5px; margin-right:5px;margin-top: 5px; margin-bottom: 5px; border: 2px dashed #ccc; height: 120px; text-align: center; justify-content: center; align-items: center; " id="uploadfile">
			<input type="file" name="file" id="file"  style="opacity: 0.0; position: absolute; top:0; left: 0; bottom: 0; right:0; width: 100%; height:100%;" />
			<p>{vtranslate('LBL_FILE_DRAGANDDROP')} </p>
		</div>
	</div>
</form>
