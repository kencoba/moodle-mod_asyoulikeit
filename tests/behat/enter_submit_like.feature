@mod @mod_asyoulikeit
Feature: Enter, submit and like in an AsYouLikeIt activity
  In order to participate in an AsYouLikeIt assignment
  As a student
  I need to be able to enter, submit my work, and like a peer's submission

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | student1 | Student   | One      |
      | student2 | Student   | Two      |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
      | student2 | C1     | student |
    And the following "activities" exist:
      | activity    | course | name | intro                     |
      | asyoulikeit | C1     | Song | Write your song for Arden |

  @javascript
  Scenario: A student enters, submits work, and a peer likes it
    Given I am logged in as "student1"
    And I am on the "Song" "asyoulikeit activity" page
    And I press "Enter this assignment"
    And I set the following fields to these values:
      | Title   | My song  |
      | Content | La la la |
    And I press "Submit your work"
    Then I should see "My song"
    And I log out
    And I am logged in as "student2"
    And I am on the "Song" "asyoulikeit activity" page
    And I press "Enter this assignment"
    And I press "Like"
    Then I should see "Likes: 1"
    And I press "Revoke like"
    Then I should see "Likes: 0"
