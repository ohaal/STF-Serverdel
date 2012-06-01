function getQuizNames(selectVal) {
	$.getJSON("ajaxpages/getquiznames.php", function(quizlist) {
		var options = '';
		
		// States used for class names in HTML
		var states = new Array("Inactive", "Active", "Finished");
		for ( var i = 0; i < quizlist.length; i++) {
			// Remember to update the regex used to find quiz name if you edit this layout! (For easy lookup, search code for @regexquizname)
			options += '<option class="' + states[quizlist[i].state].toLowerCase() + '" value="' + quizlist[i].quizid + '">'
					+'['+quizlist[i].keyword.toUpperCase()+'] '+ quizlist[i].quizname + ' (' + states[quizlist[i].state] + ')</option>';
		}

		// Only add options to select dropdown and show it if we have >0 quizzes
		if (quizlist.length > 0) {
			// Add options to select tag
			$('select#quizname').html(options);
			if (selectVal) {
				// Set to specific quiz
				$('select#quizname').val(selectVal);
			}
			else {
				// Set to last option in dropdown
				var lastValue = $('select#quizname option:last-child').val();
				$('select#quizname').val(lastValue);
			}

			// Unhide tags
    		$('.hideifnoquiz').show();
		}
		else {
			// Hide tags if we have no quizzes
			$('.hideifnoquiz').hide();
		}
		
		// Get questions or high scores based on DOM content (quizadmin_template or highscore_template)
		if ($("div#quizadmin div#questions").length) {
			getQuestions($("div#questions"),$('select#quizname').val());
		}
		if ($("div#quizscore div#highscoretable_div").length) {
			getHighScores($('select#quizname').val());
		}
		return false;
	});
}
function getQuestions(resultdiv, quiz) {
	$.getJSON("ajaxpages/getquestions.php", {quizid: quiz}, function(questionlist) {
		var questions = '';
		var pdfquestions = '';

	    updatelinksandforms(questionlist);
	    
		// Check if we actually get any questions from the JSON
		if (questionlist == null) { return false; }

		var lockedquiz = !$("select#quizname option:selected").hasClass('inactive');

		// Only allow re-ordering if quiz is not locked
		if (!lockedquiz) {
			$( "div#quizadmin #questions" ).sortable('enable');
		} else {
			$( "div#quizadmin #questions" ).sortable('disable');
		}
		
		// Show question list
		for (var i = 0; i<questionlist.length; i++) {
			var extraclass = '';
			if (lockedquiz) {
				extraclass = ' questionlocked';
			}
			questions += '<div class="question'+extraclass+'" id="q_'+questionlist[i].idquestions+'">';
			questions += '<div class="answerheader">';
			if (!lockedquiz) {
				questions += '<a href="#" id="editquestion'+questionlist[i].idquestions+'" class="nounderline editquestion">';
			}
			questions += '<strong>'+questionlist[i].questionnumber+'</strong> ';
			questions += '<span class="questiontext" id="questiontext'+questionlist[i].idquestions+'">'+questionlist[i].questiontext+'</span>';
			if (!lockedquiz) {
				questions += '<span class="ui-icon ui-icon-wrench">edit</span>';
				questions += '</a>';
			}
			if (!lockedquiz) {
				questions += '<a href="#" id="deletequestion'+questionlist[i].idquestions+'" class="nounderline deletequestion">';
				questions += '<span class="ui-icon ui-icon-trash deletequestion\">delete</span>';
				questions += '</a>';
			}
			else {
				questions += '<span class="ui-icon ui-icon-locked lockedquestion">locked</span>';
			}
			questions += '</div>';
			questions += '<div class="answers">';
			if (questionlist[i].answers) {
				for (var k = 0; k<questionlist[i].answers.length; k++) {
					if (questionlist[i].answers[k].answernumber == questionlist[i].correctanswer) {
						questions += '<div class="correct">';
					} else {
						questions += '<div>';
					}
					// Simple fix for request about using letters instead of numbers as answer alternatives @replacealphawithnumber
					// First answer number is 1. 1+64 = 65. 65 is the ascii code for 'A'
					questions += '<strong>'+String.fromCharCode(parseInt(questionlist[i].answers[k].answernumber)+64)+'</strong>:'+questionlist[i].answers[k].answertext;
					questions += '</div>';
				}
			}
			questions += '</div>';
			questions += '</div>';
			
			pdfquestions += '<b>Question '+questionlist[i].questionnumber+': '+questionlist[i].questiontext+'</b><br />';
			pdfquestions += 'Question Heading: <input type="text" name="questionheading-'+questionlist[i].questionnumber+'" value="'+questionlist[i].questionheading+'" /><br />';
			pdfquestions += 'Question Ingress: <textarea name="questioningress-'+questionlist[i].questionnumber+'">'+questionlist[i].questioningress+'</textarea><br />';
			pdfquestions += 'Question Image: <input type="file" name="questionimage-'+questionlist[i].questionnumber+'" />';
			pdfquestions += '<hr />';
		}
		$(resultdiv).html(questions);
		$('div#pdfquestions').html(pdfquestions);
		$("a.editquestion").click(function(event) {
			$("div#newquestionoverlay").dialog("option", "title", "Edit question");
			$("div#newquestionoverlay button#submitnewquestionbutton").text("Save changes");
			editquestions(this);
			return false;
		});
		$("a.deletequestion").click(function(event) {
			confirmdeletequestion(this);
			return false;
		})
	});
}

function keywordsenabled() {
	// This is a hacky way to find out if multiple keywords are allowed in the quiz
	if ($('#inputquizkeyword').css('display') === 'none') {
		return false;
	}
	return true;
}

// This will always be triggered after quizid is changed (from getQuestions)
function updatelinksandforms(questionlist) {
	var quizid = $('select#quizname').val();
	// Update forms
	$('input.quizidvalue').attr('value', quizid);
	
	// Grab the quizname and keyword from currently selected option in select list
	var selectedquiz=$('select#quizname option:selected').text();
	// Use a regex to grab the name/keyword/state of the quiz @regexquizname
	// (hacky, but we avoid doing an extra (unnecessary) ajax call to get the quizname and keyword by doing it this way)
	var grabinforegex=/^\[([A-Z0-9ÆØÅ]*)\] (.+) \((Inactive|Active|Finished)\)$/;
	// Quick explanation of this regex:
	// ^						= Start of line
	// \[						= A normal '[' (needs to be escaped, since it has special meaning in regex)
	// (						= Start (first) capture group
	// [A-Z0-9ÆØÅ]				= Match any of the characters inside []-brackets (A-Z means all letters in between and 0-9 means all numbers in between)
	// *						= Any amount of previous pattern (the one matching specific characters)
	// )						= End (first) capture group (contains keyword)
	// \]						= A normal ']' (needs to be escaped, since it has special meaning in regex)
	// (						= Start (second) capture group
	// .						= Match any character (non-newline)
	// +						= One or more of previous pattern (the one matching any character)
	// )						= End (second) capture group (contains quiz name)
	// \(						= A normal '(' (needs to be escaped, since it has special meaning in regex)
	// (						= Start (third) capture group
	// Inactive|Active|Finished	= Match one of these '|'-seperated strings
	// )						= End (third) capture group (contains 1 of 3 options)
	// \)						= A normal ')' (needs to be escaped, since it has special meaning in regex)
	// $						= End of line
	// The '/' are there just to encase the regex, the spaces are just regular spaces
	if (selectedquiz.length > 0) {
		var quizinfo=grabinforegex.exec(selectedquiz);
		var quizname=quizinfo[2];
		var keyword=quizinfo[1]+' ';
	
		var keywords_enabled = keywordsenabled();
		if (!keywords_enabled) {
			keyword = '';
		}
	
		var quizheader = quizname;
		var quizingress = '';
		var quizfooter = 'Send SMS "STF '+keyword+'$qnum <svaralternativ>" til 2077';
		if (questionlist.length > 0) {
			if (questionlist[0].quizheader != '') {
				quizheader = questionlist[0].quizheader; 
			}
			if (questionlist[0].quizingress != '') {
				quizingress = questionlist[0].quizingress; 
			}
			if (questionlist[0].quizfooter != '') {
				quizfooter = questionlist[0].quizfooter; 
			}
		}
	}
	// Update the (hidden at the moment this occurs,) create pdf form
	$('input[name="header"]').val(quizheader);
	$('textarea[name="ingress"]').val(quizingress);
	$('input[name="footer"]').val(quizfooter);
	
	// Reset create PDF errorlist 
	$("span#createpdferror").html("");
	
    // Update links
	$('a#highscorelink').attr('href', 'highscore_template.php?quizid=' + quizid);
	
	// Get current quiz state
	var finished = $("select#quizname option:selected").hasClass('finished');
	var inactive = $("select#quizname option:selected").hasClass('inactive');
	var active = $("select#quizname option:selected").hasClass('active');
	
	// Update link and rebind related events
	$("a#changequizstate").unbind('click');
	if (active) {
		// TODO: if no answers, allow deactivate without ending
    	$('a#changequizstate').text('Deactivate and end');
    	// Update events
    	$("a#changequizstate").click(function(event) {
    		confirmendquiz(quizid);
    		return false;
    	});
    	// Hide/show links
		$('a#changequizstate').show();
		$('a#newquestion').hide();
		$('a#highscorelink').show();
		$('a#createpdf').show();
	}
    else if (inactive) {
    	$('a#changequizstate').text('Activate and lock');
    	// Update events (we do some client side validation here to see if there is already an active quiz with same keyword)
    	$("a#changequizstate").click(function(event) {
    		// Loop through all options in quiz list, looking for active quizzes with same keyword
    		var quizdata=[];
    		var isactive=false;
    		var samekeyword=false;
    		$('select#quizname > option').each(function() {
        		// Use regex previously defined to grab the keyword and states of quizzes in list
    			quizdata=grabinforegex.exec(this.text);
    			isactive=(quizdata[3] === "Active");
    			samekeyword=(quizdata[1] === keyword);
    			if (samekeyword && isactive) {
    	    		erroralreadyactive();
    				// Break out of loop
    				return false;
    			}
    		});
    		
    		if (!samekeyword || !isactive) {
    			confirmactivatequiz(quizid);
    		}
    		return false;
    	});
    	// Hide/show links
    	if (questionlist.length > 0) {
    		$('a#changequizstate').show();
    		$('a#createpdf').show();
    	}
    	else {
    		$('a#createpdf').hide();
    		$('a#changequizstate').hide();
    	}
		$('a#newquestion').show();
		$('a#highscorelink').hide();
    }
    else if (finished) {
    	// Hide/show links
    	$('a#changequizstate').hide();
    	$('a#newquestion').hide();
    	$('a#highscorelink').show();
		$('a#createpdf').show();
    }
}

function erroralreadyactive() {
	$( "#error-alreadyactivekeyword" ).dialog({
		resizable: false,
		modal: true,
		buttons: {
			"OK": function() {
				$( this ).dialog( "close" );
			}
		}
	});
}

function confirmactivatequiz(quizid) {
	$( "#confirm-activate" ).dialog({
		resizable: false,
		modal: true,
		buttons: {
			"Activate and lock": function() {
				activatequiz(quizid);
				$( this ).dialog( "close" );
			},
			Cancel: function() {
				$( this ).dialog( "close" );
			}
		}
	});
}

function confirmendquiz(quizid) {
	$( "#confirm-end" ).dialog({
		resizable: false,
		modal: true,
		buttons: {
			"Deactivate and end": function() {
				endquiz(quizid);
				$( this ).dialog( "close" );
			},
			Cancel: function() {
				$( this ).dialog( "close" );
			}
		}
	});
}

function activatequiz(quizid) {
	$.post("ajaxpages/activatequiz.php", {quizid: quizid}, function(j) {
		getQuizNames($('select#quizname').val());
	});
}

function deactivatequiz(quizid) {
	$.post("ajaxpages/deactivatequiz.php", {quizid: quizid}, function(j) {
		getQuizNames($('select#quizname').val());
	});
}

function endquiz(quizid) {
	$.post("ajaxpages/endquiz.php", {quizid: quizid}, function(j) {
		getQuizNames($('select#quizname').val());
	});
}

function editquestions(el) {
	var questionid = el.id.substring(12,el.id.length);
	var qidarray=questionid.split(".");
	var quizid=qidarray[0];
	var questionnumber=qidarray[1];
	
	$.getJSON("ajaxpages/getquestion.php", {quizid: quizid, questionnumber: questionnumber, ajax : 'true'}, function(j) {
		$("div#newquestionoverlay input#hiddenquestionnumber").val(j.questionnumber);
		$("div#newquestionoverlay input#hiddenquizid").val($("select#quizname").val());
		$("div#newquestionoverlay input#inputquestiontext").val(j.questiontext);
		$("div#newquestionoverlay input.newanswer").val("");
		$("div#newquestionoverlay input:radio").removeAttr("checked");
		if (j.answers) {
			for ( var k = 0; k < j.answers.length; k++) {
				$('div#newquestionoverlay input[name*="answer'+ (k + 1) + '"]').val(j.answers[k].answertext);
			}
		}
		$('div#newquestionoverlay input:radio[name*="correctanswer"]').filter("[value="+j.correctanswer+"]").prop("checked", true);
		
		$("div#newquestionoverlay").dialog("open");
	});
	return false;
}

function confirmdeletequestion(el) {
	$( "#dialog-confirm" ).dialog({
		resizable: false,
		modal: true,
		buttons: {
			"Delete": function() {
				deletequestion(el);
				$( this ).dialog( "close" );
			},
			Cancel: function() {
				$( this ).dialog( "close" );
			}
		}
	});	
}

function deletequestion(el) {
	var questionid = el.id.substring(14,el.id.length);
	var qidarray=questionid.split(".");
	var quizid=qidarray[0];
	var questionnumber=qidarray[1];
	var questionid = qidarray[2];

	$.post("ajaxpages/deletequestion.php", {quizid: quizid, questionid: questionid, ajax : 'true'}, function(j) {
		getQuestions($("div#questions"),$("select#quizname").val());
	});
}

function getHighScores(quiz) {
	$("#teamanswers_div").html('');
	$("span#choosewinnerinfo").html('');
	$.getJSON("ajaxpages/gethighscore.php", {quizid: quiz}, function(winnerlist) {
		// Check if we get something in return and if we do, show the link
		if ($.isPlainObject(winnerlist)) {
			$('a#choosewinner').show();
		}
		else {
			$('a#choosewinner').hide();
		}
		var data = new google.visualization.DataTable(winnerlist);
		data.addColumn('string', 'Team');
		data.addColumn('number', 'Score');
		data.addColumn('string', 'teamid');
		var maxscore= 0;
		var size = 0;
		for (num in winnerlist) {
			var srow=winnerlist[num];
			if (srow['score'] > maxscore) {
				maxscore = srow['score'];
			}
			data.addRow([srow['teamname'], srow['score'], srow['teamid']]);
			size++;
		}
		maxscore = maxscore+1;
		
		//1,2,3,7,11,12 will give non-integer axis labels with the default lines.
		if (maxscore < 4) {
			maxscore = 4;
		} else if (maxscore == 7) {
			maxscore = 8;
		} else if (maxscore == 11 || maxscore == 12) {
			maxscore=13;
		}
		
		
		var view=new google.visualization.DataView(data);
		view.setColumns([0,1]);

        var chartheight = size*10;
        var options = {
                legend: 'none',
                hAxis: {viewWindow: {min: 0, max: maxscore}, minValue: 0, viewWindowMode: 'explicit'},
                height: (chartheight+50),
                chartArea: {width: '70%', height: chartheight},
                fontSize: 10
        };
		// Empty the highscore table (fixes small bug where height of chart would increase on every quiz change)
		$('#highscoretable_div').empty();
		var chart = new google.visualization.BarChart(document.getElementById('highscoretable_div'));
		
		function scoreClicked() {
			var selectedItem = chart.getSelection()[0];
			if (typeof selectedItem !== 'undefined') {
				var teamid = data.getValue(selectedItem.row,2);
				getTeaminfoForQuiz(teamid, quiz);
			}
		}
		
		google.visualization.events.addListener(chart, 'select', scoreClicked);
		chart.draw(view, options);
		
		// Update (hidden) winner selection overlay
		fillMinimumCorrectOptions(winnerlist);
	});
}

function fillMinimumCorrectOptions(correctAnswers) {
	// Initialize an array containing the amount of correct answers as the key and amount of teams with this many correct answers as value
	var amountOfTeamsWithXCorrectAnswers = new Array();
	var maxCorrectAnswers = 0;
	for (num in correctAnswers) {
		var correct = correctAnswers[num];
		if (correct['score'] > maxCorrectAnswers) {
			// Used to figure out the maximum amount of teams who can win  
			maxCorrectAnswers = correct['score'];
		}
		if (amountOfTeamsWithXCorrectAnswers[correct['score']]) {
			// Increase amount of teams with certain amount of answers
			amountOfTeamsWithXCorrectAnswers[correct['score']]++;
		}
		else {
			// Initialize team amount with this many correct answers
			amountOfTeamsWithXCorrectAnswers[correct['score']] = 1;
		}
	}
	
	var addCorrect = 0;
	var options = '';
	var i;
	var teamsWithThisManyCorrectAnswersOrMore = 0;
	for (i = maxCorrectAnswers; i > 0; i--) {
		if (amountOfTeamsWithXCorrectAnswers[i]) {
			// Include all teams with more correct answers aswell
			teamsWithThisManyCorrectAnswersOrMore = amountOfTeamsWithXCorrectAnswers[i] + addCorrect;
			// Update regex tagged as @regexteamamount in code if you change the layout of this
			options += '<option value="' + i + '">'+i+' ('+teamsWithThisManyCorrectAnswersOrMore+' teams)</option>';
			addCorrect += amountOfTeamsWithXCorrectAnswers[i];
		}
	}
	
	$('div#choosewinneroverlay select#correctanswersneeded').html(options);
	
	fillAmountOfWinnersOptions(amountOfTeamsWithXCorrectAnswers[maxCorrectAnswers]);
}
function fillAmountOfWinnersOptions(maxAmount) {
	options = '';
	// Fill amount of winners - we hard cap the value to 20 if more than that amount of teams meet the requirements
	var maxcap = maxAmount > 20 ? 20 : maxAmount;
	for (i = 1; i <= maxcap; i++) {
		options += '<option value="' + i + '">'+i+'</option>';
	}
	$('div#choosewinneroverlay select#amountofwinners').html(options);
	$('div#choosewinneroverlay select#amountofwinners').val(maxcap);
}

function getTeaminfoForQuiz(teamid, quiz) {
	$.getJSON("ajaxpages/getteaminfoforquiz.php", {teamid: teamid, quizid: quiz, ajax : 'true'}, function(j) {
		var html ='';
		var phonenumbers = j['info']['phonenumbers'];
		var teamname = j['info']['teamname'];
		html += '<div class="header">';
		html += '<form id="editteamname">';
		html += '<a href="#" id="editteamnamelink"><h2 id="teamnameheader">' + teamname + '<span class="ui-icon ui-icon-wrench">edit</span></h2></a>';
		html += '<input id="teamidinput" type="hidden" value="'+ teamid + '"/>';
		html += '<input id="teamnameinput" value="'+ teamname + '"/>';
		html += '<div class="phone">Tel: '+phonenumbers.join(", ")+'</div>';
		html += '</form>';
		html += '</div>';
		var qa=j['answersarray'];
		for ( var k = 0; k < qa.length; k++) {
			q=qa[k];
			var corr = qa['correctanswer'];
			html += '<div class="question">';
			html += '<div class="answerheader"><strong>'+q['questionnumber']+'</strong> <span class="questiontext">'+q['questiontext']+'</span></div>';
			
			var teamanswers = new Array();
			// looping through answers given.
			if (q['teamanswers'] != undefined) {
				for ( var l = 0; l < q['teamanswers'].length; l++) {
					if (teamanswers[q['teamanswers'][l]] == undefined) {
						teamanswers[q['teamanswers'][l]] = 0;
					}
					teamanswers[q['teamanswers'][l]]++;
				}
			}
			html += '<div class="answers">';
			if (q.answers) {
				
				for (var l in q.answers) {
					if (q['answers'][l].answernumber == q.correctanswer) {
						html += "<div class=\"correct\">";
					} else {
						html += "<div>";
					}
					// Simple fix for request about using letters instead of numbers as answer alternatives @replacealphawithnumber
					// First answer number is 1. 1+64 = 65. 65 is the ascii code for 'A'
					html += '<strong>'+String.fromCharCode(parseInt(q.answers[l].answernumber)+64)+'</strong>:'+q.answers[l].answertext;
					if (teamanswers[q.answers[l].answernumber] != undefined) {
						for (var m =0; m<teamanswers[q.answers[l].answernumber]; m++) {
							html += '<span class="answered" />';
						}
					}
					html += "</div>";
				}
			}
			
			
			html += '</div>';
			html += '</div>';
					
		}
		$("#teamanswers_div").html(html);
		$("div#quizscore div#teamanswers_div a#editteamnamelink").click(function() {
			$("div#quizscore div#teamanswers_div h2#teamnameheader").hide();
			$("div#quizscore div#teamanswers_div input#teamnameinput").show();
		});
		$("div#quizscore div#teamanswers_div form#editteamname").submit(function() {
			
			var teamname = $("div#quizscore div#teamanswers_div input#teamnameinput").val();
			var teamid = $("div#quizscore div#teamanswers_div input#teamidinput").val();
			if (teamname.length < 1) {
				return false;
			}
			$.getJSON("ajaxpages/editteamname.php", {teamid: teamid, teamname: teamname, ajax : 'true'}, function(j) {
				$("div#quizscore div#teamanswers_div h2#teamnameheader").html($("div#quizscore div#teamanswers_div input#teamnameinput").val()+'<span class="ui-icon ui-icon-wrench">edit</span>');
				$("div#quizscore div#teamanswers_div input#teamnameinput").hide();
				$("div#quizscore div#teamanswers_div h2#teamnameheader").show();
			});
			return false;
		});

	});
}

// GET parameters from URL
function populateGet() {
	var obj = {}, params = location.search.slice(1).split('&');
	for(var i=0,len=params.length;i<len;i++) {
		var keyVal = params[i].split('=');
		obj[decodeURIComponent(keyVal[0])] = decodeURIComponent(keyVal[1]);
	}
	return obj;
}

$(document).ready(function() {
	var get=populateGet();
	if (get.quizid != undefined) {
		getQuizNames(get.quizid);
	}
	else {
		getQuizNames();
	}
	
	//quizadmin bindings
	$("div#quizadmin div#newquizoverlay").dialog({
		modal : true,
		title : 'New quiz',
		resizable : false,
		autoOpen : false,
		open : function(event, ui) {
			$('.ui-widget-overlay').bind('click', function() {
				$("div#newquizoverlay").dialog('close');
			});
		},
	});
	
	// Title is set dynamically based on which link you click (edit or new)
	$("div#quizadmin div#newquestionoverlay").dialog({
		modal : true,
		title: 'Question',
		resizable : false,
		autoOpen : false,
		minWidth : 600,
		open : function(event, ui) {
			$('.ui-widget-overlay').bind('click', function() {
				$("div#newquestionoverlay").dialog('close');
			});
		}
	});
	
	$( "div#quizadmin #questions" ).sortable({
		axis: 'y',
		opacity : '0.6',
		update: function(event, ui) {
			var order = $('#questions').sortable('serialize');
     		$.post("ajaxpages/sortquestions.php?"+order, function(data) {
     			getQuestions($("div#questions"),$("select#quizname").val());
     		});
		}
	});
	$( "div#quizadmin #questions" ).disableSelection();
	
	$("div#quizadmin div#createpdfoverlay").dialog({
		modal : true,
		title : 'Create PDF',
		resizable : false,
		autoOpen : false,
		minWidth : 600,
		open : function(event, ui) {
			$('.ui-widget-overlay').bind('click', function() {
				$("div#createpdfoverlay").dialog('close');
			});
		},
	});
	
	$("div#quizscore div#choosewinneroverlay").dialog({
		modal : true,
		title : 'Choose winner requirements',
		resizable : false,
		autoOpen : false,
		minWidth : 600,
		open : function(event, ui) {
			$('.ui-widget-overlay').bind('click', function() {
				$("div#choosewinneroverlay").dialog('close');
			});
		},
	});
	
	$("div#quizadmin a#newquiz").click(function() {
		$("div#newquizoverlay").dialog("open");
		return false;
	});
	
	$("button#addquiznamebutton").click(function() {
		var qkey=$("#inputquizkeyword").val();
		var qname=$("#inputquizname").val();
		var qkeymatch=qkey.match(/^[a-z0-9æÆøØåÅ]{1,20}$/i);
		
		if (qkeymatch && qname.length >= 1 && qname.length <= 45) {
			$.get("ajaxpages/addquizname.php", {quizname: qname, quizkeyword: qkey}, function() {
				getQuizNames();
				$("div#newquizoverlay").dialog("close");
				if (keywordsenabled()) {
					$("div#newquizoverlay input.inputquiz").val("");
				}
				else {
					$("div#newquizoverlay input#inputquizname").val("");
				}
				// Empty error list since we are successful
				$("span#newquizerror").html("");
			});
		}
		else {
			// Keyword must only contain letters and/or numbers.
			var newquizerror="<ul>";
			if (!qkeymatch) {
				newquizerror=newquizerror+"<li>Keyword must only contain letters and/or numbers, and must be between 1 and 20 characters.</li>";
			}
			// Quiz name is required
			if (qname.length < 1 || qname.length > 45) {
				newquizerror=newquizerror+"<li>Quiz name must be between 1 and 45 characters.</li>";
			}
			newquizerror=newquizerror+"</ul>";
			if (newquizerror != "<ul></ul>") {
				$("span#newquizerror").html(newquizerror);
			}
		}
		return false;
	});

	$("#newquestionform").submit(function() {
		var qtext=$("#inputquestiontext").val();
		var atext=$('input[name="answer1"]').val();
		var answertoolongorshort=false;
		var correctanswertooshort=false;
		// ?: First answer is required
		if (atext.length < 1) {
			answertoolongorshort=true;
		}
		else {
			var correctanswer=$('input[name="correctanswer"]:checked').val();
			for (i = 1; i <= $("div.answer").length; i++) {
				atext=$('input[name="answer'+i+'"]').val();
				// ?: Answer is too long
				if (atext.length > 500) {
					answertoolongorshort=true;
				}
				// ?: Length less than one and is correct answer?
				else if (atext.length < 1 && i == correctanswer) {
					correctanswertooshort=true;
				}
			}
		}
		// ?: All requirements met to proceed with adding the question?
		if (qtext.length <= 1000 && qtext.length >= 1 && !answertoolongorshort && !correctanswertooshort) {
			// -> Add question and close dialog when done
			$.post("ajaxpages/addquestion.php", $("#newquestionform").serialize(), function(data) {
				getQuestions($("div#questions"),$("select#quizname").val());
				$("div#newquestionoverlay").dialog("close");
				// Empty error list since we are successful
				$("span#newquestionerror").html("");
			});
		}
		else {
			// -> Requirements not met, let's see what's wrong
			var newquestionerror="<ul>";
			if (qtext.length > 1000 || qtext.length < 1) {
				newquestionerror=newquestionerror+"<li>Question text must be between 1 and 1000 characters.</li>";
			}
            if (answertoolongorshort) {
				newquestionerror=newquestionerror+"<li>Answers must be between 1 and 1000 characters.</li>";
			}
            if (correctanswertooshort) {
				newquestionerror=newquestionerror+"<li>Answer marked as correct must contain an actual answer.</li>";
			}
			newquestionerror=newquestionerror+"</ul>";
			if (newquestionerror != "<ul></ul>") {
				$("span#newquestionerror").html(newquestionerror);
			}
		}
		return false;
	});
	
	$("div#quizadmin select#quizname").change(function() {
		// Show questions for selected quiz
		getQuestions($("div#questions"),$("select#quizname").val());
	});
	
	$("div#choosewinneroverlay select#correctanswersneeded").change(function() {
		// Update max amount of winners possible based on selected option
		var selected=$('select#correctanswersneeded option:selected').text();
		// In short, this regex @regexteamamount matches this format (example): 4 (2 teams)
		// The numbers can be any amount higher than 0
		var grabteamamountregex=/^[1-9][0-9]* \(([1-9][0-9]*) teams\)$/;
		var teamamount=grabteamamountregex.exec(selected);
		fillAmountOfWinnersOptions(teamamount[1]);
	});
	
	$("div#choosewinneroverlay button#choosewinnerbutton").click(function() {
		// Fetch selected options from input
		var prioritizemostcorrect = $('input#prioritizemostcorrect').is(':checked');
		var correctanswersneeded = $('select#correctanswersneeded').val();
		var amountofwinners = $('select#amountofwinners').val();
		var quizid = $('select#quizname').val();

		$.getJSON("ajaxpages/getwinners.php", {quizid: quizid, correctanswersneeded: correctanswersneeded, amountofwinners: amountofwinners, priomostcorrect: prioritizemostcorrect }, function(winnerlist) {
			var scoretable = '';
			if (winnerlist) {
				scoretable += '<table border="0" width="100%">';
				scoretable += ' <thead class="ui-widget-header">';
				scoretable += '  <tr>';
				scoretable += '   <th>Pos.</th>';
				scoretable += '   <th>Name</th>';
				scoretable += '   <th>Correct answers</th>';
				scoretable += '   <th>Phone number</th>';
				scoretable += '  </tr>';
				scoretable += ' </thead>';
				scoretable += ' <tbody class="ui-widget-content">';
				for ( var i = 0; i < winnerlist.length; i++) {
					scoretable += '  <tr>';
					scoretable += '   <td>#'+(i+1)+'</td>';
					scoretable += '   <td>'+winnerlist[i].teamname+'</td>';
					scoretable += '   <td>'+winnerlist[i].correct+'</td>';
					scoretable += '   <td>'+winnerlist[i].phonenumbers.join(", ")+'</td>';
					scoretable += '  </tr>';
				}
				scoretable += ' </tbody>';
				scoretable += '</table>'
			}
			else {
				scoretable += '<ul><li>Unable to get winner data.</li></ul>';
			}
			$("span#choosewinnerinfo").html(scoretable);
		});
		return false;
	});
	
	$("div#quizadmin a#newquestion").click(function() {
		$("div#newquestionoverlay").dialog("option", "title", "New question");
		$("div#newquestionoverlay button#submitnewquestionbutton").text("Add question");
		$("div#newquestionoverlay input#inputquestiontext").val("");
		$("div#newquestionoverlay input.newanswer").val("");
		$("div#newquestionoverlay input:radio").removeAttr("checked");
		$("div#newquestionoverlay input#hiddenquestionnumber").val("");
		$("div#newquestionoverlay input#hiddenquizid").val($("select#quizname").val());
		$("div#newquestionoverlay").dialog("open");
		return false;
	});
	
	$("div#quizadmin a#createpdf").click(function() {
		$("div#createpdfoverlay").dialog("open");
		return false;
	});
	
	$("div#quizscore a#choosewinner").click(function() {
		$("div#choosewinneroverlay").dialog("open");
		return false;
	});
	
	$("#createpdfsubmit").click(function() {
		var header=$('input[name="header"]').val();
		var footer=$('input[name="footer"]').val();
		var questions=$('div#questions > div.question');
		
		// ?: All requirements met to proceed with PDF creation?
		if (header.length > 0 && footer.length > 0 && questions.size() > 0) {
			// -> Open in new window
			this.form.target='_blank';
			return true;			
		}
		else {
			// -> Requirements not met, let's see what's wrong
			var createpdferror="<ul>";
			if (header.length <= 0) {
				createpdferror=createpdferror+"<li>Header can not be empty.</li>";
			}
            if (footer.length <= 0) {
            	createpdferror=createpdferror+"<li>Footer can not be empty.</li>";
			}
            if (questions.size() <= 0) {
            	createpdferror=createpdferror+"<li>Can not generate PDF when no questions in quiz.</li>";
			}
            // Finish error list and display
            createpdferror=createpdferror+"</ul>";
			if (createpdferror != "<ul></ul>") {
				$("span#createpdferror").html(createpdferror);
			}
			return false;
		}
	});

	//highscore bindings
	
	 $("div#quizscore select#quizname").change(function() {
		 getHighScores($("select#quizname").val());
	 });
});