function getQuizNames(selecttag) {
	$.getJSON("ajaxpages/getquiznames.php", {ajax : 'true'}, function(j) {
		var options = '';
		for ( var i = 0; i < j.length; i++) {
			options += '<option value="' + j[i].quizid + '">'
					+ j[i].quizname + '</option>';
		}
		$(selecttag).html(options);
		var lastValue = $('select#quizname option:last-child').val();
		$("select#quizname").val(lastValue);
		getQuestions($("div#questions"),$("select#quizname").val());
	});
}
function getQuestions(resultdiv, quiz) {
	$.getJSON("ajaxpages/getquestions.php", {quizid: quiz, ajax : 'true'}, function(j) {
		var questions = '';
		for (var i = 0; i<j.length; i++) {
			questions += "<div class=\"question\" id=\"q_"+j[i].idquestions+"\">";
			questions += "<div class=\"answerheader\">";
			questions += "<a href=\"#\" id=\"editquestion"+j[i].idquestions+"\" class=\"nounderline editanswer\">";
			questions += "<strong>"+j[i].questionnumber+"</strong> <span class=\"questiontext\" id=\"questiontext"+j[i].idquestions+"\">"+j[i].questiontext+"</span>";
			questions += "<span class=\"ui-icon ui-icon-wrench\">edit</span>";
			questions += "</a>";
			questions += "<a href=\"#\" id=\"deletequestion"+j[i].idquestions+"\" class=\"nounderline deleteanswer\">";
			questions += "<span class=\"ui-icon ui-icon-trash deleteanswer\">delete</span>";
			questions += "</a>";
			questions += "</div>";
			questions += "<div class=\"answers\">";
			if (j[i].answers) {
				for (var k = 0; k<j[i].answers.length; k++) {
					if (j[i].answers[k].answernumber == j[i].correctanswer) {
						questions += "<div class=\"correct\">";
					} else {
						questions += "<div>";
					}
					questions += '<strong>'+j[i].answers[k].answernumber+'</strong>:'+j[i].answers[k].answertext;
					questions += "</div>";
				}
			}
			questions += '</div>';
			questions += '</div>';
		}
		$(resultdiv).html(questions);
		$("a.editanswer").click(function(event) {
			editquestions(this);
			return false;
		});
		$("a.deleteanswer").click(function(event) {
			confirmdeletequestion(this);
			return false;
		})
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

$(document).ready(function() {
	getQuizNames("select#quizname");
	$("div#newquizoverlay").dialog({
		modal : true,
		title : 'New quiz',
		resizable : false,
		autoOpen : false,
		open : function(event, ui) {
			$('.ui-widget-overlay').bind('click', function() {
				$("div#newquizoverlay").dialog('close');
			});
		},
		close : function(event, ui) { getQuizNames("select#quizname") }
	});
	
	$("div#newquestionoverlay").dialog({
		modal : true,
		title : 'New question',
		resizable : false,
		autoOpen : false,
		minWidth : 600,
		open : function(event, ui) {
			$('.ui-widget-overlay').bind('click', function() {
				$("div#newquestionoverlay").dialog('close');
			});
		},
		close : function(event, ui) {getQuestions($("div#questions"),$("select#quizname").val()) }
	});
	

	$("a#newquiz").click(function() {
		$("div#newquizoverlay").dialog("open");
		return false;
	});
	
	$("button#addquiznamebutton").click(function() {
		$.getJSON("ajaxpages/addquizname.php", {quizname : $("#inputquizname").val(), ajax : 'true'});
		$("div#newquizoverlay").dialog("close");
		return false;
	});

	$("#newquestionform").submit(function() {
		//TODO: validation here.
		$.post("ajaxpages/addquestion.php", $("#newquestionform").serialize(), function(data) {
			$("div#newquestionoverlay").dialog("close");
		});
		return false;
	});
	
	
	$("select#quizname").change(function() {
		getQuestions($("div#questions"),$("select#quizname").val());
	});
	
	$("a#newquestion").click(function() {
		$("div#newquestionoverlay input#inputquestiontext").val("");
		$("div#newquestionoverlay input.newanswer").val("");
		$("div#newquestionoverlay input:radio").removeAttr("checked");
		$("div#newquestionoverlay input#hiddenquestionnumber").val("");
		$("div#newquestionoverlay input#hiddenquizid").val($("select#quizname").val());
		$("div#newquestionoverlay").dialog("open");
		return false;
	});
	
	$( "#questions" ).sortable({
		axis: 'y',
		opacity : '0.6',
		update: function(event, ui) {
			var order = $('#questions').sortable('serialize');
     		$.post("ajaxpages/sortquestions.php?"+order, function(data) {
     			getQuestions($("div#questions"),$("select#quizname").val());	
     		});
		}
	});
	$( "#questions" ).disableSelection();

});