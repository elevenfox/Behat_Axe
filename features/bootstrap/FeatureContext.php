<?php
date_default_timezone_set('America/Los_Angeles');;

use Behat\MinkExtension\Context\MinkContext;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends MinkContext
{
    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
    }

    /**
     * @When /^(?:|I )click (?:on |)(?:|the )"([^"]*)"(?:|.*)$/
     */
    public
    function iClickOnTheCssSelector($arg1)
    {
        $findName = $this->getSession()->getPage()->findField($arg1);

        if (null === $findName) {
            $findName = $this->getSession()->getPage()->find("css", $arg1);
        }

        if (!$findName) {
            throw new Exception('Id/label/name/CSS selector: "' . $arg1 . '" could not be found.');
        } else {
            $findName->click();
            if( get_class($this->getSession()->getDriver()) != 'Behat\Mink\Driver\GoutteDriver' ) {
                $this->getSession()->wait(5000, '(0 === jQuery.active)');
                $this->getSession()->wait(1000);
            }
        }
    }

    /**
     * Note: This is only used in javascript mode
     * @Given /^wait for "([^"]*)"$/
     */
    public function waitFor($arg1)
    {   
        //sleep($arg1/1000);
        $this->getSession()->wait($arg1);
    }

    /**
     * @When /^wait for Ajax finished$/
     */
    public function waitForAjaxFinished()
    {
        $this->getSession()->wait(5000, '(0 === jQuery.active)');
        $this->getSession()->wait(1000);
    }

    /**
     * @When /^wait for "([^"]*)" on "([^"]*)"$/
     */
    public function waitForOn($arg1, $arg2)
    {
        $this->getSession()->wait($arg1, '(' . $arg2 . ')');
    }

    /**
     * @When /^I set cookie "([^"]*)" = "([^"]*)"$/
     */
    public function iSetCookie($arg1, $arg2)
    {
        $this->getSession()->setcookie ( $arg1, $arg2) ;
    }

    /**
     * Override fillField
     */
    public function fillField($field, $value)
    {
        //$field = $this->fixStepArgument($field);
        $value = $this->fixStepArgument($value);

        $findName = $this->getSession()->getPage()->findField($field);
        if (null === $findName) {
            $findName = $this->getSession()->getPage()->find("css", $field);
        }
        if (!$findName) {
            throw new Exception('Id/label/name/CSS selector: "' . $field . '" could not be found.');
        } else {
            $field = $findName;
        }

        $findName->setValue($value);
    }


    /**
     * @Then /^I should wait max 30s to see "([^"]*)"$/
     */
    public function iShouldSeeInMaximumSeconds($arg1)
    {
        $result = 0;
        $start = microtime(true);
        $end = $start + 30;
        do {
            $actual = $this->getSession()->getPage()->getText();
            $actual = preg_replace('/\s+/u', ' ', $actual);

            $result = preg_match('/'.preg_quote($arg1, '/').'/ui', $actual);
            
            if ($result) {
                return; 
            }
            usleep(100000);
        } while (microtime(true) < $end);

        throw new Exception('Text: "' . $arg1 . '" could not be found.');
    }

    /**
     * @When /^wait for page loaded$/
     */
    public function waitForThePageToBeLoaded()
    {
        $this->getSession()->wait(10000, "document.readyState === 'complete'");
    }
}
