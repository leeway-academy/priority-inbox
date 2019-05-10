# gmail-pirority-inbox
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
===========================================

1. Create your client on your Gmail account (Go [here](https://developers.google.com/gmail/api/quickstart/php) to get it)
1.1. Enable the API
1.2. Download the client secret to a safe place
 
Face 2: Set up your server to interact with gmail
=================================================

1. Install dependencies through composer (```composer install```)
2. Run ```php fetch.php```
3. Follow instructions as they appear :)
 
Face 3: Configure your environment
==================================

1. Copy the file ```important_senders.php.dist``` into ```important_senders.php```
2. Copy the file ```urgent_senders.php.dist``` into ```urgent_senders.php```
3. Copy the file ```hidden_label_prefix.php.dist``` into ```hidden_label_prefix.php```
4. Edit the files appropriately in order to define who/when gets access to your inbox.
5. Create a new label in your gmail with the same name as reads in your ```hidden_label_prefix.php``` file
6. Create a filter in your gmail account that:
    1. Matches: ```from:(*) label:inbox```
    2. Do this: Skip Inbox, Apply label "(Contents of ```hidden_label_prefix.php```)"

## Usage

In order to use the application you need to issue at least one of the following modifiers:

* a: Pop email for every possible sender (```php fetch.php -a```)
* u: only urgent emails (```php fetch.php -u```)
* i: only important emails (```php fetch.php -i```)
* s: only this particular sender (```php fetch.php -s mauro.chojrin@leewayweb.com```)

Setup your cronjobs
===================

While this script can be used on Windows (in theory at least :p), I'm more familiar with Linux, so here's what you need to put in your crontab:


```*/10 * * * * /usr/bin/php /root/inbox-pause/fetch.php -u```

```*/10 10-17 * * 1-5 /usr/bin/php /root/inbox-pause/fetch.php -i```

```0 12 * * 1-5 /usr/bin/php /root/inbox-pause/fetch.php -a```

(This is just a sample configuration, you can tweak it to fit your particular needs)

# TODO

Here are some ideas into how this project could be extended (I'm most likely not going to implement these, but I'd be happy to receive PRs :):

1. Build the configuration into a database (Nothing fancy, SQLite should do fine)
2. Provide a GUI for the configuration/operation
3. Create a Chrome extension to add/remove people from particular senders lists directly from GMail
4. Bring fetching times into the script (In order to have just one cronjob instead of three).
