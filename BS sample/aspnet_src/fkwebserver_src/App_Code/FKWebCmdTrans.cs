using System;
using System.Collections.Generic;
using System.Web;
using System.Web.UI;
using System.Web.UI.WebControls;
using System.Data.SqlClient;
using System.Data;
using System.Runtime.InteropServices;
using System.Threading;
using System.Configuration;
using System.IO;
using System.Diagnostics;

using log4net;
using log4net.Config;
using Newtonsoft.Json;
using Newtonsoft.Json.Linq;

namespace FKWeb
{
	public class FKWebCmdTrans
	{
	    private ILog logger = log4net.LogManager.GetLogger("SiteLogger");

	    //private const string CONN_STR = "server=.\\SQLEXPRESS1;uid=golden;pwd=golden5718;database=AttDB";    
	    private const string TBL_CMD_TRANS = "tbl_fkcmd_trans";
	    private const string TBL_CMD_TRANS_BIG_FIELD = "tbl_fkcmd_trans_big_field";
	    private string msDbConn;
	    private bool m_bShowDebugMsg;
        private DateTime m_dtLastLogImgFolderUpdate;
        private string m_sLogImgRootFolder;
        private string m_sFirmwareBinRootFolder;

	    public FKWebCmdTrans()
	    {
	        const string csFuncName = "Contructor";
	        string sShowDbgMsg;

	        m_bShowDebugMsg = false;
            sShowDbgMsg = ConfigurationManager.AppSettings["ShowDebugMsg"];
            sShowDbgMsg.ToLower();
            if (sShowDbgMsg == "true" || sShowDbgMsg == "yes")
	        {
	            m_bShowDebugMsg = true;
	        }

	        msDbConn = ConfigurationManager.ConnectionStrings["SqlConnFkWeb"].ConnectionString.ToString();
	        PrintDebugMsg(csFuncName, "0 - Conn:" + msDbConn);

            m_dtLastLogImgFolderUpdate = DateTime.Now;
            m_sLogImgRootFolder = ConfigurationManager.AppSettings["LogImgRootDir"];

            m_sFirmwareBinRootFolder = ConfigurationManager.AppSettings["FirmwareBinDir"];
	    }

        //
        // The format of BSComm Binary data
        //
        // | length_json_string | string_data   |0| length_bin1_data | bin1_data | length_bin2_data | bin2_data |
        //----------------------------------------------------------------------------------------------------------
        // |   4 byte           | (len_json - 1)|1|   4 byte         | len_bin1  |   4 byte         | len_bin2  |
        //

        //===============================================================================
        // BS통신에 리용되는 바퍼로부터 문자렬에 해당한 부분을 읽어내여 그 문자렬을 복귀한다.
        static public string GetStringFromBSCommBuffer(byte[] abytBSComm)
        {
            if (abytBSComm.Length < 4)
                return "";

            try
            {
                string sRet;

                int lenText = BitConverter.ToInt32(abytBSComm, 0);
                if (lenText > abytBSComm.Length - 4)
                    return "";

                if (lenText == 0)
                    return "";

                if (abytBSComm[4 + lenText - 1] == 0) // if last value of string buufer is 0x0
                    sRet = System.Text.Encoding.UTF8.GetString(abytBSComm, 4, lenText - 1);
                else
                    sRet = System.Text.Encoding.UTF8.GetString(abytBSComm, 4, lenText);

                return sRet;
            }
            catch
            {
                return "";
            }
        }

        //===============================================================================
        // BS통신바퍼로부터 문자렬과 뒤에 놓이는 첫번째 바이너리자료를 얻는다.
        static public void GetStringAnd1stBinaryFromBSCommBuffer(
            byte[] abytBSComm,
            out string asString,
            out byte[] abytBinary)
        {
            asString = "";
            abytBinary = new byte[0];
            //PrintDebugMsg("Parse Json", "4 length = " + abytBSComm.Length);
            if (abytBSComm.Length < 4)
                return;

            try
            {
                int lenText = BitConverter.ToInt32(abytBSComm, 0);
                if (lenText > abytBSComm.Length - 4)
                    return;

                if (lenText == 0)
                {
                    asString = "";
                }
                else
                {
                    if (abytBSComm[4 + lenText - 1] == 0) // if last value of string buufer is 0x0
                        asString = System.Text.Encoding.UTF8.GetString(abytBSComm, 4, lenText - 1);
                    else
                        asString = System.Text.Encoding.UTF8.GetString(abytBSComm, 4, lenText);
                }

                int posBin = 4 + lenText;
                int lenBin = BitConverter.ToInt32(abytBSComm, posBin);
                if (lenBin < 1)
                    return;

                if (lenBin > abytBSComm.Length - posBin - 4)
                    return;

                abytBinary = new byte[lenBin];
                Buffer.BlockCopy(
                    abytBSComm, posBin + 4,
                    abytBinary, 0,
                    lenBin);
                return;
            }
            catch
            {
                return;
            }
        }

        static public bool CreateBSCommBufferFromString(string asCmdParamText, out byte[] abytBuffer)
        {
            abytBuffer = new byte[0];

            try
            {
                if (asCmdParamText.Length == 0)
                    return true;

                // 문자렬자료를 바이트배렬로 변환하고 마지막에 0을 붙인다. 그리고 그 길이를 문자렬자료길이로 본다.
                // 전송할 파라메터바이트배렬의 첫 4바이트는 문자렬자료의 길이를 나타낸다.
                byte[] bytText = System.Text.Encoding.UTF8.GetBytes(asCmdParamText);
                byte[] bytTextLen = BitConverter.GetBytes(Convert.ToUInt32(bytText.Length + 1));
                abytBuffer = new byte[4 + bytText.Length + 1];
                bytTextLen.CopyTo(abytBuffer, 0);
                bytText.CopyTo(abytBuffer, 4);
                abytBuffer[4 + bytText.Length] = 0;
                return true;
            }
            catch
            {
                abytBuffer = new byte[0];
                return false;
            }
        }

        static public void AppendBinData(ref byte[] abytBSComm, byte[] abytToAdd)
        {
            try
            {
                byte[] bytBSComm = (byte[])abytBSComm;
                byte[] bytToAdd = (byte[])abytToAdd;

                if (bytToAdd.Length == 0)
                    return;

                int len_dest = bytBSComm.Length + 4 + bytToAdd.Length;
                byte[] bytRet = new byte[len_dest];
                byte[] bytAddLen = BitConverter.GetBytes(Convert.ToUInt32(bytToAdd.Length));
                Buffer.BlockCopy(bytBSComm, 0, bytRet, 0, bytBSComm.Length);
                Buffer.BlockCopy(bytAddLen, 0, bytRet, bytBSComm.Length, 4);
                Buffer.BlockCopy(bytToAdd, 0, bytRet, bytBSComm.Length + 4, bytToAdd.Length);
                abytBSComm = bytRet;
                return;
            }
            catch (Exception)
            {
                return;
            }
        }

        public static byte[] GetBinDataFromBSCommBinData(int anOrder, byte[] abytBSComm)
        {
            byte[] bytEmpty = new byte[0];

            try
            {
                // Convert SQLSever Data types to C# data types
                // SQLSever Data type (varbinary) <-> C# data type (byte [])
                byte[] bytBSComm = (byte[])abytBSComm;
                if (bytBSComm.Length < 4)
                    return bytEmpty;

                if (anOrder < 1 || anOrder > 255)
                    return bytEmpty;

                int orderBin;
                int posBin;
                int lenBin;
                int lenText = BitConverter.ToInt32(bytBSComm, 0);
                if (lenText > bytBSComm.Length - 4)
                    return bytEmpty;

                posBin = 4 + lenText;
                orderBin = 0;
                while (true)
                {
                    if (posBin > bytBSComm.Length - 4)
                        return bytEmpty;

                    lenBin = BitConverter.ToInt32(bytBSComm, posBin);
                    if (lenBin > bytBSComm.Length - posBin - 4)
                        return bytEmpty;

                    orderBin++;
                    if (orderBin == anOrder)
                        break;

                    posBin = posBin + 4 + lenBin;
                }

                byte[] bytRet = new byte[lenBin];
                Buffer.BlockCopy(
                    bytBSComm, posBin + 4,
                    bytRet, 0,
                    lenBin);
                return bytRet;
            }
            catch (Exception)
            {
                return bytEmpty;
            }
        }

	    public void TestNewtonsoftJsonLib()
	    {
	        string sJson = "{\"fk_name\":\"fk001\",\"fk_time\":\"20141205150420\",\"fk_info\":" +
	            "{" +
	                "\"supported_enroll_data\":[\"FP\",\"PASSWORD\",\"IDCARD\",\"FACE\"]," +
	                "\"fk_bin_data_lib\":\"fk_bin_data_1001\"," +
	                "\"firmware\":\"FK725HS001\"" +
	            "}" +
	        "}";

	        JObject jobjTest = JObject.Parse(sJson);
	        string sDevName = jobjTest["fk_name"].ToString();
	        string sDevTime = jobjTest["fk_time"].ToString();
	        string sDevInfo = jobjTest["fk_info"].ToString(Formatting.None);

            JObject jobjSend = new JObject();

            jobjSend.Add("response_code", "OK");
            jobjSend.Add("trans_id", "123");
            jobjSend.Add("cmd_code", "AA_BB");
            jobjSend.Add("cmd_param", JObject.Parse("{\"time\":\"123456\"}"));
            jobjSend.Add("cmd_param_null", null);

            string sSend = jobjSend.ToString(Formatting.None);
            sSend = Newtonsoft.Json.JsonConvert.SerializeObject(jobjSend, Formatting.None);
	    }

	    public void TestStoredProcedureBinData()
	    {
	        const string csFuncName = "TestStoredProcedureBinData";

	        PrintDebugMsg(csFuncName, "0 - Start");

	        try
	        {
	            byte[] bytCmdResult = new byte[12000];
	            bytCmdResult[0] = 3;
	            bytCmdResult[1] = 3;

	            bytCmdResult[11998] = 5;
	            bytCmdResult[11999] = 5;

	            SqlConnection conn = new SqlConnection(msDbConn);
	            conn.Open();

	            PrintDebugMsg(csFuncName, "1");

	            SqlCommand sqlCmd = new SqlCommand("usp_test_bin_1", conn);
	            sqlCmd.CommandType = CommandType.StoredProcedure;

	            sqlCmd.Parameters.Add("@trans_id", SqlDbType.VarChar).Value = "tr002";
	            sqlCmd.Parameters.Add("@dev_id", SqlDbType.VarChar).Value = "dv002";
	            
	            SqlParameter sqlParamCmdResult = new SqlParameter("@cmd_result", SqlDbType.VarBinary);
	            sqlParamCmdResult.Direction = ParameterDirection.Input;
	            sqlParamCmdResult.Size = bytCmdResult.Length;
	            sqlParamCmdResult.Value = bytCmdResult;
	            sqlCmd.Parameters.Add(sqlParamCmdResult);

	            sqlCmd.ExecuteNonQuery();
	            sqlCmd.Dispose();

	            PrintDebugMsg(csFuncName, "2");

	            sqlCmd = new SqlCommand("usp_test_bin_2", conn);
	            sqlCmd.CommandType = CommandType.StoredProcedure;

	            sqlCmd.Parameters.Add("@trans_id", SqlDbType.VarChar).Value = "tr002";
	            sqlCmd.Parameters.Add("@dev_id", SqlDbType.VarChar).Value = "dv002";
	            
	            sqlParamCmdResult = sqlCmd.Parameters.Add("@cmd_result", SqlDbType.VarBinary);
	            // 바이너리자료를 출력하는 파라메터로부터 출력된 자료를 얻으려면
	            //  SqlParameter 오브젝트의 Size를 -1로 설정하여야 한다.
	            sqlParamCmdResult.Size = -1;            
	            sqlParamCmdResult.Direction = ParameterDirection.Output;
	            
	            sqlCmd.ExecuteNonQuery();
	            
	            PrintDebugMsg(csFuncName, "3");

	            bytCmdResult = (byte[])sqlParamCmdResult.Value;

	            PrintDebugMsg(csFuncName, "4 - " +
	                Convert.ToString(bytCmdResult[0]) + ", " +
	                Convert.ToString(bytCmdResult[1]) + ", " +
	                Convert.ToString(bytCmdResult[11998]) + ", " +
	                Convert.ToString(bytCmdResult[11999]));
	            
	            sqlCmd.Dispose();

	            PrintDebugMsg(csFuncName, "5");

                IsCancelledCmd(conn, "001", "201");

	            conn.Close();
                conn.Dispose();
	        }
	        catch (Exception e)
	        {
	            PrintDebugMsg(csFuncName, "except - " + e.ToString());
	            return;
	        }
	    }

	    public void Test()
	    {
	        const string csFuncName = "Test";
	        PrintDebugMsg(csFuncName, "0 - Start");

	        try
	        {
	            SqlConnection conn = new SqlConnection(msDbConn);
	            conn.Open();
	            conn.Close();
                conn.Dispose();
	        }
	        catch (Exception e)
	        {
	            PrintDebugMsg(csFuncName, "1 - " + e.ToString());
	            return;
	        }

	        if (FKWebTools.ConvertFKTimeToNormalTimeString("20121213140123") != "2012-12-13 14:01:23")
	        {
	            PrintDebugMsg(csFuncName, "Error - FK time string convert");
	            return;
	        }

	        if (FKWebTools.GetFKTimeString14(Convert.ToDateTime("2013-2-3 19:7:29")) != "20130203190729")
	        {
	            PrintDebugMsg(csFuncName, "Error - time to FKTime14 convert");
	            return;
	        }

	        PrintDebugMsg(csFuncName, "OK - End");
	    }

	    public void PrintDebugMsg(string astrFunction, string astrMsg)
	    {
	        if (!m_bShowDebugMsg)
	            return;

	        logger.Info(astrFunction + " -- " + astrMsg);
	        FKWebTools.PrintDebug(astrFunction, astrMsg);
	    }

        public void PrintDebugMsg1(string astrFunction, string astrMsg)
        {
            if (!m_bShowDebugMsg)
                return;

            logger.Info(astrFunction + " -- " + astrMsg);
            FKWebTools.PrintDebug(astrFunction, astrMsg);
        }

        // 로그화상을 서버의 지정된 폴더에 보관한다.
        public string SaveLogImageToFile(
            SqlConnection asqlConn,
            string asDevId,
            string asUserId,
            string asIOTime,
            byte[] abytLogImage)
        {
            const string csFuncName = "SaveLogImageToFile";
            string sLogImgFolder;
            string filename = "";

            try
            {
                PrintDebugMsg(csFuncName, "0");

                if (abytLogImage.Length < 1)
                    return null;

                // [2015-1-5 lih] 대방과 다시 토의하고 로그화상을 보관하는 폴더를 web.config에서 읽어들이기로 하였다. 
                /*
                // 자료기지에서 마지막으로 로그화상보관폴더를 읽어들인 시간이 현재시간보다 1분보다 더 이전이면 다시 읽어들인다. 
                // 매번 실시간로그자료가 올라올때마다 로그화상보관폴더를 자료기지에서 읽어들이는것은 자료기지의 부하를 크게 할수 있다.
                TimeSpan    span = DateTime.Now.Subtract(m_dtLastLogImgFolderUpdate);
                if (m_sLogImgRootFolder.Length == 0 || span.TotalSeconds > 60) 
                {
                    // 로그화상을 보관할 폴더의 위치를 자료기지의 tbl_config표에서 얻는다.
                    string sSql = "select @folder = field_value from tbl_config where section_name='data_folder' and field_name='log_img_folder'";
                    SqlCommand sqlCmd = new SqlCommand(sSql, asqlConn);
                    sqlCmd.CommandType = CommandType.Text;

                    SqlParameter sqlParamFolder = new SqlParameter("@folder", SqlDbType.VarChar);
                    sqlParamFolder.Direction = ParameterDirection.Output;
                    sqlParamFolder.Size = 256;
                    sqlCmd.Parameters.Add(sqlParamFolder);

                    PrintDebugMsg(csFuncName, "1");

                    sqlCmd.ExecuteNonQuery();
                    m_sLogImgRootFolder = Convert.ToString(sqlCmd.Parameters["@folder"].Value);
                    sqlCmd.Dispose();
                }
                */
                PrintDebugMsg(csFuncName, "2");

                // 로그화상파일이 한 폴더에 많으면 익스플로러에서 해당 폴더를 펼치는 시간이 길어지므로
                // 기대로그시간의 년월일(YYYYMMDD)에 해당한 새끼폴더를 만들고 거기에 로그화상파일을 보관한다.
                sLogImgFolder = Path.GetDirectoryName(m_sLogImgRootFolder) + "\\" + asIOTime.Substring(0, 8);
                if (!Directory.Exists(sLogImgFolder))
                    Directory.CreateDirectory(sLogImgFolder);

                PrintDebugMsg(csFuncName, "3 - " + sLogImgFolder);

                filename = sLogImgFolder + "\\" + asUserId + "_" + asIOTime + ".jpg";
                FileStream fs = new FileStream(filename, FileMode.OpenOrCreate, FileAccess.Write);
                fs.Write(abytLogImage, 0, abytLogImage.Length);
                fs.Close();

                PrintDebugMsg(csFuncName, "4");
                return filename;
            }
            catch(Exception ex)
            {
                PrintDebugMsg(csFuncName, "Except - " + ex.ToString());
                return null;
            }
        }

        public string InsertRealtimeGLog(
            string asDevId, 
            string asUserId,
            string asVerifyMode,
            string asIOMode,
            string asIOTime,
            byte[] abytLogImage)
	    {
            const string csFuncName = "InsertRealtimeGLog";
            // 기대에서 올려보낸 시간문자렬을 표준시간문자렬로 변환한다.
            string sStdIoTime = FKWebTools.ConvertFKTimeToNormalTimeString(asIOTime);
            string sLogFileName = "";

            PrintDebugMsg(csFuncName, "1 - " + sStdIoTime);

            try
	        {
                PrintDebugMsg(csFuncName, "2");

                if (!FKWebTools.IsValidEngDigitString(asUserId, 24))
	                return "ERROR_INVALID_USER_ID";
	            if (String.IsNullOrEmpty(asVerifyMode))
	                return "ERROR_INVALID_VERIFY_MODE";
	            if (String.IsNullOrEmpty(asIOMode))
	                return "ERROR_INVALID_IO_MODE";
                if (!FKWebTools.IsValidTimeString(sStdIoTime))
	                return "ERROR_INVALID_IO_TIME";
	        }
	        catch (Exception)
	        {
                PrintDebugMsg(csFuncName, "2 - error");
                return "ERROR_INVALID_LOG";
	        }

	        try
	        {
                PrintDebugMsg(csFuncName, "3");

                SqlConnection sqlConn = new SqlConnection(msDbConn);
                sqlConn.Open();

                PrintDebugMsg(csFuncName, "4");

                string strSql;
	            strSql = "INSERT INTO tbl_realtime_glog";
	            strSql = strSql + "(update_time, device_id, user_id, verify_mode, io_mode, io_time,log_image)";
	            strSql = strSql + "VALUES('" + FKWebTools.TimeToString(DateTime.Now) + "', ";
	            strSql = strSql + "@dev_id, @user_id, @verify_mode, @io_mode, @io_time,@log_image)";

                SqlCommand sqlCmd = new SqlCommand(strSql, sqlConn);

                sqlCmd.Parameters.Add("@dev_id", SqlDbType.VarChar).Value = asDevId;
                sqlCmd.Parameters.Add("@user_id", SqlDbType.VarChar).Value = asUserId;
                sqlCmd.Parameters.Add("@verify_mode", SqlDbType.VarChar).Value = asVerifyMode;
                sqlCmd.Parameters.Add("@io_mode", SqlDbType.VarChar).Value = asIOMode;
                sqlCmd.Parameters.Add("@io_time", SqlDbType.VarChar).Value = sStdIoTime;

                // [2014-12-30 lih] 로그화상은 실시간로그자료표에 보관하지 않고 서버의 지정된 폴더안에 보관하도록 토의결정하였다.
                //SqlParameter sqlParamLogImg = new SqlParameter("@log_image", SqlDbType.VarBinary);
                //sqlParamLogImg.Direction = ParameterDirection.Input;
                //sqlParamLogImg.Size = abytLogImage.Length;
                //sqlParamLogImg.Value = abytLogImage;
                //sqlCmd.Parameters.Add(sqlParamLogImg);
                
                 sLogFileName = SaveLogImageToFile(sqlConn, asDevId, asUserId, asIOTime, abytLogImage);
                 if (sLogFileName == null || sLogFileName == "")
                 {
                     sLogFileName = null;
                     PrintDebugMsg(csFuncName, "Error to save log image");
                 }
                sqlCmd.Parameters.Add("@log_image", SqlDbType.VarChar).Value = sLogFileName;

                sqlCmd.ExecuteNonQuery();

                PrintDebugMsg(csFuncName, "5");

                //sLogFileName = SaveLogImageToFile(sqlConn, asDevId, asUserId, asIOTime, abytLogImage);
                
                /*if (SaveLogImageToFile(sqlConn, asDevId, asUserId, asIOTime, abytLogImage) != 0)
                {
                    PrintDebugMsg(csFuncName, "Error to save log image");
                    sqlConn.Close();
                    sqlConn.Dispose();
                    return "ERROR_SAVE_LOG_IMAGE";
                }*/               
                sqlConn.Close();
                sqlConn.Dispose();

                PrintDebugMsg(csFuncName, "6");
	            return "OK";
	        }
	        catch (Exception ex)
            {
                PrintDebugMsg(csFuncName, "Except - 1 - " + ex.ToString());            
	            return "ERROR_DB_ACCESS";
	        }
	    }

        public string InsertRealtimeEnrollData(
            string asDevId,
            string asUserId,        
            byte[] abytUserData)
        {
            const string csFuncName = "InsertRealtimeEnrollData";

            try
            {
                PrintDebugMsg(csFuncName, "1");
                if (!FKWebTools.IsValidEngDigitString(asUserId, 24))
                    return "ERROR_INVALID_USER_ID";
                
                PrintDebugMsg(csFuncName, "2");
                if (abytUserData.Length < 1)
                    return "ERROR_INVALID_USER_DATA";

                SqlConnection sqlConn = new SqlConnection(msDbConn);
                sqlConn.Open();

                PrintDebugMsg(csFuncName, "3");

                string strSql;
                strSql = "INSERT INTO tbl_realtime_enroll_data";
                strSql = strSql + "(update_time, device_id, user_id, user_data)";
                strSql = strSql + "VALUES('" + FKWebTools.TimeToString(DateTime.Now) + "', ";
                strSql = strSql + "@dev_id, @user_id, @user_data)";

                SqlCommand sqlCmd = new SqlCommand(strSql, sqlConn);

                sqlCmd.Parameters.Add("@dev_id", SqlDbType.VarChar).Value = asDevId;
                sqlCmd.Parameters.Add("@user_id", SqlDbType.VarChar).Value = asUserId;

                SqlParameter sqlParamUserData = new SqlParameter("@user_data", SqlDbType.VarBinary);
                sqlParamUserData.Direction = ParameterDirection.Input;
                sqlParamUserData.Size = abytUserData.Length;
                sqlParamUserData.Value = abytUserData;
                sqlCmd.Parameters.Add(sqlParamUserData);

                sqlCmd.ExecuteNonQuery();
                sqlConn.Close();
                sqlConn.Dispose();
                PrintDebugMsg(csFuncName, "4");
                return "OK";
            }
            catch (Exception ex)
            {
                PrintDebugMsg(csFuncName, "Except - 1 - " + ex.ToString());
                return "ERROR_DB_ACCESS";
            }
        }

		// 기대의 접속상태표를 갱신한다.
	    public void UpdateFKDeviceStatus(
	        SqlConnection asqlConn,
	        string asDevId,
	        string asDevName,
	        string asDevTime,
	        string asDevInfo)
	    {
	        const string csFuncName = "UpdateFKDeviceStatus";

            PrintDebugMsg(csFuncName, "0 - DevTime:" + asDevTime + ", DevId:" + asDevId + ", DevName:" + asDevName + ", DevInfo:" + asDevInfo);

	        if (asqlConn.State != ConnectionState.Open)
	            return;

	        try
	        {
	            PrintDebugMsg(csFuncName, "1");

	            SqlCommand sqlCmd = new SqlCommand("usp_update_device_conn_status", asqlConn);
	            sqlCmd.CommandType = CommandType.StoredProcedure;
	            sqlCmd.Parameters.Add("@dev_id", SqlDbType.VarChar).Value = asDevId;
	            sqlCmd.Parameters.Add("@dev_name", SqlDbType.VarChar).Value = asDevName;
	            sqlCmd.Parameters.Add("@tm_last_update", SqlDbType.DateTime).Value = DateTime.Now;
	            sqlCmd.Parameters.Add("@fktm_last_update", SqlDbType.DateTime).Value = FKWebTools.ConvertFKTimeToNormalTimeString(asDevTime);
	            sqlCmd.Parameters.Add("@dev_info", SqlDbType.VarChar).Value = asDevInfo;

	            sqlCmd.ExecuteNonQuery();

	            PrintDebugMsg(csFuncName, "2");
	        }
	        catch (Exception e)
	        {
	            PrintDebugMsg(csFuncName, "Except - 1 - " + e.ToString());            
	        }
	    }
	    
	    public bool CheckResetCmd(
	        SqlConnection asqlConn, 
	        string asDevId, 
	        out string asTransId)
	    {
	        const string csFuncName = "CheckResetCmd";
	        string sTransId = "";
	        
	        PrintDebugMsg(csFuncName, "0 - dev_id:" + asDevId);

	        asTransId = "";
	        
	        if (asqlConn.State != ConnectionState.Open)
	            return false;

	        try
	        {
	            PrintDebugMsg(csFuncName, "1");

	            SqlCommand sqlCmd = new SqlCommand("usp_check_reset_fk_cmd", asqlConn);
	            sqlCmd.CommandType = CommandType.StoredProcedure;
	            
	            sqlCmd.Parameters.Add("@dev_id", SqlDbType.VarChar).Value = asDevId;
	            
	            SqlParameter sqlParamTransId = new SqlParameter("@trans_id", SqlDbType.VarChar);
	            sqlParamTransId.Direction = ParameterDirection.Output;
	            sqlParamTransId.Size = 16;
	            sqlCmd.Parameters.Add(sqlParamTransId);

	            sqlCmd.ExecuteNonQuery();
	            sTransId = Convert.ToString(sqlCmd.Parameters["@trans_id"].Value);
	            sqlCmd.Dispose();

	            PrintDebugMsg(csFuncName, "2 - trans_id:" + sTransId);
	            
	            if (sTransId.Length == 0)
	            {
	                PrintDebugMsg(csFuncName, "3");                
	                return false;
	            }
	            else
	            {
	                asTransId = sTransId;
	                PrintDebugMsg(csFuncName, "4 - reset fk - " + asTransId);
	                return true;
	            }
	        }
	        catch (Exception e)
	        {
	            PrintDebugMsg(csFuncName, "Except - 1 - " + e.ToString());
	            return false;
	        }
	    }

        public bool IsCancelledCmd(
            SqlConnection asqlConn,
            string asDevId,
            string asTransId)
        {
            const string csFuncName = "IsCancelledCmd";
            string sCmdStatus = "";
            
            PrintDebugMsg(csFuncName, "0 - dev_id:" + asDevId + "trans_id:" + asTransId);

            // 만일 자료기지가 접속할수 없는 상황이면 지령도 최소되였다고 보는것이 좋을것이다.
            if (asqlConn.State != ConnectionState.Open)
                return true;

            try
            {
                PrintDebugMsg(csFuncName, "1");

                string sSql = "select @cmd_status = status from tbl_fkcmd_trans where device_id=@dev_id and trans_id=@trans_id and status='CANCELLED'";
                SqlCommand sqlCmd = new SqlCommand(sSql, asqlConn);
                sqlCmd.CommandType = CommandType.Text;

                sqlCmd.Parameters.Add("@dev_id", SqlDbType.VarChar).Value = asDevId;
                sqlCmd.Parameters.Add("@trans_id", SqlDbType.VarChar).Value = asTransId;

                SqlParameter sqlParamStatus = new SqlParameter("@cmd_status", SqlDbType.VarChar);
                sqlParamStatus.Direction = ParameterDirection.Output;
                sqlParamStatus.Size = 16;
                sqlCmd.Parameters.Add(sqlParamStatus);

                PrintDebugMsg(csFuncName, "1.1");
                sqlCmd.ExecuteNonQuery();
                sCmdStatus = Convert.ToString(sqlCmd.Parameters["@cmd_status"].Value);
                sqlCmd.Dispose();

                PrintDebugMsg(csFuncName, "2 - status:" + sCmdStatus);

                if (sCmdStatus.Length == 0)
                {
                    PrintDebugMsg(csFuncName, "3");
                    return false;
                }
                else
                {
                    PrintDebugMsg(csFuncName, "4");
                    return true;
                }
            }
            catch (Exception e)
            {
                PrintDebugMsg(csFuncName, "Except - 1 - " + e.ToString());
                // 례외가 발생하는 경우 해당한 지령이 취소된것으로 본다.
                return true;
            }
        }

        //======================================================================================
        // 만일 "기대시간동기"지령이 발행되였다면 
        //  서버의 현재시간을 얻어 파라메터자료를 만든다.
	    public void MakeSetTimeCmdParamBin(string asCmdCode, ref byte[] abytCmdParam)
	    {
	        if (asCmdCode != "SET_TIME")
	            return;

	        string sCmdParam = "{\"time\":\"" + FKWebTools.GetFKTimeString14(DateTime.Now) + "\"}";
	        CreateBSCommBufferFromString(sCmdParam, out abytCmdParam);
	    }

        //======================================================================================
        // 만일 "펌웨어갱신"지령이 발행되였다면
        //  지적된 등록부에서 가장 최신판본의 펌웨어를 선택하여 파라메터를 만든다.
        public bool MakeUpdateFirmwareCmdParamBin(
            SqlConnection aSqlConn,
            string asTransId,
            string asDevId,
            string asDevInfo, 
            string asCmdCode, ref byte[] abytCmdParam)
	    {
            const string csFuncName = "MakeUpdateFirmwareCmdParamBin";

            if (asCmdCode != "UPDATE_FIRMWARE")
	            return true;

            PrintDebugMsg(csFuncName, "1 - ");

            // 파라메터자료를 초기화한다.
            abytCmdParam = new byte[0];

            // 먼저 해당기대에 대한 정보로부터 펌웨어파일이름의 앞붙이를 얻는다.
            JObject jobjDevInfo = JObject.Parse(asDevInfo);
            string sPrefix = jobjDevInfo["firmware_filename"].ToString();

            PrintDebugMsg(csFuncName, "2 - prefix:" + sPrefix);

            string sSearchPattern = sPrefix + "*.bin";
            string sUpdateFirmwareFile;
            m_sFirmwareBinRootFolder += "\\";            
            string[] sAryFilePaths = Directory.GetFiles(m_sFirmwareBinRootFolder, sSearchPattern,
                                         SearchOption.TopDirectoryOnly);

            sUpdateFirmwareFile = "";
            foreach (string sFileName in sAryFilePaths)
            {
                if (string.Compare(sFileName, sUpdateFirmwareFile, false) > 0) // 파일문자렬을 비교하여 보다 큰 문자렬을 취한다.
                    sUpdateFirmwareFile = sFileName;
            }

            PrintDebugMsg(csFuncName, "3 - firmware file:" + sUpdateFirmwareFile);

            // 만일 update할 펌웨어파일을 찾지 못하였다면 해당 지령을 취소한다.
            if (sUpdateFirmwareFile.Length == 0)
            {
                string sSql = "UPDATE tbl_fkcmd_trans SET return_code='ERROR_NO_FIRMWARE_FILE', status='CANCELLED', update_time = GETDATE() WHERE trans_id='" + asTransId + "'";
                ExecuteSimpleCmd(aSqlConn, sSql);
                return false;
            }

            PrintDebugMsg(csFuncName, "4 - ");

            FileStream fs = new FileStream(sUpdateFirmwareFile, FileMode.Open, FileAccess.Read);
            byte[] bytFirmwareBin = new byte[fs.Length];
            fs.Read(bytFirmwareBin, 0, (int)fs.Length);
            fs.Close();

            PrintDebugMsg(csFuncName, "5 - ");

            // cmd_code : UPDATE_FIRMWARE。
            // cmd_param : {"firmware_file_name":"<1>","firmware_bin_data":"BIN_1"}

            string sFileTitle = Path.GetFileName(sUpdateFirmwareFile);
            string sCmdParam = "{\"firmware_file_name\":\"" + sFileTitle + "\",\"firmware_bin_data\":\"BIN_1\"}";
	        CreateBSCommBufferFromString(sCmdParam, out abytCmdParam);
            AppendBinData(ref abytCmdParam, bytFirmwareBin);
            return true;
	    }

        // functions exported by FpDataConv.dll
        public const int FPCONV_ERR_NONE = 0;

        [DllImport("FpDataConv.dll", CharSet = CharSet.Auto)]
        static extern void FPCONV_Init();
        
        [DllImport("FpDataConv.dll", CharSet = CharSet.Auto)]
        static extern int FPCONV_GetFpDataValidity(byte [] abytFpData);
        
        [DllImport("FpDataConv.dll", CharSet = CharSet.Auto)]
        static extern int FPCONV_GetFpDataVersionAndSize(byte [] abytFpData, ref int anFpDataVer, ref int anFpDataSize);

        [DllImport("FpDataConv.dll", CharSet = CharSet.Auto)]
        static extern int FPCONV_GetFpDataSize(int anFpDataVer, ref int anFpDataSize);

        [DllImport("FpDataConv.dll", CharSet = CharSet.Auto)]
        static extern int FPCONV_Convert(int anSrcFpDataVer, byte[] abytSrcFpData, int anDstFpDataVer, byte[] abytDstFpData);


        public bool ConvertFpDataForDestFK(byte[] abytSrcFpData, int anDstFpDataVer, out byte[] abytDstFpData)
        {
            int nSrcFpDataVer = 0;
            int nSrcFpDataSize = 0;
            int nDstFpDataSize = 0;

            abytDstFpData = new byte[0];

            if (abytSrcFpData.Length < 1)
                return false;

            FPCONV_Init();
            if (FPCONV_GetFpDataVersionAndSize(abytSrcFpData, ref nSrcFpDataVer, ref nSrcFpDataSize) != FPCONV_ERR_NONE)
                return false;
            PrintDebugMsg1(" ", "dataSrcVer =" + nSrcFpDataVer + " dataDestVer =" + anDstFpDataVer);
            if ((nSrcFpDataVer == 0) || (nSrcFpDataSize == 0))
                return false;
            if (nSrcFpDataVer == anDstFpDataVer || anDstFpDataVer == 0)////지문이 필요없는 기대들에는 변환을 진행하지 않고 그냥 내려보낸다.
            {
                abytDstFpData = new byte[abytSrcFpData.Length];
                abytSrcFpData.CopyTo(abytDstFpData, 0);
                return true;
            }

            if (FPCONV_GetFpDataSize(anDstFpDataVer, ref nDstFpDataSize) != FPCONV_ERR_NONE)
                return false;

            abytDstFpData = new byte[nDstFpDataSize];
            if (FPCONV_Convert(nSrcFpDataVer, abytSrcFpData, anDstFpDataVer, abytDstFpData) != FPCONV_ERR_NONE)
            {
                abytDstFpData = new byte[0];
                return false;
            }

            return true;
        }

        public bool ConvertEnrollDataInCmdParamBin(
            SqlConnection aSqlConn,
            string asTransId,
            string asDevId,
            string asDevInfo, 
            string asCmdCode, ref byte[] abytCmdParam)
	    {
            const string csFuncName = "ConvertEnrollDataInCmdParamBin";

            PrintDebugMsg(csFuncName, "1 - ");

            // 먼저 해당기대에 대한 정보로부터 기대에서 관리하는 지문자료의 버젼값을 얻는다.
            JObject jobjDevInfo = JObject.Parse(asDevInfo);
            int DstFpDataVer = Convert.ToInt32(jobjDevInfo["fp_data_ver"]);

            int DstFaceDataVer = Convert.ToInt32(jobjDevInfo["face_data_ver"]);

            PrintDebugMsg(csFuncName, "1 - 1 - FpDataVer=" + Convert.ToString(DstFpDataVer)+" FaceDataVer ="+Convert.ToString(DstFaceDataVer));
/*            if ((DstFpDataVer < 1 || DstFpDataVer > 10000) 
            {
                string sSql = "UPDATE tbl_fkcmd_trans SET return_code='ERROR_INVALID_FK_INFO', status='CANCELLED', update_time = GETDATE() WHERE trans_id='" + asTransId + "'";
                ExecuteSimpleCmd(aSqlConn, sSql);
                return false;
            }
            */
            if (asCmdCode == "SET_ENROLL_DATA")
            {
                PrintDebugMsg(csFuncName, "2 - 1 - ");
                string sCmdParam = GetStringFromBSCommBuffer(abytCmdParam);
                if (sCmdParam.Length == 0)
                {
                    PrintDebugMsg(csFuncName, "2 - 1.1 - ");
                    string sSql = "UPDATE tbl_fkcmd_trans SET return_code='ERROR_INVALID_PARAM', status='CANCELLED', update_time = GETDATE() WHERE trans_id='" + asTransId + "'";
                    ExecuteSimpleCmd(aSqlConn, sSql);
                    return false;
                }
                PrintDebugMsg(csFuncName, "2 - 2 - ");
                JObject jobjCmdParam = JObject.Parse(sCmdParam);
                int bkNo = Convert.ToInt32(jobjCmdParam["backup_number"]);
                if (bkNo > 9)
                {
                    PrintDebugMsg(csFuncName, "2 - 2.1 - ");
                    return true;
                }
                PrintDebugMsg(csFuncName, "2 - 3 - ");
                byte[] bytFpData = GetBinDataFromBSCommBinData(1, abytCmdParam);
                if (bytFpData.Length == 0)
                {
                    PrintDebugMsg(csFuncName, "2 - 3.1 - ");
                    string sSql = "UPDATE tbl_fkcmd_trans SET return_code='ERROR_INVALID_PARAM', status='CANCELLED', update_time = GETDATE() WHERE trans_id='" + asTransId + "'";
                    ExecuteSimpleCmd(aSqlConn, sSql);
                    return false;
                }
                PrintDebugMsg1(csFuncName, "2 - 4 - DstFpDataVer :" + DstFpDataVer);
                byte[] bytFpDataConv;
                
                
                    if (!ConvertFpDataForDestFK(bytFpData, DstFpDataVer, out bytFpDataConv))
                    {
                        PrintDebugMsg(csFuncName, "2 - 4.1 - ");
                        string sSql = "UPDATE tbl_fkcmd_trans SET return_code='ERROR_CONVERT_FP', status='CANCELLED', update_time = GETDATE() WHERE trans_id='" + asTransId + "'";
                        ExecuteSimpleCmd(aSqlConn, sSql);
                        return false;
                    }
                
                PrintDebugMsg(csFuncName, "2 - 5 - ");
                if (!CreateBSCommBufferFromString(sCmdParam, out abytCmdParam))
                {
                    string sSql = "UPDATE tbl_fkcmd_trans SET return_code='ERROR_UNKNOWN', status='CANCELLED', update_time = GETDATE() WHERE trans_id='" + asTransId + "'";
                    ExecuteSimpleCmd(aSqlConn, sSql);
                    return false;
                }
                AppendBinData(ref abytCmdParam, bytFpDataConv);
                PrintDebugMsg(csFuncName, "2 - 6 - ");
                return true;
            }
            else if (asCmdCode == "SET_USER_INFO")
            {
                int k, m;

                PrintDebugMsg(csFuncName, "3 - 1 - ");
                string sCmdParam = GetStringFromBSCommBuffer(abytCmdParam);
                if (sCmdParam.Length == 0)
                {
                    PrintDebugMsg(csFuncName, "3 - 1.1 - ");
                    string sSql = "UPDATE tbl_fkcmd_trans SET return_code='ERROR_INVALID_PARAM', status='CANCELLED', update_time = GETDATE() WHERE trans_id='" + asTransId + "'";
                    ExecuteSimpleCmd(aSqlConn, sSql);
                    return false;
                }

                PrintDebugMsg(csFuncName, "3 - 2 - ");
                List<EnrollData> listEnrollData = new List<EnrollData>();
                string user_id;
                string user_name;
                string user_privilege;
                byte[] user_photo = new byte[0];
                
                try
                {
                    JObject jobjCmdParam = JObject.Parse(sCmdParam);
                    // 지령파라메터의 각 필드의 값들을 얻어낸다.
                    user_id = jobjCmdParam["user_id"].ToString();
                    user_name = jobjCmdParam["user_name"].ToString();
                    user_privilege = jobjCmdParam["user_privilege"].ToString();                    
                    // 지령파라메터에 사용자의 등록화상자료가 포함되여 있는가 본다. 있으면 등록화상자료를 파라메터자료로 부터 얻어낸다.
                    if (jobjCmdParam.SelectToken("user_photo") != null)
                    {
                        PrintDebugMsg(csFuncName, "3 - 3 - ");
                        string  sTemp;
                        sTemp = jobjCmdParam["user_photo"].ToString();
                        m = Convert.ToInt32(sTemp.Substring(4, sTemp.Length - 4));
                        if (m >= 1)
                            user_photo = GetBinDataFromBSCommBinData(m, abytCmdParam);
                    }

                    PrintDebugMsg(csFuncName, "3 - 4 - ");
                    // 지령파라메터에 포함되여 있는 등록자료들을 하나씩 꺼내면서 등록자료가 지문자료에 해당한 것이면 목적기대에 맞게 변환을 진행한다.
                    JArray jarrEnrollData = (JArray)jobjCmdParam["enroll_data_array"];
                    EnrollData ed;
                    JObject jobjOneED;
                    for (k = 0; k < jarrEnrollData.Count; k++)
                    {
                        string  sTemp;
                        byte[] bytTemp;
                        jobjOneED = (JObject)jarrEnrollData[k];
                        ed = new EnrollData();
                        ed.BackupNumber = Convert.ToInt32(jobjOneED["backup_number"]);

                        PrintDebugMsg(csFuncName, "3 - 5 - k=" + Convert.ToString(k));
                        // 등록자료에 해당한 바이너리자료를 파라메터자료로 부터 얻어낸다.
                        sTemp = jobjOneED["enroll_data"].ToString();
                        if (sTemp.Length < 5)
                        {
                            PrintDebugMsg(csFuncName, "3 - 5.1 - k=" + Convert.ToString(k));
                            string sSql = "UPDATE tbl_fkcmd_trans SET return_code='ERROR_INVALID_PARAM', status='CANCELLED', update_time = GETDATE() WHERE trans_id='" + asTransId + "'";
                            ExecuteSimpleCmd(aSqlConn, sSql);
                            return false;
                        }

                        PrintDebugMsg(csFuncName, "3 - 6 - k=" + Convert.ToString(k));
                        // 등록자료가 파라메터바이너리자료의 몇번째 위치에 놓이는가를 판단한다.
                        m = Convert.ToInt32(sTemp.Substring(4, sTemp.Length - 4));
                        if (m < 1)
                        {
                            PrintDebugMsg(csFuncName, "3 - 6.1 - k=" + Convert.ToString(k));
                            string sSql = "UPDATE tbl_fkcmd_trans SET return_code='ERROR_INVALID_PARAM', status='CANCELLED', update_time = GETDATE() WHERE trans_id='" + asTransId + "'";
                            ExecuteSimpleCmd(aSqlConn, sSql);
                            return false;
                        }

                        PrintDebugMsg(csFuncName, "3 - 7 - k=" + Convert.ToString(k));
                        bytTemp = GetBinDataFromBSCommBinData(m, abytCmdParam);
                        // 등록자료가 지문자료인 경우에만 변환을 진행한다.
                        if ((ed.BackupNumber >= 0) && (ed.BackupNumber <= 9))
                        {
                            PrintDebugMsg(csFuncName, "3 - 8 - k=" + Convert.ToString(k) + " - src_size=" + Convert.ToString(bytTemp.Length));
                            if (!ConvertFpDataForDestFK(bytTemp, DstFpDataVer, out ed.bytData))
                                {
                                    PrintDebugMsg(csFuncName, "3 - 8.1 - k=" + Convert.ToString(k));
                                    string sSql = "UPDATE tbl_fkcmd_trans SET return_code='ERROR_INVALID_PARAM', status='CANCELLED', update_time = GETDATE() WHERE trans_id='" + asTransId + "'";
                                    ExecuteSimpleCmd(aSqlConn, sSql);
                                    return false;
                                }
                            
                            PrintDebugMsg(csFuncName, "3 - 8 - 1 - k=" + Convert.ToString(k) + " - conv_size=" + Convert.ToString(ed.bytData.Length));
                        } 
                        else 
                        {
                            PrintDebugMsg(csFuncName, "3 - 9 - k=" + Convert.ToString(k));
                            ed.bytData = bytTemp;
                        }
                        PrintDebugMsg(csFuncName, "3 - 10 - k=" + Convert.ToString(k));
                        listEnrollData.Add(ed);
                    }
                }
                catch(Exception ex)
                {
                    PrintDebugMsg(csFuncName, "3 - except - " + ex.ToString());
                    string sSql = "UPDATE tbl_fkcmd_trans SET return_code='ERROR_INVALID_PARAM', status='CANCELLED', update_time = GETDATE() WHERE trans_id='" + asTransId + "'";
                    ExecuteSimpleCmd(aSqlConn, sSql);
                    return false;
                }

                PrintDebugMsg(csFuncName, "3 - 11 - ");
                if (listEnrollData.Count == 0)
                {
                    PrintDebugMsg(csFuncName, "3 - 11 - 1");
                    return true;
                }

                PrintDebugMsg(csFuncName, "3 - 12 - ");
                // 지령파라메터문자렬을 다시 생성한다.
                JObject jobjCmdParamNew = new JObject();
                jobjCmdParamNew.Add("user_id", user_id);
                jobjCmdParamNew.Add("user_name", user_name);
                jobjCmdParamNew.Add("user_privilege", user_privilege);

                PrintDebugMsg(csFuncName, "3 - 13 - ");
                JArray jarrED = new JArray();
                EnrollData  ed1;
                m = 1;
                for (k = 0; k < listEnrollData.Count; k++)
                {
                    JObject jobjOneED = new JObject();
                    ed1 = listEnrollData[k];
                    jobjOneED.Add("backup_number", ed1.BackupNumber);
                    string sTemp = "BIN_" + Convert.ToString(m);
                    jobjOneED.Add("enroll_data", sTemp);
                    jarrED.Add(jobjOneED);
                    m++;
                }
                jobjCmdParamNew.Add("enroll_data_array", jarrED);

                PrintDebugMsg(csFuncName, "3 - 14 - ");
                if (user_photo.Length > 0)
                    jobjCmdParamNew.Add("user_photo", "BIN_" + Convert.ToString(m));

                PrintDebugMsg(csFuncName, "3 - 15 - ");
                // 지령파라메터문자렬을 다시 생성하여 지령파라메터에 대한 bin자료를 만든다.
                sCmdParam = jobjCmdParamNew.ToString();
                if (!CreateBSCommBufferFromString(sCmdParam, out abytCmdParam))
                {
                    PrintDebugMsg(csFuncName, "3 - 15.1 - ");
                    string sSql = "UPDATE tbl_fkcmd_trans SET return_code='ERROR_UNKNOWN', status='CANCELLED', update_time = GETDATE() WHERE trans_id='" + asTransId + "'";
                    ExecuteSimpleCmd(aSqlConn, sSql);
                    return false;
                }
                // 변환을 거친 등록자료들을 지령파라메터bin자료에 순서대로 추가한다.
                for (k = 0; k < listEnrollData.Count; k++)
                {
                    ed1 = listEnrollData[k];
                    AppendBinData(ref abytCmdParam, ed1.bytData);
                }
                PrintDebugMsg(csFuncName, "3 - 16 - ");
                if (user_photo.Length > 0)
                    AppendBinData(ref abytCmdParam, user_photo);

                PrintDebugMsg(csFuncName, "3 - 17 - ");
                return true;
            }
            else
            {
                PrintDebugMsg(csFuncName, "3 - 18 - ");
                return true;
            }
        }

        //=============================================================================
        // 기대가 자기에 대해 발행된 조작자지령을 얻어갈때 호출되는 함수이다.
	    public void ReceiveCmd(
	        string asDevId,
	        string asDevName,
	        string asDevTime, 
	        string asDevInfo,
	        out string asResponseCode,
	        out string asTransId,
	        out string asCmdCode,
	        out byte [] abytCmdParamBin)
	    {
	        const string csFuncName = "ReceiveCmd";

	        asResponseCode = "ERROR";
	        asTransId = "";
	        asCmdCode = "";
	        abytCmdParamBin = new byte[0];

	        SqlConnection sqlConn = null;

	        PrintDebugMsg(csFuncName, "0 - Start");

	        try
	        {
	            sqlConn = new SqlConnection(msDbConn);
	            sqlConn.Open();
	            PrintDebugMsg(csFuncName, "1 - Db connected");
	        }
	        catch (Exception e)
	        {
	            PrintDebugMsg(csFuncName, "Error - Not connected to db" + e.ToString() + ", ConnString=" + sqlConn.ConnectionString);
	            sqlConn.Close();
                sqlConn.Dispose();
	            asResponseCode = "ERROR_DB_CONNECT";
	            return;
	        }

	        PrintDebugMsg(csFuncName, "2");
	        
	        // "기대재기동"지령이 발행된것이 있으면 그것을 기대로 내려보낸다.
	        if (CheckResetCmd(sqlConn, asDevId, out asTransId))
	        {
	            PrintDebugMsg(csFuncName, "2.1 - " + asTransId);
	            sqlConn.Close();
                sqlConn.Dispose();
	            asResponseCode = "RESET_FK";
	            return;
	        }

	        PrintDebugMsg(csFuncName, "3");
	        
	        // 기대의 련결상태를 갱신한다.
	        UpdateFKDeviceStatus(sqlConn, asDevId, asDevName, asDevTime, asDevInfo);

	        // 기대에 대해 발행된 조작자지령이 있으면 그것을 기대로 내려보낸다.
	        try
	        {
	            PrintDebugMsg(csFuncName, "4");

	            SqlCommand sqlCmd = new SqlCommand("usp_receive_cmd", sqlConn);
	            sqlCmd.CommandType = CommandType.StoredProcedure;
	            
	            sqlCmd.Parameters.Add("@dev_id", SqlDbType.VarChar).Value = asDevId;
	            
	            SqlParameter sqlParamTransId = new SqlParameter("@trans_id", SqlDbType.VarChar);
	            sqlParamTransId.Direction = ParameterDirection.Output;
	            sqlParamTransId.Size = 16;
	            sqlCmd.Parameters.Add(sqlParamTransId);
	            
	            SqlParameter sqlParamCmdCode = new SqlParameter("@cmd_code", SqlDbType.VarChar);
	            sqlParamCmdCode.Direction = ParameterDirection.Output;
	            sqlParamCmdCode.Size = 32;
	            sqlCmd.Parameters.Add(sqlParamCmdCode);
	            
	            SqlParameter sqlParamCmdParamBin = new SqlParameter("@cmd_param_bin", SqlDbType.VarBinary);
	            sqlParamCmdParamBin.Direction = ParameterDirection.Output;
	            sqlParamCmdParamBin.Size = -1;
	            sqlCmd.Parameters.Add(sqlParamCmdParamBin);

	            sqlCmd.ExecuteNonQuery();

	            PrintDebugMsg(csFuncName, "5");
	            
	            asTransId = Convert.ToString(sqlCmd.Parameters["@trans_id"].Value);            
	            if (asTransId.Length == 0)
	            {
	                PrintDebugMsg(csFuncName, "5 - no cmd");
	                asResponseCode = "ERROR_NO_CMD";
	            }
	            else
	            {
	                asCmdCode = Convert.ToString(sqlCmd.Parameters["@cmd_code"].Value);
                    if (sqlParamCmdParamBin.Value.GetType().IsArray)
	                    abytCmdParamBin = (byte[])sqlParamCmdParamBin.Value;

	                PrintDebugMsg(csFuncName, "6 - trans_id:" + asTransId + "cmd_code:" + asCmdCode);
	                
	                if (asCmdCode == "SET_TIME")
                    {
                        // 만일 "기대시간동기"지령이 발행된것이 발견되면 동기시킬 시간을 써버의 시간으로 설정한다.
                        MakeSetTimeCmdParamBin(asCmdCode, ref abytCmdParamBin);
                    }
                    else if (asCmdCode == "UPDATE_FIRMWARE")
                    {
                        // 만일 "펌웨어갱신"지령이 발행된것이 발견되면 지적된 등록부에서 가장 최신판본의 펌웨어를 선택하여 파라메터를 만든다.
                        if (MakeUpdateFirmwareCmdParamBin(sqlConn, asTransId, asDevId, asDevInfo, asCmdCode, ref abytCmdParamBin) == false)
                        {
                            PrintDebugMsg(csFuncName, "7 - error get cmd param(UPDATE_FIRMWARE)");
                            abytCmdParamBin = new byte[0];
                            sqlCmd.Dispose();
                            sqlConn.Close();
                            sqlConn.Dispose();
                            asResponseCode = "ERROR_GET_PARAM";
                            return;
                        }
                    }
                    else if ((asCmdCode == "SET_ENROLL_DATA") ||(asCmdCode == "SET_USER_INFO"))
                    {
                        // 만일 "등록자료써넣기" 혹은 "사용자전체정보써넣기"지령이 발행된것이 있으면 지문자료, 얼굴자료 등을 기대의 류형에 맞게 변환하여 다시 파라메터자료를 만든다.
                        
                        // 먼저 기대의 지문자료버젼, 얼굴자료버젼을 기대로부터 올라온 기대정보로부터 얻어낸다.
                        if (ConvertEnrollDataInCmdParamBin(sqlConn, asTransId, asDevId, asDevInfo, asCmdCode, ref abytCmdParamBin) == false)
                        {
                            PrintDebugMsg(csFuncName, "8 - error get cmd param(UPDATE_FIRMWARE)");
                            abytCmdParamBin = new byte[0];
                            sqlCmd.Dispose();
                            sqlConn.Close();
                            sqlConn.Dispose();
                            asResponseCode = "ERROR_GET_PARAM";
                            return;
                        }
                    }
                    
                    asResponseCode = "OK";
	            }
	            
	            sqlCmd.Dispose();

	            PrintDebugMsg(csFuncName, "11");
	            sqlConn.Close();
                sqlConn.Dispose();
	            return;
	        }
	        catch (Exception e)
	        {
	            PrintDebugMsg(csFuncName, "Except - 1 - " + e.ToString());
	            sqlConn.Close();
                sqlConn.Dispose();

	            asResponseCode = "ERROR";
	            asTransId = "";
	            asCmdCode = "";
	            abytCmdParamBin = new byte[0];
	        }
	    }

        //===================================================================================
        // dev_id에 해당한 기대의 상태정보로부터 이 기대로부터 올라오는 자료들을 해석하기 위한 
        //  서고의 이름을 얻는다.
        public string GetFKDataLibName(
           SqlConnection asqlConn,
           string asDevId)
        {
            if (asqlConn.State != ConnectionState.Open)
                return "";

            string sSql;
            SqlCommand sqlCmd;
            SqlDataReader sqlDr;

            sSql = "SELECT device_info from tbl_fkdevice_status WHERE device_id=@dev_id";
            sqlCmd = new SqlCommand(sSql, asqlConn);
            sqlCmd.CommandType = CommandType.Text;
            sqlCmd.Parameters.Add("@dev_id", SqlDbType.VarChar).Value = asDevId;

            sqlDr = sqlCmd.ExecuteReader();
            if (!sqlDr.Read())
            {
                sqlDr.Close();
                sqlCmd.Dispose();
                return "";
            }            
            string sDevInfo = FKWebTools.GetStringFromObject(sqlDr[0]);
            
            sqlDr.Close();
            sqlCmd.Dispose();

            try
            {
                JObject jobjDevInfo = JObject.Parse(sDevInfo);
                string sFKDataLib = jobjDevInfo["fk_bin_data_lib"].ToString();
                return sFKDataLib;
            }
            catch (Exception)
            {
                return "";
            }
        }

        //===================================================================================
        // trans_id에 해당한 지령코드가 파라메터로 준 지령코드와 같은가 확인한다. 
        public bool IsCmdCodeEqual(
           SqlConnection asqlConn,
           string asTransId,
           string asCmdCode)
        {
            if (asqlConn.State != ConnectionState.Open)
                return false;

            string sSql;
            SqlCommand sqlCmd;
            SqlDataReader sqlDr;

            // 지령코드가 파라메터에 준 코드와 같은가를 알아본다.
            sSql = "SELECT trans_id from tbl_fkcmd_trans WHERE trans_id =@trans_id AND cmd_code=@cmd_code";
            sqlCmd = new SqlCommand(sSql, asqlConn);
            sqlCmd.CommandType = CommandType.Text;
            sqlCmd.Parameters.Add("@trans_id", SqlDbType.VarChar).Value = asTransId;
            sqlCmd.Parameters.Add("@cmd_code", SqlDbType.VarChar).Value = asCmdCode;

            sqlDr = sqlCmd.ExecuteReader();
            if (!sqlDr.HasRows)
            {
                sqlDr.Close();
                sqlCmd.Dispose();
                return false;
            }
            sqlDr.Close();
            sqlCmd.Dispose();
            return true;
        }

        //===================================================================================
        // SQL지령이 수행되여 결과가 필요없는 간단한 지령들을 수행한다.
        public void ExecuteSimpleCmd(SqlConnection asqlConn, string asSql)
        {
            SqlCommand sqlCmd = new SqlCommand(asSql, asqlConn);
            sqlCmd.CommandType = CommandType.Text;
            sqlCmd.ExecuteNonQuery();
            sqlCmd.Dispose();
        }

        //===================================================================================
        // trans_id에 해당한 지령코드가 'GET_LOG_DATA'이면 지령처리결과를 해석하여 로그자료들을 
        //  tbl_fkcmd_trans_cmd_result_log_data표에 보관한다.
        //
        // 자료기지가 열려진 상태가 아니면 true를 복귀한다. 
        // trans_id에 해당한 지령코드가 'GET_LOG_DATA'가 아니면 true를 복귀한다.
        // 보관도중 오유가 발생하면 false를 복귀한다.
        public bool SaveCmdResultLogData(
           SqlConnection asqlConn,
           string asTransId,
           string asDevId,           
           byte[] abytCmdResult)
        {
            const string csFuncName = "SaveCmdResultLogData";

            PrintDebugMsg(csFuncName, "1");
            if (asqlConn.State != ConnectionState.Open)
                return true;

            PrintDebugMsg(csFuncName, "2");
            // 지령코드가 GET_LOG_DATA 인가를 알아본다.
            if (!IsCmdCodeEqual(asqlConn, asTransId, "GET_LOG_DATA"))
                return true;

            PrintDebugMsg(csFuncName, "3");
            // 기대의 로그자료해석을 어느 서고로 하여야 하는가를 알아본다.
            string sFKDataLib = GetFKDataLibName(asqlConn, asDevId);

            // 결과자료를 해석한다.
            string sJson;
            byte[] bytLogList;
            GetStringAnd1stBinaryFromBSCommBuffer(abytCmdResult, out sJson, out bytLogList);
            if (sJson.Length == 0)
                return false;

            PrintDebugMsg(csFuncName, "4");
            int cntLog, sizeOneLog;
            const string csTblLog = "tbl_fkcmd_trans_cmd_result_log_data";
            try
            {
                JObject jobjLogInfo = JObject.Parse(sJson);
                cntLog = Convert.ToInt32(jobjLogInfo["log_count"].ToString());
                sizeOneLog = Convert.ToInt32(jobjLogInfo["one_log_size"].ToString());

                PrintDebugMsg(csFuncName, "4-1  cntLog =" + cntLog + ",sizeOneLog = " + sizeOneLog + ",length = " + bytLogList.Length);

                if (bytLogList.Length < cntLog*sizeOneLog)
                    return false;

                PrintDebugMsg1(csFuncName, "5 log count="+Convert.ToString(cntLog));
                if (FKWebTools.CompareStringNoCase(sFKDataLib, "FKDataHS101") == 0)
                {
                    if (sizeOneLog != 12)
                        return false;

                    PrintDebugMsg(csFuncName, "6 - HS101");
                    // 이전에 해당 trans_id, dev_id에 대하여 얻은 로그자료들이 있으면 모두 삭제한다.
                    string sSqlTemp = "DELETE FROM " + csTblLog + " WHERE trans_id='" + asTransId +
                        "' AND device_id='" + asDevId + "'";
                    ExecuteSimpleCmd(asqlConn, sSqlTemp);

                    int k;
                    byte[] bytOneLog = new byte[sizeOneLog];
                    for (k = 0; k < cntLog; k++)
                    {
                        Buffer.BlockCopy(
                            bytLogList, k * sizeOneLog,
                            bytOneLog, 0,
                            sizeOneLog);

                        FKDataHS101.GLog glog = new FKDataHS101.GLog(bytOneLog);
                        string sUserId = Convert.ToString(glog.UserId);
                        string sIoMode = FKDataHS101.GLog.GetInOutModeString(glog.IoMode);
                        string sVerifyMode = FKDataHS101.GLog.GetVerifyModeString(glog.VerifyMode);
                        string sIoTime = glog.GetIoTimeString();

                        sSqlTemp = "INSERT INTO  " + csTblLog;
                        sSqlTemp += "(trans_id, device_id, user_id, verify_mode, io_mode, io_time)";
                        sSqlTemp += "VALUES('" + asTransId + "', '";
                        sSqlTemp += asDevId + "', '";
                        sSqlTemp += sUserId + "', '";
                        sSqlTemp += sVerifyMode + "', '";
                        sSqlTemp += sIoMode + "', '";
                        sSqlTemp += sIoTime + "')";                        
                        
                        ExecuteSimpleCmd(asqlConn, sSqlTemp);
                    }
                    PrintDebugMsg(csFuncName, "7 - HS101");
                }

                else if (FKWebTools.CompareStringNoCase(sFKDataLib, "FKDataHS102") == 0)
                {
                    if (sizeOneLog != 32)
                        return false;

                    PrintDebugMsg(csFuncName, "6 - HS102");
                    // 이전에 해당 trans_id, dev_id에 대하여 얻은 로그자료들이 있으면 모두 삭제한다.
                    string sSqlTemp = "DELETE FROM " + csTblLog + " WHERE trans_id='" + asTransId +
                        "' AND device_id='" + asDevId + "'";
                    ExecuteSimpleCmd(asqlConn, sSqlTemp);

                    int k;
                    byte[] bytOneLog = new byte[sizeOneLog];
                    for (k = 0; k < cntLog; k++)
                    {
                        Buffer.BlockCopy(
                            bytLogList, k * sizeOneLog,
                            bytOneLog, 0,
                            sizeOneLog);

                        FKDataHS102.GLog glog = new FKDataHS102.GLog(bytOneLog);
                        string sUserId = glog.GetUserID();
                        string sIoMode = FKDataHS102.GLog.GetInOutModeString(glog.IoMode);
                        string sVerifyMode = FKDataHS102.GLog.GetVerifyModeString(glog.VerifyMode);
                        string sIoTime = glog.GetIoTimeString();

                        sSqlTemp = "INSERT INTO  " + csTblLog;
                        sSqlTemp += "(trans_id, device_id, user_id, verify_mode, io_mode, io_time)";
                        sSqlTemp += "VALUES('" + asTransId + "', '";
                        sSqlTemp += asDevId + "', '";
                        sSqlTemp += sUserId + "', '";
                        sSqlTemp += sVerifyMode + "', '";
                        sSqlTemp += sIoMode + "', '";
                        sSqlTemp += sIoTime + "')";

                        ExecuteSimpleCmd(asqlConn, sSqlTemp);
                    }
                    PrintDebugMsg(csFuncName, "7 - HS102");
                }

                else if (FKWebTools.CompareStringNoCase(sFKDataLib, "FKDataHS100") == 0)
                {
                    if (sizeOneLog != 12)
                        return false;

                    PrintDebugMsg1(csFuncName, "6 - HS100   cnt=" + Convert.ToString(cntLog));
                    // 이전에 해당 trans_id, dev_id에 대하여 얻은 로그자료들이 있으면 모두 삭제한다.
                    string sSqlTemp = "DELETE FROM " + csTblLog + " WHERE trans_id='" + asTransId +
                        "' AND device_id='" + asDevId + "'";
                    ExecuteSimpleCmd(asqlConn, sSqlTemp);

                    int k;
                    byte[] bytOneLog = new byte[sizeOneLog];
                    for (k = 0; k < cntLog; k++)
                    {
                        Buffer.BlockCopy(
                            bytLogList, k * sizeOneLog,
                            bytOneLog, 0,
                            sizeOneLog);

                        FKDataHS100.GLog glog = new FKDataHS100.GLog(bytOneLog);
                        string sUserId = Convert.ToString(glog.UserId);
                        string sIoMode = FKDataHS100.GLog.GetInOutModeString(glog.IoMode);
                        string sVerifyMode = FKDataHS100.GLog.GetVerifyModeString(glog.VerifyMode);
                        string sIoTime = glog.GetIoTimeString();

                        sSqlTemp = "INSERT INTO  " + csTblLog;
                        sSqlTemp += "(trans_id, device_id, user_id, verify_mode, io_mode, io_time)";
                        sSqlTemp += "VALUES('" + asTransId + "', '";
                        sSqlTemp += asDevId + "', '";
                        sSqlTemp += sUserId + "', '";
                        sSqlTemp += sVerifyMode + "', '";
                        sSqlTemp += sIoMode + "', '";
                        sSqlTemp += sIoTime + "')";

                        ExecuteSimpleCmd(asqlConn, sSqlTemp);
                      //  PrintDebugMsg1(csFuncName, "6 - HS100 num="+Convert.ToString(k)+"---->"+sSqlTemp);
                    }
                    PrintDebugMsg(csFuncName, "7 - HS100");

                }
                else
                {
                    PrintDebugMsg(csFuncName, "error - 1");
                    return false;
                }
            }
            catch(Exception ex)
            {
                PrintDebugMsg1(csFuncName, "error - 2 - " + ex.ToString());
                return false;
            }
            PrintDebugMsg(csFuncName, "8");
            return true;            
        }

        //===================================================================================
        // trans_id에 해당한 지령코드가 'GET_USER_ID_LIST'이면 지령처리결과를 해석하여 로그자료들을 
        //  tbl_fkcmd_trans_cmd_result_user_id_list표에 보관한다.
        //
        // 자료기지가 열려진 상태가 아니면 true를 복귀한다. 
        // trans_id에 해당한 지령코드가 'GET_USER_ID_LIST'가 아니면 true를 복귀한다.
        // 보관도중 오유가 발생하면 false를 복귀한다.
        public bool SaveUserIdList(
           SqlConnection asqlConn,
           string asTransId,
           string asDevId,
           byte[] abytCmdResult)
        {
            const string csFuncName = "SaveUserIdList";
            
            PrintDebugMsg(csFuncName, "1");
            if (asqlConn.State != ConnectionState.Open)
                return true;

            PrintDebugMsg(csFuncName, "2" + " astrans_id = " + asTransId);
            // 지령코드가 GET_USER_ID_LIST 인가를 알아본다.
            if (!IsCmdCodeEqual(asqlConn, asTransId, "GET_USER_ID_LIST"))
            {
                PrintDebugMsg(csFuncName, "2-1" + " astrans_id = " + asTransId);
                return true;
            }

            PrintDebugMsg(csFuncName, "3");
            // 기대의 로그자료해석을 어느 서고로 하여야 하는가를 알아본다.
            string sFKDataLib = GetFKDataLibName(asqlConn, asDevId);

            // 결과자료를 해석한다.
            string sJson;
            byte[] bytUserIdList;
            GetStringAnd1stBinaryFromBSCommBuffer(abytCmdResult, out sJson, out bytUserIdList);
            if (sJson.Length == 0)
                return false;

            PrintDebugMsg(csFuncName, "4");
            int cntUserId, sizeOneUserId;
            const string csTblUserId = "tbl_fkcmd_trans_cmd_result_user_id_list";
            try
            {
                JObject jobjLogInfo = JObject.Parse(sJson);
                cntUserId = Convert.ToInt32(jobjLogInfo["user_id_count"].ToString());
                sizeOneUserId = Convert.ToInt32(jobjLogInfo["one_user_id_size"].ToString());
                if (bytUserIdList.Length < cntUserId * sizeOneUserId)
                    return false;

                PrintDebugMsg(csFuncName, "5");
                if (FKWebTools.CompareStringNoCase(sFKDataLib, "FKDataHS101") == 0)
                {
                    if (sizeOneUserId != 8)
                        return false;

                    PrintDebugMsg(csFuncName, "6 - HS101");
                    
                    string sSqlTemp = "DELETE FROM " + csTblUserId + " WHERE trans_id='" + asTransId +
                        "' AND device_id='" + asDevId + "'";
                    ExecuteSimpleCmd(asqlConn, sSqlTemp);

                    int k;
                    byte[] bytOneUserId = new byte[sizeOneUserId];
                    byte[] bytEnrolledFlag;
                    for (k = 0; k < cntUserId; k++)
                    {
                        int nEnrollDataCount = 0;

                        Buffer.BlockCopy(
                            bytUserIdList, k * sizeOneUserId,
                            bytOneUserId, 0,
                            sizeOneUserId);

                        FKDataHS101.UserIdInfo usrid = new FKDataHS101.UserIdInfo(bytOneUserId);
                        string sUserId = Convert.ToString(usrid.UserId);
                        usrid.GetBackupNumberEnrolledFlag(out bytEnrolledFlag);
                        //PrintDebugMsg(csFuncName, "6 - " + FKWebTools.GetHexString(bytEnrolledFlag));
                        for (int bkn = 0; bkn < bytEnrolledFlag.Length; bkn++)
                        {
                            if (bytEnrolledFlag[bkn] == 1)
                            {
                                nEnrollDataCount++;
                                sSqlTemp = "INSERT INTO " + csTblUserId;
                                sSqlTemp += "(trans_id, device_id, user_id, backup_number)";
                                sSqlTemp += "VALUES('" + asTransId + "', '";
                                sSqlTemp += asDevId + "', '";
                                sSqlTemp += sUserId + "', ";
                                sSqlTemp += bkn + ")";

                                ExecuteSimpleCmd(asqlConn, sSqlTemp);
                            }
                        }
                        if (nEnrollDataCount == 0)
                        {
                            sSqlTemp = "INSERT INTO " + csTblUserId;
                            sSqlTemp += "(trans_id, device_id, user_id, backup_number)";
                            sSqlTemp += "VALUES('" + asTransId + "', '";
                            sSqlTemp += asDevId + "', '";
                            sSqlTemp += sUserId + "', ";
                            sSqlTemp += "-1)";

                            ExecuteSimpleCmd(asqlConn, sSqlTemp);
                        }
                    }
                    PrintDebugMsg(csFuncName, "7 - HS101");
                }
                else if (FKWebTools.CompareStringNoCase(sFKDataLib, "FKDataHS102") == 0)
                {
                    if (sizeOneUserId != 20)
                        return false;

                    PrintDebugMsg(csFuncName, "6 - HS102");

                    string sSqlTemp = "DELETE FROM " + csTblUserId + " WHERE trans_id='" + asTransId +
                        "' AND device_id='" + asDevId + "'";
                    ExecuteSimpleCmd(asqlConn, sSqlTemp);

                    int k;
                    byte[] bytOneUserId = new byte[sizeOneUserId];
                    byte[] bytEnrolledFlag;
                    for (k = 0; k < cntUserId; k++)
                    {
                        int nEnrollDataCount = 0;

                        Buffer.BlockCopy(
                            bytUserIdList, k * sizeOneUserId,
                            bytOneUserId, 0,
                            sizeOneUserId);

                        FKDataHS102.UserIdInfo usrid = new FKDataHS102.UserIdInfo(bytOneUserId);
                        string sUserId = usrid.GetUserID();
                        usrid.GetBackupNumberEnrolledFlag(out bytEnrolledFlag);
                        //PrintDebugMsg(csFuncName, "6 - " + FKWebTools.GetHexString(bytEnrolledFlag));
                        for (int bkn = 0; bkn < bytEnrolledFlag.Length; bkn++)
                        {
                            if (bytEnrolledFlag[bkn] == 1)
                            {
                                nEnrollDataCount++;
                                sSqlTemp = "INSERT INTO " + csTblUserId;
                                sSqlTemp += "(trans_id, device_id, user_id, backup_number)";
                                sSqlTemp += "VALUES('" + asTransId + "', '";
                                sSqlTemp += asDevId + "', '";
                                sSqlTemp += sUserId + "', ";
                                sSqlTemp += bkn + ")";

                                ExecuteSimpleCmd(asqlConn, sSqlTemp);
                            }
                        }
                        if (nEnrollDataCount == 0)
                        {
                            sSqlTemp = "INSERT INTO " + csTblUserId;
                            sSqlTemp += "(trans_id, device_id, user_id, backup_number)";
                            sSqlTemp += "VALUES('" + asTransId + "', '";
                            sSqlTemp += asDevId + "', '";
                            sSqlTemp += sUserId + "', ";
                            sSqlTemp += "-1)";

                            ExecuteSimpleCmd(asqlConn, sSqlTemp);
                        }
                    }
                    PrintDebugMsg(csFuncName, "7 - HS102");
                }
                else if (FKWebTools.CompareStringNoCase(sFKDataLib, "FKDataHS100") == 0)
                {
                    if (sizeOneUserId != 8)
                        return false;

                    PrintDebugMsg(csFuncName, "6 - HS100");

                    string sSqlTemp = "DELETE FROM " + csTblUserId + " WHERE trans_id='" + asTransId +
                        "' AND device_id='" + asDevId + "'";
                    ExecuteSimpleCmd(asqlConn, sSqlTemp);

                    int k;
                    byte[] bytOneUserId = new byte[sizeOneUserId];
                   // byte[] bytEnrolledFlag;
                    for (k = 0; k < cntUserId; k++)
                    {
                      //  int nEnrollDataCount = 0;

                        Buffer.BlockCopy(
                            bytUserIdList, k * sizeOneUserId,
                            bytOneUserId, 0,
                            sizeOneUserId);

                        FKDataHS100.UserIdInfo usrid = new FKDataHS100.UserIdInfo(bytOneUserId);
                        string sUserId = Convert.ToString(usrid.UserId);
                        int BackupNumber = (int)usrid.BackupNumber;
                       //if (BackupNumber == 255) 
                       // usrid.GetBackupNumberEnrolledFlag(out bytEnrolledFlag);
                        //PrintDebugMsg(csFuncName, "6 - " + FKWebTools.GetHexString(bytEnrolledFlag));
                        //for (int bkn = 0; bkn < bytEnrolledFlag.Length; bkn++)
                        //{
                        //    if (bytEnrolledFlag[bkn] == 1)
                        //    {
                        //        nEnrollDataCount++;
                                sSqlTemp = "INSERT INTO " + csTblUserId;
                                sSqlTemp += "(trans_id, device_id, user_id, backup_number)";
                                sSqlTemp += "VALUES('" + asTransId + "', '";
                                sSqlTemp += asDevId + "', '";
                                sSqlTemp += sUserId + "', ";
                                sSqlTemp += BackupNumber + ")";
                                PrintDebugMsg(csFuncName, "6 - HS100" + sSqlTemp);
                                ExecuteSimpleCmd(asqlConn, sSqlTemp);
                        /*    }
                        }
                        if (nEnrollDataCount == 0)
                        {
                            sSqlTemp = "INSERT INTO " + csTblUserId;
                            sSqlTemp += "(trans_id, device_id, user_id, backup_number)";
                            sSqlTemp += "VALUES('" + asTransId + "', '";
                            sSqlTemp += asDevId + "', '";
                            sSqlTemp += sUserId + "', ";
                            sSqlTemp += "-1)";

                            ExecuteSimpleCmd(asqlConn, sSqlTemp);
                        }*/
                    }
                    PrintDebugMsg(csFuncName, "7 - HS100");

                }
                else
                {
                    PrintDebugMsg(csFuncName, "error - 1");
                    return false;
                }
            }
            catch (Exception ex)
            {
                PrintDebugMsg(csFuncName, "error - 2 - " + ex.ToString());
                return false;
            }
            PrintDebugMsg(csFuncName, "8");
            return true;
        }

        //===================================================================================
        // 기대가 조작자지령을 접수하고 올려보내는 결과를 받을때 호출되는 함수이다.
	    public void SetCmdResult(
	        string asTransId, 
	        string asDevId, 
	        string asReturnCode, 
	        byte[] abytCmdResult,
	        out string asResponseCode)
	    {
	        const string csFuncName = "SetCmdResult";
	        
            SqlConnection sqlConn = null;

	        PrintDebugMsg1(csFuncName, "0 - trans_id:" + asTransId + ", dev_id:" + asDevId);

	        try
	        {
	            sqlConn = new SqlConnection(msDbConn);
	            sqlConn.Open();
	        }
	        catch (Exception)
	        {
	            PrintDebugMsg1(csFuncName, "Error - Not connected db");
	            sqlConn.Close();
                sqlConn.Dispose();
	            asResponseCode = "ERROR_DB_CONNECT";
	            return;
	        }

            PrintDebugMsg1(csFuncName, "1");
            try
	        {
                PrintDebugMsg1(csFuncName, "2");

                // 만일 지령코드가 'GET_LOG_DATA'  혹은 'GET_USER_ID_LIST'일때에는
                //  결과자료를 해석하여 해당한 표에 보관하도록 약속되였다.
                // 'GET_LOG_DATA' 지령 : tbl_fkcmd_trans_cmd_result_log_data 표
                // 'GET_USER_ID_LIST' 지령 : tbl_fkcmd_trans_cmd_result_user_id_list 표
                
                if (SaveCmdResultLogData(sqlConn, asTransId, asDevId, abytCmdResult) == false)
                {
                    asResponseCode = "ERROR_DB_SAVE_LOG";
                    sqlConn.Close();
                    sqlConn.Dispose();
                    return;
                }
                
                if (SaveUserIdList(sqlConn, asTransId, asDevId, abytCmdResult) == false)
                {
                    asResponseCode = "ERROR_DB_SAVE_USER_ID_LIST";
                    sqlConn.Close();
                    sqlConn.Dispose();
                    return;
                }

                SqlCommand sqlCmd = new SqlCommand("usp_set_cmd_result", sqlConn);
                sqlCmd.CommandType = CommandType.StoredProcedure;

                sqlCmd.Parameters.Add("@trans_id", SqlDbType.VarChar).Value = asTransId;
                sqlCmd.Parameters.Add("@dev_id", SqlDbType.VarChar).Value = asDevId;
                sqlCmd.Parameters.Add("@return_code", SqlDbType.VarChar).Value = asReturnCode;

                SqlParameter sqlParamCmdResult = new SqlParameter("@cmd_result_bin", SqlDbType.VarBinary);
                sqlParamCmdResult.Direction = ParameterDirection.Input;
                sqlParamCmdResult.Size = abytCmdResult.Length;
                sqlParamCmdResult.Value = abytCmdResult;
                sqlCmd.Parameters.Add(sqlParamCmdResult);

                sqlCmd.ExecuteNonQuery();

                PrintDebugMsg1(csFuncName, "3");

	            asResponseCode = "OK";
	            return;
	        }
	        catch (Exception e)
	        {
	            PrintDebugMsg1(csFuncName, "Except - 1 - " + e.ToString());
	            sqlConn.Close();

	            asResponseCode = "ERROR_DB_ACCESS";
	            return;
	        }     
	    }
	}
}