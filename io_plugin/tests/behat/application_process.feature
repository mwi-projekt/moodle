@mod @mod_dhbwio
Feature: End-to-End Bewerbungsprozess (Wirtschaftsinformatik)
  Als Studierender (Kurs WWI23B2) moechte ich den gesamten Prozess
  vom Login bis zum Formular-Absenden durchlaufen koennen.

  # Background verwendet ausschliesslich Standard-Moodle-Steps und einen eigenen
  # dhbwio-Setup-Step, um keine Abhaengigkeit von behat_mod_dataform zu erzeugen.
  Background:
    Given the following "users" exist:
      | username | firstname | lastname   | email                |
      | student1 | Max       | Mustermann | student1@example.com |
    And the following "courses" exist:
      | fullname        | shortname |
      | WWI Test Course | WWI23B2   |
    And the following "course enrolments" exist:
      | user     | course  | role    |
      | student1 | WWI23B2 | student |
    And a dhbwio application environment is set up for course "WWI23B2"

  # AC1: @javascript aktiviert den Selenium-Treiber (Behat-Anforderung fuer
  #       JS-Rendering und vollstaendige Browser-Interaktion).
  @javascript
  Scenario: Student durchlaeuft den vollstaendigen Bewerbungs-Happy-Path
    Given I log in as "student1"
    And I am on "WWI Test Course" course homepage
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
