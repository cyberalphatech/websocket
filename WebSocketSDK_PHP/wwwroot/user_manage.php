<html>
<head>
	<meta charset="utf-8">
	<link rel="stylesheet" type="text/css" href="css/style.css">
	<script src="js/xml.js"></script>
	<script src="js/utf16.js"></script>
	<script src="js/ws_client.js"></script>
	<script type="text/javascript">

		const EnrollMagic = 0x454E524F; //ENRO
		const MaxUserPasswordLength = 6;
		const MaxUserNameLength = 24;
		const FaceDataSize = 27668;
		const FpDataSize = 1404;
		var all_user_data = Array();
		var all_user_working = false;
		var all_user_data_pos = 0;
		var g_remaining_user_count = 0;
		var g_sent_user_count = 0;
		var g_total_user_count = 0;
		var g_read_user_count = 0;
		var g_enroll_mask = 0;
		var g_duress_mask = 0;
		var g_current_user_id = "";
		var g_current_priv = 0;
		var g_no_more_user = false;
		var enrolldir = null;
		var enrollfileHandle = null;

		var timer = null;
		var timer_working = false;
		var step = "";
		var ready = false;
		var canceled = false;

		const UserPrivs = ["User", "Manager", "Administrator"];

		function on_error_report(xml) {
			var err = "";
			var x = xml.getElementsByTagName("Error");
			if (x.length > 0)
				err = "Error: " + x[0].childNodes[0].nodeValue;
			if (err != "")
				document.getElementById("result").innerHTML = err;

			all_user_working = false;
			if (timer_working)
				timer_working = false;
		}

		function check_user_id(str) {

			if (str.length < 1)
			{
				set_result("Please input user id.");
				return false;
			}

			return true;
		}

		function dump_integer(val, byte_count) {
			for (var i = 0; i < byte_count; i++)
			{
				var v = val % 256;
				all_user_data.push(v);
				val = (val - v) / 256;
			}
		}

		function read_integer(byte_count) {
			var ret = 0;
			if (all_user_data_pos + byte_count > all_user_data.length)
				throw new Error("Invalid data length");

			for (var i = 0; i < byte_count; i++)
			{
				ret = ret * 256;
				ret = ret + all_user_data[all_user_data_pos + byte_count - 1 - i];
			}
			all_user_data_pos += byte_count;
			return ret;
		}

		function dump_str(str, len) {
			var strlen = str.length;
			if (strlen > len)
				strlen = len;

			var i = 0;
			for (; i < strlen; i++)
			{
				c = str[i].charCodeAt(0);
				
				var ch = c >> 8;
				var cl = c & 0xff;
				
				all_user_data.push(cl);
				all_user_data.push(ch);
			}

			for (; i <= len; i++)
			{
				all_user_data.push(0);
				all_user_data.push(0);
			}
		}

		function read_str(len) {
			var ret = "";

			if (all_user_data_pos + (len + 1) * 2 > all_user_data.length)
				throw new Error("Invalid data length");

			for (var i = 0; i < len; i++)
			{
				var c = all_user_data[all_user_data_pos + i * 2] + all_user_data[all_user_data_pos + i * 2 + 1] * 256;
				if (c == 0)
					break;
				ret = ret + String.fromCharCode(c);
			}
			
			all_user_data_pos += (len + 1) * 2;

			return ret;
		}

		function dump_array(arr) {
			for (var i = 0; i < arr.length; i++)
			{
				all_user_data.push(arr[i]);
			}
		}

		function read_array(len) {
			if (all_user_data_pos + len > all_user_data.length)
				throw new Error("Invalid data length");

			var arr = Array();
			for (var i = 0; i < len; i++)
				arr.push(all_user_data[all_user_data_pos++]);
			return arr;
		}

		function convert_string_to_password(str, len) {
			var ret = 0;
			if (len == 0 || str.length > len)
				return ret;

			for (var i = 0; i < str.length; i++)
			{
				var c = str[str.length - 1 - i];
				ret = ret << 4;
				if (c >= '0' && c <= '9')
					ret = ret + (c - '0' + 1);
				else
					return 0;
			}

			return ret;
		}

		function convert_password_to_string(pwd) {
			
			var ret = "";
			while (pwd > 0)
			{
				var c = pwd & 0x0F;
				pwd = pwd >> 4;
				ret = ret + String.fromCharCode(0x30 + c - 1);
			}
			return ret;
		}
		
		function dump_one_user(userid, username, priv, depart, card, pwd, qr, enabled, face, fpmask, tz,
								userperiod_use, userperiod_start, userperiod_end) {
			// convert pwd to integer
			var pwd_val = convert_string_to_password(pwd, MaxUserPasswordLength);

			// merge tz array to integer
			var tz_all = 0;
			for (var i = 5; i >= 1; i--)
			{
				tz_all = (tz_all << 6);
				tz_all = tz_all + tz[i];
			}

			// convert priv to integer
			var priv_val = 0;
			if (priv == "Manager")
				priv_val = 1;
			else if (priv == "Administrator")
				priv_val = 2;

			var enroll_mask = 0;
			var duress_mask = 0;

			for (var i = 0; i < 10; i++)
			{
				if (fpmask & (1 << (i * 2)))
					enroll_mask |= (1 << i);
				if (fpmask & (1 << (i * 2 + 1)))
					duress_mask |= (1 << i);
			}
			
			if (face)
				enroll_mask |= (1 << 13);
			
			g_enroll_mask = enroll_mask;

			if (pwd_val != 0)
				enroll_mask |= (1 << 10);
			if (card != 0)
				enroll_mask |= (1 << 11); 
			if (qr != 0)
				enroll_mask |= (1 << 14);  
			dump_str(userid, 20);	// NOTE: If you want to export exactly same ENROLLDB.dat file which generated by terminal, please convert base36 encoded UserID to 64/128bit integer, and save 8/16 byte data.
			dump_integer(card, 4);

			dump_integer(pwd_val, 4);

			dump_integer(enroll_mask, 2);
			dump_integer(duress_mask, 2);

			dump_integer(0, 4); // photo_id, reserved to 0
			dump_integer(enabled, 1);
			dump_integer(priv_val, 1);
			dump_integer(depart, 1);
			dump_integer(0, 1); // message_id, reserved to 0

			dump_integer(tz_all, 4);
			dump_str(username, MaxUserNameLength);
           
			dump_integer(0, 6); // padding 6 bytes

            // Not compatible with U-disk down/upload
			dump_integer(userperiod_use, 1);
			dump_integer(userperiod_start, 4);
			dump_integer(userperiod_end, 4);
			dump_integer(qr, 4);
		}

		function get_user_backup() {
			for (var fpno = 0; fpno < 10; fpno++)
			{
				if (g_enroll_mask & (1 << fpno))
				{
					get_finger_data(g_current_user_id, fpno);
					return;
				}
			}

			if (g_enroll_mask & (1 << 13))
			{
				get_face_data(g_current_user_id);
				return;
			}

			get_photo_data(g_current_user_id);
		}

		function on_get_user_data(xml, op, one_step) {
			var x;
			var txt = op + ": ";

			var success = false;
			x = xml.getElementsByTagName("Result");
			if (x.length > 0)
			{
				txt += "Result = " + x[0].childNodes[0].nodeValue;
				success = (x[0].childNodes[0].nodeValue == "OK");
			}

			// id
			var userid = "";
			x = xml.getElementsByTagName("UserID");
			if (x.length > 0 && x[0].childNodes[0].length > 0)
				userid = x[0].childNodes[0].nodeValue;

			// name
			var username = "";
			x = xml.getElementsByTagName("Name");
			if (x.length > 0 && x[0].childNodes[0].length > 0)
				username = utf16Decode(atob(x[0].childNodes[0].nodeValue));

			// Privilege
			var priv = "User";
			x = xml.getElementsByTagName("Privilege");
			if (x.length > 0 && x[0].childNodes[0].length > 0)
				priv = x[0].childNodes[0].nodeValue;

			// Depart
			var depart = 0;
			x = xml.getElementsByTagName("Depart");
			if (x.length > 0 && x[0].childNodes[0].length > 0)
				depart = parseInt(x[0].childNodes[0].nodeValue);

			// Card
			var card = 0;
			x = xml.getElementsByTagName("Card");
			if (x.length > 0 && x[0].childNodes[0].length > 0)
				card = convertToInteger(atob(x[0].childNodes[0].nodeValue));

			// Pwd
			var pwd = "";
			x = xml.getElementsByTagName("PWD");
			if (x.length > 0 && x[0].childNodes[0].length > 0)
				pwd = x[0].childNodes[0].nodeValue;

			// QR
			var qr = 0;
			x = xml.getElementsByTagName("QR");
			if (x.length > 0 && x[0].childNodes[0].length > 0)
				qr = convertToInteger(atob(x[0].childNodes[0].nodeValue));

			// Enabled
			var enabled = false;
			x = xml.getElementsByTagName("Enabled");
			if (x.length > 0 && x[0].childNodes[0].length > 0)
				enabled = (x[0].childNodes[0].nodeValue == "Yes");

			var tz = [0, 0, 0, 0, 0, 0];
			// TimeSet
			for (var i = 1; i <= 5; i++)
			{
				tz[i] = 0;
				x = xml.getElementsByTagName("TimeSet" + i);
				if (x.length > 0 && x[0].childNodes[0].length > 0)
					tz[i] = parseInt(x[0].childNodes[0].nodeValue);			
			}

			var userperiod_use = false;
			x = xml.getElementsByTagName("UserPeriod_Used");
			if (x.length > 0 && x[0].childNodes[0].length > 0)
				userperiod_use = (x[0].childNodes[0].nodeValue == "Yes");

			var userperiod_start = 0;
			x = xml.getElementsByTagName("UserPeriod_Start");
			if (x.length > 0 && x[0].childNodes[0].length > 0)
				userperiod_start = parseInt(x[0].childNodes[0].nodeValue);

			var userperiod_end = 0;
			x = xml.getElementsByTagName("UserPeriod_End");
			if (x.length > 0 && x[0].childNodes[0].length > 0)
				userperiod_end = parseInt(x[0].childNodes[0].nodeValue);

			// FP
			var fpmask = 0;
			x = xml.getElementsByTagName("Fingers");
			if (x.length > 0 && x[0].childNodes[0].length > 0)
				fpmask = parseInt(x[0].childNodes[0].nodeValue);

			// Face
			var faceexist = false;
			x = xml.getElementsByTagName("FaceEnrolled");
			if (x.length > 0 && x[0].childNodes[0].length > 0)
				faceexist = (x[0].childNodes[0].nodeValue == "Yes");

			if (all_user_working)
			{
				dump_one_user(userid, username, priv, depart, card, pwd, qr, enabled, faceexist, fpmask, tz,
								userperiod_use, userperiod_start, userperiod_end);
				g_current_user_id = userid;

				val = "Yes";
				x = xml.getElementsByTagName("More");
				if (x.length > 0 && x[0].childNodes[0].length > 0)
					val = x[0].childNodes[0].nodeValue;

				g_no_more_user = false;
				if (val != "Yes")
					g_no_more_user = true;

				if (timer_working)
				{
					g_read_user_count++;
					set_result("Get All User Data: Reading " + g_read_user_count + "/" + g_total_user_count);

					ready = true;
					step = "get_user_backup";					
				}
				else
				{
					get_user_backup();
				}
			}
			else
			{
				if (success)
				{
					document.getElementById("user_id").value = userid;
					document.getElementById("user_name").value = username;
					document.getElementById("priv").value = priv;
					document.getElementById("depart").value = depart;
					document.getElementById("card").value = card;
					document.getElementById("qr").value = qr;
					document.getElementById("pwd").value = pwd;			
					document.getElementById("enabled").checked = enabled;
					document.getElementById("face_exist").checked = faceexist;

					for (var i = 0; i < 10; i++)
					{
						if (fpmask & (1 << (i * 2)))
							document.getElementById("fp_exist" + i).checked = true;
						else
							document.getElementById("fp_exist" + i).checked = false;

						if (fpmask & (1 << (i * 2 + 1)))
							document.getElementById("fp_duress" + i).checked = true;
						else
							document.getElementById("fp_duress" + i).checked = false;
					}

					for (var i = 1; i <= 5; i++)
						document.getElementById("tz" + i).value = tz[i];

					document.getElementById("ChkUserPeriodUse").checked = userperiod_use;
					document.getElementById("TextUserPeriodStart_y").value = (userperiod_start >> 16) + 2000;
					document.getElementById("TextUserPeriodStart_m").value = (userperiod_start & 0xFF00) >> 8;
					document.getElementById("TextUserPeriodStart_d").value = (userperiod_start & 0xFF);
					document.getElementById("TextUserPeriodEnd_y").value = (userperiod_end >> 16) + 2000;
					document.getElementById("TextUserPeriodEnd_m").value = (userperiod_end & 0xFF00) >> 8;
					document.getElementById("TextUserPeriodEnd_d").value = (userperiod_end & 0xFF);
				}

				if (!one_step)
				{
					val = "Yes";
					x = xml.getElementsByTagName("More");
					if (x.length > 0 && x[0].childNodes[0].length > 0)
						val = x[0].childNodes[0].nodeValue;

					if (val != "Yes")
						txt += "  [Last User!!!]";
				}
				set_result(txt);
			}
		}

		function get_user_data() {

			if (!check_user_id(document.getElementById("user_id").value))
				return;

			var doc = document.implementation.createDocument("", "", null);
			
			var messageElem = doc.createElement("Message");
			var requestElem = doc.createElement("Request");
			requestElem.innerHTML = "GetUserData";
			messageElem.appendChild(requestElem);
			
			var useridElem = doc.createElement("UserID");
			useridElem.innerHTML = document.getElementById("user_id").value;
			messageElem.appendChild(useridElem);

			send_relay_message(doc, document.getElementById("session").value, messageElem);
			set_result("");
		}

		function get_first_user_data() {
			var doc = document.implementation.createDocument("", "", null);
			
			var messageElem = doc.createElement("Message");
			var requestElem = doc.createElement("Request");
			requestElem.innerHTML = "GetFirstUserData";
			messageElem.appendChild(requestElem);

			send_relay_message(doc, document.getElementById("session").value, messageElem);
			if (!timer_working)
				set_result("");
		}

		function get_next_user_data(userid) {

			if (!check_user_id(userid))
				return;

			var doc = document.implementation.createDocument("", "", null);
			
			var messageElem = doc.createElement("Message");
			var requestElem = doc.createElement("Request");
			requestElem.innerHTML = "GetNextUserDataExt";
			messageElem.appendChild(requestElem);
			
			var useridElem = doc.createElement("UserID");
			useridElem.innerHTML = userid;
			messageElem.appendChild(useridElem);

			send_relay_message(doc, document.getElementById("session").value, messageElem);
			if (!timer_working)
				set_result("");
		}

		function on_get_user_count(xml) {
			var x;

			x = xml.getElementsByTagName("ParamName");
			if (x.length > 0 && 
				x[0].childNodes.length > 0 &&
				x[0].childNodes[0].nodeValue == "UserCount")
			{
				var count = 0;
				x = xml.getElementsByTagName("Value");
				if (x.length > 0)
					count = parseInt(x[0].childNodes[0].nodeValue);

				dump_integer(EnrollMagic, 4);
				dump_integer(count, 4);

				if (timer_working)
				{
					if (count == 0)
					{
						set_result("Get All User Data: No User Enrolled!");
						ready = true;
						step = "enable_device";
					}
					else
					{
						set_result("Get All User Data: Get Count = " + count);
						g_total_user_count = count;
						g_read_user_count = 0;
						ready = true;
						step = "get_first_user_data";
					}
				}
				else
				{
					if (count == 0)
						set_result("Get All User Data: No User Enrolled!");
					else
						get_first_user_data();
				}
				
			}
		}

		function get_user_count() {
			var doc = document.implementation.createDocument("", "", null);
			
			var messageElem = doc.createElement("Message");
			var requestElem = doc.createElement("Request");
			requestElem.innerHTML = "GetDeviceStatus";
			messageElem.appendChild(requestElem);
			
			var paramElem = doc.createElement("ParamName");
			paramElem.innerHTML = "UserCount";
			messageElem.appendChild(paramElem);

			send_relay_message(doc, document.getElementById("session").value, messageElem);
			if (!timer_working)
				set_result("");
		}

		async function get_all_user_data() {
			all_user_working = true;
			all_user_data = Array();
			enrolldir = await window.showDirectoryPicker();
			enrollfileHandle = await enrolldir.getFileHandle("ENROLLDB.dat", { create: true });

			get_user_count();
		}
		
		async function send_one_backup() {
			// check fingerprints
			try
			{
				for (var fpno = 0; fpno < 10; fpno++)
				{
					if (g_enroll_mask & (1 << fpno))
					{
						g_enroll_mask &= ~(1 << fpno);
						send_one_fp_data(fpno);
						return;
					}
				}
			
				// check face
				if (g_enroll_mask & (1 << 13))
				{
					g_enroll_mask &= ~(1 << 13);
					send_one_face_data();
					return;
				}
			}
			catch
			{
				set_result("Set All User Data Failed.");
				all_user_working = false;
				return;
			}

			// check photo
			var ret = await send_one_photo_data();
			if (!ret)
				send_next_user();			
		}

		function set_one_user() {
			try
			{
				g_current_user_id = read_str(20);
				var card = read_integer(4);
				var pwd = read_integer(4);
				var qr = read_integer(4); 
				g_enroll_mask = read_integer(2);
				g_duress_mask = read_integer(2);
				var photoid = read_integer(4);
				var enabled = read_integer(1);
				g_current_priv = read_integer(1);
				var depart = read_integer(1);
				var messageid = read_integer(1);
				var tzval = read_integer(4);
				var username = read_str(MaxUserNameLength);
				read_integer(6);			

				var userperiod_use = read_integer(1);
				var userperiod_start = read_integer(4);
				var userperiod_end = read_integer(4);

				var doc = document.implementation.createDocument("", "", null);

				var messageElem = doc.createElement("Message");
				var requestElem = doc.createElement("Request");
				requestElem.innerHTML = "SetUserData";
				messageElem.appendChild(requestElem);

				var typeElem = doc.createElement("Type");
				typeElem.innerHTML = "Set";
				messageElem.appendChild(typeElem);

				var useridElem = doc.createElement("UserID");
				useridElem.innerHTML = g_current_user_id;
				messageElem.appendChild(useridElem);

				var privElem = doc.createElement("Privilege");
				privElem.innerHTML = UserPrivs[g_current_priv];
				messageElem.appendChild(privElem);

				var departElem = doc.createElement("Depart");
				departElem.innerHTML = document.getElementById("depart").value;
				messageElem.appendChild(departElem);

				var enabledElem = doc.createElement("Enabled");
				if (enabled)
					enabledElem.innerHTML = "Yes";
				else
					enabledElem.innerHTML = "No";
				messageElem.appendChild(enabledElem);

				var nameElem = doc.createElement("Name");
				nameElem.innerHTML = btoa(utf16Encode(username));
				messageElem.appendChild(nameElem);

				var cardElem = doc.createElement("Card");
				cardElem.innerHTML = btoa(convertFromInteger(card));
				messageElem.appendChild(cardElem);
			
				var pwdElem = doc.createElement("PWD");
				pwdElem.innerHTML = convert_password_to_string(pwd);
				messageElem.appendChild(pwdElem);

				var qrElem = doc.createElement("QR");
				qrElem.innerHTML = btoa(convertFromInteger(qr));
				messageElem.appendChild(qrElem);

				for (var i = 1; i <= 5; i++)
				{
					var tzElem = doc.createElement("TimeSet" + i);
					tzElem.innerHTML = tzval & 0x3F;
					messageElem.appendChild(tzElem);
					tzval = (tzval >> 6);
				}

				var userperiod_use_Elem = doc.createElement("UserPeriod_Used");
				if (userperiod_use)
					userperiod_use_Elem.innerHTML = "Yes";
				else
					userperiod_use_Elem.innerHTML = "No";
				messageElem.appendChild(userperiod_use_Elem);

				var userperiod_start_Elem = doc.createElement("UserPeriod_Start");
				userperiod_start_Elem.innerHTML = userperiod_start;
				messageElem.appendChild(userperiod_start_Elem);

				var userperiod_end_Elem = doc.createElement("UserPeriod_End");
				userperiod_end_Elem.innerHTML = userperiod_end;
				messageElem.appendChild(userperiod_end_Elem);

				g_sent_user_count++;
   				send_relay_message(doc, document.getElementById("session").value, messageElem);
				set_result("");			
			}
			catch
			{
				set_result("Set All User Data Failed");
				all_user_working = false;
			}			
		}

		function send_next_user() {
			g_remaining_user_count--;
			if (g_remaining_user_count > 0)
			{
				set_one_user();
				return;
			}

			set_result("Set All User OK: Total Count = " + g_sent_user_count);
			all_user_working = false;
		}

		function parse_user_data() {
			try
			{
				var magic = read_integer(4);
				if (magic != EnrollMagic)
				{
					set_result("ENROLLDB.dat file is invalid");
					return;
				}

				g_remaining_user_count = read_integer(4);
				if (g_remaining_user_count == 0)
				{
					set_result("Set All User Data OK: Total Count = 0");
					return;
				}
			}
			catch
			{
				set_result("Set All User Data Failed.");
				all_user_working = false;
				return;
			}

			set_one_user();
		}

		async function set_all_user_data() {
			all_user_working = true;
			all_user_data = Array();
			enrolldir = await window.showDirectoryPicker();

			var filehandle;
			try
			{
				fileHandle = await enrolldir.getFileHandle("ENROLLDB.dat");
			}
			catch 
			{
				set_result("Please select directory which include ENROLLDB.dat");
				return;
			}

			try
			{
				var file = await fileHandle.getFile();
				var reader = new FileReader();
				reader.readAsArrayBuffer(file);
				reader.onload = function() {
					all_user_data = new Uint8Array(this.result);
					all_user_data_pos = 0;
					parse_user_data();
				};
			}
			catch 
			{
				set_result("Failed to read ENROLLDB.dat");
				return;
			}
		}

		function on_set_user_data(xml) {
			var x;
			var txt = "Set User Data: ";

			if (all_user_working)
			{
				send_one_backup();
			}
			else 
			{
				x = xml.getElementsByTagName("Result");
				if (x.length > 0)
					txt += "Result = " + x[0].childNodes[0].nodeValue;

				x = xml.getElementsByTagName("Type");
				if (x.length > 0)
					txt += ",  Type = " + x[0].childNodes[0].nodeValue;

				x = xml.getElementsByTagName("Error");
				if (x.length > 0)
					txt += ",  Error = " + x[0].childNodes[0].nodeValue;

				set_result(txt);
			}			
		}

		function set_user_data() {

			if (!check_user_id(document.getElementById("user_id").value))
				return;

			var doc = document.implementation.createDocument("", "", null);

			var messageElem = doc.createElement("Message");
			var requestElem = doc.createElement("Request");
			requestElem.innerHTML = "SetUserData";
			messageElem.appendChild(requestElem);

			var typeElem = doc.createElement("Type");
			typeElem.innerHTML = "Set";
			messageElem.appendChild(typeElem);

			var useridElem = doc.createElement("UserID");
			useridElem.innerHTML = document.getElementById("user_id").value;
			messageElem.appendChild(useridElem);

			var privElem = doc.createElement("Privilege");
			privElem.innerHTML = document.getElementById("priv").value;
			messageElem.appendChild(privElem);

			var departElem = doc.createElement("Depart");
			departElem.innerHTML = document.getElementById("depart").value;
			messageElem.appendChild(departElem);

			var enabledElem = doc.createElement("Enabled");
			if (document.getElementById("enabled").checked)
				enabledElem.innerHTML = "Yes";
			else
				enabledElem.innerHTML = "No";
			messageElem.appendChild(enabledElem);

			var nameElem = doc.createElement("Name");
			nameElem.innerHTML = btoa(utf16Encode(document.getElementById("user_name").value));
			messageElem.appendChild(nameElem);

			var cardElem = doc.createElement("Card");
			cardElem.innerHTML = btoa(convertFromInteger(parseInt(document.getElementById("card").value)));
			messageElem.appendChild(cardElem);

			var pwdElem = doc.createElement("PWD");
			pwdElem.innerHTML = document.getElementById("pwd").value;
			messageElem.appendChild(pwdElem);

			var qrElem = doc.createElement("QR");
			qrElem.innerHTML = btoa(convertFromInteger(parseInt(document.getElementById("qr").value)));
			messageElem.appendChild(qrElem);

			for (var i = 1; i <= 5; i++)
			{
				var tzElem = doc.createElement("TimeSet" + i);
				tzElem.innerHTML = document.getElementById("tz" + i).value;
				messageElem.appendChild(tzElem);
			}

			var userperiod_use_Elem = doc.createElement("UserPeriod_Used");
			if (document.getElementById("ChkUserPeriodUse").checked)
				userperiod_use_Elem.innerHTML = "Yes";
			else
				userperiod_use_Elem.innerHTML = "No";
			messageElem.appendChild(userperiod_use_Elem);

			var yy, mm, dd;
			yy = parseInt(document.getElementById("TextUserPeriodStart_y").value);
			mm = parseInt(document.getElementById("TextUserPeriodStart_m").value);
			dd = parseInt(document.getElementById("TextUserPeriodStart_d").value);
			var userperiod_start_Elem = doc.createElement("UserPeriod_Start");
			userperiod_start_Elem.innerHTML = ((yy - 2000) << 16) + (mm << 8) + dd;
			messageElem.appendChild(userperiod_start_Elem);

			yy = parseInt(document.getElementById("TextUserPeriodEnd_y").value);
			mm = parseInt(document.getElementById("TextUserPeriodEnd_m").value);
			dd = parseInt(document.getElementById("TextUserPeriodEnd_d").value);
			var userperiod_end_Elem = doc.createElement("UserPeriod_End");
			userperiod_end_Elem.innerHTML = ((yy - 2000) << 16) + (mm << 8) + dd;
			messageElem.appendChild(userperiod_end_Elem);

   			send_relay_message(doc, document.getElementById("session").value, messageElem);
			set_result("");
		}

		function delete_user() {

			if (!check_user_id(document.getElementById("user_id").value))
				return;

			var doc = document.implementation.createDocument("", "", null);

			var messageElem = doc.createElement("Message");
			var requestElem = doc.createElement("Request");
			requestElem.innerHTML = "SetUserData";
			messageElem.appendChild(requestElem);

			var typeElem = doc.createElement("Type");
			typeElem.innerHTML = "Delete";
			messageElem.appendChild(typeElem);

			var useridElem = doc.createElement("UserID");
			useridElem.innerHTML = document.getElementById("user_id").value;
			messageElem.appendChild(useridElem);

   			send_relay_message(doc, document.getElementById("session").value, messageElem);
			set_result("");
		}

		function strToArray(bytes) {
			var v = Array();
			var offset = 0, length = bytes.length;
			while (offset < length) {
				v.push(bytes[offset].charCodeAt(0));
				offset ++;
			}
			
			return v;
		}

		async function saveDatFile(content) {
			// (A) CREATE BLOB OBJECT
			var myBlob = new Blob([new Uint8Array(content).buffer], {type: "application/octet-stream"});
			
			// (B) FILE HANDLER & FILE STREAM
			var fileHandle = await window.showSaveFilePicker({
				types: [{
					description: "Dat file",
					accept: {"dat/bin": [".dat"]}
				}]
			});
			
			var fileStream = await fileHandle.createWritable();
			// (C) WRITE FILE
			await fileStream.write(myBlob);
			await fileStream.close();
		}

		async function saveEnrollFile(content) {
			// (A) CREATE BLOB OBJECT
			var myBlob = new Blob([new Uint8Array(content).buffer], {type: "application/octet-stream"});

			// (B) FILE HANDLER & FILE STREAM
  			var fileStream = await enrollfileHandle.createWritable();
			// (C) WRITE FILE
			await fileStream.write(myBlob);
			await fileStream.close();
		}

		async function savePhotoFile(content) {
			// (A) CREATE BLOB OBJECT
			var myBlob = new Blob([new Uint8Array(content).buffer], {type: "application/octet-stream"});
			
			// (B) FILE HANDLER & FILE STREAM
			var fileHandle = await window.showSaveFilePicker({
				types: [{
					description: "JPEG file",
					accept: {"dat/bin": [".jpg"]}
				}]
			});
			
			var fileStream = await fileHandle.createWritable();
			// (C) WRITE FILE
			await fileStream.write(myBlob);
			await fileStream.close();
		}

		async function savePhotoFileWithUserId(content, userid) {
			// (A) CREATE BLOB OBJECT
			var myBlob = new Blob([new Uint8Array(content).buffer], {type: "application/octet-stream"});

			// (B) FILE HANDLER & FILE STREAM
			var fileHandle = await enrolldir.getFileHandle(userid + ".jpg", { create: true });

			var fileStream = await fileHandle.createWritable();
			// (C) WRITE FILE
			await fileStream.write(myBlob);
			await fileStream.close();
		}

		function on_get_face_data(xml) {
			var x;
			var txt = "Get Face Data: ";

			if (all_user_working)
			{
				x = xml.getElementsByTagName("FaceData");
				if (x.length > 0 && x[0].childNodes.length > 0)
				{
					var face = strToArray(atob(x[0].childNodes[0].nodeValue));
					dump_array(face);
				}
				
				g_enroll_mask = g_enroll_mask & ~(1 << 13);
				if (timer_working)
				{
					ready = true;
					step = "get_user_backup";
				}
				else
				{
					get_user_backup();
				}
			}
			else
			{
				x = xml.getElementsByTagName("Result");
				if (x.length > 0)
					txt += "Result = " + x[0].childNodes[0].nodeValue;

				x = xml.getElementsByTagName("FaceEnrolled");
				if (x.length > 0)
				{
					if (x[0].childNodes[0].nodeValue == "Yes")
					{
						x = xml.getElementsByTagName("FaceData");
						if (x.length > 0 && x[0].childNodes.length > 0)
						{
							var face = strToArray(atob(x[0].childNodes[0].nodeValue));
							saveDatFile(face);
						}
					}
					else 
					{
						txt += ",  Face not enrolled for this user.";
					}
				}

				set_result(txt);
			}
		}

		function get_face_data(userid) {

			if (!check_user_id(userid))
				return;

			var doc = document.implementation.createDocument("", "", null);

			var messageElem = doc.createElement("Message");
			var requestElem = doc.createElement("Request");
			requestElem.innerHTML = "GetFaceData";
			messageElem.appendChild(requestElem);

			var useridElem = doc.createElement("UserID");
			useridElem.innerHTML = userid;
			messageElem.appendChild(useridElem);

			send_relay_message(doc, document.getElementById("session").value, messageElem);
			if (!timer_working)
				set_result("");
		}

		function on_set_face_data(xml) {
			var x;
			var txt = "Set Face Data: ";

			if (all_user_working)
			{
				send_one_backup();
			}
			else 
			{
				x = xml.getElementsByTagName("Result");
				if (x.length > 0)
					txt += "Result = " + x[0].childNodes[0].nodeValue;

				x = xml.getElementsByTagName("Action");
				if (x.length > 0)
					txt += ",  Action = " + x[0].childNodes[0].nodeValue;

				x = xml.getElementsByTagName("Reason");
				if (x.length > 0)
					txt += ",  Reason = " + x[0].childNodes[0].nodeValue;

				set_result(txt);
			}
		}

		function set_face_data() {

			if (!check_user_id(document.getElementById("user_id").value))
				return;

			var doc = document.implementation.createDocument("", "", null);

			var messageElem = doc.createElement("Message");
			var requestElem = doc.createElement("Request");
			requestElem.innerHTML = "SetFaceData";
			messageElem.appendChild(requestElem);

			var useridElem = doc.createElement("UserID");
			useridElem.innerHTML = document.getElementById("user_id").value;
			messageElem.appendChild(useridElem);

			var privElem = doc.createElement("Privilege");
			privElem.innerHTML = document.getElementById("priv").value;
			messageElem.appendChild(privElem);

			var dupcheckElem = doc.createElement("DuplicationCheck");
			if (document.getElementById("dup_check").checked)
				dupcheckElem.innerHTML = "Yes";
			else
				dupcheckElem.innerHTML = "No";
			messageElem.appendChild(dupcheckElem);

			let input = document.createElement('input');
			input.type = 'file';
			input.onchange = _this => {
				var reader = new FileReader();
				reader.readAsArrayBuffer(input.files[0]);
				reader.onload = function() {
					var faceElem = doc.createElement("FaceData");
					faceElem.innerHTML = btoa(String.fromCharCode.apply(null, new Uint8Array(this.result)));
					messageElem.appendChild(faceElem);

					send_relay_message(doc, document.getElementById("session").value, messageElem);
					set_result("");
				};
			};
			input.click();
		}

		function send_one_face_data() {
			var doc = document.implementation.createDocument("", "", null);

			var messageElem = doc.createElement("Message");
			var requestElem = doc.createElement("Request");
			requestElem.innerHTML = "SetFaceData";
			messageElem.appendChild(requestElem);

			var useridElem = doc.createElement("UserID");
			useridElem.innerHTML = g_current_user_id;
			messageElem.appendChild(useridElem);

			var privElem = doc.createElement("Privilege");
			privElem.innerHTML = UserPrivs[g_current_priv];
			messageElem.appendChild(privElem);

			var dupcheckElem = doc.createElement("DuplicationCheck");
			dupcheckElem.innerHTML = "Yes";
			messageElem.appendChild(dupcheckElem);

			var faceElem = doc.createElement("FaceData");
			faceElem.innerHTML = btoa(String.fromCharCode.apply(null, read_array(FaceDataSize)));
			messageElem.appendChild(faceElem);

			send_relay_message(doc, document.getElementById("session").value, messageElem);
			set_result("");
		}

		function on_get_finger_data(xml) {
			var x;
			var txt = "Get Finger Data: ";

			if (all_user_working)
			{
				x = xml.getElementsByTagName("FingerData");
				if (x.length > 0 && x[0].childNodes.length > 0)
				{
					var fp = strToArray(atob(x[0].childNodes[0].nodeValue));
					dump_array(fp);
				}
				x = xml.getElementsByTagName("FingerNo");
				if (x.length > 0 && x[0].childNodes.length > 0)
				{
					var fpno = parseInt(x[0].childNodes[0].nodeValue);
					g_enroll_mask = g_enroll_mask & ~(1 << fpno);
					if (timer_working)
					{
						ready = true;
						step = "get_user_backup";
					}
					else
					{
						get_user_backup();
					}
				}
			}
			else
			{
				x = xml.getElementsByTagName("Result");
				if (x.length > 0)
					txt += "Result = " + x[0].childNodes[0].nodeValue;

				x = xml.getElementsByTagName("FingerData");
				if (x.length > 0 && x[0].childNodes.length > 0)
				{
					var fp = strToArray(atob(x[0].childNodes[0].nodeValue));
					saveDatFile(fp);
					txt += "Result = OK";
				}

				x = xml.getElementsByTagName("Duress");
				if (x.length > 0 && x[0].childNodes.length > 0)
				{
					txt += ", Duress = " + x[0].childNodes[0].nodeValue;
				}

				set_result(txt);
			}

		}

		function get_finger_data(userid, fpno) {

			if (!check_user_id(userid))
				return;

			var doc = document.implementation.createDocument("", "", null);

			var messageElem = doc.createElement("Message");
			var requestElem = doc.createElement("Request");
			requestElem.innerHTML = "GetFingerData";
			messageElem.appendChild(requestElem);

			var useridElem = doc.createElement("UserID");
			useridElem.innerHTML = userid;
			messageElem.appendChild(useridElem);

			var fpnoElem = doc.createElement("FingerNo");
			fpnoElem.innerHTML = fpno;
			messageElem.appendChild(fpnoElem);

			var fponlyElem = doc.createElement("FingerOnly");
			fponlyElem.innerHTML = "1";
			messageElem.appendChild(fponlyElem);

			send_relay_message(doc, document.getElementById("session").value, messageElem);
			if (!timer_working)
				set_result("");
		}

		function on_set_finger_data(xml) {
			var x;
			var txt = "Set Finger Data: ";

			if (all_user_working)
			{
				send_one_backup();
			}
			else 
			{
				x = xml.getElementsByTagName("Result");
				if (x.length > 0)
					txt += "Result = " + x[0].childNodes[0].nodeValue;

				x = xml.getElementsByTagName("Action");
				if (x.length > 0)
					txt += ",  Action = " + x[0].childNodes[0].nodeValue;

				x = xml.getElementsByTagName("Reason");
				if (x.length > 0)
					txt += ",  Reason = " + x[0].childNodes[0].nodeValue;

				set_result(txt);
			}
		}

		function set_finger_data() {

			if (!check_user_id(document.getElementById("user_id").value))
				return;

			var doc = document.implementation.createDocument("", "", null);

			var messageElem = doc.createElement("Message");
			var requestElem = doc.createElement("Request");
			requestElem.innerHTML = "SetFingerData";
			messageElem.appendChild(requestElem);

			var useridElem = doc.createElement("UserID");
			useridElem.innerHTML = document.getElementById("user_id").value;
			messageElem.appendChild(useridElem);

			var fpnoElem = doc.createElement("FingerNo");
			fpnoElem.innerHTML = document.getElementById("fp_no").value;
			messageElem.appendChild(fpnoElem);

			var privElem = doc.createElement("Privilege");
			privElem.innerHTML = document.getElementById("priv").value;
			messageElem.appendChild(privElem);

			var dupcheckElem = doc.createElement("DuplicationCheck");
			if (document.getElementById("dup_check").checked)
				dupcheckElem.innerHTML = "1";
			else
				dupcheckElem.innerHTML = "0";
			messageElem.appendChild(dupcheckElem);

			var duressElem = doc.createElement("Duress");
			if (document.getElementById("duress").checked)
				duressElem.innerHTML = "1";
			else
				duressElem.innerHTML = "0";
			messageElem.appendChild(duressElem);

			let input = document.createElement('input');
			input.type = 'file';
			input.onchange = _this => {
				var reader = new FileReader();
				reader.readAsArrayBuffer(input.files[0]);
				reader.onload = function() {
					var fpElem = doc.createElement("FingerData");
					fpElem.innerHTML = btoa(String.fromCharCode.apply(null, new Uint8Array(this.result)));
					messageElem.appendChild(fpElem);

					send_relay_message(doc, document.getElementById("session").value, messageElem);
					set_result("");
				};
			};
			input.click();
		}

		function send_one_fp_data(fpno) {
			var doc = document.implementation.createDocument("", "", null);

			var messageElem = doc.createElement("Message");
			var requestElem = doc.createElement("Request");
			requestElem.innerHTML = "SetFingerData";
			messageElem.appendChild(requestElem);

			var useridElem = doc.createElement("UserID");
			useridElem.innerHTML = g_current_user_id;
			messageElem.appendChild(useridElem);

			var fpnoElem = doc.createElement("FingerNo");
			fpnoElem.innerHTML = fpno;
			messageElem.appendChild(fpnoElem);

			var privElem = doc.createElement("Privilege");
			privElem.innerHTML = UserPrivs[g_current_priv];
			messageElem.appendChild(privElem);

			var dupcheckElem = doc.createElement("DuplicationCheck");
			dupcheckElem.innerHTML = "1";
			messageElem.appendChild(dupcheckElem);

			var duressElem = doc.createElement("Duress");
			if (g_duress_mask & (1 << fpno))
				duressElem.innerHTML = "1";
			else
				duressElem.innerHTML = "0";
			messageElem.appendChild(duressElem);

			var fpElem = doc.createElement("FingerData");
			fpElem.innerHTML = btoa(String.fromCharCode.apply(null, read_array(FpDataSize)));
			messageElem.appendChild(fpElem);

			send_relay_message(doc, document.getElementById("session").value, messageElem);
			set_result("");
		}

		function on_get_photo_data(xml) {
			var x;
			var txt = "Get Photo Data: ";

			if (all_user_working)
			{
				x = xml.getElementsByTagName("PhotoData");
				if (x.length > 0 && x[0].childNodes.length > 0)
				{
					var photo = strToArray(atob(x[0].childNodes[0].nodeValue));
					savePhotoFileWithUserId(photo, g_current_user_id);
				}

				if (timer_working)
				{
					if (g_no_more_user)
					{
						saveEnrollFile(all_user_data);
						all_user_working = false;
						ready = true;
						step = "enable_device";
						set_result("Get All User Data OK: Total Count = " + g_total_user_count);
					}
					else 
					{
						ready = true;
						step = "get_next_user_data";
					}
				}
				else 
				{
					if (g_no_more_user)
					{
						saveEnrollFile(all_user_data);
						all_user_working = false;
						set_result("Get All User Data OK");
					}
					else 
					{
						get_next_user_data(g_current_user_id);
					}
				}				
			}
			else 
			{
				x = xml.getElementsByTagName("Result");
				if (x.length > 0)
					txt += "Result = " + x[0].childNodes[0].nodeValue;

				x = xml.getElementsByTagName("PhotoData");
				if (x.length > 0 && x[0].childNodes.length > 0)
				{
					var photo = strToArray(atob(x[0].childNodes[0].nodeValue));
					savePhotoFile(photo);
				}

				set_result(txt);
			}
		}

		function get_photo_data(userid) {

			if (!check_user_id(userid))
				return;

			var doc = document.implementation.createDocument("", "", null);

			var messageElem = doc.createElement("Message");
			var requestElem = doc.createElement("Request");
			requestElem.innerHTML = "GetUserPhoto";
			messageElem.appendChild(requestElem);

			var useridElem = doc.createElement("UserID");
			useridElem.innerHTML = userid;
			messageElem.appendChild(useridElem);

			send_relay_message(doc, document.getElementById("session").value, messageElem);
			if (!timer_working)
				set_result("");
		}
		
		function on_set_photo_data(xml) {
			var x;
			var txt = "Set Photo Data: ";

			if (all_user_working)
			{
				send_next_user();
			}
			else
			{
				x = xml.getElementsByTagName("Result");
				if (x.length > 0)
					txt += "Result = " + x[0].childNodes[0].nodeValue;

				x = xml.getElementsByTagName("Reason");
				if (x.length > 0)
					txt += ",  Reason = " + x[0].childNodes[0].nodeValue;

				set_result(txt);
			}
		}

		function set_photo_data() {

			if (!check_user_id(document.getElementById("user_id").value))
				return;

			var doc = document.implementation.createDocument("", "", null);

			var messageElem = doc.createElement("Message");
			var requestElem = doc.createElement("Request");
			requestElem.innerHTML = "SetUserPhoto";
			messageElem.appendChild(requestElem);

			var useridElem = doc.createElement("UserID");
			useridElem.innerHTML = document.getElementById("user_id").value;
			messageElem.appendChild(useridElem);

			let input = document.createElement('input');
			input.type = 'file';
			input.onchange = _this => {
				var reader = new FileReader();
				reader.readAsArrayBuffer(input.files[0]);
				reader.onload = function() {
					if (this.result.byteLength > 32 * 1024)
					{
						set_result("Photo file size is invalid.");
						return;
					}

					var bytearray = new Uint8Array(this.result);
					var sizeElem = doc.createElement("PhotoSize");
					sizeElem.innerHTML = bytearray.length;
					messageElem.appendChild(sizeElem);

					var photoElem = doc.createElement("PhotoData");
					photoElem.innerHTML = btoa(String.fromCharCode.apply(null, bytearray));
					messageElem.appendChild(photoElem);

					send_relay_message(doc, document.getElementById("session").value, messageElem);
					set_result("");
				};
			};
			input.click();
		}

		async function send_one_photo_data() {
			var doc = document.implementation.createDocument("", "", null);

			var messageElem = doc.createElement("Message");
			var requestElem = doc.createElement("Request");
			requestElem.innerHTML = "SetUserPhoto";
			messageElem.appendChild(requestElem);

			var useridElem = doc.createElement("UserID");
			useridElem.innerHTML = g_current_user_id;
			messageElem.appendChild(useridElem);

			var privElem = doc.createElement("Privilege");
			privElem.innerHTML = UserPrivs[g_current_priv];
			messageElem.appendChild(privElem);

			try
			{
				var fileHandle = await enrolldir.getFileHandle(g_current_user_id + ".jpg");
				var file = await fileHandle.getFile();
				var reader = new FileReader();
				reader.readAsArrayBuffer(file);
				reader.onload = function() {
					var bytearray = new Uint8Array(reader.result);

					var sizeElem = doc.createElement("PhotoSize");
					sizeElem.innerHTML = bytearray.length;
					messageElem.appendChild(sizeElem);

					var photoElem = doc.createElement("PhotoData");
					photoElem.innerHTML = btoa(String.fromCharCode.apply(null, bytearray));
					messageElem.appendChild(photoElem);

					send_relay_message(doc, document.getElementById("session").value, messageElem);
					set_result("");
				};
				return true;
			}
			catch
			{
				return false;
			}
		}

		function on_enroll_face_by_photo(xml) {
			var x;
			var txt = "Enroll Face By Photo: ";

			x = xml.getElementsByTagName("Result");
			if (x.length > 0)
				txt += "Result = " + x[0].childNodes[0].nodeValue;

			x = xml.getElementsByTagName("Reason");
			if (x.length > 0)
				txt += ",  Reason = " + x[0].childNodes[0].nodeValue;

			set_result(txt);
		}

		function enroll_face_by_photo() {

			if (!check_user_id(document.getElementById("user_id").value))
				return;

			var doc = document.implementation.createDocument("", "", null);

			var messageElem = doc.createElement("Message");
			var requestElem = doc.createElement("Request");
			requestElem.innerHTML = "EnrollFaceByPhoto";
			messageElem.appendChild(requestElem);

			var useridElem = doc.createElement("UserID");
			useridElem.innerHTML = document.getElementById("user_id").value;
			messageElem.appendChild(useridElem);

			let input = document.createElement('input');
			input.type = 'file';
			input.onchange = _this => {
				var reader = new FileReader();
				reader.readAsArrayBuffer(input.files[0]);
				reader.onload = function() {
					if (this.result.byteLength > 32 * 1024)
					{
						set_result("Photo file size is invalid.");
						return;
					}

					var bytearray = new Uint8Array(this.result);
					var sizeElem = doc.createElement("PhotoSize");
					sizeElem.innerHTML = bytearray.length;
					messageElem.appendChild(sizeElem);

					var photoElem = doc.createElement("PhotoData");
					photoElem.innerHTML = btoa(String.fromCharCode.apply(null, bytearray));
					messageElem.appendChild(photoElem);

					send_relay_message(doc, document.getElementById("session").value, messageElem);
					set_result("");
				};
			};
			input.click();
		}

		function on_get_user_password(xml) {
			var x;
			var pwd = "";
			var txt = "Get User Password: ";

			x = xml.getElementsByTagName("Result");
			if (x.length > 0)
				txt += "Result = " + x[0].childNodes[0].nodeValue;

			x = xml.getElementsByTagName("Password");
			if (x.length > 0 && x[0].childNodes.length > 0)
				pwd = x[0].childNodes[0].nodeValue;

			document.getElementById("pwd").value = pwd;

			set_result(txt);
		}

		function get_user_password() {

			if (!check_user_id(document.getElementById("user_id").value))
				return;

			var doc = document.implementation.createDocument("", "", null);

			var messageElem = doc.createElement("Message");
			var requestElem = doc.createElement("Request");
			requestElem.innerHTML = "GetUserPassword";
			messageElem.appendChild(requestElem);

			var useridElem = doc.createElement("UserID");
			useridElem.innerHTML = document.getElementById("user_id").value;
			messageElem.appendChild(useridElem);

			send_relay_message(doc, document.getElementById("session").value, messageElem);
			set_result("");
		}

		function convertToInteger(bytes) {
			var v = 0, offset = 0, length = bytes.length;
			while (offset < length) {
				var c = bytes[length - offset - 1].charCodeAt(0);
				v = v * 256;
				v = v + c;
				offset ++;
			}
			
			return v;
		}

		function convertFromInteger(val) {
			var result = "", offset = 0;
			while (offset < 4) {
				var c = val % 256;
				result = result + String.fromCharCode(c);
				val = val - c;
				val = val / 256;
				offset ++;
			}
			
			return result;
		}

		function on_get_user_card(xml) {
			var x;
			var card = 0;
			var txt = "Get User Card No: ";

			x = xml.getElementsByTagName("Result");
			if (x.length > 0)
				txt += "Result = " + x[0].childNodes[0].nodeValue;

			x = xml.getElementsByTagName("CardNo");
			if (x.length > 0 && x[0].childNodes.length > 0)
			{
				card = convertToInteger(atob(x[0].childNodes[0].nodeValue));
				if (card == 0)
				{
					txt += "  [No Card Enrolled For This User!]";
					document.getElementById("card").value = "";
				}
				else
				{
					document.getElementById("card").value = card;
				}
			}

			set_result(txt);
		}

		function get_user_card() {

			if (!check_user_id(document.getElementById("user_id").value))
				return;

			var doc = document.implementation.createDocument("", "", null);

			var messageElem = doc.createElement("Message");
			var requestElem = doc.createElement("Request");
			requestElem.innerHTML = "GetUserCardNo";
			messageElem.appendChild(requestElem);

			var useridElem = doc.createElement("UserID");
			useridElem.innerHTML = document.getElementById("user_id").value;
			messageElem.appendChild(useridElem);

			send_relay_message(doc, document.getElementById("session").value, messageElem);
			set_result("");
		}

		function on_get_user_qr(xml) {
			var x;
			var qr = 0;
			var txt = "Get User QR: ";

			x = xml.getElementsByTagName("Result");
			if (x.length > 0)
				txt += "Result = " + x[0].childNodes[0].nodeValue;

			x = xml.getElementsByTagName("QR");
			if (x.length > 0 && x[0].childNodes.length > 0)
			{
				qr = convertToInteger(atob(x[0].childNodes[0].nodeValue));
				if (qr == 0)
				{
					txt += "  [No QR Enrolled For This User!]";
					document.getElementById("qr").value = "";
				}
				else
				{
					document.getElementById("qr").value = qr;
				}
			}

			set_result(txt);
		}

		function get_user_qr() {

			if (!check_user_id(document.getElementById("user_id").value))
				return;

			var doc = document.implementation.createDocument("", "", null);

			var messageElem = doc.createElement("Message");
			var requestElem = doc.createElement("Request");
			requestElem.innerHTML = "GetUserQR";
			messageElem.appendChild(requestElem);

			var useridElem = doc.createElement("UserID");
			useridElem.innerHTML = document.getElementById("user_id").value;
			messageElem.appendChild(useridElem);

			send_relay_message(doc, document.getElementById("session").value, messageElem);
			set_result("");
		}

		function on_take_off_manager(xml) {
			var x;
			var txt = "Take Off Manager: ";

			x = xml.getElementsByTagName("Result");
			if (x.length > 0)
				txt += "Result = " + x[0].childNodes[0].nodeValue;

			set_result(txt);
		}

		function take_off_manager() {
			var doc = document.implementation.createDocument("", "", null);

			var messageElem = doc.createElement("Message");
			var requestElem = doc.createElement("Request");
			requestElem.innerHTML = "TakeOffManager";
			messageElem.appendChild(requestElem);

			send_relay_message(doc, document.getElementById("session").value, messageElem);
			set_result("");
		}

		function on_remote_enroll(xml) {
			var x;
			var txt = "Remote Enroll: ";

			x = xml.getElementsByTagName("ResultCode");
			if (x.length > 0)
				txt += "Result = " + x[0].childNodes[0].nodeValue;

			set_result(txt);	
		}

		function remote_enroll(backup) {

			if (!check_user_id(document.getElementById("user_id").value))
				return;

			var doc = document.implementation.createDocument("", "", null);

			var messageElem = doc.createElement("Message");
			var requestElem = doc.createElement("Request");
			requestElem.innerHTML = "RemoteEnroll";
			messageElem.appendChild(requestElem);

			var useridElem = doc.createElement("UserID");
			useridElem.innerHTML = document.getElementById("user_id").value;
			messageElem.appendChild(useridElem);

			var backupElem = doc.createElement("Backup");
			backupElem.innerHTML = backup;
			messageElem.appendChild(backupElem);

			if (backup == "RemoteEnrollFP")
			{
				var fpnoElem = doc.createElement("FingerNo");
				fpnoElem.innerHTML = document.getElementById("fp_no").value;
				messageElem.appendChild(fpnoElem);
			}

			send_relay_message(doc, document.getElementById("session").value, messageElem);
			set_result("");		
		}

		function on_exit_remote_enroll(xml) {
			var x;
			var txt = "Exit Remote Enroll: ";

			x = xml.getElementsByTagName("ResultCode");
			if (x.length > 0)
				txt += "Result = " + x[0].childNodes[0].nodeValue;

			set_result(txt);	
		}

		function exit_remote_enroll(backup) {

			if (!check_user_id(document.getElementById("user_id").value))
				return;

			var doc = document.implementation.createDocument("", "", null);

			var messageElem = doc.createElement("Message");
			var requestElem = doc.createElement("Request");
			requestElem.innerHTML = "ExitRemoteEnroll";
			messageElem.appendChild(requestElem);

			send_relay_message(doc, document.getElementById("session").value, messageElem);
			set_result("");		
		}

		function on_enable_device(xml) {
			var x;
			var txt = "";
			x = xml.getElementsByTagName("Result");
			if (x.length > 0 && 
				x[0].childNodes.length > 0 &&
				x[0].childNodes[0].nodeValue == "OK")
			{
				if (step == "disable_device")
				{
					if (!canceled)
					{
						ready = true;
						txt = "Get All User Data: Diable device";
						step = "get_user_count";
					}
				}
				else if (step == "enable_device")
				{
					timer_working = false;
					txt = document.getElementById("result").innerHTML;
				}
			}
			else 
			{
				txt = "Get All User Data: Failed";
				timer_working = false;
			}

			set_result(txt);
		}

		function send_enable_device(enable) {
			var doc = document.implementation.createDocument("", "", null);
			
			var messageElem = doc.createElement("Message");
			var requestElem = doc.createElement("Request");
			requestElem.innerHTML = "EnableDevice";
			messageElem.appendChild(requestElem);
			
			var enableElem = doc.createElement("Enable");
			enableElem.innerHTML = enable;
			messageElem.appendChild(enableElem);

			send_relay_message(doc, document.getElementById("session").value, messageElem);
			if (enable == "No")
				set_result("");
		}

		function on_timer() {
			if (!timer_working)
			{
				document.getElementById("timer_button").innerHTML = "Get All User Data(Timer)";
				if (canceled)
					set_result("Get All User data: Canceled");

				cleanInterval(timer);
				timer = null;
				return;
			}

			if (!ready)
				return;

			ready = false;
			if (step == "disable_device")
				send_enable_device("No");			
			else if (step == "get_user_count")
				get_user_count();
			else if (step == "get_first_user_data")
				get_first_user_data();
			else if (step == "get_next_user_data")
				get_next_user_data(g_current_user_id);
			else if (step == "get_user_backup")
				get_user_backup();
			else if (step == "enable_device")
				send_enable_device("Yes");
		}

		async function on_timer_click() {
			if (timer_working)
			{
				canceled = true;
				step = "enable_device";
				ready = true;
			}
			else
			{
				all_user_working = true;
				all_user_data = Array();
				enrolldir = await window.showDirectoryPicker();

				canceled = false;
				timer_working = true;
				step = "disable_device";
				ready = true;
				timer = setInterval(on_timer, 100);
				document.getElementById("timer_button").innerHTML = "Stop";
			}			
		}

		function on_get_all_updated_users(xml) {
			var x;
			var txt = "Get All Updated Users: ";

			x = xml.getElementsByTagName("Result");
			if (x.length > 0)
				txt += "Result = " + x[0].childNodes[0].nodeValue;

			x = xml.getElementsByTagName("User");
			txt += ", Count = " + x.length;
			for (i = 0; i < x.length; i++) {
				if (i == 0)
					txt += ", Users = [";
				else
					txt += ", ";
				txt += x[i].childNodes[0].nodeValue;
				if (i == x.length - 1)
					txt += "]";
			}

			set_result(txt);
		}

		function get_all_updated_users() {
			var doc = document.implementation.createDocument("", "", null);
			var messageElem = doc.createElement("Message");
			var requestElem = doc.createElement("Request");
			requestElem.innerHTML = "browserGetAllUpdatedUsers";
			messageElem.appendChild(requestElem);
			
			var deviceElem = doc.createElement("Device");
			deviceElem.innerHTML = document.getElementById("session").value;
			messageElem.appendChild(deviceElem);

			doc.appendChild(messageElem);
			
			serializer = new XMLSerializer();
			ws.send(serializer.serializeToString(doc));
		}

		function on_clear_all_updated_users(xml) {
			var x;
			var txt = "Clear All Updated Users: ";

			x = xml.getElementsByTagName("Result");
			if (x.length > 0)
				txt += "Result = " + x[0].childNodes[0].nodeValue;

			set_result(txt);
		}

		function clear_all_updated_users() {
			var doc = document.implementation.createDocument("", "", null);
			var messageElem = doc.createElement("Message");
			var requestElem = doc.createElement("Request");
			requestElem.innerHTML = "browserClearAllUpdatedUsers";
			messageElem.appendChild(requestElem);
			
			var deviceElem = doc.createElement("Device");
			deviceElem.innerHTML = document.getElementById("session").value;
			messageElem.appendChild(deviceElem);

			doc.appendChild(messageElem);
			
			serializer = new XMLSerializer();
			ws.send(serializer.serializeToString(doc));
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
				else if (res == "EnableDevice")
					on_enable_device(xml)
				else if (res == "GetDeviceStatus")
					on_get_user_count(xml);
				else if (res == "GetUserData")
					on_get_user_data(xml, "Get User Data", true);
				else if (res == "GetFirstUserData")
					on_get_user_data(xml, "Get First User Data", false);
				else if (res == "GetNextUserData" || res == "GetNextUserDataExt")
					on_get_user_data(xml, "Get Next User Data", false);
				else if (res == "SetUserData")
					on_set_user_data(xml);
				else if (res == "GetFaceData")
					on_get_face_data(xml);
				else if (res == "SetFaceData")
					on_set_face_data(xml);
				else if (res == "GetFingerData")
					on_get_finger_data(xml);
				else if (res == "SetFingerData")
					on_set_finger_data(xml);
				else if (res == "GetUserPhoto")
					on_get_photo_data(xml);
				else if (res == "SetUserPhoto")
					on_set_photo_data(xml);
				else if (res == "EnrollFaceByPhoto")
					on_enroll_face_by_photo(xml);
				else if (res == "GetUserPassword")
					on_get_user_password(xml);
				else if (res == "GetUserCardNo")
					on_get_user_card(xml);
				else if (res == "GetUserQR")
					on_get_user_qr(xml);
				else if (res == "TakeOffManager")
					on_take_off_manager(xml);
				else if (res == "RemoteEnroll")
					on_remote_enroll(xml);
				else if (res == "ExitRemoteEnroll")
					on_exit_remote_enroll(xml);
				else if (res == "browserGetAllUpdatedUsers")
					on_get_all_updated_users(xml);
				else if (res == "browserClearAllUpdatedUsers")
					on_clear_all_updated_users(xml);
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
			<td><button style="width:210px" onclick="get_user_data(); return false;">Get User Data</button></td>
			<td>User ID:</td>
			<td><input type="text" id="user_id" /></td>
			<td><button id="timer_button" style="width:240px" onclick="on_timer_click(); return false;">Get All User Data(Timer)</button></td>
		</tr>
		<tr>
			<td><button style="width:210px" onclick="set_user_data(); return false;">Set User Data</button></td>
			<td>Name:</td>
			<td><input type="text" id="user_name" /></td>
		</tr>
		<tr>
			<td><button style="width:210px" onclick="delete_user(); return false;">Delete User</button></td>
			<td>Depart:</td>
			<td><input type="text" id="depart" value="0"/></td>
			<td><button style="width:240px" onclick="get_all_updated_users(); return false;">Get All Updated Users</button></td>			
		</tr>
		<tr>
			<td><button style="width:210px" onclick="get_user_password(); return false;">Get User Password</button></td>
			<td>Privilege:</td>
			<td>
				<select id="priv">
					<option value="User">Normal User</option>
					<option value="Manager">Manager</option>
					<option value="Administrator">Administrator</option>
				</select>
			</td>
			<td><button style="width:240px" onclick="clear_all_updated_users(); return false;">Clear All Updated Users</button></td>			
		</tr>
		<tr>
			<td><button style="width:210px" onclick="get_user_card(); return false;">Get User CardNo</button></td>
			<td>Enabled:</td>
			<td><input type="checkbox" id="enabled" checked/></td>
		</tr>
		<tr>
			<td><button style="width:210px" onclick="get_user_qr(); return false;">Get User QR</button></td>
			<td rowspan="3">TimeSet:</td>
			<td rowspan="3">
				<table>
					<tr>
						<td>1</td>
						<td>2</td>
						<td>3</td>
						<td>4</td>
						<td>5</td>
					</tr>
					<tr>
						<td><input style="width:40px;" type="text" id="tz1" value="0"/></td>
						<td><input style="width:40px;" type="text" id="tz2" value="0"/></td>
						<td><input style="width:40px;" type="text" id="tz3" value="0"/></td>
						<td><input style="width:40px;" type="text" id="tz4" value="0"/></td>
						<td><input style="width:40px;" type="text" id="tz5" value="0"/></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td><button style="width:210px" onclick="get_finger_data(document.getElementById('user_id').value, document.getElementById('fp_no').value); return false;">Get Finger Data</button></td>
		</tr>
		<tr>
			<td colspan="3"><button style="width:210px" onclick="set_finger_data(); return false;">Set Finger Data</button></td>
			<td><button style="width:210px" onclick="remote_enroll('RemoteEnrollFace'); return false;">Remote Enroll Face</button></td>
		</tr>
		<tr>
			<td><button style="width:210px" onclick="get_face_data(document.getElementById('user_id').value); return false;">Get Face Data</button></td>
			<td>CardNo:</td>
			<td><input type="text" id="card"/></td>			
			<td><button style="width:210px" onclick="remote_enroll('RemoteEnrollFP'); return false;">Remote Enroll FP</button></td>
		</tr>
		<tr>
			<td><button style="width:210px" onclick="set_face_data(); return false;">Set Face Data</button></td>
			<td>Password:</td>
			<td><input type="text" id="pwd"/></td>
			<td><button style="width:210px" onclick="remote_enroll('RemoteEnrollCard'); return false;">Remote Enroll Card</button></td>
		</tr>
		<tr>
			<td><button style="width:210px" onclick="get_photo_data(document.getElementById('user_id').value); return false;">Get User Photo</button></td>
			<td>QR:</td>
			<td><input type="text" id="qr"/></td>
			<td><button style="width:210px" onclick="remote_enroll('RemoteEnrollQR'); return false;">Remote Enroll QR</button></td>
		</tr>
		<tr>
			<td>
				<button style="width:210px" onclick="set_photo_data(); return false;">Set User Photo</button><br>
				<button style="width:210px" onclick="enroll_face_by_photo(); return false;">EnrollFaceByPhoto</button>
			</td>
			<td rowspan="1">FP Enrolled:</td>
			<td rowspan="1">
				<table>
					<tr>
						<td>0</td>
						<td>1</td>
						<td>2</td>
						<td>3</td>
						<td>4</td>
						<td>5</td>
						<td>6</td>
						<td>7</td>
						<td>8</td>
						<td>9</td>
					</tr>
					<tr>
						<td><input type="checkbox" id="fp_exist0"/></td>
						<td><input type="checkbox" id="fp_exist1"/></td>
						<td><input type="checkbox" id="fp_exist2"/></td>
						<td><input type="checkbox" id="fp_exist3"/></td>
						<td><input type="checkbox" id="fp_exist4"/></td>
						<td><input type="checkbox" id="fp_exist5"/></td>
						<td><input type="checkbox" id="fp_exist6"/></td>
						<td><input type="checkbox" id="fp_exist7"/></td>
						<td><input type="checkbox" id="fp_exist8"/></td>
						<td><input type="checkbox" id="fp_exist9"/></td>
					</tr>
				</table>
			</td>
			<td><button style="width:210px" onclick="exit_remote_enroll(); return false;">Exit Remote Enroll</button></td>
		</tr>
		<tr>
			<td><button style="width:210px" onclick="take_off_manager(); return false;">Take Off Manager</button></td>
			<td>FP Duress:</td>
			<td>
				<table>
					<tr>
						<td><input type="checkbox" id="fp_duress0"/></td>
						<td><input type="checkbox" id="fp_duress1"/></td>
						<td><input type="checkbox" id="fp_duress2"/></td>
						<td><input type="checkbox" id="fp_duress3"/></td>
						<td><input type="checkbox" id="fp_duress4"/></td>
						<td><input type="checkbox" id="fp_duress5"/></td>
						<td><input type="checkbox" id="fp_duress6"/></td>
						<td><input type="checkbox" id="fp_duress7"/></td>
						<td><input type="checkbox" id="fp_duress8"/></td>
						<td><input type="checkbox" id="fp_duress9"/></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<td><button style="width:210px" onclick="get_first_user_data(); return false;">Get First User Data</button></td>
			<td>Face Enrolled:</td>
			<td><input type="checkbox" id="face_exist"/></td>
			<td rowspan="3">
                <table>
					<tr><td>Use Period:</td>
						<td><input type="checkbox" id="ChkUserPeriodUse"/></td></tr>
                    <tr><td>Start Time:</td>
                        <td><input style="width:60px;" type="text" id="TextUserPeriodStart_y" value=""/>-
							<input style="width:40px;" type="text" id="TextUserPeriodStart_m" value=""/>-
							<input style="width:40px;" type="text" id="TextUserPeriodStart_d" value=""/>
                        </td></tr>
					<tr><td>End Time:</td>
                        <td><input style="width:60px;" type="text" id="TextUserPeriodEnd_y" value=""/>-
							<input style="width:40px;" type="text" id="TextUserPeriodEnd_m" value=""/>-
							<input style="width:40px;" type="text" id="TextUserPeriodEnd_d" value=""/>
                        </td></tr>
                </table>
			</td>
		</tr>
		<tr>
			<td><button style="width:210px" onclick="get_next_user_data(document.getElementById('user_id').value); return false;">Get Next User Data</button></td>
			<td>Finger No:</td>
			<td><input type="text" id="fp_no" value="0"/>&nbsp;&nbsp;0~9</td>
		</tr>
		<tr>
			<td><button style="width:210px" onclick="get_all_user_data(); return false;">Get All User Data</button></td>
			<td>Duress:</td>
			<td><input type="checkbox" id="duress"/></td>
		</tr>
		<tr>
			<td><button style="width:210px" onclick="set_all_user_data(); return false;">Set All User Data</button></td>
			<td>Duplication Check:</td>
			<td><input type="checkbox" id="dup_check" checked/></td>
		</tr>
	</table>

	<input type="hidden" id='session' name='session' value='<?php echo $_GET["session"]; ?>'></input>
</body>
</html>