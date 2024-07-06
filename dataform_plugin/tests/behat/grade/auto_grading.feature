@mod @mod_dataform @set_dataform@dataformgrading
Feature: Auto grading

    @javascript
    Scenario: Auto grading by number of entries.
        Given a fresh site for dataform scenario

        #Section: Add activity
        And the following dataform exists:
            | course                | C1        |
            | idnumber              | dataform1 |
            | name                  | Auto grade by number of entries |
            | intro                 | Auto grade by number of entries |
            | grade                 | 80        |
            | gradeitem 0 ca        | SUM(##numentries##) |
        #:Section

        #Section: Add view
        And the following dataform "views" exist:
            | name     | type      | dataform  | default   |
            | View 01  | aligned   | dataform1 | 1         |
        #:Section

        #Section: Add entries
        And the following dataform "entries" exist:
            | dataform  | user          | group | timecreated   | timemodified  |
            | dataform1 | student1      |       |               |               |
            | dataform1 | student1      |       |               |               |
            | dataform1 | student1      |       |               |               |
            | dataform1 | student1      |       |               |               |
            | dataform1 | student2      |       |               |               |
            | dataform1 | student2      |       |               |               |
            | dataform1 | student3      |       |               |               |
        #:Section

        #Section: Grades
        Then I log in as "teacher1"
        And I am on "Course 1" course homepage

        And I follow "Auto grade by number of entries"
        And I navigate to "Edit settings" in current page administration
        And I press "Save and display"

        And I am on "Course 1" course gradebook
        And I should see "4.00" in the "Student 1" "table_row"
        And I should see "2.00" in the "Student 2" "table_row"
        #:Section
