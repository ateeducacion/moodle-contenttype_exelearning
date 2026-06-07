@contenttype @contenttype_exelearning @_switch_iframe @javascript
Feature: Render eXeLearning packages in the content bank
  In order to reuse eXeLearning content
  As a teacher
  I need to upload eXeLearning packages and view them inside the content bank

  Background:
    Given I log in as "admin"
    And the following "contentbank content" exist:
      | contextlevel | reference | contenttype             | user  | contentname        | filepath                                                          |
      | System       |           | contenttype_exelearning | admin | Sample eXeLearning | /contentbank/contenttype/exelearning/tests/fixtures/sample.elpx   |

  Scenario: A stored eXeLearning package is listed and rendered in an iframe
    Given I am on site homepage
    And I turn editing mode on
    And the following config values are set as admin:
      | unaddableblocks | | theme_boost |
    And I add the "Navigation" block if not present
    And I expand "Site pages" node
    When I click on "Content bank" "link"
    Then I should see "Sample eXeLearning"
    When I click on "Sample eXeLearning" "link"
    Then "iframe.contenttype-exelearning-frame" "css_element" should exist
