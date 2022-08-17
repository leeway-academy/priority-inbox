Feature: fetch
  In order to read my priority email
  As a user
  I need my email to be moved to my inbox at the established times

  Scenario: Move to the inbox the emails coming from whitelisted senders
    Given The whitelist contains "mchojrin@gmail.com"
    And The whitelist contains "maria.pappen@gmail.com"
    And There is an email from "mchojrin@gmail.com" waiting
    And There is an email from "maria.pappen@gmail.com" waiting
    And There is an email from "mauro.chojrin@leewayweb.com" waiting
    When I ask for my emails
    Then I should get
      """
      2 emails were moved to the inbox
      """

