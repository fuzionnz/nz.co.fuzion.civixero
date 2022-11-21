<?php

use Psr\Log\LogLevel;

require_once 'Civixero.civix.php';

/**
 * Implementation of hook_civicrm_config
 *
 * @param \CRM_Core_Config $config
 */
function civixero_civicrm_config(CRM_Core_Config $config) {
  _civixero_civix_civicrm_config($config);
  require_once __DIR__ . '/vendor/autoload.php';
  if (!function_exists('random_bytes')) {
    require_once(__DIR__ . '/vendor/paragonie/random_compat/lib/random.php');
  }
}

/**
 * Implementation of hook_civicrm_install
 */
function civixero_civicrm_install() {
  _civixero_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
 */
function Civixero_civicrm_postInstall() {
  _Civixero_civix_civicrm_postInstall();
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function civixero_civicrm_uninstall() {
  _civixero_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function civixero_civicrm_enable() {
  _civixero_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function civixero_civicrm_disable() {
  return _civixero_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue|null, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 */
function civixero_civicrm_upgrade($op, ?CRM_Queue_Queue $queue = NULL) {
  return _civixero_civix_civicrm_upgrade($op, $queue);
}

/**
 * Is a given extension installed.
 *
 * Currently adding very roughly just to support checking if connectors is installed.
 *
 * I like to snaffle hacks into their own function for easy later fixing :-)
 *
 * @param string $extension
 *
 * @return bool
 * @todo - test using CRM_Extension_System::singleton()->getManager()->getStatus($key)
 *
 */
function civixero_is_extension_installed(string $extension): bool {
  return ($extension === 'nz.co.fuzion.connectors') && function_exists('connectors_civicrm_entityTypes');
}

/**
 * Implements hook_civicrm_alterSettingsMetaData(().
 *
 * This hook sets the default for each setting to our preferred value.
 * It can still be overridden by specifically setting the setting.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterSettingsMetaData/
 */
function civixero_civicrm_alterSettingsMetaData(array &$settingsMetaData, int $domainID, $profile): void {
  $weight = 100;
  foreach ($settingsMetaData as $index => $setting) {
    if ($setting['group'] === 'accountsync') {
      $settingsMetaData[$index]['settings_pages'] = ['xero' => ['weight' => $weight]];
    }
    $weight++;
  }
}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * Adds entries to the navigation menu.
 *
 * @param array $menu
 */
function civixero_civicrm_navigationMenu(&$menu) {
  // @todo - remove these in favour of using now-preferred method of adding via mgd.
  // note the intent is to remove rather than migrate some of these - ie
  // replace with search form.
  _Civixero_civix_insert_navigation_menu($menu, 'Administer', [
    'label' => 'Xero',
    'name' => 'Xero',
    'url' => NULL,
    'permission' => 'administer CiviCRM',
    'operator' => NULL,
    'separator' => NULL,
  ]);
  _Civixero_civix_insert_navigation_menu($menu, 'Administer/Xero', [
    'label' => 'Xero Settings',
    'name' => 'Xero Settings',
    'url' => 'civicrm/admin/setting/xero',
    'permission' => 'administer CiviCRM',
    'operator' => NULL,
    'separator' => 0,
  ]);
  // @todo - replace with the search kit display
  _Civixero_civix_insert_navigation_menu($menu, 'Administer/Xero', [
    'label' => 'Xero Error Logs',
    'name' => 'XeroErrorLogs',
    'url' => NULL,
    'permission' => 'administer CiviCRM',
    'operator' => NULL,
    'separator' => 1,
  ]);

  _Civixero_civix_insert_navigation_menu($menu, 'Administer/Xero', [
    'label' => 'Synchronize contacts',
    'name' => 'Contact Sync',
    'url' => 'civicrm/a/#/accounts/contact/sync',
    'permission' => 'administer CiviCRM',
    'operator' => NULL,
    'separator' => 1,
  ]);

  // @todo - replace with the search kit display
  _Civixero_civix_insert_navigation_menu($menu, 'Administer/Xero/XeroErrorLogs', [
    'label' => 'Contact Errors',
    'name' => 'Contact Errors',
    'url' => 'civicrm/xero/errorlog',
    'permission' => 'administer CiviCRM',
    'operator' => NULL,
    'separator' => 0,
  ]);

  // @todo - replace with the search kit display
  _Civixero_civix_insert_navigation_menu($menu, 'Administer/Xero/XeroErrorLogs', [
    'label' => 'Invoice Errors',
    'name' => 'Invoice Errors',
    'url' => 'civicrm/xero/errorlog?for=invoice',
    'permission' => 'administer CiviCRM',
    'operator' => NULL,
    'separator' => 0,
  ]);
  $connectors = _civixero_get_connectors();
  foreach ($connectors as $connectorID => $details) {
    _Civixero_civix_insert_navigation_menu($menu, 'Administer/Xero/', [
      'label' => 'Xero Authorize ' . $details['name'],
      'name' => 'Xero Authorize ' . $details['name'],
      'url' => 'civicrm/xero/authorize?connector_id=' . $connectorID,
      'permission' => 'administer CiviCRM',
      'operator' => NULL,
      'separator' => 0,
    ]);
  }
}

/**
 * Get contributions for a single contact.
 *
 * @param int $contactid
 *
 * @return array
 * @throws \CiviCRM_API3_Exception
 */
function getContactContributions(int $contactid): array {
  $contributions = civicrm_api3('Contribution', 'get', [
    'contact_id' => $contactid,
    'return' => ['contribution_id'],
    'sequential' => TRUE,
  ])['values'];
  return array_column($contributions, 'id');
}

/**
 * Get AccountInvoice data for contributions with errors.
 *
 * @param array $contributions
 *
 * @return array
 *
 * @throws \CiviCRM_API3_Exception
 */
function getErroredInvoicesOfContributions($contributions) {
  return civicrm_api3('AccountInvoice', 'get', [
    'plugin' => 'xero',
    'sequential' => TRUE,
    'contribution_id' => ['IN' => $contributions],
    'error_data' => ['<>' => ''],
  ]);
}

/**
 * Implementation of hook_civicrm_check.
 *
 * Add a check to the status page. Check if there are any account contact or invoice sync errors.
 *
 * @param array $messages
 *
 * @throws \CRM_Core_Exception
 */
function civixero_civicrm_check(array &$messages) {

  $accountContactErrors = civicrm_api3('AccountContact', 'getcount', [
    'error_data' => ['NOT LIKE' => '%error_cleared%'],
    'plugin' => 'xero',
  ]);
  $accountInvoiceErrors = civicrm_api3('AccountInvoice', 'getcount', [
    'error_data' => ['NOT LIKE' => '%error_cleared%'],
    'plugin' => 'xero',
  ]);
  $errorMessage = '';
  $errorsPageUrl = CRM_Utils_System::url('civicrm/xero/errorlog');

  if ($accountContactErrors > 0) {
    $errorMessage .= 'Found ' . $accountContactErrors . ' contact sync errors. <a href="' . $errorsPageUrl . '" target="_blank">Click here</a> to resolve them.';
    if ($accountInvoiceErrors > 0) {
      $errorMessage .= '<br><br>';
    }
  }
  if ($accountInvoiceErrors > 0) {
    $errorMessage .= 'Found ' . $accountInvoiceErrors . ' invoice sync errors. <a href="' . $errorsPageUrl . '?for=invoice" target="_blank">Click here</a> to resolve them.';
  }

  if ($accountInvoiceErrors > 0 || $accountContactErrors > 0) {
    $messages[] = new CRM_Utils_Check_Message(
      'civixero_sync_errors',
      $errorMessage,
      'Xero Sync Errors',
      LogLevel::ERROR,
      'fa-refresh'
    );
  }
  $clientID = Civi::settings()->get('xero_client_id');
  $clientSecret = Civi::settings()->get('xero_client_secret');
  $accessTokenData = Civi::settings()->get('xero_access_token');
  if (!$clientID || !$clientSecret) {
    $messages[] = new CRM_Utils_Check_Message(
      'civixero_clientrequired',
      ts('Please configure a Client ID and Client Secret from your Xero app.'),
      ts('Missing Xero App Details'),
      LogLevel::WARNING,
      'fa-flag'
    );
  }
  elseif (empty($accessTokenData['access_token'])) {
    $messages[] = new CRM_Utils_Check_Message(
      'civixero_authorizationrequired',
      ts('Please Authorize with Xero to enable a connection.'),
      ts('Xero Authorization Required'),
      LogLevel::WARNING,
      'fa-flag'
    );

  }
}

/**
 * Implements hook pageRun().
 *
 * Add Xero links to contact summary
 *
 * @param $page
 */
function civixero_civicrm_pageRun(&$page) {
  $pageName = get_class($page);
  if ($pageName !== 'CRM_Contact_Page_View_Summary' || !CRM_Core_Permission::check('view all contacts')) {
    return;
  }

  if (($contactID = $page->getVar('_contactId')) !== FALSE) {

    CRM_Civixero_Page_Inline_ContactSyncStatus::addContactSyncStatusBlock($page, $contactID);
    CRM_Civixero_Page_Inline_ContactSyncLink::addContactSyncLinkBlock($page, $contactID);
    CRM_Civixero_Page_Inline_InvoiceSyncLink::addInvoiceSyncLinkBlock($page, $contactID);
    CRM_Civixero_Page_Inline_ContactSyncErrors::addContactSyncErrorsBlock($page, $contactID);
    CRM_Civixero_Page_Inline_InvoiceSyncErrors::addInvoiceSyncErrorsBlock($page, $contactID);

    CRM_Core_Region::instance('contact-basic-info-left')->add([
      'template' => 'CRM/Civixero/ContactSyncBlock.tpl',
    ]);

  }

  CRM_Core_Resources::singleton()->addScriptFile('nz.co.fuzion.civixero', 'js/civixero_errors.js');
}

/**
 * Get available connectors.
 *
 * @return array
 */
function _civixero_get_connectors(): array {
  static $connectors = [];
  if (empty($connectors)) {
    try {
      $connectors = civicrm_api3('connector', 'get', ['connector_type_id' => 'CiviXero']);
      $connectors = $connectors['values'];
    }
    /** @noinspection PhpUnusedLocalVariableInspection */
    catch (CiviCRM_API3_Exception $e) {
      $connectors = [0 => 0];
    }
  }
  return $connectors;
}

/**
 * @param string $objectName
 * @param array $headers
 * @param array $values
 *
 * @noinspection PhpUnusedParameterInspection
 */
function civixero_civicrm_searchColumns(string $objectName, array &$headers, array &$values) {
  if ($objectName === 'contribution') {
    foreach ($values as &$value) {
      try {
        $invoiceID = civicrm_api3('AccountInvoice', 'getvalue', [
          'plugin' => 'xero',
          'contribution_id' => $value['contribution_id'],
          'return' => 'accounts_invoice_id',
        ]);
        $value['contribution_status'] .= "<a href='https://go.xero.com/AccountsReceivable/View.aspx?InvoiceID=" . $invoiceID . "'> <p>Xero</p></a>";
      }
      catch (Exception $e) {
        continue;
      }
    }
  }
}

/**
 * Map xero accounts data to generic data.
 *
 * @param array $accountsData
 * @param string $entity
 * @param string $plugin
 */
function civixero_civicrm_mapAccountsData(array &$accountsData, string $entity, string $plugin) {
  if ($plugin !== 'xero' || $entity !== 'contact') {
    return;
  }
  $accountsData['civicrm_formatted'] = [];
  $mappedFields = [
    'Name' => 'display_name',
    'FirstName' => 'first_name',
    'LastName' => 'last_name',
    'EmailAddress' => 'email',
  ];
  foreach ($mappedFields as $xeroField => $civicrmField) {
    if (!empty($accountsData[$xeroField])) {
      $accountsData['civicrm_formatted'][$civicrmField] = $accountsData[$xeroField];
    }
  }

  if (is_array($accountsData['Addresses']) && is_array($accountsData['Addresses']['Address'])) {
    foreach ($accountsData['Addresses']['Address'] as $address) {
      if (count($address) > 1) {
        $addressMappedFields = [
          'AddressLine1' => 'street_address',
          'City' => 'city',
          'PostalCode' => 'postal_code',
        ];
        foreach ($addressMappedFields as $xeroField => $civicrmField) {
          if (!empty($address[$xeroField])) {
            $accountsData['civicrm_formatted'][$civicrmField] = $address[$xeroField];
          }
        }
        break;
      }
    }
  }

  if (is_array($accountsData['Phones']) && is_array($accountsData['Phones']['Phone'])) {
    foreach ($accountsData['Phones']['Phone'] as $address) {
      if (count($address) > 1) {
        $addressMappedFields = [
          'PhoneNumber' => 'phone',
        ];
        foreach ($addressMappedFields as $xeroField => $civicrmField) {
          if (!empty($address[$xeroField])) {
            $accountsData['civicrm_formatted'][$civicrmField] = $address[$xeroField];
          }
        }
        break;
      }
    }
  }

}

/**
 * Implements hook_civicrm_accountsync_plugins().
 *
 * @param $plugins
 */
function civixero_civicrm_accountsync_plugins(&$plugins) {
  $plugins[] = 'xero';
}

/**
 * Implements hook_civicrm_contactSummaryBlocks().
 *
 * @link https://github.com/civicrm/org.civicrm.contactlayout
 *
 * @param $blocks
 */
function civixero_civicrm_contactSummaryBlocks(&$blocks) {
  $blocks += [
    'civixeroblock' => [
      'title' => ts('Civi Xero'),
      'blocks' => [],
    ],
  ];
  $blocks['civixeroblock']['blocks']['contactsyncstatus'] = [
    'title' => ts('Contact Sync Status'),
    'tpl_file' => 'CRM/Civixero/Page/Inline/ContactSyncStatus.tpl',
    'edit' => FALSE,
  ];
  $blocks['civixeroblock']['blocks']['contactsyncerrors'] = [
    'title' => ts('Contact Sync Errors'),
    'tpl_file' => 'CRM/Civixero/Page/Inline/ContactSyncErrors.tpl',
    'edit' => FALSE,
  ];
  $blocks['civixeroblock']['blocks']['invoicesyncerrors'] = [
    'title' => ts('Invoice Sync Errors'),
    'tpl_file' => 'CRM/Civixero/Page/Inline/InvoiceSyncErrors.tpl',
    'edit' => FALSE,
  ];
  $blocks['civixeroblock']['blocks']['invoicesynclink'] = [
    'title' => ts('Invoice Sync Link'),
    'tpl_file' => 'CRM/Civixero/Page/Inline/InvoiceSyncLink.tpl',
    'edit' => FALSE,
  ];
  $blocks['civixeroblock']['blocks']['contactsynclink'] = [
    'title' => ts('Contact Sync Link'),
    'tpl_file' => 'CRM/Civixero/Page/Inline/ContactSyncLink.tpl',
    'edit' => FALSE,
  ];

}
