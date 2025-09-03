using System;
using System.Collections;
using System.Configuration;
using System.Data;
using System.Web;
using System.Web.Security;
using System.Web.UI;
using System.Web.UI.HtmlControls;
using System.Web.UI.WebControls;
using System.Web.UI.WebControls.WebParts;
using System.Configuration;
using System.Data;
using System.Data.SqlClient;
using System.IO;
using Newtonsoft.Json;
using Newtonsoft.Json.Linq;
using FKWeb;

public partial class FKAttend : System.Web.UI.Page
{
    SqlConnection msqlConn;
    private String mDeviceId;
    protected void Page_Load(object sender, EventArgs e)
    {
        msqlConn = FKWebTools.GetDBPool();
        mDeviceId = (string)Session["dev_id"];
        makeConfig();
        Session["run_time"] = DateTime.Now; 
    }
    public void refresh_page(){
            Device_List.DataSource = DetectDevice();
            Device_List.DataTextField = "DeviceNameField";
            Device_List.DataValueField = "DeviceIDField";

            Device_List.DataBind();

           try
            {
                if (mDeviceId.Length != 0)
                {
                    Device_List.SelectedIndex = Device_List.Items.IndexOf(Device_List.Items.FindByValue(mDeviceId));
                    select_device();
                }
            }catch{
                Device_List.SelectedIndex = 0;
            }
  }

    public ICollection DetectDevice()
    {
        string sSql;
       
        sSql = "select * from tbl_fkdevice_status";
        SqlCommand sqlCmd = new SqlCommand(sSql, msqlConn);
        SqlDataReader sqlReader = sqlCmd.ExecuteReader();

        DataTable dt = new DataTable();
        int mCount = 0;
        dt.Columns.Add(new DataColumn("DeviceNameField", typeof(String)));
        dt.Columns.Add(new DataColumn("DeviceIDField", typeof(String)));

        dt.Rows.Add(CreateRow("none", null, dt));
     
       if (sqlReader.HasRows)
        {
            while (sqlReader.Read())
            {
                mCount++;
                dt.Rows.Add(CreateRow(sqlReader.GetString(1), sqlReader.GetString(0), dt));
            }
        }
        sqlReader.Close();
        StatusTxt.Text = "Device Count: "+mCount ;
        DataView dv = new DataView(dt);
        return dv;

    }

    DataRow CreateRow(String Text, String Value, DataTable dt)
    {

        DataRow dr = dt.NewRow();

        dr[0] = Text;
        dr[1] = Value;

        return dr;

    }

   


    protected void RefreshBtn_Click(object sender, EventArgs e)
    {
        refresh_page();
    }

    private void select_device(){
        
            string sSql;
            string sJson;
            mDeviceId = (string)Session["dev_id"];//Device_List.SelectedItem.Value;
            LDev_Name.Text = Device_List.SelectedItem.Text + ":" + Device_List.SelectedItem.Value;// sqlReader.GetString(1);
            int mFlag = 0;
            sSql = "select * from tbl_fkdevice_status where device_id='" + mDeviceId + "'";
            SqlCommand sqlCmd = new SqlCommand(sSql, msqlConn);
            SqlDataReader sqlReader = sqlCmd.ExecuteReader();
            DateTime dtNow, dtDev;
            dtNow = DateTime.Now;
            long nTimeDiff =0;
            try
            {

            if (sqlReader.HasRows)
            {
                if (sqlReader.Read())
                {
                    mFlag = 1;
                    LDev_ID.Text = mDeviceId;
                    LDev_Name.Text = sqlReader.GetString(1);
                    sJson = sqlReader.GetString(5);
                    JObject jobjTest = JObject.Parse(sJson);
                    LDev_Firmware.Text = jobjTest["firmware"].ToString();
                    LDev_Support.Text = jobjTest["supported_enroll_data"].ToString();//
                    dtDev = sqlReader.GetDateTime(3);
                    nTimeDiff = dtNow.Ticks - dtDev.Ticks ;
                    UpdateTimeTxt.Text = Convert.ToString(dtDev);// +":" + Convert.ToString(dtNow) + "---->" + Convert.ToString(nTimeDiff);//
                    if (nTimeDiff > 1800000000)
                    {
                        sqlReader.Close();
                        sSql = "UPDATE tbl_fkdevice_status SET connected='0' where device_id='"+mDeviceId+"'";
                        FKWebTools.ExecuteSimpleCmd(msqlConn, sSql);
                        FKWebTools.CheckDeviceLives(msqlConn, StatusImg, UpdateTimeTxt, mDeviceId);
                        return;
                    }
                }
            }
            if (mFlag == 0)
            {
                LDev_ID.Text = mDeviceId;
                LDev_Name.Text = Device_List.SelectedItem.Text;
                LDev_Firmware.Text = "";
                LDev_Support.Text = "";//
            }
            sqlReader.Close();
            FKWebTools.CheckDeviceLives(msqlConn, StatusImg, UpdateTimeTxt, mDeviceId);
           
        }
        catch(Exception ex)
        {
            //StatusTxt.Text = ex.ToString();
            sqlReader.Close();
        }
    }

   
    protected void Device_List_SelectedIndexChanged(object sender, EventArgs e)
    {
        StatusTxt.Text = Device_List.SelectedItem.Value;
        Session["dev_id"] = Device_List.SelectedItem.Value;
        select_device();

    }
    protected void RTLogBtn_Click(object sender, EventArgs e)

    {
       
        Response.Redirect("RTLogView.aspx");
    }
    protected void UserManBtn_Click(object sender, EventArgs e)
    {

        if (Device_List.SelectedItem == null)
        {
            StatusTxt.Text = ("You must select device!");
            return;
        }
        if (mDeviceId == null || mDeviceId == "" || mDeviceId.Length == 0 )
        {
            StatusTxt.Text = ("You must select avaiable device!");
            return;
        }

        if (FKWebTools.CheckDeviceLives(msqlConn, StatusImg, UpdateTimeTxt, mDeviceId) == 0)
        {
            StatusTxt.Text = "Error: Device has been disconnected!";
            return;
        }

        emptyUserListTable();
        Session["operation"] = 0;
        Response.Redirect("UserManage.aspx");
    }
    public void emptyUserListTable()
    {
        Session["trans_id"] = FKWebTools.MakeCmd(msqlConn, "GET_USER_ID_LIST", mDeviceId, null);
    }
    public void ShowMessage(String msgStr){
        System.Text.StringBuilder sb = new System.Text.StringBuilder();

        sb.Append("<script type = 'text/javascript'>");

        sb.Append("window.onload=function(){");

        sb.Append("alert('");

        sb.Append(msgStr);

        sb.Append("')};");

        sb.Append("</script>");

        ClientScript.RegisterClientScriptBlock(this.GetType(), "alert", sb.ToString());

    }
    

    protected void LogManBtn_Click(object sender, EventArgs e)
    {
        if (Device_List.SelectedItem == null)
        {
            StatusTxt.Text = ("You must select device!");
            return;
        }
        mDeviceId = Device_List.SelectedItem.Value;
        StatusTxt.Text = "ID=" + mDeviceId;
        if (mDeviceId == null || mDeviceId == "" || mDeviceId.Length == 0)
        {
            StatusTxt.Text = ("You must select avaiable device!");
            return;
        }
        if (FKWebTools.CheckDeviceLives(msqlConn, StatusImg,UpdateTimeTxt, mDeviceId) == 0)
        {
            StatusTxt.Text = "Error: Device has been disconnected!";
            return;
        }

        emptyUserListTable();
        Response.Redirect("LogManager.aspx");
    }
    protected void DevManBtn_Click(object sender, EventArgs e)
    {
        if (mDeviceId == null || mDeviceId == "" || mDeviceId.Length == 0)
        {
            StatusTxt.Text = ("You must select avaiable device!");
            return;
        }
        if (FKWebTools.CheckDeviceLives(msqlConn, StatusImg, UpdateTimeTxt, mDeviceId) == 0)
        {
            StatusTxt.Text = "Error: Device has been disconnected!";
            return;
        }
        Response.Redirect("DeviceManage.aspx");
    }


    private void Enables(Boolean flag)
    {

    }


    protected void Timer_Watch(object sender, EventArgs e)
    {
        Timer.Interval = 3000;
        refresh_page();
        StatusTxt.Text = "Current Time  " + Convert.ToString(DateTime.Now);
    
    }
    protected void RTEnrollBtn_Click(object sender, EventArgs e)
    {
        Response.Redirect("RTEnrollView.aspx");
    }

    private void makeConfig()
    {
        string defaultdir = Path.GetDirectoryName(ConfigurationManager.AppSettings["DefaultDir"]);
        string logimagedir = Path.GetDirectoryName(ConfigurationManager.AppSettings["LogImgRootDir"]);
        string FirmwareBinDir = Path.GetDirectoryName(ConfigurationManager.AppSettings["FirmwareBinDir"]);
        string photodir = Server.MapPath(".") + "\\" + Path.GetDirectoryName(ConfigurationManager.AppSettings["EnrollImgView"]);
        string rtlog_photodir =  Server.MapPath(".") + "\\" + Path.GetDirectoryName(ConfigurationManager.AppSettings["RTLogImgView"]);
        LDev_Firmware.Text = rtlog_photodir;
        if (!Directory.Exists(defaultdir))
            Directory.CreateDirectory(defaultdir);
        if (!Directory.Exists(logimagedir))
            Directory.CreateDirectory(logimagedir);
        if (!Directory.Exists(FirmwareBinDir))
            Directory.CreateDirectory(FirmwareBinDir);
        if (!Directory.Exists(photodir))
            Directory.CreateDirectory(photodir);
        if (!Directory.Exists(rtlog_photodir))
            Directory.CreateDirectory(rtlog_photodir);
        
    }
}
