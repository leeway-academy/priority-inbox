# pirority-inbox
Decide for yourself who gets your attention over email at any given time

This script (together with a few usefull cronjobs) allows you to automatically triage email.

Currently it supports three levels:

* Urgent emails: these will show up in your inbox as soon as they arrive 24x7x365
* Important emails: these will show up in your inbox as soon as they arrive but only during business hours
* Rest of the world: these will show up in your inbox just once a day

The script is designed around [php Gmail API](https://developers.google.com/gmail/api/v1/reference/).

# Requirements

* PHP 5.4+
* [Composer](https://getcomposer.org)

## Setup

The setup is a two faces process:

Face 1: Configure your gmail for cli access

1. Create your client on your Gmail account (Go [here](https://developers.google.com/gmail/api/quickstart/php) to get it)
1.1. Enable the API
1.2. Download the client secret to a safe place
 
Face 2: Set up your server to interact with gmail

1. Install dependencies through composer (```composer install```)
2. Run ```php fetch.php```
 
## Usage

