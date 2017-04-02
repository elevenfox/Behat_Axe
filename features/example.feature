Feature: Example - Basic links and search
Check basic links all over site and those search and filters

    Scenario: Homepage
        Given I am on the homepage
        Then I should see "Google Search"

    Scenario: Search
        Given I am on the homepage
        When I fill in "input[name='q']" with "google"
        And I press "Google Search"
        Then I should be on "/search"
        And the url should match "[q=google]"

    @javascript 
    Scenario: Guided Lesson search
        Given I am on "/"
        And wait for "2000"
        When I follow "Gmail"
        And wait for "2000"
        Then I should be on "/gmail/about/"
        And wait for "2000"