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
using System.Data.SqlClient;
using System.IO;
using FKWeb;
public partial class RTLogView : System.Web.UI.Page
{
      string mDevId;
      SqlConnection msqlConn;
    protected void Page_Load(object sender, EventArgs e)
    {
        gvLog.AllowSorting = true;

        msqlConn = FKWebTools.GetDBPool();
        // Initialize the sorting expression. 
        ViewState["SortExpression"] = "update_time ASC";
       
        gvLog.AllowPaging = true;
        gvLog.PageSize = 20;

        if (Request["log_image"] != "")
            UserPhoto.ImageUrl = ".\\" + Path.GetDirectoryName(ConfigurationManager.AppSettings["RTLogImgView"]) + "\\" + Request["log_image"];
        //if (Request["page"] != "")
        gvLog.PageIndex = Convert.ToInt32(Session["PageIndex"]); //Convert.ToInt32(Request["page"]);
        // Populate the GridView. 

        BindGridView();

    }

    private void BindGridView()
    {
        // Get the connection string from Web.config.  
        // When we use Using statement,  
        // we don't need to explicitly dispose the object in the code,  
        // the using statement takes care of it. 
        try
        {
           // using (SqlConnection conn = new SqlConnection(ConfigurationManager.ConnectionStrings["SqlConnFkWeb"].ToString()))
            {
                // Create a DataSet object. 
                DataSet dsLog = new DataSet();

                byte[] abytLogImage = new byte[0];
                // Create a SELECT query. 
                string strSelectCmd = "SELECT * FROM tbl_realtime_glog where update_time >= '"+Session["run_time"]+"'";


                // Create a SqlDataAdapter object 
                // SqlDataAdapter represents a set of data commands and a  
                // database connection that are used to fill the DataSet and  
                // update a SQL Server database.  
                SqlDataAdapter da = new SqlDataAdapter(strSelectCmd, msqlConn);


                // Open the connection 
                //conn.Open();


                // Fill the DataTable named "Person" in DataSet with the rows 
                // returned by the query.new n 
                da.Fill(dsLog, "tbl_realtime_glog");


                // Get the DataView from Person DataTable. 
                DataView dvLog = dsLog.Tables["tbl_realtime_glog"].DefaultView;


                // Set the sort column and sort order. 
                dvLog.Sort = ViewState["SortExpression"].ToString();
                string realFilePath="";
                string realFileName = "";
                string rtlog_dir = Server.MapPath(".") + "\\" + Path.GetDirectoryName(ConfigurationManager.AppSettings["RTLogImgView"]);
                string rtlog_file = "";
                //;
                for (int i = 0; i < dvLog.Count; i++)
                {
                    realFilePath = dvLog[i]["log_image"].ToString();
                    string[] b = realFilePath.Split('\\');
                    realFileName = b[b.Length - 1];
                    try
                    {
                        FileStream fsRead = new FileStream(realFilePath, FileMode.Open, FileAccess.Read);
                        abytLogImage = new byte[fsRead.Length];
                        fsRead.Read(abytLogImage, 0, abytLogImage.Length);
                        fsRead.Close();

                        rtlog_file = rtlog_dir + "\\" + realFileName;
                        FileStream fsWrite = new FileStream(rtlog_file, FileMode.OpenOrCreate, FileAccess.Write);
                        fsWrite.Write(abytLogImage, 0, abytLogImage.Length);
                        fsWrite.Close();
                        
                    }
                    catch (Exception ex)
                    {
                       // Label9.Text = realFilePath + ":" + rtlog_file + ":" + ex.ToString();

                    }
                    if (abytLogImage.Length > 0)
                        dvLog[i]["log_image"] = "<a href = \"RTLogView.aspx?log_image=" + realFileName + "\">View</a>";
                    else
                        dvLog[i]["log_image"] = "";
                }

                    // Bind the GridView control. 
                    gvLog.DataSource = dvLog;
                gvLog.DataBind();


                StatusTxt.Text = "       Total Count : " + gvLog.Rows.Count + "&nbsp;&nbsp;&nbsp; Current Time :" + DateTime.Now.ToString("HH:mm:ss tt");
            }
        }catch(Exception ex){
            StatusTxt.Text = ex.ToString();
        }

    }

    protected void gvLog_PageIndexChanging(object sender, GridViewPageEventArgs e)
    {
        // Set the index of the new display page.  
        gvLog.PageIndex = e.NewPageIndex;

        Session["PageIndex"] = e.NewPageIndex;
        // Rebind the GridView control to  
        // show data in the new page. 
        BindGridView();
    } 


    protected void gvLog_Sorting(object sender, GridViewSortEventArgs e)
    {
        string[] strSortExpression = ViewState["SortExpression"].ToString().Split(' ');


        // If the sorting column is the same as the previous one,  
        // then change the sort order. 
        if (strSortExpression[0] == e.SortExpression)
        {
            if (strSortExpression[1] == "ASC")
            {
                ViewState["SortExpression"] = e.SortExpression + " " + "DESC";
            }
            else
            {
                ViewState["SortExpression"] = e.SortExpression + " " + "ASC";
            }
        }
        // If sorting column is another column,   
        // then specify the sort order to "Ascending". 
        else
        {
            ViewState["SortExpression"] = e.SortExpression + " " + "ASC";
        }

        //Label1.Text = ViewState["SortExpression"].ToString();
        // Rebind the GridView control to show sorted data. 
        BindGridView();
    }


    protected void goback_Click(object sender, EventArgs e)
    {
        Response.Redirect("Default.aspx");
    }

   protected void Timer_Watch(object sender, EventArgs e)
    {
        //label is on first panel
   
        BindGridView();

    }
}
