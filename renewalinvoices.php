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
 * Implementation of hook_civicrm_buildForm
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_buildForm
 */
function renewalinvoices_civicrm_buildForm($formName, &$form) {
  if ($formName == "CRM_Admin_Form_ScheduleReminders" && ($form->_action & CRM_Core_Action::ADD || $form->_action & CRM_Core_Action::UPDATE)) {
    if ($form->getVar('_id')) {
      $values = $form->getVar('_values');
      if ($values['mapping_id'] == 4) {
        $relationshipTypeId = CRM_RenewalInvoices_BAO_RenewalInvoice::checkRelationship($values['id']);
        if ($relationshipTypeId) {
          $form->assign('relationshiptypeid', $relationshipTypeId);
        }
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
    if ($form->_submitValues['entity'][0] == 4 && $form->_submitValues['recipient'] == 'relationship') {
      CRM_RenewalInvoices_BAO_RenewalInvoice::addEntity($params);
    }
    else {
      CRM_RenewalInvoices_BAO_RenewalInvoice::deleteEntity($id);
    }
  }
}

function renewalinvoices_civicrm_alterMailParams(&$params, $context) {
  if ($params['groupName'] == "Scheduled Reminder Sender" && $params['entity'] == "action_schedule") {
    $contacts = CRM_RenewalInvoices_BAO_RenewalInvoice::checkRelatedContacts($params['entity_id'], $params['toEmail']);
    if (empty($contacts)) {
      $params['abortMailSend'] = TRUE;
    }
  }
}