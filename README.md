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

**5.2.1:**
- Added cancel url to fix issue caused by clicking "previous page" on Mollie hosted payment page

**5.2.0:**
- Update from iDeal 1.0 to iDeal 2.0

**5.1.0:**
- Added compatibility with OroCommerce 5.1.0

**5.0.10:**
 - Added support for OroCommerce 5.0.0.
 - Added single-click payments.
 - Added surcharge rules.