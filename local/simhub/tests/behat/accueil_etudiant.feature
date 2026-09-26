@local @local_simhub
Feature: Accueil étudiant SimHub
  Afin de choisir un atelier en salle
  En tant qu'étudiant
  Je filtre les ateliers disponibles

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | etu      | Emma      | Etu      |
    And the following "local_simhub > ateliers" exist:
      | numero | nomcourt   | categorie      | statut  |
      | A1     | Suture     | Gestes de base | actif   |
      | A2     | Perfusion  | Gestes de base | actif   |
      | A3     | Ancien     |                | archive |
    And the following "local_simhub > sessions" exist:
      | user | atelier | statut  |
      | etu  | A1      | realise |

  Scenario: Les ateliers archivés restent cachés et le filtre de statut personnel fonctionne
    Given I log in as "etu"
    When I visit "/local/simhub/index.php"
    Then I should see "Suture"
    And I should see "Perfusion"
    And I should see "Gestes de base"
    And I should not see "Ancien"
    When I set the field "statutperso" to "Completed"
    And I press "Filter"
    Then I should see "Suture" in the ".local-simhub-cartes:last-child" "css_element"
    And I should not see "Perfusion" in the ".local-simhub-cartes:last-child" "css_element"
