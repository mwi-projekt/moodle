@mod @mod_dataform @set_dataform@dataformactivity
Feature: Add dataform to courses

    @javascript
    Scenario: Add a dataform to a course
        Given a fresh site for dataform scenario
        And I log in as "teacher1"
        And I am on "Course 1" course homepage

        ## Add a dataform
        Then I turn editing mode on
        And I add a "Dataform" to section "1"
        And I set the field "Name" to "Dataform activity 01"
        And I press "Save and return to course"
        Then I see "Dataform activity 01"
        
        ## Delete the dataform
        Then I delete "Dataform activity 01" activity
        
        Then I am on homepage
        And I am on "Course 1" course homepage
        Then I do not see "Dataform activity 01"
