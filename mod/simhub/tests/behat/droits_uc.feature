@mod @mod_simhub @local_simhub
Feature: Droits limités à l'UC et avancement dans l'activité SimHub
  Afin de suivre les ateliers de mon UC
  En tant qu'enseignant
  Je n'ai de droits que sur les UC où je suis inscrit

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | resp     | Rose      | Resp     | resp@example.com     |
      | ens      | Eric      | Ens      | ens@example.com      |
      | autre    | Alain     | Autre    | autre@example.com    |
      | etu      | Emma      | Etu      | etu@example.com      |
    And the following "courses" exist:
      | fullname | shortname |
      | UC A     | UCA       |
      | UC B     | UCB       |
    And the following "course enrolments" exist:
      | user  | course | role           |
      | resp  | UCA    | editingteacher |
      | ens   | UCA    | teacher        |
      | autre | UCB    | editingteacher |
      | etu   | UCA    | student        |
      | etu   | UCB    | student        |
    And the following "local_simhub > ateliers" exist:
      | numero | nomcourt | dureeindicative |
      | T1     | Suture   | 20              |
      | T2     | Bandage  | 15              |
    And the following "activities" exist:
      | activity | name        | course | idnumber |
      | simhub   | Ateliers UC A | UCA  | simhubA  |
      | simhub   | Ateliers UC B | UCB  | simhubB  |
    And the following "local_simhub > uc ateliers" exist:
      | activity      | atelier | obligatoire |
      | Ateliers UC A | T1      | 1           |
      | Ateliers UC A | T2      | 1           |
      | Ateliers UC B | T1      | 1           |

  Scenario: Le responsable d'UC compose son UC, l'enseignant la suit sans la modifier
    When I am on the "Ateliers UC A" "simhub activity" page logged in as "resp"
    Then I should see "Choose the course unit workshops"
    And I should see "Suture"
    And I should see "Self-assessment grid"
    And I should see "Emma Etu"
    When I am on the "Ateliers UC A" "simhub activity" page logged in as "ens"
    Then I should not see "Choose the course unit workshops"
    And I should not see "Self-assessment grid"
    And I should see "Emma Etu"

  Scenario: Un enseignant n'a aucun accès à une UC où il n'est pas inscrit
    Given I log in as "autre"
    When I am on "UC A" course homepage
    Then I should see "You cannot enrol yourself in this course"

  Scenario: Une séance sur un atelier fait avancer l'étudiant dans toutes les UC qui le contiennent
    Given the following "local_simhub > sessions" exist:
      | user | atelier | statut  |
      | etu  | T1      | realise |
    When I am on the "Ateliers UC A" "simhub activity" page logged in as "etu"
    Then I should see "Your progress: 50 %"
    When I am on the "Ateliers UC B" "simhub activity" page
    Then I should see "Your progress: 100 %"
    When I am on the "Ateliers UC A" "simhub activity" page logged in as "resp"
    Then I should see "50 % (1/2)"

  Scenario: L'étudiant fait ses ateliers sans quitter l'activité et ne refait pas un atelier validé
    Given the following "local_simhub > sessions" exist:
      | user | atelier | statut   |
      | etu  | T1      | certifie |
    When I am on the "Ateliers UC A" "simhub activity" page logged in as "etu"
    Then "Start" "link" should not exist in the "Suture" "table_row"
    And "Start" "link" should exist in the "Bandage" "table_row"
    When I click on "Start" "link" in the "Bandage" "table_row"
    Then I should see "Workshop started."
    And I should see "Ateliers UC A" in the ".breadcrumb" "css_element"
    When I am on the "Ateliers UC A" "simhub activity" page
    And I click on "Suture" "link" in the "region-main" "region"
    Then I should see "You have already validated this workshop"
    And "Start" "link" should not exist in the "region-main" "region"

  Scenario: L'enseignant voit le détail des séances et valide d'un clic celles sans anomalie
    Given the following "local_simhub > sessions" exist:
      | user | atelier | statut  |
      | etu  | T1      | realise |
    When I am on the "Ateliers UC A" "simhub activity" page logged in as "ens"
    Then I should see "0 min (expected: 20 min)"
    And I should see "No self-assessment"
    When I press "Validate the 1 session(s) without issues"
    Then I should see "1 session(s) processed."
    And I should see "No session awaiting validation."
