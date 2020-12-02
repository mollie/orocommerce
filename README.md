# Mollie for OROCommerce® 4.x #

***

## Installation & Update the Mollie Payments plugin ##

[1. Installation by Composer](#install-using-composer)

[2. Update by Composer](#update-through-composer)

[- Configuration](#configure-the-extension)

[- Troubleshooting](#troubleshooting)

[- Release notes](#release-notes)


## About Mollie Payments ##
With Mollie, you can accept payments and donations online and expand your customer base internationally with support for all major payment methods through a single integration. No need to spend weeks on paperwork or security compliance procedures. No more lost conversions because you don’t support a shopper’s favourite payment method or because they don’t feel safe. We made our products and API expansive, intuitive, and safe for merchants, customers and developers alike. 

Mollie requires no minimum costs, no fixed contracts, no hidden costs. At Mollie you only pay for successful transactions. More about this pricing model can be found [here](https://www.mollie.com/en/pricing/). You can create an account [here](https://www.mollie.com/dashboard/signup). The Mollie OROCommerce® plugin quickly integrates all major payment methods ready-made into your OROCommerce® webshop.
   

## Supported Mollie Payment Methods ##
- iDEAL

- Creditcard

- CartaSi & Cartes Bancaires

- Bancontact

- Belfius Pay Button

- ING HomePay

- KBC/CBC-Betaalknop

- SOFORT Banking

- BankTransfer

- PayPal

- Paysafecard

- Przelewy24

- SEPA bank transfer

- Klarna

- Giftcards

- Apple Pay

## Configuration, FAQ and Troubleshooting  ##
If you experience problems with the extension installation, setup or whenever you need more information about how to setup the Mollie Payment extension in OROCommerce® 4.x, please send an e-mail to [info@mollie.com](mailto:info@mollie.com) with an exact description of the problem.


## License ##
[OSL-3.0 (The Open Software 3.0) License](https://opensource.org/licenses/OSL-3.0).
Copyright (c) 2011-2020, Mollie B.V.

# Installation using Composer

OROCommerce® uses the Composer to manage the module package and the library. Composer is a dependency manager for PHP. Composer declares the libraries your project depends on and it will manage (install/update) them for you.

## Check Composer Status

Check if your server has composer installed by running the following command:

```
composer –v
```

If your server doesn’t have the composer install, you can easily install it. https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx

# Install using Composer

Step-by-step to install the OROCommerce® extension by Composer:

 1. Run the ssh console.
 2. Locate your Root
 3. Install the OROCommerce® extension
 4. Clear cache
---
 1. Run your SSH Console to connect to your OROCommerce® store
 2. Locate the root of your OROCommerce® store.
 3. Start with upgrading Composer to the latest version. This may be needed in case the extension to be installed uses some bleeding-edge feature in its composer.json file:
    
    ```
    composer self-update
    ```
    
    Then, install the extension’s Composer package using the Composer require command:
    
    ```
    composer require mollie/orocommerce:<extension-version> --prefer-dist --update-no-dev
    ```
    
    Next, remove the old cache:
    
    ```
    composer rm -rf var/cache/prod
    ```
    
    When you are finished with adding new packages, use the oro:platform: update command to make the application aware of the newly installed extension:
    
    ```
    php bin/console oro:platform:update --env=prod --force
    ```
    
 4. Finally, make sure to properly clean the cache:
 
    ```
    php bin/console cache:clear --env=prod
    ```

# Update through Composer
You can use the composer to update the OROCommerce® Mollie Payment extension package and the library. Below the 4 steps to update the OROCommerce® extension by Composer:

 1. Run the ssh console.
 2. Locate your Root
 3. Update the OROCommerce® extension
 4. Clear cache
 ---
 1. Run your SSH Console to connect to your OROCommerce® store
 2. Locate the root of your OROCommerce® store.
 3. Enter the following command line and wait as a composer will download the update:
    
    ```
    composer update mollie/orocommerce
    php bin/console oro:platform:update --env=prod --force
    ```
    
 4. Finally, make sure to properly clean the cache:
    
    ```
    php bin/console cache:clear --env=prod
    ```

# Configure the extension
To configure the Mollie Payment extension you can go to your OROCommerce® admin portal, to ‘System’ » ‘Manage Integrations’

 1. Click on the ‘Create Integration‘ and set name and status to ACTIVE
 2. Enter the Organization access token of your webshop. You can create Organization access token key in your [Mollie Dashboard](https://www.mollie.com/dashboard/)
 3. Click on the ‘Verify token‘ button
 4. If token is correct, click on the save button
 5. Under the ‘Website profile‘ tab, you can choose your Mollie profile
 6. Under the ‘Payment methods‘ tab you can Configure each payment method you would like to offer in your webshop
 7. For every payment method you would like to offer, choose a name, description, logo and api method 
 8. Add Mollie payment methods as new OROCommerce® payment rule (‘System’ » ‘Payment Rules’)
 
# Troubleshooting

Whenever you experiencing some difficulties or troubles with the installation and/or configuration of the Mollie Payment extension for OROCommerce® you can check the following points to make sure the configuration is right.

 1. **Perform a API Check**
 Use the [Verify token] button to check if the API Key is valid. You can find the [Verify token] button in the configuration Authorization section located in ‘System’ » ‘Manage Integrations’ » Mollie.
 2. **Check if you enabled the payment methods in the ORO ‘System’ » ‘Payment Rules’ configuration**
 3. **Check if you enabled the payment methods in your [Mollie Dashboard](https://www.mollie.com/dashboard/)**
The payment methods are disabled by default in your account so you firstly need to activate the payment methods that you want to implement in your [Mollie Dashboard](https://www.mollie.com/dashboard/).
 4. **Check if the order amount min and/or max value is fulfilled**
 5. **Check if there is any information in the Notifications section ‘System’ » ‘Manage Integrations’ » ’Mollie’ » ’Notifications’**
 6. **Check if there is any information in the logfile inside shop /var/logs/ folder**

# Release notes
 - **4.0.3:** Translations for NL, DE, and FR are added.