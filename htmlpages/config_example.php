<?php
$config['pswin_username'] = '';
$config['pswin_password'] = '';
// Sender number is the number (or string, max 8(?) chars) the service will pretend to be - recommend this be the same as servicenumber
$config['pswin_sendernumber'] = '2077';
// Service number is the actual number the service receives messages at
$config['pswin_servicenumber'] = '2077';
// Tariff is the cost pr. message
$config['pswin_tariff'] = 0; // øre

// Enable sub-keyword support?
$config['keywords_enabled'] = false;
// Keyword for SMS service
$config['keywords_default'] = 'STF';

// Set host and port for websocket server (MMSadmin)
$config['ws_ip_lan_bind'] = '127.0.0.1';
$config['ws_ip_wan_bind'] = '127.0.0.1';
$config['ws_port'] = '7678';

// MMS related options
$config['mms_folder'] = __DIR__.DIRECTORY_SEPARATOR.'mms'; // Make sure the webserver has write access to this folder

// Most of these are SMS related. Avoid messages with a length higher than 160 characters, as that will cause the message to be sent over two or more SMS
$config['lang_no_invalidkeyword'] = 'Stavanger Turistforening har for øyeblikket ingen aktive quiz.';
$config['lang_no_invalidansweralternativeprovided'] = 'Du må angi et gyldig svaralternativ! Send STF <spørsmålsnummer> <svaralternativ> til '.$config['pswin_servicenumber'].'.';
$config['lang_no_invalidquestionnumberprovided'] = 'Du må angi et gyldig spørsmålsnummer! Send STF <spørsmålsnummer> <svaralternativ> til '.$config['pswin_servicenumber'].'.';
$config['lang_no_invalidquestionnumberoranswer'] = 'Du har oppgitt et ugyldig spørsmålnummer ($questionnumber$) eller svaralternativ ($answer$)!';
$config['lang_no_registeredanswer'] = 'Ditt svar ($answer$) på spørsmål $questionnumber$ er registrert! Svar på flere spørsmål for å øke dine vinnersjanser.';
$config['lang_no_unknownformat'] = 'For å svare på et spørsmål, send STF <spørsmålsnummer> <svaralternativ> til '.$config['pswin_servicenumber'].'.';
$config['lang_no_mmsreceived'] = 'Din MMS er mottatt og lagt til i køen for godkjenning.';

// Don't change anything below this line 
if (!key_exists('ws_ip_lan_bind', $config)) {
	$config['ws_ip_lan_bind'] = gethostbyname(php_uname('n'));
}