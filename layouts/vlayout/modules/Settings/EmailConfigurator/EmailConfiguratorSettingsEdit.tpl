<div class="container-fluid">

<div class="widget_header row-fluid">
{if empty($EMAILADDRESS.emailid)}
    <h3>neue E-Mail Adresse anlegen</h3>
{else}
    <h3>E-Mail Adresse bearbeiten</h3>
{/if}
</div>
<HR>
<div class="contents row-fluid">

<form action="index.php?module=EmailConfigurator&action=Save&parent=Settings" method="POST">


<input type="hidden" name="emailid" value="{$EMAILADDRESS.emailid}">

<table class="table table-bordered table-condensed themeTableColor"
<tr><td>Vorname<td><input type="text" name="email_firstname" value="{$EMAILADDRESS.email_firstname}"><br>
<tr><td>Nachname<td><input type="text" name="email_lastname" value="{$EMAILADDRESS.email_lastname}"><br>
<tr><td>E-Mail Adresse<td><input type="text" name="email_address" value="{$EMAILADDRESS.email_address}" class="span5" required><br>
<tr><td>Beschreibung<td><input type="text" name="email_desc" value="{$EMAILADDRESS.email_desc}" class="span8" required><br>
</table>
<br>
<div class="pull-right cancelLinkContainer"><a class="cancelLink" href='index.php?module=EmailConfigurator&view=Index&parent=Settings'>Abbrechen</a></div>
<input type="submit" class="btn btn-success saveButton" value="Speichern">
</form>
 
</div>

</div>
