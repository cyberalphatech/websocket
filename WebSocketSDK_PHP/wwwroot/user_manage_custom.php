<html>
<head>
	<meta charset="utf-8">
	<link rel="stylesheet" type="text/css" href="css/style.css">
	<script src="js/xml.js"></script>
	<script src="js/utf16.js"></script>
	<script src="js/ws_client.js"></script>
	<script type="text/javascript">

		function on_error_report(xml) {
			var err = "";
			var x = xml.getElementsByTagName("Error");
			if (x.length > 0)
				err = "Error: " + x[0].childNodes[0].nodeValue;
			if (err != "")
				document.getElementById("result").innerHTML = err;
		}

		function check_user_id(str) {

			if (str.length < 1)
			{
				set_result("Please input user id.");
				return false;
			}

			return true;
		}

		function on_get_user_attend_only(xml) {
			var x;
			var txt = "GetUserAttendOnly: ";

			var success = false;
			x = xml.getElementsByTagName("Result");
			if (x.length > 0)
			{
				txt += "Result = " + x[0].childNodes[0].nodeValue;
				success = (x[0].childNodes[0].nodeValue == "OK");
			}

			// AttendOnly
			var attend_only = false;
			x = xml.getElementsByTagName("Value");
			if (x.length > 0 && x[0].childNodes[0].length > 0)
				attend_only = (x[0].childNodes[0].nodeValue == "Yes");

			if (success)
			{
				document.getElementById("attend_only").checked = attend_only;
			}

			set_result(txt);
		}

		function get_user_attend_only() {

			if (!check_user_id(document.getElementById("user_id").value))
				return;

			var doc = document.implementation.createDocument("", "", null);
			
			var messageElem = doc.createElement("Message");
			var requestElem = doc.createElement("Request");
			requestElem.innerHTML = "GetUserAttendOnly";
			messageElem.appendChild(requestElem);
			
			var useridElem = doc.createElement("UserID");
			useridElem.innerHTML = document.getElementById("user_id").value;
			messageElem.appendChild(useridElem);

			send_relay_message(doc, document.getElementById("session").value, messageElem);
			set_result("");
		}

		function on_set_user_attend_only(xml) {
			var x;
			var txt = "SetUserAttendOnly: ";

			x = xml.getElementsByTagName("Result");
			if (x.length > 0)
				txt += "Result = " + x[0].childNodes[0].nodeValue;

			x = xml.getElementsByTagName("Error");
			if (x.length > 0)
				txt += ",  Error = " + x[0].childNodes[0].nodeValue;

			set_result(txt);
		}

		function set_user_attend_only() {

			if (!check_user_id(document.getElementById("user_id").value))
				return;

			var doc = document.implementation.createDocument("", "", null);

			var messageElem = doc.createElement("Message");
			var requestElem = doc.createElement("Request");
			requestElem.innerHTML = "SetUserAttendOnly";
			messageElem.appendChild(requestElem);

			var useridElem = doc.createElement("UserID");
			useridElem.innerHTML = document.getElementById("user_id").value;
			messageElem.appendChild(useridElem);

			var attendOnlyElem = doc.createElement("Value");
			if (document.getElementById("attend_only").checked)
				attendOnlyElem.innerHTML = "Yes";
			else
				attendOnlyElem.innerHTML = "No";
			messageElem.appendChild(attendOnlyElem);

   			send_relay_message(doc, document.getElementById("session").value, messageElem);
			set_result("");
		}

		function init() {
			var port = <?php include '../config.inc.php'; echo $Port; ?>;
			var use_wss = <?php include '../config.inc.php'; echo $Use_WSS; ?>;
			ws_init(port, use_wss);

			// Set event handlers.
			ws.onmessage = function(e) {
				// e.data contains received string.
				var xml = parseXml (e.data);
				
				var res = "";
				if (xml.getElementsByTagName("Response").length > 0)
					res = xml.getElementsByTagName("Response")[0].childNodes[0].nodeValue;
				
				if (res == "ErrorReport")
					on_error_report(xml);
				else if (res == "GetUserAttendOnly")
					on_get_user_attend_only(xml);
				else if (res == "SetUserAttendOnly")
					on_set_user_attend_only(xml);
			};
		}

		function set_result(str) {
			document.getElementById("result").innerHTML = str;
		}

	</script>
</head>

<body onload="init();" onunload="ws_exit();">
	<div id="result" class="result"></div>
	<table>
		<tr>
			<td><button style="width:210px" onclick="get_user_attend_only(); return false;">GetUserAttendOnly</button></td>
			<td>User ID:</td>
			<td><input type="text" id="user_id" /></td>
		</tr>
		<tr>
			<td><button style="width:210px" onclick="set_user_attend_only(); return false;">SetUserAttendOnly</button></td>
			<td>Enabled:</td>
			<td><input type="checkbox" id="attend_only" checked/></td>
		</tr>
	</table>

	<input type="hidden" id='session' name='session' value='<?php echo $_GET["session"]; ?>'></input>
</body>
</html>