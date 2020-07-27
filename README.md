# EWP Institutions

Drupal implementation of the EWP Institutions API.

See the **Erasmus Without Paper** specification for more information:

  - [Institutions API](https://github.com/erasmus-without-paper/ewp-specs-api-institutions/tree/v2.1.0)
  - [Registry API](https://github.com/erasmus-without-paper/ewp-specs-api-registry/tree/v1.4.1)

## Installation

Include the repository in your project's `composer.json` file:

    "repositories": [
        ...
        {
            "type": "vcs",
            "url": "https://github.com/EuropeanUniversityFoundation/ewp_institutions"
        }
    ],

Then you can require the package as usual:

    composer require euf/ewp_institutions

Finally, install the module:

    drush en ewp_institutions

## Usage

A custom content entity named **Institution** is provided with initial configuration to match the EWP specification. It can be configured like any other fieldable entity on the system. The administration paths are placed under `/admin/ewp/`.

The **Other HEI ID** field type becomes available in the Field UI so it can be added to any fieldable entity like any other field type.
