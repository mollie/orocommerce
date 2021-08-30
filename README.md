<p align="center">
  <img src="https://repository-images.githubusercontent.com/39599498/cc516500-7166-11e9-9813-8806d8943e3b" width="150"/>
</p>
<h1 align="center">Mollie for OroCommerce</h1>

![GitHub release (latest by date)](https://img.shields.io/github/v/release/mollie/orocommerce)   ![GitHub commits since latest release (by date)](https://img.shields.io/github/commits-since/mollie/orocommerce/latest)

## Introduction

Mollie offers various payment methods which can be easily integrated into your OroCommerce webshop by using our official integration. Mollie accepts all major payment methods such as Visa, Mastercard, American Express, PayPal, iDEAL, SOFORT Banking, SEPA Bank Transfer, SEPA Direct Debit, ING Home'Pay, KBC/CBC Payment Button, Bancontact, Belfius Pay Button, paysafecard, CartaSi, Cartes Bancaires and Gift cards

1.  Installation is easy.
2.  Go to  [Mollie](https://www.mollie.com/signup/)  to create a Mollie account
3.  Install the integration using Composer in your OroCommerce shop
4.  Activate the integration and enter your Mollie organization API token

Once the onboarding process in your Mollie account is completed, start accepting payments. You’ll usually be up and running within one working day.   

## Installation
OroCommerce uses the Composer to manage the module package and the library. You can read detailed instructions on how to install the Mollie integration in our [installation guide](https://github.com/logeecom/orocommerce/wiki/Installation-and-setup).

## Finalizing steps
To finalize the installation you need to enter your organization API token in the corresponding box. You can find yours in the [Mollie Dashboard](https://www.mollie.com/dashboard/payments). Verify the token and if it's valid, your payment methods will be installed.

## Wiki
Read more about the integration configuration on [our Wiki](https://github.com/mollie/orocommerce/wiki).

## Release notes
 - **4.2.8:**
   - Optimization: Updated translations
   - Optimization: Fixed Composer version number   
 - **4.2.7:**
   - Added compatibility with OroCommerce 4.2
 - **4.1.7:**
   - New feature: Added order expiry days configuration
   - New feature: Added a dropdown for environment configuration
   - New feature: Added a transaction description on payment methods
   - New feature: Added a notification when the current version is outdated
   - Optimization: Payment methods are visible as soon as the token is verified
   - Optimization: Extended payment method voucher
 - **4.1.6:**
   - Optimization: Skip shop order line changes synchronization to Mollie for changed shop order identifier 
 - **4.1.5:**
   - Remove discontinued payment methods from integration configuration form
 - **4.1.4:** 
   - New feature: Implemented integration with [Mollie Components](https://docs.mollie.com/guides/mollie-components/overview)
   - New feature: Added iDeal, Giftcard and KBC/CBC issuer selection in the checkout.
   - Optimization: Organization API token is now hidden with ***.
 - **4.1.3:** Translations for NL, DE, and FR are added.