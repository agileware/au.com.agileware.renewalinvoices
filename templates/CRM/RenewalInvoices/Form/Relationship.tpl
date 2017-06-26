<table>
<tr id="relationshipGroup" class="crm-scheduleReminder-form-block-recipient_relationship_type_id recipient" style="display: table-row;">
<td class="label">{$form.relationship_type.label}</td>
<td>{$form.relationship_type.html}</td>
</tr>
</table>

{literal}
<script type="text/javascript">
CRM.$(function($) {
$('#relationshipGroup').insertAfter('#recipientList');
$(document).ajaxComplete(function( event, xhr, settings ) {
  if (~settings.url.indexOf("civicrm/ajax/mapping?mappingID=4")) {
    $("#recipient").append('<option value = "relationship">Select Relationship</option>');
  }

  $('#recipient').change(function() {
    if ($(this).val() == 'relationship') {
      $('#relationshipGroup').show();
    }
    else {
      $('#relationshipGroup').hide();
    }
  });
});
});
</script>
{/literal}