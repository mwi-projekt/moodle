@mod @mod_dhbwio
Feature: End-to-End Bewerbungsprozess (Wirtschaftsinformatik)
  Als Studierender (Kurs WWI23B2) moechte ich den gesamten Prozess
  vom Login bis zum Formular-Absenden durchlaufen koennen.

  # Background nutzt den Fresh-Site-Step des Dataform-Plugins.
  # Dieser erzeugt: Kurs "Course 1" (shortname C1), Nutzer student1/teacher1
  # sowie die Kurseinschreibungen – alles mit vorhersehbaren DB-IDs.
  Background:
    Given a fresh site with dataform "Bewerbungsformular"
    And the following dataform "fields" exist:
      | name       | type | dataform  |
      | Kursgruppe | text | dataform1 |
      | Vorname    | text | dataform1 |
      | Nachname   | text | dataform1 |
      | E-Mail     | text | dataform1 |
    And the following dataform "views" exist:
      | name    | type    | dataform  | default |
      | Ansicht | aligned | dataform1 | 1       |
    And a dhbwio instance is set up in course "C1" linked to dataform "dataform1"

  # AC1: @javascript aktiviert den Selenium-Treiber (Behat-Anforderung fuer
  #       JS-Rendering und vollstaendige Browser-Interaktion).
  @javascript
  Scenario: Student durchlaeuft den vollstaendigen Bewerbungs-Happy-Path
    Given I log in as "student1"
    And I am on "Course 1" course homepage
    When I follow "Bewerbungsformular"
    And I follow "Add a new entry"
    And I fill the dhbwio application form with:
      | Kursgruppe | WWI23B2                |
      | Vorname    | Max                    |
      | Nachname   | Mustermann             |
      | E-Mail     | max.mustermann@dhbw.de |
    And I press "Save"
    # AC2: Kein technischer Fehler (HTTP 500) nach dem Absenden.
    Then no HTTP 500 error is visible on the page
    # AC3: E-Mail-Benachrichtigung wurde ausgeloest – Log-Eintrag muss existieren.
    And an "application_received" email log entry exists for user "student1"
