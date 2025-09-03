<%@ Page Language="C#" MasterPageFile="~/Site.Master" AutoEventWireup="true" CodeBehind="UserManageCustomPane.aspx.cs" Inherits="SmackBio.WebSocketSDK.Sample.Pages.UserManageCustomPane" 
    Async="true" %>

<asp:Content ID="Content1" ContentPlaceHolderID="HeadContent" runat="server">
    <style type="text/css">
        .auto-style1 {
            height: 49px;
        }
    </style>
</asp:Content>
<asp:Content ID="Content2" ContentPlaceHolderID="FeaturedContent" runat="server">
</asp:Content>
<asp:Content ID="Content3" ContentPlaceHolderID="MainContent" runat="server">
    <table>
        <tr>
            <td colspan ="2">
                Session ID:  
                <asp:Label ID="session_id" runat="server"></asp:Label> <br />

                Device UID:
                <asp:Label ID="device_uid" runat="server"></asp:Label>
            </td>
            <td><asp:Label runat="server" ID="TextMessage" ForeColor="Red" Font-Bold="True" Font-Size="Larger" /></td>
        </tr>
        <tr>
            <td>
                <table>
                    <tr><td style="margin: 0px; padding: 0px"><asp:Button ID="btnGetUserAttendOnly" runat="server" Text="GetUserAttendOnly" Width="150px" OnClick="btnGetUserAttendOnly_Click" /></td></tr>
                    <tr><td style="margin: 0px; padding: 0px"><asp:Button ID="btnSetUserAttendOnly" runat="server" Text="SetUserAttendOnly" Width="150px" OnClick="btnSetUserAttendOnly_Click" /></td></tr>
                </table>
            </td>
            <td>
                <table class="entity_table" style="padding: 0px; margin: 0px">
                    <tr class=""><td>UserID:</td><td><asp:TextBox ID="TextUserID" runat="server" /></td></tr>
                    <tr><td>AttendOnly:</td><td><asp:CheckBox ID="ChkAttendOnly" runat="server" checked="true"/></td></tr>
                </table>
            </td>
        </tr>
    </table>
</asp:Content>