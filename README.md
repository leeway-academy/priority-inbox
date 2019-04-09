# pirority-inbox
Decide for yourself who gets your attention over email at any given time

This script (together with a few usefull cronjobs) allows you to automatically triage email.

Currently it supports three levels:

* Urgent emails: these will show up in your inbox as soon as they arrive 24x7x365
* Important emails: these will show up in your inbox as soon as they arrive but only during business hours
* Rest of the world: these will show up in your inbox just once a day

The script is designed around Gmail API.

It uses composer for dependency management.
