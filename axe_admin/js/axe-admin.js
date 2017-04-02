var buildKeywordDropdown = function(idStr, keyword) {
    return keywordDropdown = '<select name="stepKeyword" id="'+idStr+'-keywordDropdown">' + 
            '<option ' + (keyword == keywordGiven ? 'selected' : '') + '>' + keywordGiven + '</option>' +
            '<option ' + (keyword == keywordWhen ? 'selected' : '') + '>' + keywordWhen + '</option>' +
            '<option ' + (keyword == keywordThen ? 'selected' : '') + '>' + keywordThen + '</option>' +
            '<option ' + (keyword == keywordAnd ? 'selected' : '') + '>' + keywordAnd + '</option>' +
        '</select>';
}

// Define text dropdown change action
var handleTextDropdown = function(idStr, obj){
    var option = obj.val();
    option = option.replace(/%TEXT%/g, '<input type="text" name="tbd" value="" class="'+idStr+'-arg">');
    obj.hide();
    $('#' + idStr + '-new-text').show();
    $('#' + idStr + '-new-text').html(option);
    $('#' + idStr + '-down-dir').show();
};

// Define method of build text dropdown with given options
var buildTextDropDown = function(idStr, options, stepText) {
    var html = '<select style="display: none" id="'+idStr+'-textDropDown">';
    if( options != null ) {
        $.each(options, function(key, value){
            var args = findBetween(stepText, ' "', '"', []);
            $.each(args, function(tkey, between){
                stepText = stepText.replace('"'+between+'"', '%TEXT%');
            });
            var selected = stepText == value ? 'selected' : '';
            html += '<option ' + selected + '>' + value + '</option>'; 
        });
    }
    html += '</select>';
    return html;  
}

// Base on user input to get a step text string
var getStepText = function(idStr) {
    var text = $('#'+idStr+'-new-text').html();
    var argObjs = $('.'+idStr+'-arg');
    $.each(argObjs, function(key, argObj){
        var arg = $(argObj).val();
        text = text.replace($(argObj).clone().wrap('<div/>').parent().html(), '"'+arg+'"');
    });
    return text;
};

var iconsInEditMode = function(idStr, flag) {
    flag ? $('.glyphicon-edit[target="'+idStr+'"]').hide() : $('.glyphicon-edit[target="'+idStr+'"]').show();
    flag ? $('.glyphicon-trash[target="'+idStr+'"]').hide() : $('.glyphicon-trash[target="'+idStr+'"]').show();
    flag ? $('.glyphicon-plus[target="'+idStr+'"]').hide() : $('.glyphicon-plus[target="'+idStr+'"]').show();
    flag ? $('.glyphicon-ok[target="'+idStr+'"]').show() : $('.glyphicon-ok[target="'+idStr+'"]').hide();
    flag ? $('.glyphicon-remove[target="'+idStr+'"]').show() : $('.glyphicon-remove[target="'+idStr+'"]').hide();
};

var doneStepEdit = function(idStr) {
    $('#' + idStr + '-keyword').show();
    $('#' + idStr + '-text').show();
    $('#' + idStr + '-keywordDropdown').remove();
    $('#' + idStr + '-textDropDown').remove();
    $('#' + idStr + '-new-text').remove();
    $('#' + idStr + '-down-dir').remove();
    iconsInEditMode(idStr, false);
    toggleCover('off', idStr);
};

var processStepEdit = function(idStr) {
    toggleCover('on', idStr);

    iconsInEditMode(idStr, true);

    var keyword = $('#'+idStr+'-keyword').text();
    var stepText = $('#' + idStr + '-text').text();
    $('#' + idStr + '-keyword').hide();
    $('#' + idStr + '-text').hide();

    // Add new-text span and dropdown button
    $('#' + idStr + '-text').after('<span class="step-text" id="'+idStr+'-new-text"></span>');
    $('#' + idStr + '-new-text').html(stepText);
    $('#' + idStr + '-new-text').after('<i class="glyphicon glyphicon-triangle-bottom" target="'+idStr+'" id="'+idStr+'-down-dir"></i>');

    // Build keyword dropdown
    $('#' + idStr + '-keyword').after(buildKeywordDropdown(idStr, keyword));

    // Keyword dropown onchange
    $('#'+idStr+'-keywordDropdown').change(function(){
        $('#' + idStr + '-new-text').hide();
        $('#' + idStr + '-down-dir').hide();
        $('#' + idStr + '-textDropDown').remove();
        $('#' + idStr + '-new-text').after(buildTextDropDown(idStr, steps[$('#'+idStr+'-keywordDropdown').val()], stepText)); 
        $('#' + idStr + '-textDropDown').show();
        $('#' + idStr + '-textDropDown').focus();
        // If choose an option, display the text with input if there're arguments
        $('#'+idStr+'-textDropDown').change(function(){handleTextDropdown(idStr, $(this))});
        $('#'+idStr+'-textDropDown').blur(function(){handleTextDropdown(idStr, $(this))});
    });

    // Build text input via replacing words between ""
    var args = findBetween(stepText, ' "', '"', []);
    $.each(args, function(key, value){
        stepText = stepText.replace('"'+value+'"', '<input type="text" name="tbd" value="'+value+'" class="'+idStr+'-arg">');
    });
    $('#' + idStr + '-new-text').html(stepText);

    // Build text dropdown based on current keyword if it's not there
    if($('#' + idStr + '-textDropDown').length === 0) {
        $('#' + idStr + '-new-text').after(buildTextDropDown(idStr, steps[keyword], stepText));    
    }

    // If click down icon, show text dropsown options
    $('.glyphicon-triangle-bottom[target="'+idStr+'"]').click(function(){
        $('#' + idStr + '-new-text').hide();
        $('#' + idStr + '-textDropDown').show();
        $('#' + idStr + '-textDropDown').focus();
        $(this).hide();
        // If choose an option, display the text with input if there're arguments
        $('#'+idStr+'-textDropDown').change(function(){handleTextDropdown(idStr, $(this))});
        $('#'+idStr+'-textDropDown').blur(function(){handleTextDropdown(idStr, $(this))});
    });

    $('.glyphicon-ok[target="'+idStr+'"]:not(.bound)').addClass('bound').on('click', function(){
        var idStr = $(this).attr('target');
        
        var keyword = $('#'+idStr+'-keywordDropdown').val();
        $('#' + idStr + '-keyword').html(keyword);
        $('#' + idStr + '-keyword-hidden').val(keyword);
        
        var stepText = getStepText(idStr);
        $('#' + idStr + '-text').html(stepText);
        $('#' + idStr + '-text-hidden').val(stepText);

        $('#'+idStr).addClass('modified');

        doneStepEdit(idStr);
    });

    $('.glyphicon-remove[target="'+idStr+'"]').click(function(){
        doneStepEdit($(this).attr('target'));
    });
};

var newStep = function(obj, defaultText = '') {
    // Get a seed
    idStr = obj.attr('target');
    parentSeed = $('#'+idStr).attr('parent');
    var seed = parseInt(idStr.split('-').pop()) + 1;

    // Use new seed to append new-step-html to the current step
    var newIdStr = 'step-' + seed;
    var stepText = defaultText.length > 0 ? defaultText : keywordGiven;
    var html = '<li class="step" id="'+newIdStr+'" parent="'+parentSeed+'">';
    html += '<span class="step-keyword color-red" id="'+newIdStr+'-keyword">' + keywordGiven + '</span>'
    html += '<input type="hidden" name="step-keyword['+parentSeed+'-'+seed+']" id="step-'+seed+'-keyword-hidden" value="' + keywordGiven + '">';
    html += '<span class="step-text" id="'+newIdStr+'-text">I am on homepage</span>'
    html += '<input type="hidden" name="step-text['+parentSeed+'-'+seed+']" id="step-'+seed+'-text-hidden" value="I am on homepage">';
    html += '<i class="glyphicon glyphicon-triangle-bottom" target="'+newIdStr+'" id="'+newIdStr+'-down-dir" style="display: none"></i>';
    html += '<i class="glyphicon glyphicon-edit" target="'+newIdStr+'" style="display: none"></i>';
    html += '<i class="glyphicon glyphicon-trash" target="'+newIdStr+'" style="display: none"></i>';
    html += '<i class="glyphicon glyphicon-plus" type="step" target="'+newIdStr+'" style="display: none"></i>';
    html += '<i class="glyphicon glyphicon-ok" target="'+newIdStr+'"></i>';
    html += '<i class="glyphicon glyphicon-remove" target="'+newIdStr+'"></i>';
    html += '</li>';
    $('#' + idStr).after(html);
    
    processStepEdit(newIdStr);

    // Override ccw action in processEdit
    $('.glyphicon-remove[target="'+newIdStr+'"').click(function(){
        toggleCover('off', newIdStr);
        $('#' + newIdStr).remove();
    });

    bindEventsToIcons(newIdStr);
};

var newScenario = function(targetIdStr, title) {
    var seed = parseInt(targetIdStr.split('-').pop()) + 1000000;
    var newIdStr = 'scenario-' + seed;
    var html = '<li class="scenario modified" id="'+newIdStr+'">';
    html += '<div class="scen-tags">';
    html += '<span class="color-blue" id="'+newIdStr+'-tags">@default</span>';
    html += '<input type="text" name="scenarioTags['+seed+']" value="@default" id="input-'+newIdStr+'-tags" style="display:none">';
    html += '<i class="glyphicon glyphicon-edit" target="'+newIdStr+'-tags"></i></div>';
    html += '<h4><span class="scenario-text"><i class="glyphicon glyphicon-triangle-bottom"></i><span class="color-red">Scenario: </span>';
    html += '<span class="color-orange" id="'+newIdStr+'-title" style="display: inline;">'+title+'</span>';
    html += '<input type="text" name="scenarioTitle['+seed+']" value="'+title+'" id="input-'+newIdStr+'-title" style="display: none;"></span>';
    html += '<i class="glyphicon glyphicon-edit" target="'+newIdStr+'-title"></i><i class="glyphicon glyphicon-trash" target="'+newIdStr+'"></i></h4>';
    html += '<ul class="step-list">';

    var stepGivenSeed = seed + 100;
    var stepGivenId = 'step-' + stepGivenSeed;
    html += '<li class="step" id="'+stepGivenId+'" parent="'+seed+'">';
    html += '<span class="step-keyword color-red" id="'+stepGivenId+'-keyword">' + keywordGiven + '</span>';
    html += '<input type="hidden" name="step-keyword['+seed+'-'+stepGivenSeed+']" id="'+stepGivenId+'-keyword-hidden" value="' + keywordGiven + '">';
    html += '<span class="step-text" id="'+stepGivenId+'-text">____</span>';
    html += '<input type="hidden" name="step-text['+seed+'-'+stepGivenSeed+']" id="'+stepGivenId+'-text-hidden" value="">';
    html += '<i class="glyphicon glyphicon-edit" target="'+stepGivenId+'"></i>';
    html += '<i class="glyphicon glyphicon-trash" target="'+stepGivenId+'"></i>';
    html += '<i class="glyphicon glyphicon-plus" type="step" target="'+stepGivenId+'"></i>';
    html += '<i class="glyphicon glyphicon-ok" target="'+stepGivenId+'" style="display: none;"></i>';
    html += '<i class="glyphicon glyphicon-remove" target="'+stepGivenId+'" style="display: none;"></i>';
    html += '</li>';
    

    var stepThenSeed = stepGivenSeed + 100;
    var stepThenId = 'step-' + stepThenSeed;
    html += '<li class="step" id="'+stepThenId+'" parent="'+seed+'">';
    html += '<span class="step-keyword color-red" id="'+stepThenId+'-keyword">Then</span>';
    html += '<input type="hidden" name="step-keyword['+seed+'-'+stepThenSeed+']" id="'+stepThenId+'-keyword-hidden" value="' + keywordThen + '">';
    html += '<span class="step-text" id="'+stepThenId+'-text">____</span>';
    html += '<input type="hidden" name="step-text['+seed+'-'+stepThenSeed+']" id="'+stepThenId+'-text-hidden" value="">';
    html += '<i class="glyphicon glyphicon-edit" target="'+stepThenId+'"></i>';
    html += '<i class="glyphicon glyphicon-trash" target="'+stepThenId+'"></i>';
    html += '<i class="glyphicon glyphicon-plus" type="step" target="'+stepThenId+'"></i>';
    html += '<i class="glyphicon glyphicon-ok" target="'+stepThenId+'" style="display: none;"></i>';
    html += '<i class="glyphicon glyphicon-remove" target="'+stepThenId+'" style="display: none;"></i>';
    html += '</li>';
    html += '</ul></li>';

    $('#' + targetIdStr).after(html);
    
    $('#' + newIdStr + ' h4 .scenario-text').click(function(){
        toggleScenario($(this));
    });

    bindEventsToIcons(newIdStr);
    bindEventsToIcons(newIdStr+'-title');
    bindEventsToIcons(stepGivenId);
    bindEventsToIcons(stepThenId);
}

var cancelEditAction = function(obj){
    var idStr = obj.attr('target');
    $('#'+idStr).remove();
};

var editAction = function(obj) {
    var minWidth = 100;
    var maxWidth = 600;
    var idStr = obj.attr('target');
    var width = parseInt($('#'+idStr).width()) + 20;
    var theWidth = width < minWidth ? minWidth : (width > maxWidth ? maxWidth : width);

    if(idStr.indexOf('step') === 0) {
        processStepEdit(idStr);
    }
    else {
        $('#'+idStr).hide();
        var innerText = $.trim($('#'+idStr).text());
        $('#input-'+idStr).css('width', theWidth);
        $('#input-'+idStr).show();
        $('#input-'+idStr).focus();
        $('#input-'+idStr).on('blur', function(){
            $('#'+idStr).html($('#input-'+idStr).val());
            $('#'+idStr).show();
            $('#input-'+idStr).hide();
            if(innerText != $('#input-'+idStr).val()) {
                $('#'+idStr).addClass('modified');
            }
        });
    } 
}

// Bind event method for those buttons
var bindEventsToIcons = function(idStr = '') {
    if(idStr.length == 0  || (idStr.length > 0 && idStr.indexOf('step') === 0)) {
        var plusObj = idStr.length > 0 ? $('.glyphicon-plus[target="'+idStr+'"]') : $('.glyphicon-plus');
        plusObj.click(function(){
            var type = $(this).attr('type');
            if(type == 'step') {
                newStep($(this));    
            }
            if(type == 'scenario') {
                var title = $(this).prev().val();
                if( title.length > 0 ) {
                    newScenario($(this).parent().prev().attr('id'), title);
                }
                else {
                    $(this).prev().css('border-color', 'red');
                    $(this).prev().focus();
                }
            }
        });
    }
    
    var editObj = idStr.length > 0 ? $('.glyphicon-edit[target="'+idStr+'"]') : $('.glyphicon-edit');
    var cancelObj = idStr.length > 0 ? $('.glyphicon-trash[target="'+idStr+'"]') : $('.glyphicon-trash');
    editObj.click(function(){
        editAction($(this));
    });
    cancelObj.click(function(){
        cancelEditAction($(this));
    });
};

var toggleScenario = function(obj){
    obj.parent().parent().find('.step-list').toggle();
    if(obj.find('i').attr('class') == 'glyphicon glyphicon-triangle-right') {
        obj.find('i').attr('class', 'glyphicon glyphicon-triangle-bottom');
    }
    else {
        obj.find('i').attr('class', 'glyphicon glyphicon-triangle-right');
    }
};


$('.scenario h4 .scenario-text').click(function(){
    toggleScenario($(this));
});

bindEventsToIcons();

$('.glyphicon-remove[target="messagestack"]').click(function(){
    $('.'+$(this).attr('target')).hide();
});

var runTest = function(command){
    toggleCover('on', 'test-result');
    $('#test-result').show();
    $('#test-result').css('position', 'fixed');
    $('#test-result').append('<div id="loading" class="loader"></div>');
    $.get( $(location).attr('href') + '?action=test&case=' + command, function( data ) {
      $('#loading').remove();  
      $('#test-result-text').html('<pre>' + data + '</pre>');
      $('#test-result .glyphicon-remove').click(function(){
        toggleCover('off', 'test-result');
        $('#test-result-text').html('');
        $(this).parent().hide();
      });
    });
};

$('.glyphicon-play').click(function(){
    var line = $(this).attr('target');
    var file = $(this).attr('data');

    runTest(file + ':' + line);
});

$('.new_feature').click(function(){
    var title = $(this).prev().val();
    if( title.length > 0 ) {
        $.get( $(location).attr('href') + '?action=new_feature&title=' + title, function( data ) {
          window.location.reload();
        });
    }
    else {
        $(this).prev().css('border-color', 'red');
        $(this).prev().focus();
    }
});

$('.delete').click(function(){
    var fileFullPath = $(this).attr('target');
    if(confirm('Are you sure to delete this feature?')) {
        $.get( $(location).attr('href') + '?action=delete_feature&fileFullPath=' + fileFullPath, function( data ) {
          window.location.reload();
        });
    }
});

$('.run').click(function(){
    var file = $(this).attr('target');
    runTest(file);
});

function findBetween(srcString, startFlag, endFlag, result) {
    var between = srcString.match(startFlag + '(.*?)' + endFlag);
    if(between != null) {
        srcString = srcString.replace(startFlag + between[1] + endFlag, '');
        result.push(between[1]);
        return findBetween(srcString, startFlag, endFlag, result);
    }
    return result;
}

function toggleCover(flag, highlight) {
    if(flag == 'on') {
        if($('#ajax_cover').length == 0) {
            $('body').append('<div id="ajax_cover" style="background-color: rgba(0, 0, 0, 0.7);width: 100%;height: 100%;position: fixed;top:0;left:0;z-index: 999;display:inline-block"></div>');
            $('#'+highlight).css('z-index', '9999');
            $('#'+highlight).css('background-color', 'white');
            $('#'+highlight).css('position', 'relative');
            $('#'+highlight).css('display', 'inline-block');
        }
    }
    if(flag == 'off') {
        $('#ajax_cover').remove();
        $('#'+highlight).css('z-index', '1');
        $('#'+highlight).css('display', 'block');
    }
}   