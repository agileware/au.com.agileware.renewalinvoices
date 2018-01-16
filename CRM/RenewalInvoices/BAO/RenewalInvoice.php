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

  /**
   * Function to retrieve contact ID from email address.
   *
   * @param $email|string
   *   Email address.
   *
   * @return $cid|integer
   */
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
          self::getIndividualEmails($value[$to], $contacts);
        }
      }
    }
    return $contacts;
  }

  /**
   * Function to get email addresses of individual contact.
   *
   * @param $cid|integer
   *   Contact ID.
   * @param $emails|array
   *   Array of existing email addresses.
   */
  public static function getIndividualEmails($cid, &$emails) {
      $aEmails = civicrm_api3('Email', 'get', array(
          'sequential' => 1,
          'contact_id' => $cid,
      ));

      if ($aEmails['count'] > 0) {
          foreach ($aEmails['values'] as $key => $val) {
              $displayName = CRM_Contact_BAO_Contact::displayName($val['contact_id']);
              if ($val['location_type_id'] == CRM_Core_PseudoConstant::getKey('CRM_Core_DAO_Address', 'location_type_id', 'Billing')) {
                  $emails[$val['contact_id']] = $displayName . " <" . $val['email'] . ">";
                  break;
              }
              elseif ($val['is_primary'] == 1) {
                  $emails[$val['contact_id']] = $displayName . " <" . $val['email'] . ">";
              }
          }
      }
  }

  /**
   * Function to check if the current scheduled reminder has related contacts and retrieve their emails.
   *
   * @param $reminderId|integer
   *   Scheduled Reminder ID.
   * @param $email|string
   *   Email Address of member.
   * @param $membership|array
   *   Membership array which is being processed.
   *
   * @return $emails|array
   */
  public static function checkRelatedContacts($reminderId, $email, $relationshipTypeId, $membership) {
    // Get contact ID.
    $cid = self::getContactID($email);
    $contact = civicrm_api3("Contact","getsingle",array(
        "id" => $cid
    ));

    $directions = array(
      'contact_id_a' => 'contact_id_b',
      'contact_id_b' => 'contact_id_a',
    );

    if(array_key_exists("owner_membership_id", $membership) || $contact["contact_type"] == "Organization") {
        // Get emails of related contacts.
        $emails = self::getEmails($relationshipTypeId, $cid, $directions, $membership);
    } else {
        $emails = array();
        self::getIndividualEmails($cid, $emails);
    }

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
  public static function createInvoice($contribution, $membership) {
    // Get the template - Online Membership receipt.
    $workflow = civicrm_api3('OptionValue', 'get', array(
      'option_group_id' => 'msg_tpl_workflow_contribution',
      'name' => 'contribution_invoice_receipt',
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
    self::setTplParams($contribution, $membership);

    // Tokenize the HTML template.
    $template = CRM_Core_Smarty::singleton()->fetch("string:{$template}");

    return $template;
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
  public static function setTplParams($contribution, $membership) {

      $contact = civicrm_api3("Contact","getsingle",array(
         "id" => $contribution["contact_id"]
      ));
      $invoiceElements = CRM_Contribute_Form_Task_PDF::getElements(array($contribution["id"]), array('output' => 'pdf_invoice'), array($contribution["contact_id"]));
      $prefixValue = Civi::settings()->get('contribution_invoice_settings');
      $config = CRM_Core_Config::singleton();
      $invoiceDate = date("F j, Y");
      $lineItem = CRM_Price_BAO_LineItem::getLineItemsByContributionID($contribution["id"]);

      $contributionStatusID = CRM_Contribute_PseudoConstant::contributionStatus(NULL, 'name');
      $refundedStatusId = CRM_Utils_Array::key('Refunded', $contributionStatusID);
      $cancelledStatusId = CRM_Utils_Array::key('Cancelled', $contributionStatusID);
      $pendingStatusId = CRM_Utils_Array::key('Pending', $contributionStatusID);
      $domain = CRM_Core_BAO_Domain::getDomain();
      $locParams = array('contact_id' => $domain->contact_id);
      $locationDefaults = CRM_Core_BAO_Location::getValues($locParams);

      if (isset($locationDefaults['address'][1]['state_province_id'])) {
          $stateProvinceAbbreviationDomain = CRM_Core_PseudoConstant::stateProvinceAbbreviation($locationDefaults['address'][1]['state_province_id']);
      }
      else {
          $stateProvinceAbbreviationDomain = '';
      }
      if (isset($locationDefaults['address'][1]['country_id'])) {
          $countryDomain = CRM_Core_PseudoConstant::country($locationDefaults['address'][1]['country_id']);
      }
      else {
          $countryDomain = '';
      }

      foreach ($invoiceElements['details'] as $contribID => $detail) {
          $daoName = 'CRM_Contribute_DAO_ContributionPage';
          $mailElements = array(
              'title',
              'receipt_from_name',
              'receipt_from_email',
              'cc_receipt',
              'bcc_receipt',
          );
          $pageid = 2;
          CRM_Core_DAO::commonRetrieveAll($daoName, 'id', $pageid, $mailDetails, $mailElements);
          $component = $detail["component"];

          $contributionobj = new CRM_Contribute_BAO_Contribution();
          $contributionobj->id = $contribution["id"];
          $contributionobj->find(TRUE);

          $invoiceNumber = CRM_Utils_Array::value('invoice_prefix', $prefixValue) . "" . $contributionobj->id;

          $dataArray = array();
          $subTotal = 0;
          foreach ($lineItem as $lineindex => $taxRate) {
              if (isset($dataArray[(string) $taxRate['tax_rate']])) {
                  $dataArray[(string) $taxRate['tax_rate']] = $dataArray[(string) $taxRate['tax_rate']] + CRM_Utils_Array::value('tax_amount', $taxRate);
              }
              else {
                  $dataArray[(string) $taxRate['tax_rate']] = CRM_Utils_Array::value('tax_amount', $taxRate);
              }
              $subTotal += CRM_Utils_Array::value('subTotal', $taxRate);
              $lineItem[$lineindex]["label"] = "Renewal Amount for ".$lineItem[$lineindex]["label"]." Membership";
              $lineItem[$lineindex]["label"] .= "\nExpiring on : ".CRM_Utils_Date::customFormat($membership["end_date"]);
              $lineItem[$lineindex]["html_type"] = "Text";
          }

          $addressParams = array('contact_id' => $contributionobj->contact_id);
          $addressDetails = CRM_Core_BAO_Address::getValues($addressParams);
          $billingAddress = array();
          foreach ($addressDetails as $address) {
              if (($address['is_billing'] == 1) && ($address['is_primary'] == 1) && ($address['contact_id'] == $contribution->contact_id)) {
                  $billingAddress[$address['contact_id']] = $address;
                  break;
              }
              elseif (($address['is_billing'] == 0 && $address['is_primary'] == 1) || ($address['is_billing'] == 1) && ($address['contact_id'] == $contribution->contact_id)) {
                  $billingAddress[$address['contact_id']] = $address;
              }
          }

          if (!empty($billingAddress[$contribution->contact_id]['state_province_id'])) {
              $stateProvinceAbbreviation = CRM_Core_PseudoConstant::stateProvinceAbbreviation($billingAddress[$contribution->contact_id]['state_province_id']);
          }
          else {
              $stateProvinceAbbreviation = '';
          }

          $tplParams = array(
              'title' => $mailDetails[$pageid]["title"],
              'component' => $component,
              'id' => $contributionobj->id,
              'source' => $contributionobj->source,
              'invoice_number' => $invoiceNumber,
              'invoice_id' => $contributionobj->invoice_id,
              'resourceBase' => $config->userFrameworkResourceURL,
              'defaultCurrency' => $config->defaultCurrency,
              'amount' => $contributionobj->total_amount,
              'amountDue' => $contributionobj->total_amount,
              'amountPaid' => 0,
              'invoice_date' => $invoiceDate,
              'notes' => CRM_Utils_Array::value('notes', $prefixValue),
              'display_name' => $contact["display_name"],
              'lineItem' => $lineItem,
              'dataArray' => $dataArray,
              'refundedStatusId' => $refundedStatusId,
              'pendingStatusId' => $pendingStatusId,
              'cancelledStatusId' => $cancelledStatusId,
              'contribution_status_id' => $contributionobj->contribution_status_id,
              'subTotal' => $subTotal,
              'street_address' => CRM_Utils_Array::value('street_address', CRM_Utils_Array::value($contributionobj->contact_id, $billingAddress)),
              'supplemental_address_1' => CRM_Utils_Array::value('supplemental_address_1', CRM_Utils_Array::value($contributionobj->contact_id, $billingAddress)),
              'supplemental_address_2' => CRM_Utils_Array::value('supplemental_address_2', CRM_Utils_Array::value($contributionobj->contact_id, $billingAddress)),
              'supplemental_address_3' => CRM_Utils_Array::value('supplemental_address_3', CRM_Utils_Array::value($contributionobj->contact_id, $billingAddress)),
              'city' => CRM_Utils_Array::value('city', CRM_Utils_Array::value($contributionobj->contact_id, $billingAddress)),
              'stateProvinceAbbreviation' => $stateProvinceAbbreviation,
              'postal_code' => CRM_Utils_Array::value('postal_code', CRM_Utils_Array::value($contributionobj->contact_id, $billingAddress)),
              'is_pay_later' => $contributionobj->is_pay_later,
              'organization_name' => $contributionobj->_relatedObjects['contact']->organization_name,
              'domain_organization' => $domain->name,
              'domain_street_address' => CRM_Utils_Array::value('street_address', CRM_Utils_Array::value('1', $locationDefaults['address'])),
              'domain_supplemental_address_1' => CRM_Utils_Array::value('supplemental_address_1', CRM_Utils_Array::value('1', $locationDefaults['address'])),
              'domain_supplemental_address_2' => CRM_Utils_Array::value('supplemental_address_2', CRM_Utils_Array::value('1', $locationDefaults['address'])),
              'domain_supplemental_address_3' => CRM_Utils_Array::value('supplemental_address_3', CRM_Utils_Array::value('1', $locationDefaults['address'])),
              'domain_city' => CRM_Utils_Array::value('city', CRM_Utils_Array::value('1', $locationDefaults['address'])),
              'domain_postal_code' => CRM_Utils_Array::value('postal_code', CRM_Utils_Array::value('1', $locationDefaults['address'])),
              'domain_state' => $stateProvinceAbbreviationDomain,
              'domain_country' => $countryDomain,
              'domain_email' => CRM_Utils_Array::value('email', CRM_Utils_Array::value('1', $locationDefaults['email'])),
              'domain_phone' => CRM_Utils_Array::value('phone', CRM_Utils_Array::value('1', $locationDefaults['phone'])),
          );

          foreach ($tplParams as $key => $value) {
              CRM_Core_Smarty::singleton()->assign($key, $value);
          }
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
      'contribution_id' => array('IS NOT NULL' => 1),
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
      'source'        => $membership["values"][0]['membership_name']." Membership : Renewal",
      'contribution_status_id' => 'Pending',
      'contact_id' => $cid,
      'total_amount' => $lineItems['values'][0]['line_total'],
      'financial_type_id' => $lineItems['values'][0]['financial_type_id'],
    );
    $contribution = civicrm_api3('Order', 'create', $params);

    if ($contribution['count'] > 0) {
      $entities = array($membership['values'][0], $contribution['values'][0]);
      return $entities;
    }
  }
}