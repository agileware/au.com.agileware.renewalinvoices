<?php

require_once 'renewalinvoices.civix.php';

/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function renewalinvoices_civicrm_config(&$config) {
  _renewalinvoices_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function renewalinvoices_civicrm_xmlMenu(&$files) {
  _renewalinvoices_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function renewalinvoices_civicrm_install() {
  _renewalinvoices_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function renewalinvoices_civicrm_uninstall() {
  _renewalinvoices_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function renewalinvoices_civicrm_enable() {
  _renewalinvoices_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function renewalinvoices_civicrm_disable() {
  _renewalinvoices_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function renewalinvoices_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _renewalinvoices_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function renewalinvoices_civicrm_managed(&$entities) {
  $entities[] = array(
    'module' => 'au.com.agileware.renewalinvoices',
    'name' => 'relationshiptype',
    'update' => 'never',
    'entity' => 'RelationshipType',
    'params' => array(
      'name_a_b' => "Key Contact of",
      'label_a_b' => "Key Contact of",
      'name_b_a' => "Key Contact is",
      'label_b_a' => "Key Contact is",
      'description' => "Key Contact responsible for processing membership renewals",
      'contact_type_a' => "Individual",
      'contact_type_b' => "Organization",
      'is_reserved' => 0,
      'is_active' => 1,
      'version' => 3,
    ),
  );
  _renewalinvoices_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_caseTypes
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function renewalinvoices_civicrm_caseTypes(&$caseTypes) {
  _renewalinvoices_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implementation of hook_civicrm_alterSettingsFolders
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function renewalinvoices_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _renewalinvoices_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implementation of hook_civicrm_tokens
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_tokens
 */
function renewalinvoices_civicrm_tokens(&$tokens) {
  $tokens['contribution'] = array(
    'contribution.attachInvoice' => ts("Attach Invoice"),
  );
  $tokens['membership'] = array(
    'membership.nextEndDate' => ts("Membership Future End Date"),
  );
}

/**
 * Implementation of hook_civicrm_tokenValues
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_tokenValues
 */
function renewalinvoices_civicrm_tokenValues(&$values, $cids, $job = null, $tokens = array(), $context = null) {
  if ($context == "CRM_Core_BAO_ActionSchedule") {
    if (in_array('attachInvoice', $tokens['contribution'])) {
      foreach ($cids as $cid) {
        $values[$cid]['contribution.attachInvoice'] = "[attachInvoice]";
      }
    }
    if (in_array('nextEndDate', $tokens['membership'])) {
      foreach ($cids as $cid) {
        $values[$cid]['membership.nextEndDate'] = "[nextEndDate]";
      }
    }
    if (in_array('invoice_id', $tokens['contribution'])) {
      foreach ($cids as $cid) {
          $values[$cid]['contribution.invoice_id'] = "[invoice_id]";
      }
    }
  }
}

/**
 * Implementation of hook_civicrm_buildForm
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_buildForm
 */
function renewalinvoices_civicrm_buildForm($formName, &$form) {
  if ($formName == "CRM_Admin_Form_ScheduleReminders" && ($form->_action & CRM_Core_Action::ADD || $form->_action & CRM_Core_Action::UPDATE)) {
    if ($form->getVar('_id')) {
      $values = $form->getVar('_values');
      if ($values['mapping_id'] == 4) {
        $form->assign('reminderID', $form->getVar('_id'));
      }
    }
    $form->addEntityRef('relationship_type', ts('Relationship'), array(
      'entity' => 'RelationshipType',
      'placeholder' => ts('- Select Relationship -'),
      'select' => array('minimumInputLength' => 0),
    ));
    CRM_Core_Region::instance('page-body')->add(array(
      'template' => 'CRM/RenewalInvoices/Form/Relationship.tpl',
    ));
  }
}

/**
 * Implementation of hook_civicrm_validateForm
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_validateForm
 */
function renewalinvoices_civicrm_validateForm($formName, &$fields, &$files, &$form, &$errors) {
  if ($formName == "CRM_Admin_Form_ScheduleReminders") {
    if (in_array(4, CRM_Utils_Array::value('entity', $fields)) && CRM_Utils_Array::value('recipient', $fields) == "relationship" && empty($fields['relationship_type'])) {
      $errors['recipient'] = ts('If Relationship is selected, you must specify at least one option from the dropdown below.');
    }
  }
}

/**
 * Implementation of hook_civicrm_pre
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_pre
 */
function renewalinvoices_civicrm_pre($op, $objectName, $id, &$params) {
  if ($objectName == "Activity" && $op == "create" &&
    $params['activity_type_id'] == CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Membership Renewal Reminder')) {

    // Check to see if we have tokens we need to replace in the activity details.
    if (strpos($params['details'], '[attachInvoice]') !== FALSE) {
      $params['details'] = str_replace("[attachInvoice]","", $params['details']);
      $params['attachFile_1'] = array(
        'uri' => CRM_Core_Smarty::singleton()->get_template_vars('fileName'),
        'type' => 'application/pdf',
        'location' => CRM_Core_Smarty::singleton()->get_template_vars('fileName'),
        'upload_date' => date('YmdHis'),
      );
    }
    if (strpos($params['details'], '[nextEndDate]') !== FALSE) {
      $endDate = CRM_Core_Smarty::singleton()->get_template_vars('mem_end_date');
      $params['details'] = str_replace("[nextEndDate]", $endDate, $params['details']);
    }

    // Assign additional params.
    $params['target_contact_id'] = CRM_Core_Smarty::singleton()->get_template_vars('target_contact_id');
    $params['source_record_id'] = CRM_Core_Smarty::singleton()->get_template_vars('source_record_id');
  }
}

/**
 * Implementation of hook_civicrm_postProcess
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postProcess
 */
function renewalinvoices_civicrm_postProcess($formName, &$form) {
  if ($formName == "CRM_Admin_Form_ScheduleReminders") {
    $id = $form->get('id');
    $params = array(
      'reminder_id' => $id,
      'relationship_type' => CRM_Utils_Array::value('relationship_type', $form->_submitValues),
    );
    if ($form->_submitValues['entity'][0] == 4 && $form->_submitValues['recipient'] == 'relationship' && in_array($form->_submitValues['limit_to'], array('1', '0'))) {
      CRM_RenewalInvoices_BAO_RenewalInvoice::addEntity($params);
    }
    else {
      CRM_RenewalInvoices_BAO_RenewalInvoice::deleteEntity($id);
    }
  }
}

/**
 * Implementation of hook_civicrm_alterMailParams
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterMailParams
 */
function renewalinvoices_civicrm_alterMailParams(&$params, $context) {
  if ($params['groupName'] == "Scheduled Reminder Sender" && $params['entity'] == "action_schedule") {
    $response = CRM_RenewalInvoices_BAO_RenewalInvoice::getMembershipAndContribution($params['entity_id'], $params['toEmail']);

    $relationshipTypeId = CRM_RenewalInvoices_BAO_RenewalInvoice::checkRelationship($params['entity_id']);
    if (!$relationshipTypeId) {
      $relationshipTypeId = NULL;
    }

    if ($response == NULL) {
      $params['abortMailSend'] = TRUE;
      return;
    }

    $membership = $response[0];
    $contribution = $response[1];

    $contacts = CRM_RenewalInvoices_BAO_RenewalInvoice::checkRelatedContacts($params['entity_id'], $params['toEmail'], $relationshipTypeId, $membership);
    if (empty($contacts)) {
      $params['abortMailSend'] = TRUE;
      return;
    }
    $params['isEmailPdf'] = TRUE;
    $params['contributionId'] = $contribution['id'];
    $params['contactId'] = $contribution['contact_id'];

    // Calculate new end date.
    if (strpos($params['html'], '[nextEndDate]') !== FALSE) {
      $dates = CRM_Member_BAO_MembershipType::getRenewalDatesForMembershipType($membership['id']);
      $params['html'] = str_replace("[nextEndDate]", CRM_Utils_Date::customFormat($dates['end_date']), $params['html']);
      $params['text'] = str_replace("[nextEndDate]", CRM_Utils_Date::customFormat($dates['end_date']), $params['text']);
    }

    // Generate the invoice PDF to be attached to the mail.
    if (strpos($params['html'], '[attachInvoice]') !== FALSE) {
      $params['html'] = str_replace("[attachInvoice]","", $params['html']);
      $params['text'] = str_replace("[attachInvoice]","", $params['text']);

      // Membership Offline Renewal Receipt
      $pdfHtml = CRM_RenewalInvoices_BAO_RenewalInvoice::createInvoice($contribution, $membership);

      // Uncomment this to print a contribution receipt instead.
      // $pdfHtml = CRM_Contribute_BAO_ContributionPage::addInvoicePdfToEmail($params['contributionId'], $params['contactId']);

      $date = date('YmdHis');
      $pdfFileName = "Invoice_{$contribution['id']}_$date.pdf";
      $fileName = CRM_Contribute_Form_Task_Invoice::putFile($pdfHtml, $pdfFileName);
      if (empty($params['attachments'])) {
        $params['attachments'] = array();
      }
      $params['attachments'][] = CRM_Utils_Mail::appendPDF($pdfFileName, $pdfHtml, NULL);

      // Create the activity
      CRM_Core_Smarty::singleton()->assign('fileName', $fileName);
      CRM_Core_Smarty::singleton()->assign('target_contact_id', array_keys($contacts));
      CRM_Core_Smarty::singleton()->assign('source_record_id', $contribution['id']);
    }

      // Append invoice id generated by invoice PDF
      if (strpos($params['html'], '[invoice_id]') !== FALSE) {
          $invoiceNumber = CRM_Core_Smarty::singleton()->get_template_vars("invoice_number");
          $params['html'] = str_replace("[invoice_id]", $invoiceNumber, $params['html']);
          $params['text'] = str_replace("[invoice_id]", $invoiceNumber, $params['text']);
      }

    $params['toEmail'] = implode(',', $contacts);
  }
}
