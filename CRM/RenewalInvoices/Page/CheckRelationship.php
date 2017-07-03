<?php

class CRM_RenewalInvoices_Page_CheckRelationship extends CRM_Core_Page {
  function run() {
    $reminderId = $_POST['reminder_id'];
    $check = CRM_RenewalInvoices_BAO_RenewalInvoice::checkRelationship($reminderId);
    if (!empty($check)) {
      echo $check;
    }
    exit();
  }
}
