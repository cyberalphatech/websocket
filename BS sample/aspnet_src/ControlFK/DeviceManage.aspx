﻿<%@ Page Language="C#" AutoEventWireup="true" CodeFile="DeviceManage.aspx.cs" Inherits="DeviceManage" %>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml">
<head runat="server">
    <title>FKAttend BS Sample</title>
</head>
<body>
    <form id="form1" runat="server">
     <asp:ScriptManager ID="ScriptManager1" runat="server"></asp:ScriptManager>
        <div>
         <asp:UpdatePanel ID="UpdatePanel2" runat="server" UpdateMode="Always">
        <ContentTemplate>
    <div>
    
    <div style="border: thin hidden #00FF00; font-size: xx-large; background-color: #C0C0C0; height: 49px; margin-bottom: 17px;">
    
    &nbsp;&nbsp; FKAttend BS Sample</div>
    
    </div>
        <asp:Panel ID="Panel1" runat="server" BackColor="#CCCCCC" Font-Size="Large" 
            Height="28px" style="margin-bottom: 10px">
            &nbsp;&nbsp;&nbsp;&nbsp; Device Manage&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <asp:Label ID="Label1" runat="server" Text="Device ID :"></asp:Label>
            &nbsp;&nbsp;
            <asp:Label ID="DevID" runat="server"></asp:Label>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            
            
            <asp:LinkButton ID="goback" runat="server" onclick="goback_Click">Go Home</asp:LinkButton>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        </asp:Panel>

        
        <asp:Panel ID="Panel2" runat="server" BackColor="#EEEEEE" Height="383px" 
            style="margin-bottom: 12px" Width="1054px">
            <br />
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 
            Opreations&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <asp:TextBox ID="mTransIdTxt" runat="server" Visible="False"></asp:TextBox>
            <br />
            <br />
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <asp:Button ID="SetTimeBtn" runat="server" Height="30px" 
                onclick="SetTimeBtn_Click" Text="Set Sync" Width="150px" />
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <asp:Button ID="ClearBtn" runat="server" Height="30px" onclick="ClearBtn_Click" 
                Text="Clear Enroll Data" Width="150px" />
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <br />
            <br />
            <br />
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <asp:Button ID="ResetBtn" runat="server" Height="30px" onclick="ResetBtn_Click" 
                Text="Reset" Width="150px" />
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <asp:Button ID="ClearLogBtn" runat="server" Height="30px" 
                onclick="ClearLogBtn_Click" Text="Clear Log Data" Width="150px" />
            <br />
            <br />
            <br />
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <asp:Label ID="Label2" runat="server" Text="Device Name :"></asp:Label>
            &nbsp;&nbsp;
            <asp:TextBox ID="mDeviceName" runat="server" Height="20px" MaxLength="10" 
                Width="150px"></asp:TextBox>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <asp:Button ID="DevNameChangeBtn" runat="server" Height="30px" 
                onclick="DevNameChangeBtn_Click" Text="Device Name Change" Width="150px" />
            &nbsp;&nbsp;&nbsp;
            <asp:Label ID="Label10" runat="server" 
                Text="* Attention: Device name must be english charactors!"></asp:Label>
            <br />
            <br />
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ServerIP :&nbsp;&nbsp;&nbsp;
            <asp:TextBox ID="mServerIP" runat="server" Height="20px"></asp:TextBox>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Port :
            <asp:TextBox ID="mServerPort" runat="server" Height="20px"></asp:TextBox>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <asp:Button ID="ServerChangeBtn" runat="server" Height="30px" 
                onclick="ServerChangeBtn_Click" Text="Server Info Change" Width="150px" />
            <br />
            <br />
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <br />
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Firmware : &nbsp;&nbsp;&nbsp;
            <asp:TextBox ID="Firmware" runat="server" Enabled="False"></asp:TextBox>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <asp:Button ID="UpdateBtn" runat="server" Height="30px" 
                onclick="UpdateBtn_Click" Text="Update Firmware" Width="150px" />
        </asp:Panel>
            <asp:Panel ID="Panel5" runat="server" BorderStyle="Groove" Height="44px" 
                style="margin-top: 11px" BackColor="#EEEEEE">
                &nbsp;
                <asp:Label ID="Label9" runat="server" Font-Size="Large" Text="Status"></asp:Label>
                <br />
                &nbsp; &nbsp;&nbsp;&nbsp;
                <asp:Label ID="StatusTxt" runat="server"></asp:Label>
            </asp:Panel>
        <asp:timer ID="Timer" runat="server" Interval="10" OnTick="Timer_Watch" 
                    Enabled="False"></asp:timer>
        </ContentTemplate>
        </asp:UpdatePanel>
    </form>
</body>
</html>
