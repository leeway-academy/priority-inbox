Feature: fetch
  In order to read my priority email
  As a user
  I need my email to be moved to my inbox at the established times

  Background:
    Given The hidden labelId is "hidden"

  Scenario: Move to the inbox the emails coming from whitelisted senders
    Given There is an email with id "email1" from "sender1@domain.com" labeled "hidden"
    And There is an email with id "email2" from "sender2@domain.com" labeled "hidden"
    And There is an email with id "email3" from "sender3@domain.com" labeled "hidden"
    And There is an email with id "email4" from "sender1@domain.com" labeled "hidden"
    And There is an email with id "email5" from "sender4@domain.com" labeled "hidden"
    When I run the command run.php "-w sender1@domain.com -w sender2@domain.com"
    Then the email with id "email1" should be labeled "INBOX"
    And the email with id "email1" should not be labeled "hidden"
    And the email with id "email2" should be labeled "INBOX"
    And the email with id "email2" should not be labeled "hidden"
    And the email with id "email3" should not be labeled "INBOX"
    And the email with id "email3" should be labeled "hidden"
    And the email with id "email4" should be labeled "INBOX"
    And the email with id "email4" should not be labeled "hidden"
    And the email with id "email5" should not be labeled "INBOX"
    And the email with id "email5" should be labeled "hidden"

  Scenario: Not move to the inbox the emails coming from blacklisted senders
    And There is an email with id "email1" from "sender1@domain.com" labeled "hidden"
    And There is an email with id "email2" from "sender2@domain.com" labeled "hidden"
    And There is an email with id "email3" from "sender3@domain.com" labeled "hidden"
    And There is an email with id "email4" from "sender1@domain.com" labeled "hidden"
    And There is an email with id "email5" from "sender4@domain.com" labeled "hidden"
    When I run the command run.php "-b sender1@domain.com -b sender2@domain.com"
    Then the email with id "email1" should not be labeled "INBOX"
    And the email with id "email1" should be labeled "hidden"
    And the email with id "email2" should not be labeled "INBOX"
    And the email with id "email2" should be labeled "hidden"
    And the email with id "email3" should be labeled "INBOX"
    And the email with id "email3" should not be labeled "hidden"
    And the email with id "email4" should not be labeled "INBOX"
    And the email with id "email4" should be labeled "hidden"
    And the email with id "email5" should be labeled "INBOX"
    And the email with id "email5" should not be labeled "hidden"

  Scenario: Build whitelist from a file
    Given The file "whitelist.txt" contains "sender1@domain.com"
    And The file "whitelist.txt" contains "sender2@domain.com"
    And There is an email with id "email1" from "sender1@domain.com" labeled "hidden"
    And There is an email with id "email2" from "sender2@domain.com" labeled "hidden"
    And There is an email with id "email3" from "sender3@domain.com" labeled "hidden"
    When I run the command run.php "-w whitelist.txt"
    Then the email with id "email1" should be labeled "INBOX"
    And the email with id "email1" should not be labeled "hidden"
    And the email with id "email2" should be labeled "INBOX"
    And the email with id "email2" should not be labeled "hidden"
    And the email with id "email3" should not be labeled "INBOX"
    And the email with id "email3" should be labeled "hidden"

  Scenario: The whitelist recognizes partial matches
    Given There is an email with id "email1" from "sender1@domain.com" labeled "hidden"
    And There is an email with id "email2" from "sender2@domain.com" labeled "hidden"
    And There is an email with id "email3" from "sender3@domain.com" labeled "hidden"
    When I run the command run.php "-w sender1"
    Then the email with id "email1" should be labeled "INBOX"
    And the email with id "email1" should not be labeled "hidden"
    And the email with id "email2" should not be labeled "INBOX"
    And the email with id "email2" should be labeled "hidden"
    And the email with id "email3" should not be labeled "INBOX"
    And the email with id "email3" should be labeled "hidden"