<?php
include_once('../vendor/autoload.php');
ini_set("display_errors", 1);
/**
* Config section
**/
$featureDir = __DIR__ . '/../features/'; // Must have the ending "/"
$behatHome = __DIR__ . '/../'; // Must have the ending "/"
//$behatBin = $behatHome . 'vendor/behat/behat/bin/behat';
$behatBin = './start.sh';
$seed = 0;

/**
* Define constant
**/
define('KEYWORD_GIVEN', 'Given');
define('KEYWORD_WHEN', 'When');
define('KEYWORD_THEN', 'Then');
define('KEYWORD_AND', 'And');


/*********************************
* Request handler section
*********************************/
if(!empty($_REQUEST['action'])) {
    switch ($_REQUEST['action']) {    
        // Run test 
        case 'test':
            $case = $_REQUEST['case'];
            $command = "cd $behatHome; $behatBin $case 2>&1";
            $res = shell_exec($command);
            $res = str_replace('failed', '<span class="color-red"><strong>failed</strong></span>', $res);
            $res = str_replace('passed', '<span class="color-teal"><strong>passed</strong></span>', $res);
            echo $res;
            exit;

        // Create new feature file    
        case 'new_feature':
            if(!empty($_REQUEST['title'])) {
                $content = 'Feature: ' . $_REQUEST['title'] . "\n";
                $content .= '  Scenario: ____' . " \n";
                $content .= '    Given ____' . "\n";
                $content .= '    Then ____' . "\n";
                $fileName = strshorten($_REQUEST['title']) . time() . '.feature';
                $fp = fopen($featureDir . $fileName, "wb");
                fwrite($fp,$content);
                fclose($fp);
            }
            break; 

        // Delete a feature file    
        case 'delete_feature':
            if(!empty($_REQUEST['fileFullPath'])) {
                unlink($_REQUEST['fileFullPath']);
            }
            break;

        // Save feature files     
        case 'save':
            // Get feature info
            $fileFullPath = $_POST['fileFullPath'];
            $featureTitle = $_POST['featureTitle'];
            $featureDesc = $_POST['featureDesc'];

            // Merge each step keyword and text into one step line
            $steps = array();
            foreach ($_POST['step-keyword'] as $key => $w) {
                $steps[$key] = '        ' . $w . ' ' . $_POST['step-text'][$key];
            }

            // Build scenario text
            $scenarios = array();
            foreach ($_POST['scenarioTags'] as $key => $s) {
                $scenarios[$key] = $s == '@default' ? '' : "    " . $s . "\n";
                $scenarios[$key] .= "    Scenario: " . $_POST['scenarioTitle'][$key] . "\n";
                foreach ($steps as $stepKey => $stepText) {
                    $skeys = explode('-', $stepKey);
                    if($skeys[0] == $key) {
                        $scenarios[$key] .= $stepText . "\n";
                    }
                } 
            }

            // Build content
            $content = "Feature: " . $featureTitle . "\n";
            $content .= $featureDesc . "\n\n";
            foreach ($scenarios as $key => $value) {
                $content .= $value . "\n";
            }
            
            file_put_contents($fileFullPath, $content);

            $message = 'Successfully saved feature file: ' . $fileFullPath;
            break;
        default:
            break;
    }

}
/*********************************
* End of request handler section
*********************************/


// Get all feature files in feature directory
$feaureList = array();
foreach (scandir($featureDir) as $f) {
	if($f != '.' && $f != '..' && is_file($featureDir.$f) 
		&& pathinfo($featureDir.$f, PATHINFO_EXTENSION) == 'feature') {
		// Get feature file content, append to feature object
		$featureObj = buildFeatureObject($f);
        $featureObj->fileFullPath = $featureDir.$f;
		$feaureList[] = $featureObj;
	}
}

// Get all Behat mink options via command line
$allOptions = preg_split("#[\r\n]+#", shell_exec("cd $behatHome; $behatBin -dl"));
$given = array();
$when = array();
$then = array();
foreach ($allOptions as $opt) {
	$opt = str_replace('default | ', '', $opt);
	if(stripos($opt, KEYWORD_GIVEN) === 0) {
		$given[] = parseOpt($opt);
	}
	if(stripos($opt, KEYWORD_WHEN) === 0) {
		$when[] = parseOpt($opt);
	}
	if(stripos($opt, KEYWORD_THEN) === 0) {
		$then[] = parseOpt($opt);
	}
}

/**
* PHP functions section
**/
function buildFeatureObject($featureFileName) {
	global $featureDir;

    $keywords = new Behat\Gherkin\Keywords\ArrayKeywords(array(
        'en' => array(
            'feature'          => 'Feature',
            'background'       => 'Background',
            'scenario'         => 'Scenario',
            'scenario_outline' => 'Scenario Outline|Scenario Template',
            'examples'         => 'Examples|Scenarios',
            'given'            => KEYWORD_GIVEN,
            'when'             => KEYWORD_WHEN,
            'then'             => KEYWORD_THEN,
            'and'              => KEYWORD_AND,
            'but'              => 'But'
        )
    ));
    $lexer  = new Behat\Gherkin\Lexer($keywords);
    $parser = new Behat\Gherkin\Parser($lexer);

    try {
        $featureObj = $parser->parse(file_get_contents($featureDir.$featureFileName));

        return $featureObj;
	}
    catch(Exception $e) {
        trigger_error('Failed to parse a feature file: ' . $featureDir.$featureFileName . ' -- ' . $e->getMessage() );
        echo 'Failed to parse a feature file: ' . $featureDir.$featureFileName . ' -- ' . $e->getMessage();
        return false;
    }
}
function strshorten($string) {
    //Lower case everything
    $string = strtolower($string);
    //Make alphanumeric (removes all other characters)
    $string = preg_replace("/[^a-z0-9_\s-]/", "", $string);
    //Clean up multiple dashes or whitespaces
    $string = preg_replace("/[\s-]+/", " ", $string);
    //Convert whitespaces and underscore to dash
    $string = preg_replace("/[\s_]/", "-", $string);
    return $string;
}
function get_string_between($string, $start, $end){
    $string = ' ' . $string;
    $ini = strpos($string, $start);
    if ($ini == 0) return '';
    $ini += strlen($start);
    $len = strpos($string, $end, $ini) - $ini;
    return substr($string, $ini, $len);
}
function parseOpt($opt) {
    // Get the option string between /^ and $/
    $opt = get_string_between($opt, '/^', '$/');

    // Use default word if there's choice-regex
    $isChoiceStr = true;
    while ($isChoiceStr) {
        $choice = get_string_between($opt, '(?:', ')');
        if(empty($choice)) {
            $isChoiceStr = false;
        }
        else {
            $opt = str_replace('(?:'.$choice.')', str_replace('|', '', $choice), $opt);
        }
    }

    // Use %TEXT% for any arguments which between "( and )"
    $isArg = true;
    while($isArg) {
        $arg = get_string_between($opt, '"(', ')"');
        if(empty($arg)) {
            $isArg = false;
        }
        else {
            $opt = str_replace('"('.$arg.')"', '%TEXT%', $opt);
        }
    }

    // Other regex need to be replaced
    $opt = str_replace('(?i)', '', $opt);
    $opt = str_replace('(?-i)', '', $opt);
    $opt = str_replace('(?P<pattern>"[^"]\\\\"*")', '%TEXT%', $opt);
    $opt = str_replace('(?P<code>\d+)', '%TEXT%', $opt);
    $opt = str_replace('(?P<num>\d+)', '%TEXT%', $opt);
    $opt = str_replace('.*', '', $opt);
    
    return $opt;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="cache-control" content="max-age=0" />
    <meta http-equiv="cache-control" content="no-cache" />
    <meta http-equiv="expires" content="0" />
    <meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT" />
    <meta http-equiv="pragma" content="no-cache" />

    <title>Test Cases Admin</title>

    <link rel="stylesheet" href="css/bootstrap-superhero.min.css">
    <link rel="stylesheet" href="css/responsive.css?<?=time()?>">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <link href="https://code.jquery.com/ui/1.10.2/themes/smoothness/jquery-ui.css" rel="Stylesheet">

    <script src="js/jquery-1.11.2.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
    <script src="https://code.jquery.com/ui/1.10.2/jquery-ui.js" ></script>

    

</head>
<body class="search-results">

<div class='container'>
    <h1>Test Cases List</h1>
    
    <?php if(!empty($message)):?>
    <div class="messagestack proxima-nova">
        <div>
          <ul class="messagestack-message success">
                <li>
                    <i class="glyphicon glyphicon-ok-circle"></i>
                    <div class="message"><?=$message?></div>
                    <div class="close-message"><i class="glyphicon glyphicon-remove" target="messagestack"></i></div>
                </li>
            </ul>
        </div>
    </div>
    <?php endif;?>

    <div style="display: inline-block;margin: 30px 0 10px 0;">
        <input type="text" name="new_feature"><button id='new' class='btn btn-success new_feature' style='margin: 10px;'>New Feature</button>
    </div>
    <ul class="feature-list">
    <?php foreach($feaureList as $feature):?>
        <?php $seed += 100; $line = 1;?>
        <form name="feature-form" id="feature-<?=$seed?>-form" method="POST">
    	<li class="feature" id="feature-<?=$seed?>">
            <h3>
                <span class="color-red">Feature</span>: 
                <span class="color-orange" id="feature-<?=$seed?>-title">
                    <?=$feature->getTitle()?>
                </span>
                <input type="text" name="featureTitle" value="<?=$feature->getTitle()?>" id="input-feature-<?=$seed?>-title" style="display:none">
                <i class="glyphicon glyphicon-edit" target="feature-<?=$seed?>-title"></i>
            </h3>
            <div>
                <span id="feature-<?=$seed?>-desc"><?=$feature->getDescription()?></span>
                <input type="text" name="featureDesc" value="<?=$feature->getDescription()?>" id="input-feature-<?=$seed?>-desc" style="display:none">
                <i class="glyphicon glyphicon-edit" target="feature-<?=$seed?>-desc"></i>
            </div>
            <ul class="scenario-list">
                <?php foreach ($feature->getScenarios() as $key => $scenObj):?>
                    <?php 
                        $seed += 100; 
                        $scenSeed = $seed;
                        $line = $scenObj->getLine();
                    ?>
                    <li class="scenario" id="scenario-<?=$seed?>">
                        <div class="scen-tags">
                            <?php 
                                $tagsStr = '';
                                foreach($scenObj->getTags() as $key => $tag) {
                                    $tagsStr .= '@'.$tag .' '; 
                                }
                                $tagsStr = empty($tagsStr) ? '@default' : $tagsStr;
                            ?>
                            <span class="color-blue" id="scenario-<?=$seed?>-tags"><?=$tagsStr?></span>
                            <input type="text" name="scenarioTags[<?=$seed?>]" value="<?=$tagsStr?>" id="input-scenario-<?=$seed?>-tags" style="display:none">
                            <i class="glyphicon glyphicon-edit" target="scenario-<?=$seed?>-tags"></i>
                        </div>
                        <h4>
                            <span class="scenario-text">
                                <i class="glyphicon glyphicon-triangle-right"></i>
                                <span class="color-red">Scenario:</span>
                                <span class="color-orange" id="scenario-<?=$seed?>-title"><?=$scenObj->gettitle()?></span>
                                <input type="text" name="scenarioTitle[<?=$seed?>]" value="<?=$scenObj->getTitle()?>" id="input-scenario-<?=$seed?>-title" style="display:none">
                            </span>
                            <i class="glyphicon glyphicon-edit" target="scenario-<?=$seed?>-title" title="Edit this scenario"></i>
                            <i class="glyphicon glyphicon-trash" target="scenario-<?=$seed?>" title="Delete this scenario"></i>
                            <i class="glyphicon glyphicon-play" target="<?=$line?>" data="<?=$feature->fileFullPath?>" title="Run test for this scenario"></i>
                        </h4>
                        <ul class="step-list" style="display:none">
                        <?php foreach ($scenObj->getSteps() as $key => $stepObj):?>
                            <?php $seed += 100; ?>
                            <li class="step" id="step-<?=$seed?>" parent="<?=$scenSeed?>">
                                <span class="step-keyword color-red" id="step-<?=$seed?>-keyword"><?=$stepObj->getKeyword()?></span>
                                <input type="hidden" name="step-keyword[<?=$scenSeed?>-<?=$seed?>]" id="step-<?=$seed?>-keyword-hidden" value="<?=$stepObj->getKeyword()?>">
                                <span class="step-text" id="step-<?=$seed?>-text"><?=$stepObj->getText()?></span>
                                <input type="hidden" name="step-text[<?=$scenSeed?>-<?=$seed?>]" id="step-<?=$seed?>-text-hidden" value="<?=htmlspecialchars($stepObj->getText())?>">
                                <i class="glyphicon glyphicon-edit" target="step-<?=$seed?>"></i>
                                <i class="glyphicon glyphicon-trash" target="step-<?=$seed?>"></i>
                                <i class="glyphicon glyphicon-plus" type="step" target="step-<?=$seed?>"></i>
                                <i class="glyphicon glyphicon-ok" target="step-<?=$seed?>" style="display: none;"></i>
                                <i class="glyphicon glyphicon-remove" target="step-<?=$seed?>" style="display: none;"></i>
                            </li>
                        <?php endforeach;?>
                        </ul>
                    </li>
                <?php endforeach;?>
                <li class="scenario new">Scenario: <input type="text" name="new_scen" value=""><i class="glyphicon glyphicon-plus" type="scenario"></i></li>
            </ul>
            <div id='action-bar'>
                <button id='save' class='btn btn-primary pull-right save' style='margin-right: 10px;'>Save</button>
                <span class='btn btn-danger pull-right delete' target="<?=$feature->fileFullPath?>">Delete</span>
                <span class='btn btn-warning pull-right run' target="<?=$feature->fileFullPath?>">Run Test</span>
                <div style='clear:both;'></div>
            </div>
        </li>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="fileFullPath" value="<?=$feature->fileFullPath?>">
        </form>
    <?php endforeach;?>
    </ul>
</div>
<div id="test-result" style="display: none;"><h2>Test result:</h2> <i class="glyphicon glyphicon-remove"></i><div id="test-result-text"></div></div>
</body>
</html>
<script>
var keywordGiven = '<?=KEYWORD_GIVEN?>';
var keywordWhen = '<?=KEYWORD_WHEN?>';
var keywordThen = '<?=KEYWORD_THEN?>';
var keywordAnd = '<?=KEYWORD_AND?>';
var steps = {
    [keywordGiven] : <?=json_encode($given)?>,
    [keywordWhen] : <?=json_encode($when)?>,
    [keywordThen] : <?=json_encode($then)?>
};
steps[keywordAnd] = steps[keywordGiven].concat(steps[keywordWhen]).concat(steps[keywordThen]);
</script>
<script src="./js/axe-admin.js"></script>