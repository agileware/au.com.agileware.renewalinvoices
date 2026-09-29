# Renewal Invoices for Memberships (au.com.agileware.renewalinvoices)

This is a [CiviCRM](https://civicrm.org) extension which adds extra features to Membership
Scheduled Reminders, allowing membership renewal invoices to be sent to related contacts of a
member rather than (or as well as) the member themselves.

The extension is licensed under [AGPL-3.0](LICENSE.txt).

## Status

As of 11/11/2024 this extension was reported as non-functional and not recommended for use, with
a caution that installing and using it could cause problems. Version 2.1.1 and 2.1.2 have since
been released (including a `civix` upgrade of the extension's scaffolding), but no record of a
functional fix has been confirmed in this review. If you are considering using this extension,
test it thoroughly in a non-production environment first.

## Purpose

Membership renewal invoices are typically sent well in advance of a membership's expiry date (30,
60, or even 90 days prior) via a Scheduled Reminder, so the member has time to process the
invoice.

For individual memberships, sending the renewal invoice to the individual member is usually fine.
For organisation memberships, however, invoices sent to a generic address like
`accounts@example.org` often sit unprocessed while the accounts team tries to work out who is
responsible for approving the expenditure. Organisations commonly want the invoice to go instead
to a specific related contact, for example someone with a "Key Contact of" relationship to the
organisation.

This extension adds that capability to CiviCRM's built-in Membership Scheduled Reminders: it lets
an admin choose a relationship type as the recipient of a renewal reminder, generates the pending
contribution for the renewal, and can attach a PDF invoice (and the new membership end date) to
the reminder email.

## Usage

### Sending reminders to a related contact

On the **Scheduled Reminders** admin page (`Administer > Communications > Scheduled Reminders`),
when a reminder's entity is set to **Membership(s)**, this extension adds a **Relationship**
option to the reminder's **Recipients** dropdown, together with a new field for selecting one or
more Relationship Types.

* Selecting **Relationship** and a relationship type sends the reminder to the related
  contact(s) of that type (for example, a member's "Key Contact") instead of the member's own
  email address, according to whether **Limit to** or **Also include** is selected.
* The extension automatically creates a `Key Contact of` / `Key Contact is` relationship type
  (between an Individual and an Organization) on install, for use as the reminder recipient. Any
  other applicable relationship type can be used instead.
* The recipient relationship type chosen for each reminder is stored in a new
  `civicrm_renewalinvoices_entity` table, linked to the Scheduled Reminder (`civicrm_action_schedule`)
  record.

When the reminder runs, the extension:

1. Generates a "Pending" contribution for the membership renewal (using the Order API and the
   membership's existing line items).
2. Determines the recipient(s) via the configured relationship, falling back to the member's own
   email/related contacts as configured.
3. Optionally generates and attaches a PDF invoice, and records a `Membership Renewal Reminder`
   activity noting the attached invoice.

### Tokens

The following tokens are available when composing the reminder email on the Scheduled Reminders
page:

* **`{contribution.attachInvoice}`** (also selectable as **Attach Invoice** from the Insert Token
  menu) - generates a PDF invoice for the pending renewal contribution and attaches it to the
  reminder email. The PDF is rendered using the **Contributions - Invoice** message template, so
  changes to its wording/layout should be made there.
* **`{membership.nextEndDate}`** (also selectable as **Membership Future End Date**) - inserts the
  membership's future end date (i.e. the end date that will apply once the renewal is paid) into
  the email body.
* **`{contribution.invoice_id}`** - CiviCRM's standard invoice ID token is populated with the
  invoice number generated for the attached PDF invoice, when used together with
  `{contribution.attachInvoice}`.
* To insert a link for the member to renew online, add the following snippet to the
  **Contributions - Invoice** message template, replacing `https://example.org` with your actual
  site URL:

  ```
  https://example.org{crmURL p='civicrm/contribute/transact' q="reset=1&id=`$id`&cid=`$contactID`&cs=`$contact.checksum`"}
  ```

## Special configuration requirements

* No API keys, OAuth apps, or other external credentials are required.
* The extension only activates its Relationship/invoice behaviour for reminders whose entity is
  **Membership(s)**; it has no effect on reminders for other entities.
* The generated PDF invoice always uses whichever **Contributions - Invoice** message template
  (workflow `contribution_invoice_receipt`) is configured in CiviCRM - review and customise this
  template before relying on `{contribution.attachInvoice}`.
* The internal page `civicrm/checkrelationship`, registered by this extension, is used only to
  support the Scheduled Reminders edit form's JavaScript (to preload a previously saved
  relationship type) and requires only the standard `access CiviCRM` permission. It is not
  intended to be accessed directly by users.
* On install, the extension creates the `civicrm_renewalinvoices_entity` database table and the
  `Key Contact of` / `Key Contact is` relationship type; no manual setup of these is required.

## Requirements

* CiviCRM 5.51+

## Installation (Web UI)

Learn more about installing CiviCRM extensions in the [CiviCRM Sysadmin
Guide](https://docs.civicrm.org/sysadmin/en/latest/customize/extensions/).

# About the Authors

This CiviCRM extension was developed by the team at
[Agileware](https://agileware.com.au).

[Agileware](https://agileware.com.au) provide a range of CiviCRM
services including:

* CiviCRM migration
* CiviCRM integration
* CiviCRM extension development
* CiviCRM support
* CiviCRM hosting
* CiviCRM remote training services

Support your Australian [CiviCRM](https://civicrm.org) developers,
[contact Agileware](https://agileware.com.au/contact) today!

![Agileware](logo/agileware-logo.png)
