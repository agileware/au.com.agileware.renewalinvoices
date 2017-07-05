### Membership Renewal Invoices (au.com.agileware.renewalinvoices)

About
-----

### This extension provides the additional option of sending an invoice for a membership renewal using a scheduled reminder. 

The use case is that typically the membership renewal invoice is sent 30 days (or more) prior to the membership expiry date, so that the member has time to process the invoice. It's also common for invoices to be sent out 60 and even 90 days prior depending on the membership fees involved.
Membership renewal invoices are almost always sent to an individual contact for action regardless of whether they are the primary member or have an inherited membership.
For individual memberships, sending the membership renewal invoice to the individual is fine.
For organisation memberships, membership renewal invoices that are sent to generic "accounts@acme.com.au" experience significant delay in processing whilst the accounts team locate who is responsible for approving the expenditure.
For organisation memberships a related contact with a defined relationship like "key contact for" is often used to identify to whom the membership renewal invoice should be sent.

Features
--------

The features required are very similar to scheduled reminder functionality, with the addition:

* The action for the scheduled reminder would be to generate a "pending" contribution for the membership.
* The existing "Send confirmation and receipt" contribution functionality would be executed so that the invoice could be sent.
* The ability to define which contact the membership renewal invoice should be sent to for the membership is critical. Similar to the scheduled reminders feature which allows the reminder to be limited to a "group of contacts" - having this flexibility would meet the requirement of redirecting the membership renewal to the appropriate individual contact when the primary member is an organisation.

Tokens
------

Some tokens which can be used while creating the email on the scheduled reminders page.

* To attach the invoice to the mail, use {contribution.attachInvoice} or select 'Attach Invoice' from the tokens menu.
* To display the effective end date after renewal in the mail, use {membership.nextEndDate} or select 'Membership Future End Date' from the tokens menu.
* The PDF used is the Memberships - Signup and Renewal Receipts (off-line) template, so changes can be made there to alter the output of the invoice.

