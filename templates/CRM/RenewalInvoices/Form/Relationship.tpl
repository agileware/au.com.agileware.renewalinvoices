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
if ($("#recipient").val() != 'relationship') {
  $('#relationshipGroup').hide();
}
else {
  $('#relationshipGroup').show();
}

$(document).ajaxComplete(function( event, xhr, settings ) {
  if (~settings.url.indexOf("civicrm/ajax/mapping?mappingID=4")) {
    if ($("#recipient option[value='relationship']").length <= 0) {
      $("#recipient").append('<option value = "relationship">Select Relationship</option>');
    }
    var reminderId = '{/literal}{$reminderID}{literal}';
    var url = CRM.url('civicrm/checkrelationship');
    $.post( url, { reminder_id: reminderId })
      .done(function( data ) {
      var relationshiptypeid = data;
      if (relationshiptypeid) {
        $("#recipient").val('relationship');
        $("#relationship_type").select2("val", relationshiptypeid);
        $('#relationshipGroup').show();
        $('#recipientGroup').hide();
        $('#recipientManual').hide();
        if (!$('#limit_to').val()) {
          $('#relationshipGroup').hide();
        }
      }
    });
    if ($("#recipient").val() == 'relationship') {
      $('#relationshipGroup').show();
    }
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