﻿<%@ Page Language="C#" AutoEventWireup="true" CodeFile="UserManage.aspx.cs" Inherits="UserManage" %>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head runat="server">
    <title>FKAttend BS Sample</title>
    <style type="text/css">
        #form1
        {
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <form id="form1" runat="server">
    <div>
    
    <div style="border: thin hidden #00FF00; font-size: xx-large; background-color: #C0C0C0; height: 49px; margin-bottom: 17px;">
    
    &nbsp;&nbsp; FKAttend BS Sample</div>
    
    </div>
     <asp:ScriptManager ID="ScriptManager1" runat="server"></asp:ScriptManager>
        <div>
         <asp:UpdatePanel ID="UpdatePanel2" runat="server" UpdateMode="Always">
        <ContentTemplate>
        <asp:Panel ID="Panel1" runat="server" BackColor="#CCCCCC" Font-Size="Large" 
            Height="28px" style="margin-bottom: 7px">
            &nbsp;&nbsp;&nbsp;&nbsp; User Manage&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<asp:Label ID="Label1" runat="server" 
                Text="Device ID :"></asp:Label>
            &nbsp;&nbsp;&nbsp;&nbsp;<asp:Label ID="DevID" runat="server"></asp:Label>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<asp:Label ID="Label12" 
                runat="server" Text="Update Time:" Visible="False"></asp:Label>
            &nbsp;&nbsp;&nbsp;<asp:Label ID="UpdateTimeTxt" runat="server" Text="Label" 
                Visible="False"></asp:Label>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<asp:Label ID="Label14" 
                runat="server" Text="Connect Status: " Visible="False"></asp:Label>
            <asp:Image ID="StatusImg" runat="server" Height="20px" 
                ImageUrl="~/Image/redon.png" Width="20px" Visible="False" />
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <asp:LinkButton ID="goback" runat="server" onclick="goback_Click">Go Home</asp:LinkButton>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        </asp:Panel>

        
        <asp:Panel ID="Panel2" runat="server" BackColor="#EEEEEE" Height="55px" 
            style="margin-bottom: 8px">
            &nbsp;&nbsp;<br />
&nbsp;<asp:Label ID="Label2" runat="server" Text="User List"></asp:Label>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <asp:DropDownList ID="UserList" runat="server" 
                Width="130px" Enabled="False" 
            >
               
            </asp:DropDownList>
            
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        </asp:Panel>
        
       
   
        <asp:Panel runat="server" BackColor="#EEEEEE" Height="517px" 
            style="margin-bottom: 6px">
            <br />
            &nbsp;&nbsp;
            <asp:Label ID="Label3" runat="server" Text="User Infomation"></asp:Label>
            <br />
            <br />
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <asp:Image ID="UserPhoto" runat="server" Height="188px" Width="154px" 
                BorderColor="#666666" BorderWidth="2px" EnableTheming="False" 
                EnableViewState="False" />
            <br />
            &nbsp;&nbsp;&nbsp;
            <asp:Label ID="Label11" runat="server" Text="ID"></asp:Label>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <asp:TextBox ID="UserID" runat="server" Enabled="False"></asp:TextBox>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <asp:Label ID="Label5" runat="server" Text="Name"></asp:Label>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <asp:TextBox ID="UserName" runat="server" Width="156px" Enabled="False"></asp:TextBox>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <br />
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <br />
            &nbsp;&nbsp;&nbsp; Privilige&nbsp;&nbsp;&nbsp;
            <asp:DropDownList ID="UserPriv" runat="server" Width="100px" Enabled="False" 
                AutoPostBack="True">
                <asp:ListItem Value="0">USER</asp:ListItem>
                <asp:ListItem Value="1">MANAGER</asp:ListItem>
                <asp:ListItem Value="2">OPERATOR</asp:ListItem>
                <asp:ListItem Value="3">REGISTOR</asp:ListItem>
            </asp:DropDownList>
            <br />
            <br />
            &nbsp;&nbsp;&nbsp;<asp:Label ID="Label7" runat="server" Text="Card"></asp:Label>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <asp:TextBox ID="CardNum" runat="server" Enabled="False"></asp:TextBox>
            <br />
            <br />
            &nbsp;&nbsp; Password&nbsp;
            <asp:TextBox ID="Password" runat="server" Enabled="False"></asp:TextBox>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <asp:TextBox ID="mTransIdTxt" runat="server" Visible="False"></asp:TextBox>
            &nbsp;&nbsp;&nbsp;
            <br />
            <br />
            &nbsp;&nbsp;&nbsp;&nbsp;
            <asp:CheckBox ID="EnableUser" runat="server" Text="Enable" Visible="False" />
            &nbsp;&nbsp;&nbsp;
            <asp:CheckBox ID="Fp" runat="server" Text="FingerPrint" Enabled="False" />
            &nbsp;&nbsp;&nbsp;
            <asp:CheckBox ID="Face" runat="server" Text="Face" Enabled="False" />
            <br />
            <br />
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <asp:Button ID="UpdateUserListBtn" runat="server" Enabled="False" 
                onclick="UpdateUserListBtn_Click" Text="Update User List" Width="130px" />
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <asp:Button ID="NewBtn" runat="server" Enabled="False" onclick="NewBtn_Click" 
                Text="New User" Width="130px" />
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<asp:Button ID="ModifyBtn" runat="server" Enabled="False" 
                onclick="ModifyBtn_Click" Text="Modify User" Width="130px" />
            <br />
            <br />
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <asp:Button ID="GetInfoBtn" runat="server" Text="Get User Info" Width="130px" 
                onclick="GetInfoBtn_Click" Enabled="False" />
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <asp:Button ID="SetInfoBtn" runat="server" Text="Set User Info" Width="130px" 
                onclick="SetInfoBtn_Click" Enabled="False" />
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <asp:Button ID="DeleteUserBtn" runat="server" Text="Delete User" 
                Width="130px" onclick="DeleteUserBtn_Click" Enabled="False" />
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <asp:Button ID="ClearBtn" runat="server" onclick="ClearBtn_Click" 
                Text="Clear All Data" Enabled="False" />
            <br />
            &nbsp;&nbsp;&nbsp;
            <br />
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;
            <br />
        </asp:Panel>
            <asp:Panel ID="Panel5" runat="server" BackColor="#EEEEEE" BorderStyle="Groove" 
                Height="44px" style="margin-top: 11px">
                &nbsp;
                <asp:Label ID="Label9" runat="server" Font-Size="Large" Text="Status"></asp:Label>
                <br />
                &nbsp; &nbsp;&nbsp;&nbsp;
                <asp:Label ID="StatusTxt" runat="server"></asp:Label>
            </asp:Panel>
        <asp:timer ID="Timer" runat="server" Interval="1000" OnTick="Timer_Watch" 
                    Enabled="true"></asp:timer>
        </ContentTemplate>
        </asp:UpdatePanel>
       </div>
   
    <p>
&nbsp;&nbsp;&nbsp; 
   
       </form>
   
    </body>
</html>
