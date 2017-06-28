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
    return CRM_Core_DAO::singleValueQuery($sql);
  }

  /**
   * Function to check if the current scheduled reminder has recipients as related contacts.
   *
   * @param $id|integer
   *   Scheduled Reminder ID.
   */
  public static function checkRelationship($id) {
    $sql = "SELECT relationship_type_id FROM civicrm_renewalinvoices_entity WHERE reminder_id = {$id}";
    return CRM_Core_DAO::singleValueQuery($sql);
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
   */
  public static function checkRelatedContacts($reminderId, $email) {
    $sql = "SELECT relationship_type_id FROM civicrm_renewalinvoices_entity where reminder_id = {$reminderId}";
    $relationshipTypeId = CRM_Core_DAO::singleValueQuery($sql);

    // Get contact ID.
    $result = civicrm_api3('Email', 'get', array(
      'sequential' => 1,
      'return' => array("contact_id"),
      'email' => $email,
    ));
    if ($result['count'] > 0) {
      $cid = $result['values'][0]['contact_id'];
    }

    $directions = array(
      'contact_id_a' => 'contact_id_b',
      'contact_id_b' => 'contact_id_a',
    );

    $emails = self::getEmails($relationshipTypeId, $cid, $directions);

    // Check if Limit to or Also include.
    $extra = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_ActionSchedule', $reminderId, 'limit_to');
    if ($extra) {
      return $emails;
    }
    else {
      array_push($emails, $email);
      return $emails;
    }
  }
}