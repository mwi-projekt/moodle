@mod @mod_dataform @set_dataform@dataformviewmanagement
Feature: View management

    @javascript
    Scenario: View management
        Given I run dataform scenario "view management" with:
            | viewtype  |
            | aligned   |
            | csv   |
            | grid   |
            | interval   |
            | rss   |
            | tabular   |
