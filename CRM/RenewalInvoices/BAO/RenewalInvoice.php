<?php

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2017
 * $Id$
 *
 */
class CRM_RenewalInvoices_BAO_RenewalInvoice extends CRM_Core_DAO {

  /**
   * Adds an entry in civicrm_renewalinvoices_entity table.
   *
   * @param $params|array
   *   Array consisting of elements to be inserted into table.
   */
  public static function addEntity($params) {
    $sql = "INSERT INTO civicrm_renewalinvoices_entity (reminder_id, relationship_type_id) VALUES ({$params['reminder_id']}, {$params['relationship_type']})
      ON DUPLICATE KEY UPDATE reminder_id = {$params['reminder_id']}, relationship_type_id = {$params['relationship_type']}";
    CRM_Core_DAO::executeQuery($sql);
  }

  /**
   * Deletes an entry in civicrm_renewalinvoices_entity table.
   *
   * @param $id|integer
   *   Scheduled Reminder ID.
   */
  public static function deleteEntity($id) {
    $sql = "DELETE FROM civicrm_renewalinvoices_entity WHERE reminder_id = {$id}";
    CRM_Core_DAO::singleValueQuery($sql);
  }

  /**
   * Function to check if the current scheduled reminder has recipients as related contacts.
   *
   * @param $id|integer
   *   Scheduled Reminder ID.
   *
   * @return $relationship_type_id|integer
   */
  public static function checkRelationship($id) {
    $sql = "SELECT relationship_type_id FROM civicrm_renewalinvoices_entity WHERE reminder_id = {$id}";
    return CRM_Core_DAO::singleValueQuery($sql);
  }

  public static function getContactID($email) {
    $cid = NULL;
    $result = civicrm_api3('Email', 'get', array(
      'sequential' => 1,
      'return' => array("contact_id"),
      'email' => $email,
    ));
    if ($result['count'] > 0) {
      $cid = $result['values'][0]['contact_id'];
    }
    return $cid;
  }

  /**
   * Function to check if the current scheduled reminder has recipients as related contacts.
   *
   * @param $relationshipTypeId|integer
   *   Relationship Type ID.
   * @param $cid|integer
   *   Contact ID.
   * @param $directions|array
   *   Array of directions used to retrieve relationships against.
   *
   * @return $contacts|array
   */
  public static function getEmails($relationshipTypeId, $cid, $directions) {
    $contacts = array();
    foreach ($directions as $from => $to) {
      $result = civicrm_api3('Relationship', 'get', array(
        'sequential' => 1,
        'relationship_type_id' => $relationshipTypeId,
        $from => $cid,
        'is_active' => 1,
      ));

      if ($result['count'] > 0) {
        foreach ($result['values'] as $value) {
          $aEmails = civicrm_api3('Email', 'get', array(
            'sequential' => 1,
            'contact_id' => $value[$to],
          ));
          if ($aEmails['count'] > 0) {
            foreach ($aEmails['values'] as $key => $val) {
              if ($val['location_type_id'] == CRM_Core_PseudoConstant::getKey('CRM_Core_DAO_Address', 'location_type_id', 'Billing')) {
                $contacts[$val['contact_id']] = $val['email'];
                break;
              }
              elseif ($val['is_primary'] == 1) {
                $contacts[$val['contact_id']] = $val['email'];
              }
            }
          }
        }
      }
    }
    return $contacts;
  }

  /**
   * Function to check if the current scheduled reminder has related contacts and retrieve their emails.
   *
   * @param $reminderId|integer
   *   Scheduled Reminder ID.
   * @param $email|string
   *   Email Address of member.
   *
   * @return $emails|array
   */
  public static function checkRelatedContacts($reminderId, $email) {
    $sql = "SELECT relationship_type_id FROM civicrm_renewalinvoices_entity where reminder_id = {$reminderId}";
    $relationshipTypeId = CRM_Core_DAO::singleValueQuery($sql);

    // Get contact ID.
    $cid = self::getContactID($email);

    $directions = array(
      'contact_id_a' => 'contact_id_b',
      'contact_id_b' => 'contact_id_a',
    );

    // Get emails of related contacts.
    $emails = self::getEmails($relationshipTypeId, $cid, $directions);

    // Check if Limit to or Also include.
    $limit = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_ActionSchedule', $reminderId, 'limit_to');
    if ($limit) {
      return $emails;
    }
    else {
      array_push($emails, $email);
      return $emails;
    }
  }

  /**
   * Create Invoice which will be attached to the mail.
   *
   * @param $contactID|integer
   *   Contact ID.
   * @param $contribution|array
   *   Pending contribution array.
   * @param $membership|array
   *   Membership array.
   * @param $lineItems|array
   *   Line items array.
   *
   */
  public static function createInvoice($contactID, $contribution, $membership, $lineItems) {
    // Get the template - Online Membership receipt.
    $workflow = civicrm_api3('OptionValue', 'get', array(
      'option_group_id' => 'msg_tpl_workflow_membership',
      'name' => 'membership_online_receipt',
      'return' => array('id'),
    ));
    $workflowId = CRM_Utils_Array::value('id', $workflow, NULL);
    if (empty($workflowId)) {
      return;
    }
    $result = civicrm_api3('MessageTemplate', 'get', array(
      'sequential' => 1,
      'return' => array("msg_html"),
      'workflow_id' => $workflowId,
    ));
    if ($result['count'] > 0) {
      $template = $result['values'][0]['msg_html'];
    }
    // Set the template tokens.
    self::setTplParams($contactID, $contribution, $membership, $lineItems);

    // Tokenize the HTML template.
    $template = CRM_Core_Smarty::singleton()->fetch("string:{$template}");

    // Generate PDF Invoice.
    return CRM_Utils_PDF_Utils::html2pdf($template,
      'civicrmContributionReceipt.pdf',
      FALSE,
      NULL
    );
  }

  /**
   * Function that assigns various tokens to PDF template. Drop any missing tokens here.
   *
   * @param $contactID|integer
   *   Contact ID.
   * @param $contribution|array
   *   Pending contribution array.
   * @param $membership|array
   *   Membership array.
   * @param $lineItems|array
   *   Line items array.
   *
   * @return $tplParams|array
   */
  public static function setTplParams($contactID, $contribution, $membership, $lineItems = array()) {
    $title = isset($contribution['title']) ? $contribution['title'] : CRM_Contribute_PseudoConstant::contributionPage($contribution['contribution_page_id']);

    $tplParams = array(
      'contactID' => $contactID,
      'membership_name' => CRM_Member_PseudoConstant::membershipType($membership['membership_type_id']),
      'mem_start_date' => $membership['start_date'],
      'mem_join_date' => $membership['join_date'],
      'mem_end_date' => $membership['end_date'],
      'membership_assign' => TRUE,
      'mem_status' => CRM_Member_PseudoConstant::membershipStatus($membership['status_id'], NULL, 'label'),
      'displayName' => CRM_Contact_BAO_Contact::displayName($contactID),
      'contributionID' => CRM_Utils_Array::value('id', $contribution),
      'contributionOtherID' => CRM_Utils_Array::value('contribution_other_id', $contribution),
      'lineItem' => CRM_Utils_Array::value('0', $lineItems),
      'title' => $title,
      'amount' => $contribution['total_amount'],
    );

    if ($contributionTypeId = CRM_Utils_Array::value('financial_type_id', $contribution)) {
      $tplParams['financialTypeId'] = $contributionTypeId;
      $tplParams['financialTypeName'] = CRM_Core_DAO::getFieldValue('CRM_Financial_DAO_FinancialType',
                                                                    $contributionTypeId);
      // Legacy support
      $tplParams['contributionTypeName'] = $tplParams['financialTypeName'];
      $tplParams['contributionTypeId'] = $contributionTypeId;
    }
    
    if ($contributionPageId = CRM_Utils_Array::value('id', $contribution)) {
      $tplParams['contributionPageId'] = $contributionPageId;
    }
    foreach ($tplParams as $key => $value) {
      CRM_Core_Smarty::singleton()->assign($key, $value);
    }
  }

  /**
   * Function to retrieve membership related to contact and perform operations.
   *
   * @param $reminderId|integer
   *   Scheduled Reminder ID.
   * @param $email|string
   *   Email address.
   *
   */
  public static function getMembershipAndContribution($reminderId, $email) {
    // Get contact ID.
    $cid = self::getContactID($email);
    // Create pending contribution for renewed membership.
    $entities = self::generateRenewal($reminderId, $cid);

    return $entities;
  }

  /**
   * Renew existing membership with pending contribution.
   *
   * @param $reminderId|integer
   *   Scheduled Reminder ID.
   * @param $cid|integer
   *   Contact ID.
   *
   */
  public static function generateRenewal($reminderId, $cid) {
    // Get current membership ID for user from action log table.
    $membershipId = CRM_Core_DAO::singleValueQuery("SELECT entity_id FROM civicrm_action_log WHERE contact_id = {$cid} AND entity_table = 'civicrm_membership' AND action_schedule_id = {$reminderId}");

    // Get line items for membership. We need this for the Order API.
    $lineItems = civicrm_api3('LineItem', 'get', array(
      'sequential' => 1,
      'entity_table' => "civicrm_membership",
      'entity_id' => $membershipId,
    ));

    // Get membership info. We need this to supply tokens to the PDF.
    $membership = civicrm_api3('Membership', 'get', array(
      'sequential' => 1,
      'id' => $membershipId,
    ));

    // Order API to create pending contribution for membership.
    $params = array(
      'sequential' => 1,
      'line_items' => array(
        '0' => array(
          'line_item' => $lineItems['values'],
          'params' => array(
            'contact_id' => $cid,
            'membership_type_id' => $membership['values'][0]['membership_type_id'],
            'membership_id' => $membershipId,
          ),
        ),
      ),
      'contribution_status_id' => 'Pending',
      'contact_id' => $cid,
      'total_amount' => $lineItems['values'][0]['line_total'],
      'financial_type_id' => $lineItems['values'][0]['financial_type_id'],
    );
    $contribution = civicrm_api3('Order', 'create', $params);

    if ($contribution['count'] > 0) {
      $entities = array($membership['values'][0], $contribution['values'][0]);
      return $entities;
      // Create the invoice - Use this if we need to change the template for the invoice later.
      // return self::createInvoice($cid, $contribution['values'][0], $membership['values'][0], $lineItems['values']);
    }
  }
}