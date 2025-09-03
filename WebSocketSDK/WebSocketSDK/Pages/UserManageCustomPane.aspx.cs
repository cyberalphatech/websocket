using System;
using System.Collections.Generic;
using System.Linq;
using System.Web;
using System.Web.UI;
using System.Web.UI.WebControls;
using System.Xml;
using System.IO;
using SmackBio.WebSocketSDK.Cmd;
using SmackBio.WebSocketSDK.DB;
using SmackBio.WebSocketSDK.M50;
using SmackBio.WebSocketSDK.M50.Cmd;
using SmackBio.WebSocketSDK.Util;

namespace SmackBio.WebSocketSDK.Sample.Pages
{
    public partial class UserManageCustomPane : System.Web.UI.Page
    {
        protected void Page_Load(object sender, EventArgs e)
        {
            var sid = Context.Request.Params["session_id"];
            var dev_uid = Context.Request.Params["device_uid"];
            if (!string.IsNullOrEmpty(sid))
            {
                session_id.Text = sid;
                device_uid.Text = dev_uid;
            }
            else
                Context.Response.Redirect("~/ViewOnlineDevices.aspx");
        }

        ////////////////////////////////////////////////////////////////////////////////
        protected void btnGetUserAttendOnly_Click(object sender, EventArgs e)
        {
            try
            {
                CmdGetUserAttendOnly cmd = new CmdGetUserAttendOnly(Convert.ToInt64(TextUserID.Text));
                try
                {
                    var session = SessionRegistry.GetSession(Guid.Parse(session_id.Text));

                    XmlDocument doc = new XmlDocument();
                    doc.LoadXml(cmd.Build());

                    session.ExecuteCommand(this, doc, (response) =>
                    {
                        CmdGetUserAttendOnlyResponse cmd_resp = new CmdGetUserAttendOnlyResponse();
                        if (cmd_resp.Parse(response.Xml))
                        {
                            TextMessage.Text = "GetUserAttendOnly Success!";
                            ChkAttendOnly.Checked = cmd_resp.Value;
                        }
                        else
                            TextMessage.Text = "GetUserAttendOnly Failed!";
                    }, (ex) => { TextMessage.Text = ex.Message; });
                }
                catch (Exception ex)
                {
                    TextMessage.Text = ex.Message;
                }
            }
            catch (Exception)
            {
                TextMessage.Text = "Please Input UserID Correctly!";
                TextUserID.Focus();
            }
        }

        protected void btnSetUserAttendOnly_Click(object sender, EventArgs e)
        {
            try
            {
                CmdSetUserAttendOnly cmd = new CmdSetUserAttendOnly(Convert.ToInt64(TextUserID.Text),
                    ChkAttendOnly.Checked);
                try
                {
                    var session = SessionRegistry.GetSession(Guid.Parse(session_id.Text));

                    XmlDocument doc = new XmlDocument();
                    doc.LoadXml(cmd.Build());

                    session.ExecuteCommand(this, doc, (response) =>
                    {
                        CmdSetUserAttendOnlyResponse cmd_resp = new CmdSetUserAttendOnlyResponse();
                        if (cmd_resp.Parse(response.Xml))
                            TextMessage.Text = "SetUserAttendOnly Success!";
                        else
                            TextMessage.Text = "SetUserAttendOnly Failed!";
                    }, (ex) => { TextMessage.Text = ex.Message; });
                }
                catch (Exception ex)
                {
                    TextMessage.Text = ex.Message;
                }
            }
            catch (Exception)
            {
                TextMessage.Text = "Please Input UserID Correctly!";
                TextUserID.Focus();
            }
        }
    }
}