# gmail-pirority-inbox

Decide for yourself who gets your attention over email at any given time.

This script (together with a few usefull cronjobs) allows you to automatically triage email.

The script is designed around [php Gmail API](https://developers.google.com/gmail/api/v1/reference/).

# Requirements

* PHP 8.1+
* [Composer](https://getcomposer.org)
* [MailParse](https://www.php.net/manual/es/book.mailparse.php)

Alternatively the script can be used on top of [Docker](https://www.docker.com/).

# How it works

Every time the script runs it will go through the emails with the designated label. For each one, it will check whether:

1. The sender address matches an allowed pattern
2. The sender address doesn't match a not allowed pattern
3. The send date and time is prior to the established minimum

In practice this allows you to create sender's whitelists and blacklists, depending on your particular needs.

## Patterns

The patterns for matching email addresses are simple strings, the checks are based on inclusion.

<u>Examples</u>:

* Pattern `@gmail.com` matches `john@gmail.com` and `maria@gmail.com.ar` but doesn't match `pete@gmail.es`
* Pattern `willy.sanders` matches `willy.sanders@gmail.com` and `willy.sanders@hotmail.com` but doesn't match `willy@sanders.com`
* Pattern `Mauro Chojrin` matches `Mauro Chojrin <mauro.chojrin@leewayweb.com>` and `Mauro Chojrin <mchojrin@leewayweb.com>` but doesn't match `Mauro Uriel Chojrin <mauro.chojrin@leewayweb.com>`

# Setup

The setup is a two faces process:

Face 1: Configure your gmail for CLI access
===========================================

1. Create your client on your Gmail account (Go [here](https://developers.google.com/gmail/api/quickstart/php) to get it)
    1. Enable the API
    2. Download the client secret to a safe place
 
Face 2: Set up your server to interact with gmail
=================================================

1. Install dependencies through composer (```composer install```)
 
Face 3: Configure your environment
==================================

1. Create a filter in your gmail account with the following specification:
   1. **Matches**: ```from:(*) label:inbox```
   2. **Do this**: Skip Inbox, Apply label "`$HIDDEN_LABEL_PREFIX`"
1. Copy the file `app/.env.example` into `app/.env` and edit to match your own environment.

## Usage

In the following subscections you'll learn specific uses of the script but the real power of the application comes from combining them together.

### Getting all emails

The easiest way to use the script is without any parameter (```php app/fetch.php```). In this case every email labeled `$HIDDEN_LABEL_PREFIX` will be moved to the inbox.

### Limiting by sending time

To limit the emails moved to the inbox to those sent within a specific timeframe you can use the `-m` modifier together with a number. This number establishes the minimum number of hours before the current time to use as threshold.

For instance, if you only want to bring to your inbox those emails sent at most three hours ago you can run ```php app/fetch.php -m 3```. In this case, any email sent after now minus three hours will be kept hidden.

### Allowing emails from certain senders

In case you want to allow emails from specific senders to reach your inbox you can use the `-w` modifier. This modifier takes an argument which can be a pattern or the path to a text file containing one pattern per line.

For instance, say you only want to get emails from your co-workers, you could use the following: ```php app/fetch.php -w @yourcompany.com```.

### Disallowing emails from certain senders

In case you want to allow emails from specific senders to not reach your inbox you can use the `-b` modifier. This modifier takes an argument which can be a pattern or the path to a text file containing one pattern per line.

For instance, say you don't want to get emails from your co-workers, you could use the following: ```php app/fetch.php -b @yourcompany.com```.

Setup your cronjobs
===================

While this script can be used on Windows (in theory at least :p), I'm more familiar with Linux, so here's a few examples of what you could put in your crontab:

`# Every ten minutes get urgent emails`
```*/10 * * * * /usr/bin/php /path/to/priority-inbox/app/fetch.php -w urgent_senders.txt```

`# From Monday through Friday every half hour between 10 and 17 get emails from your coworkers and friends given they were sent at least two hours ago`
```*/30 10-17 * * 1-5 /usr/bin/php /path/to/priority-inbox/app/fetch.php -w @mycompany.com -w friends.txt -m 2```

`# During the weekends at 11 and 16 get emails from your everyone but your coworkers given they were sent at least three hours ago`
```0 11-16 * * 6-7 /usr/bin/php /path/to/priority-inbox/app/fetch.php -m 3 -b @mycompany.com```

(This is just a sample configuration, you can tweak it to fit your particular needs)

# TODO

Here are some ideas into how this project could be extended (I'm most likely not going to implement these, but I'd be happy to receive PRs :):

1. Build the configuration into a database (Nothing fancy, SQLite should do fine)
2. Provide a GUI for the configuration/operation
3. Create a Chrome extension to add/remove people from particular senders lists directly from GMail
4. Bring fetching times into the script (In order to have just one cronjob instead of three).